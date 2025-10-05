from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional, List
from datetime import datetime
from enum import Enum
import uuid

app = FastAPI()

# Disable CORS. Do not remove this for full-stack development.
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Allows all origins
    allow_credentials=True,
    allow_methods=["*"],  # Allows all methods
    allow_headers=["*"],  # Allows all headers
)

class UserType(str, Enum):
    CUSTOMER = "customer"
    DRIVER = "driver"

class RideStatus(str, Enum):
    REQUESTED = "requested"
    ACCEPTED = "accepted"
    PICKED_UP = "picked_up"
    COMPLETED = "completed"
    CANCELLED = "cancelled"

class User(BaseModel):
    id: str
    name: str
    email: str
    phone: str
    user_type: UserType

class Driver(BaseModel):
    id: str
    name: str
    email: str
    phone: str
    vehicle_number: str
    vehicle_model: str
    rating: float
    is_available: bool
    current_lat: Optional[float] = None
    current_lng: Optional[float] = None

class Location(BaseModel):
    lat: float
    lng: float
    address: str

class RideRequest(BaseModel):
    pickup: Location
    destination: Location
    customer_id: str
    customer_name: str
    customer_phone: str

class Ride(BaseModel):
    id: str
    customer_id: str
    customer_name: str
    customer_phone: str
    driver_id: Optional[str] = None
    driver_name: Optional[str] = None
    driver_phone: Optional[str] = None
    driver_vehicle: Optional[str] = None
    pickup: Location
    destination: Location
    status: RideStatus
    price: Optional[float] = None
    created_at: str
    accepted_at: Optional[str] = None
    picked_up_at: Optional[str] = None
    completed_at: Optional[str] = None
    driver_lat: Optional[float] = None
    driver_lng: Optional[float] = None

class LocationUpdate(BaseModel):
    lat: float
    lng: float

users_db = {}
drivers_db = {}
rides_db = {}

@app.get("/healthz")
async def healthz():
    return {"status": "ok"}

@app.get("/")
async def root():
    return {"message": "Taxi Zug API"}

@app.post("/api/customers/register")
async def register_customer(name: str, email: str, phone: str):
    customer_id = str(uuid.uuid4())
    customer = User(
        id=customer_id,
        name=name,
        email=email,
        phone=phone,
        user_type=UserType.CUSTOMER
    )
    users_db[customer_id] = customer
    return customer

@app.post("/api/drivers/register")
async def register_driver(
    name: str,
    email: str,
    phone: str,
    vehicle_number: str,
    vehicle_model: str
):
    driver_id = str(uuid.uuid4())
    driver = Driver(
        id=driver_id,
        name=name,
        email=email,
        phone=phone,
        vehicle_number=vehicle_number,
        vehicle_model=vehicle_model,
        rating=5.0,
        is_available=True
    )
    drivers_db[driver_id] = driver
    return driver

@app.get("/api/drivers/{driver_id}")
async def get_driver(driver_id: str):
    if driver_id not in drivers_db:
        raise HTTPException(status_code=404, detail="Fahrer nicht gefunden")
    return drivers_db[driver_id]

@app.put("/api/drivers/{driver_id}/availability")
async def update_driver_availability(driver_id: str, is_available: bool):
    if driver_id not in drivers_db:
        raise HTTPException(status_code=404, detail="Fahrer nicht gefunden")
    drivers_db[driver_id].is_available = is_available
    return drivers_db[driver_id]

@app.put("/api/drivers/{driver_id}/location")
async def update_driver_location(driver_id: str, location: LocationUpdate):
    if driver_id not in drivers_db:
        raise HTTPException(status_code=404, detail="Fahrer nicht gefunden")
    drivers_db[driver_id].current_lat = location.lat
    drivers_db[driver_id].current_lng = location.lng
    
    for ride_id, ride in rides_db.items():
        if ride.driver_id == driver_id and ride.status in [RideStatus.ACCEPTED, RideStatus.PICKED_UP]:
            ride.driver_lat = location.lat
            ride.driver_lng = location.lng
    
    return {"status": "success"}

@app.post("/api/rides/request")
async def request_ride(ride_request: RideRequest):
    ride_id = str(uuid.uuid4())
    
    distance = abs(ride_request.destination.lat - ride_request.pickup.lat) + abs(ride_request.destination.lng - ride_request.pickup.lng)
    price = round(10 + distance * 50, 2)
    
    ride = Ride(
        id=ride_id,
        customer_id=ride_request.customer_id,
        customer_name=ride_request.customer_name,
        customer_phone=ride_request.customer_phone,
        pickup=ride_request.pickup,
        destination=ride_request.destination,
        status=RideStatus.REQUESTED,
        price=price,
        created_at=datetime.now().isoformat()
    )
    rides_db[ride_id] = ride
    return ride

@app.get("/api/rides/available")
async def get_available_rides():
    available_rides = [
        ride for ride in rides_db.values()
        if ride.status == RideStatus.REQUESTED
    ]
    return available_rides

@app.get("/api/rides/{ride_id}")
async def get_ride(ride_id: str):
    if ride_id not in rides_db:
        raise HTTPException(status_code=404, detail="Fahrt nicht gefunden")
    return rides_db[ride_id]

@app.get("/api/rides/customer/{customer_id}")
async def get_customer_rides(customer_id: str):
    customer_rides = [
        ride for ride in rides_db.values()
        if ride.customer_id == customer_id
    ]
    return sorted(customer_rides, key=lambda x: x.created_at, reverse=True)

@app.get("/api/rides/driver/{driver_id}")
async def get_driver_rides(driver_id: str):
    driver_rides = [
        ride for ride in rides_db.values()
        if ride.driver_id == driver_id
    ]
    return sorted(driver_rides, key=lambda x: x.created_at, reverse=True)

@app.post("/api/rides/{ride_id}/accept")
async def accept_ride(ride_id: str, driver_id: str):
    if ride_id not in rides_db:
        raise HTTPException(status_code=404, detail="Fahrt nicht gefunden")
    if driver_id not in drivers_db:
        raise HTTPException(status_code=404, detail="Fahrer nicht gefunden")
    
    ride = rides_db[ride_id]
    if ride.status != RideStatus.REQUESTED:
        raise HTTPException(status_code=400, detail="Fahrt kann nicht akzeptiert werden")
    
    driver = drivers_db[driver_id]
    ride.driver_id = driver_id
    ride.driver_name = driver.name
    ride.driver_phone = driver.phone
    ride.driver_vehicle = f"{driver.vehicle_model} ({driver.vehicle_number})"
    ride.status = RideStatus.ACCEPTED
    ride.accepted_at = datetime.now().isoformat()
    ride.driver_lat = driver.current_lat
    ride.driver_lng = driver.current_lng
    
    driver.is_available = False
    
    return ride

@app.post("/api/rides/{ride_id}/pickup")
async def pickup_customer(ride_id: str):
    if ride_id not in rides_db:
        raise HTTPException(status_code=404, detail="Fahrt nicht gefunden")
    
    ride = rides_db[ride_id]
    if ride.status != RideStatus.ACCEPTED:
        raise HTTPException(status_code=400, detail="Fahrt muss zuerst akzeptiert werden")
    
    ride.status = RideStatus.PICKED_UP
    ride.picked_up_at = datetime.now().isoformat()
    return ride

@app.post("/api/rides/{ride_id}/complete")
async def complete_ride(ride_id: str):
    if ride_id not in rides_db:
        raise HTTPException(status_code=404, detail="Fahrt nicht gefunden")
    
    ride = rides_db[ride_id]
    if ride.status != RideStatus.PICKED_UP:
        raise HTTPException(status_code=400, detail="Kunde muss zuerst abgeholt werden")
    
    ride.status = RideStatus.COMPLETED
    ride.completed_at = datetime.now().isoformat()
    
    if ride.driver_id and ride.driver_id in drivers_db:
        drivers_db[ride.driver_id].is_available = True
    
    return ride

@app.post("/api/rides/{ride_id}/cancel")
async def cancel_ride(ride_id: str):
    if ride_id not in rides_db:
        raise HTTPException(status_code=404, detail="Fahrt nicht gefunden")
    
    ride = rides_db[ride_id]
    ride.status = RideStatus.CANCELLED
    
    if ride.driver_id and ride.driver_id in drivers_db:
        drivers_db[ride.driver_id].is_available = True
    
    return ride

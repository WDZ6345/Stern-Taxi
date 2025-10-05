import './App.css'
import { useState, useEffect } from 'react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { MapPin, Navigation, Clock, CheckCircle, XCircle, User, Phone, Mail, Car } from 'lucide-react'
import { LoadScript } from '@react-google-maps/api'
import { Map } from '@/components/Map'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000'
const GOOGLE_MAPS_API_KEY = import.meta.env.VITE_GOOGLE_MAPS_API_KEY as string || ''

interface Location {
  lat: number
  lng: number
  address: string
}

interface Ride {
  id: string
  customer_id: string
  customer_name: string
  customer_phone: string
  driver_id?: string
  driver_name?: string
  driver_phone?: string
  driver_vehicle?: string
  pickup: Location
  destination: Location
  status: string
  price?: number
  created_at: string
  accepted_at?: string
  picked_up_at?: string
  completed_at?: string
  driver_lat?: number
  driver_lng?: number
}

function App() {
  const [driverId, setDriverId] = useState<string | null>(localStorage.getItem('driverId'))
  const [driverName, setDriverName] = useState(localStorage.getItem('driverName') || '')
  const [driverEmail, setDriverEmail] = useState('')
  const [driverPhone, setDriverPhone] = useState(localStorage.getItem('driverPhone') || '')
  const [vehicleNumber, setVehicleNumber] = useState(localStorage.getItem('vehicleNumber') || '')
  const [vehicleModel, setVehicleModel] = useState(localStorage.getItem('vehicleModel') || '')
  
  const [isAvailable, setIsAvailable] = useState(true)
  const [availableRides, setAvailableRides] = useState<Ride[]>([])
  const [activeRide, setActiveRide] = useState<Ride | null>(null)
  const [rideHistory, setRideHistory] = useState<Ride[]>([])
  const [loading, setLoading] = useState(false)

  useEffect(() => {
    if (driverId) {
      fetchActiveRide()
      fetchRideHistory()
      if (!activeRide) {
        fetchAvailableRides()
      }
      const interval = setInterval(() => {
        if (!activeRide) {
          fetchAvailableRides()
        } else {
          fetchActiveRide()
        }
      }, 3000)
      return () => clearInterval(interval)
    }
  }, [driverId, activeRide?.id])

  const registerDriver = async () => {
    setLoading(true)
    try {
      const response = await fetch(`${API_URL}/api/drivers/register?name=${encodeURIComponent(driverName)}&email=${encodeURIComponent(driverEmail)}&phone=${encodeURIComponent(driverPhone)}&vehicle_number=${encodeURIComponent(vehicleNumber)}&vehicle_model=${encodeURIComponent(vehicleModel)}`, {
        method: 'POST'
      })
      const data = await response.json()
      setDriverId(data.id)
      localStorage.setItem('driverId', data.id)
      localStorage.setItem('driverName', driverName)
      localStorage.setItem('driverPhone', driverPhone)
      localStorage.setItem('vehicleNumber', vehicleNumber)
      localStorage.setItem('vehicleModel', vehicleModel)
      updateLocation()
    } catch (error) {
      console.error('Registrierung fehlgeschlagen:', error)
    }
    setLoading(false)
  }

  const updateLocation = () => {
    if (!driverId) return
    const lat = 47.1667 + (Math.random() - 0.5) * 0.01
    const lng = 8.5167 + (Math.random() - 0.5) * 0.01
    fetch(`${API_URL}/api/drivers/${driverId}/location`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ lat, lng })
    }).catch(err => console.error('Standort-Update fehlgeschlagen:', err))
  }

  const toggleAvailability = async () => {
    if (!driverId) return
    setLoading(true)
    try {
      await fetch(`${API_URL}/api/drivers/${driverId}/availability?is_available=${!isAvailable}`, {
        method: 'PUT'
      })
      setIsAvailable(!isAvailable)
    } catch (error) {
      console.error('Verfügbarkeit konnte nicht geändert werden:', error)
    }
    setLoading(false)
  }

  const fetchAvailableRides = async () => {
    try {
      const response = await fetch(`${API_URL}/api/rides/available`)
      const rides = await response.json()
      setAvailableRides(rides)
    } catch (error) {
      console.error('Fehler beim Laden der verfügbaren Fahrten:', error)
    }
  }

  const fetchActiveRide = async () => {
    if (!driverId) return
    try {
      const response = await fetch(`${API_URL}/api/rides/driver/${driverId}`)
      const rides = await response.json()
      const active = rides.find((r: Ride) => 
        r.status === 'accepted' || r.status === 'picked_up'
      )
      setActiveRide(active || null)
    } catch (error) {
      console.error('Fehler beim Laden der aktiven Fahrt:', error)
    }
  }

  const fetchRideHistory = async () => {
    if (!driverId) return
    try {
      const response = await fetch(`${API_URL}/api/rides/driver/${driverId}`)
      const rides = await response.json()
      setRideHistory(rides.filter((r: Ride) => r.status === 'completed'))
    } catch (error) {
      console.error('Fehler beim Laden der Geschichte:', error)
    }
  }

  const acceptRide = async (rideId: string) => {
    if (!driverId) return
    setLoading(true)
    try {
      const response = await fetch(`${API_URL}/api/rides/${rideId}/accept?driver_id=${driverId}`, {
        method: 'POST'
      })
      const ride = await response.json()
      setActiveRide(ride)
      setAvailableRides(availableRides.filter(r => r.id !== rideId))
      updateLocation()
    } catch (error) {
      console.error('Fahrt konnte nicht akzeptiert werden:', error)
    }
    setLoading(false)
  }

  const pickupCustomer = async () => {
    if (!activeRide) return
    setLoading(true)
    try {
      const response = await fetch(`${API_URL}/api/rides/${activeRide.id}/pickup`, {
        method: 'POST'
      })
      const ride = await response.json()
      setActiveRide(ride)
    } catch (error) {
      console.error('Abholen fehlgeschlagen:', error)
    }
    setLoading(false)
  }

  const completeRide = async () => {
    if (!activeRide) return
    setLoading(true)
    try {
      await fetch(`${API_URL}/api/rides/${activeRide.id}/complete`, {
        method: 'POST'
      })
      setActiveRide(null)
      fetchRideHistory()
    } catch (error) {
      console.error('Abschluss fehlgeschlagen:', error)
    }
    setLoading(false)
  }

  const getStatusText = (status: string) => {
    switch (status) {
      case 'requested': return 'Angefordert'
      case 'accepted': return 'Akzeptiert'
      case 'picked_up': return 'Unterwegs'
      case 'completed': return 'Abgeschlossen'
      case 'cancelled': return 'Storniert'
      default: return status
    }
  }

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'requested': return <Clock className="w-5 h-5 text-yellow-500" />
      case 'accepted': return <Navigation className="w-5 h-5 text-blue-500" />
      case 'picked_up': return <Car className="w-5 h-5 text-green-500" />
      case 'completed': return <CheckCircle className="w-5 h-5 text-green-500" />
      case 'cancelled': return <XCircle className="w-5 h-5 text-red-500" />
      default: return null
    }
  }

  if (!driverId) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-600 to-blue-800 flex items-center justify-center p-4">
        <Card className="w-full max-w-md">
          <CardHeader className="text-center">
            <div className="mx-auto w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mb-4">
              <Car className="w-8 h-8 text-white" />
            </div>
            <CardTitle className="text-2xl">Taxi Zug</CardTitle>
            <CardDescription>Fahrer-App</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="name">Name</Label>
              <div className="relative">
                <User className="absolute left-3 top-3 w-4 h-4 text-gray-400" />
                <Input
                  id="name"
                  placeholder="Ihr Name"
                  value={driverName}
                  onChange={(e) => setDriverName(e.target.value)}
                  className="pl-10"
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label htmlFor="email">E-Mail</Label>
              <div className="relative">
                <Mail className="absolute left-3 top-3 w-4 h-4 text-gray-400" />
                <Input
                  id="email"
                  type="email"
                  placeholder="ihre@email.com"
                  value={driverEmail}
                  onChange={(e) => setDriverEmail(e.target.value)}
                  className="pl-10"
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label htmlFor="phone">Telefon</Label>
              <div className="relative">
                <Phone className="absolute left-3 top-3 w-4 h-4 text-gray-400" />
                <Input
                  id="phone"
                  placeholder="+41 XX XXX XX XX"
                  value={driverPhone}
                  onChange={(e) => setDriverPhone(e.target.value)}
                  className="pl-10"
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label htmlFor="vehicle_model">Fahrzeugmodell</Label>
              <div className="relative">
                <Car className="absolute left-3 top-3 w-4 h-4 text-gray-400" />
                <Input
                  id="vehicle_model"
                  placeholder="z.B. Mercedes E-Klasse"
                  value={vehicleModel}
                  onChange={(e) => setVehicleModel(e.target.value)}
                  className="pl-10"
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label htmlFor="vehicle_number">Kennzeichen</Label>
              <Input
                id="vehicle_number"
                placeholder="z.B. ZG 12345"
                value={vehicleNumber}
                onChange={(e) => setVehicleNumber(e.target.value)}
              />
            </div>
            <Button 
              onClick={registerDriver} 
              disabled={!driverName || !driverEmail || !driverPhone || !vehicleModel || !vehicleNumber || loading}
              className="w-full bg-blue-600 hover:bg-blue-700"
            >
              Registrieren
            </Button>
          </CardContent>
        </Card>
      </div>
    )
  }

  return (
    <LoadScript googleMapsApiKey={GOOGLE_MAPS_API_KEY}>
      <div className="min-h-screen bg-gray-50">
        <div className="bg-blue-600 text-white p-4 shadow-lg">
          <div className="container mx-auto flex justify-between items-center">
            <div className="flex items-center gap-2">
              <Car className="w-6 h-6" />
              <h1 className="text-xl font-bold">Taxi Zug - Fahrer</h1>
            </div>
            <div className="flex items-center gap-3">
              <span className="text-sm">{driverName}</span>
              <Button
                size="sm"
                variant={isAvailable ? "secondary" : "outline"}
                onClick={toggleAvailability}
                className={isAvailable ? "bg-green-500 hover:bg-green-600" : "bg-gray-500 hover:bg-gray-600"}
              >
                {isAvailable ? 'Verfügbar' : 'Nicht verfügbar'}
              </Button>
            </div>
          </div>
        </div>

      <div className="container mx-auto p-4 max-w-4xl">
        <Tabs defaultValue={activeRide ? "active" : "available"} className="w-full">
          <TabsList className="grid w-full grid-cols-3">
            <TabsTrigger value="available">Verfügbar</TabsTrigger>
            <TabsTrigger value="active">Aktive Fahrt</TabsTrigger>
            <TabsTrigger value="history">Geschichte</TabsTrigger>
          </TabsList>

          <TabsContent value="available" className="space-y-4">
            {availableRides.length === 0 ? (
              <Card>
                <CardContent className="py-8 text-center text-gray-500">
                  Keine verfügbaren Fahrten
                </CardContent>
              </Card>
            ) : (
              <>
                <Card>
                  <CardHeader>
                    <CardTitle>Verfügbare Fahrten auf Karte</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <Map
                      availableRides={availableRides.map(ride => ({
                        id: ride.id,
                        pickup: { lat: ride.pickup.lat, lng: ride.pickup.lng },
                        destination: { lat: ride.destination.lat, lng: ride.destination.lng },
                        price: ride.price || 0
                      }))}
                    />
                  </CardContent>
                </Card>
                {availableRides.map((ride) => (
                <Card key={ride.id}>
                  <CardContent className="pt-6">
                    <div className="flex items-start justify-between mb-4">
                      <div className="space-y-3 flex-1">
                        <div className="flex items-start gap-2">
                          <MapPin className="w-5 h-5 text-green-600 mt-1" />
                          <div>
                            <div className="font-semibold">Abholung</div>
                            <div className="text-sm text-gray-600">{ride.pickup.address}</div>
                          </div>
                        </div>
                        <div className="flex items-start gap-2">
                          <MapPin className="w-5 h-5 text-red-600 mt-1" />
                          <div>
                            <div className="font-semibold">Ziel</div>
                            <div className="text-sm text-gray-600">{ride.destination.address}</div>
                          </div>
                        </div>
                        <div className="flex items-center gap-4 text-sm text-gray-600">
                          <div className="flex items-center gap-1">
                            <User className="w-4 h-4" />
                            {ride.customer_name}
                          </div>
                          <div className="flex items-center gap-1">
                            <Phone className="w-4 h-4" />
                            {ride.customer_phone}
                          </div>
                        </div>
                      </div>
                      <div className="flex flex-col items-end gap-2">
                        {ride.price && (
                          <div className="text-xl font-bold text-blue-600">
                            CHF {ride.price.toFixed(2)}
                          </div>
                        )}
                        <Button 
                          onClick={() => acceptRide(ride.id)}
                          disabled={loading}
                          className="bg-green-600 hover:bg-green-700"
                        >
                          Akzeptieren
                        </Button>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              ))}
              </>
            )}
          </TabsContent>

          <TabsContent value="active" className="space-y-4">
            {activeRide ? (
              <Card>
                <CardHeader>
                  <div className="flex items-center justify-between">
                    <CardTitle className="flex items-center gap-2">
                      {getStatusIcon(activeRide.status)}
                      {getStatusText(activeRide.status)}
                    </CardTitle>
                    {activeRide.price && (
                      <div className="text-2xl font-bold text-blue-600">
                        CHF {activeRide.price.toFixed(2)}
                      </div>
                    )}
                  </div>
                </CardHeader>
                <CardContent className="space-y-4">
                  <Map
                    activeRide={{
                      pickup: { lat: activeRide.pickup.lat, lng: activeRide.pickup.lng },
                      destination: { lat: activeRide.destination.lat, lng: activeRide.destination.lng }
                    }}
                    driverLocation={activeRide.driver_lat && activeRide.driver_lng ? { lat: activeRide.driver_lat, lng: activeRide.driver_lng } : undefined}
                  />

                  <div className="space-y-3">
                    <div className="flex items-start gap-2">
                      <MapPin className="w-5 h-5 text-green-600 mt-1" />
                      <div>
                        <div className="font-semibold">Abholung</div>
                        <div className="text-sm text-gray-600">{activeRide.pickup.address}</div>
                      </div>
                    </div>
                    <div className="flex items-start gap-2">
                      <MapPin className="w-5 h-5 text-red-600 mt-1" />
                      <div>
                        <div className="font-semibold">Ziel</div>
                        <div className="text-sm text-gray-600">{activeRide.destination.address}</div>
                      </div>
                    </div>
                  </div>

                  <div className="border-t pt-4 space-y-2">
                    <div className="font-semibold">Kunden-Informationen</div>
                    <div className="grid grid-cols-2 gap-2 text-sm">
                      <div>
                        <div className="text-gray-600">Name</div>
                        <div className="font-medium">{activeRide.customer_name}</div>
                      </div>
                      <div>
                        <div className="text-gray-600">Telefon</div>
                        <div className="font-medium">{activeRide.customer_phone}</div>
                      </div>
                    </div>
                  </div>

                  <div className="flex gap-2 pt-4">
                    {activeRide.status === 'accepted' && (
                      <Button 
                        onClick={pickupCustomer}
                        disabled={loading}
                        className="flex-1 bg-blue-600 hover:bg-blue-700"
                      >
                        Kunde abgeholt
                      </Button>
                    )}
                    {activeRide.status === 'picked_up' && (
                      <Button 
                        onClick={completeRide}
                        disabled={loading}
                        className="flex-1 bg-green-600 hover:bg-green-700"
                      >
                        Fahrt abschließen
                      </Button>
                    )}
                  </div>
                </CardContent>
              </Card>
            ) : (
              <Card>
                <CardContent className="py-8 text-center text-gray-500">
                  Keine aktive Fahrt
                </CardContent>
              </Card>
            )}
          </TabsContent>

          <TabsContent value="history" className="space-y-4">
            {rideHistory.length === 0 ? (
              <Card>
                <CardContent className="py-8 text-center text-gray-500">
                  Keine abgeschlossenen Fahrten
                </CardContent>
              </Card>
            ) : (
              rideHistory.map((ride) => (
                <Card key={ride.id}>
                  <CardContent className="pt-6">
                    <div className="flex items-center justify-between mb-3">
                      <div className="flex items-center gap-2">
                        {getStatusIcon(ride.status)}
                        <span className="font-semibold">{getStatusText(ride.status)}</span>
                      </div>
                      {ride.price && (
                        <div className="font-bold text-blue-600">CHF {ride.price.toFixed(2)}</div>
                      )}
                    </div>
                    <div className="space-y-1 text-sm">
                      <div className="flex items-start gap-2">
                        <MapPin className="w-4 h-4 text-green-600 mt-0.5" />
                        <span className="text-gray-600">{ride.pickup.address}</span>
                      </div>
                      <div className="flex items-start gap-2">
                        <MapPin className="w-4 h-4 text-red-600 mt-0.5" />
                        <span className="text-gray-600">{ride.destination.address}</span>
                      </div>
                      <div className="flex items-center gap-2 text-gray-400 pt-2">
                        <User className="w-3 h-3" />
                        <span>{ride.customer_name}</span>
                        <span>•</span>
                        <span>{new Date(ride.created_at).toLocaleString('de-CH')}</span>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              ))
            )}
          </TabsContent>
        </Tabs>
      </div>
      </div>
    </LoadScript>
  )
}

export default App

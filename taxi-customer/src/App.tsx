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
  const [customerId, setCustomerId] = useState<string | null>(localStorage.getItem('customerId'))
  const [customerName, setCustomerName] = useState(localStorage.getItem('customerName') || '')
  const [customerEmail, setCustomerEmail] = useState('')
  const [customerPhone, setCustomerPhone] = useState(localStorage.getItem('customerPhone') || '')
  
  const [pickupAddress, setPickupAddress] = useState('')
  const [pickupLat, setPickupLat] = useState<number | null>(null)
  const [pickupLng, setPickupLng] = useState<number | null>(null)
  const [destAddress, setDestAddress] = useState('')
  const [destLat, setDestLat] = useState<number | null>(null)
  const [destLng, setDestLng] = useState<number | null>(null)
  const [mapMode, setMapMode] = useState<'selecting-pickup' | 'selecting-destination' | 'tracking'>('selecting-pickup')
  
  const [currentRide, setCurrentRide] = useState<Ride | null>(null)
  const [rideHistory, setRideHistory] = useState<Ride[]>([])
  const [loading, setLoading] = useState(false)

  useEffect(() => {
    if (customerId) {
      fetchCurrentRide()
      fetchRideHistory()
      const interval = setInterval(() => {
        if (currentRide && currentRide.status !== 'completed' && currentRide.status !== 'cancelled') {
          fetchCurrentRide()
        }
      }, 3000)
      return () => clearInterval(interval)
    }
  }, [customerId, currentRide?.id])

  const registerCustomer = async () => {
    setLoading(true)
    try {
      const response = await fetch(`${API_URL}/api/customers/register?name=${encodeURIComponent(customerName)}&email=${encodeURIComponent(customerEmail)}&phone=${encodeURIComponent(customerPhone)}`, {
        method: 'POST'
      })
      const data = await response.json()
      setCustomerId(data.id)
      localStorage.setItem('customerId', data.id)
      localStorage.setItem('customerName', customerName)
      localStorage.setItem('customerPhone', customerPhone)
    } catch (error) {
      console.error('Registrierung fehlgeschlagen:', error)
    }
    setLoading(false)
  }

  const handlePickupClick = (location: { lat: number; lng: number }) => {
    setPickupLat(location.lat)
    setPickupLng(location.lng)
    setPickupAddress(`${location.lat.toFixed(4)}, ${location.lng.toFixed(4)}`)
    setMapMode('selecting-destination')
  }

  const handleDestinationClick = (location: { lat: number; lng: number }) => {
    setDestLat(location.lat)
    setDestLng(location.lng)
    setDestAddress(`${location.lat.toFixed(4)}, ${location.lng.toFixed(4)}`)
  }

  const requestRide = async () => {
    if (!customerId || pickupLat === null || pickupLng === null || destLat === null || destLng === null) return
    setLoading(true)
    try {
      const response = await fetch(`${API_URL}/api/rides/request`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          pickup: { lat: pickupLat, lng: pickupLng, address: pickupAddress },
          destination: { lat: destLat, lng: destLng, address: destAddress },
          customer_id: customerId,
          customer_name: customerName,
          customer_phone: customerPhone
        })
      })
      const data = await response.json()
      setCurrentRide(data)
      setPickupLat(null)
      setPickupLng(null)
      setDestLat(null)
      setDestLng(null)
      setPickupAddress('')
      setDestAddress('')
      setMapMode('selecting-pickup')
    } catch (error) {
      console.error('Fahrt konnte nicht angefordert werden:', error)
    }
    setLoading(false)
  }

  const fetchCurrentRide = async () => {
    if (!customerId) return
    try {
      const response = await fetch(`${API_URL}/api/rides/customer/${customerId}`)
      const rides = await response.json()
      const active = rides.find((r: Ride) => 
        r.status === 'requested' || r.status === 'accepted' || r.status === 'picked_up'
      )
      setCurrentRide(active || null)
    } catch (error) {
      console.error('Fehler beim Laden der Fahrt:', error)
    }
  }

  const fetchRideHistory = async () => {
    if (!customerId) return
    try {
      const response = await fetch(`${API_URL}/api/rides/customer/${customerId}`)
      const rides = await response.json()
      setRideHistory(rides.filter((r: Ride) => r.status === 'completed' || r.status === 'cancelled'))
    } catch (error) {
      console.error('Fehler beim Laden der Geschichte:', error)
    }
  }

  const cancelRide = async () => {
    if (!currentRide) return
    setLoading(true)
    try {
      await fetch(`${API_URL}/api/rides/${currentRide.id}/cancel`, { method: 'POST' })
      setCurrentRide(null)
      fetchRideHistory()
    } catch (error) {
      console.error('Stornierung fehlgeschlagen:', error)
    }
    setLoading(false)
  }

  const getStatusText = (status: string) => {
    switch (status) {
      case 'requested': return 'Suche nach Fahrer...'
      case 'accepted': return 'Fahrer unterwegs'
      case 'picked_up': return 'Fahrt läuft'
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

  if (!customerId) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-600 to-blue-800 flex items-center justify-center p-4">
        <Card className="w-full max-w-md">
          <CardHeader className="text-center">
            <div className="mx-auto w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mb-4">
              <Car className="w-8 h-8 text-white" />
            </div>
            <CardTitle className="text-2xl">Taxi Zug</CardTitle>
            <CardDescription>Kunden-App</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="name">Name</Label>
              <div className="relative">
                <User className="absolute left-3 top-3 w-4 h-4 text-gray-400" />
                <Input
                  id="name"
                  placeholder="Ihr Name"
                  value={customerName}
                  onChange={(e) => setCustomerName(e.target.value)}
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
                  value={customerEmail}
                  onChange={(e) => setCustomerEmail(e.target.value)}
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
                  value={customerPhone}
                  onChange={(e) => setCustomerPhone(e.target.value)}
                  className="pl-10"
                />
              </div>
            </div>
            <Button 
              onClick={registerCustomer} 
              disabled={!customerName || !customerEmail || !customerPhone || loading}
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
              <h1 className="text-xl font-bold">Taxi Zug</h1>
            </div>
            <div className="text-sm">{customerName}</div>
          </div>
        </div>

      <div className="container mx-auto p-4 max-w-4xl">
        <Tabs defaultValue="ride" className="w-full">
          <TabsList className="grid w-full grid-cols-2">
            <TabsTrigger value="ride">Fahrt buchen</TabsTrigger>
            <TabsTrigger value="history">Geschichte</TabsTrigger>
          </TabsList>

          <TabsContent value="ride" className="space-y-4">
            {currentRide ? (
              <Card>
                <CardHeader>
                  <div className="flex items-center justify-between">
                    <CardTitle className="flex items-center gap-2">
                      {getStatusIcon(currentRide.status)}
                      {getStatusText(currentRide.status)}
                    </CardTitle>
                    {currentRide.status === 'requested' && (
                      <Button variant="destructive" size="sm" onClick={cancelRide}>
                        Stornieren
                      </Button>
                    )}
                  </div>
                </CardHeader>
                <CardContent className="space-y-4">
                  <Map
                    pickupLocation={{ lat: currentRide.pickup.lat, lng: currentRide.pickup.lng }}
                    destinationLocation={{ lat: currentRide.destination.lat, lng: currentRide.destination.lng }}
                    driverLocation={currentRide.driver_lat && currentRide.driver_lng ? { lat: currentRide.driver_lat, lng: currentRide.driver_lng } : undefined}
                    mode="tracking"
                  />

                  <div className="space-y-2">
                    <div className="flex items-start gap-2">
                      <MapPin className="w-5 h-5 text-green-600 mt-1" />
                      <div>
                        <div className="font-semibold">Abholung</div>
                        <div className="text-sm text-gray-600">{currentRide.pickup.address}</div>
                      </div>
                    </div>
                    <div className="flex items-start gap-2">
                      <MapPin className="w-5 h-5 text-red-600 mt-1" />
                      <div>
                        <div className="font-semibold">Ziel</div>
                        <div className="text-sm text-gray-600">{currentRide.destination.address}</div>
                      </div>
                    </div>
                  </div>

                  {currentRide.price && (
                    <div className="bg-blue-50 p-3 rounded-lg">
                      <div className="text-sm text-gray-600">Geschätzter Preis</div>
                      <div className="text-2xl font-bold text-blue-600">CHF {currentRide.price.toFixed(2)}</div>
                    </div>
                  )}

                  {currentRide.driver_name && (
                    <div className="border-t pt-4 space-y-2">
                      <div className="font-semibold">Fahrer-Informationen</div>
                      <div className="grid grid-cols-2 gap-2 text-sm">
                        <div>
                          <div className="text-gray-600">Name</div>
                          <div className="font-medium">{currentRide.driver_name}</div>
                        </div>
                        <div>
                          <div className="text-gray-600">Telefon</div>
                          <div className="font-medium">{currentRide.driver_phone}</div>
                        </div>
                        <div className="col-span-2">
                          <div className="text-gray-600">Fahrzeug</div>
                          <div className="font-medium">{currentRide.driver_vehicle}</div>
                        </div>
                      </div>
                    </div>
                  )}
                </CardContent>
              </Card>
            ) : (
              <Card>
                <CardHeader>
                  <CardTitle>Neue Fahrt buchen</CardTitle>
                  <CardDescription>Klicken Sie auf die Karte, um Standorte auszuwählen</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="flex gap-2 mb-4">
                    <Button
                      variant={mapMode === 'selecting-pickup' ? 'default' : 'outline'}
                      onClick={() => setMapMode('selecting-pickup')}
                      className={mapMode === 'selecting-pickup' ? 'bg-green-600 hover:bg-green-700' : ''}
                    >
                      <MapPin className="w-4 h-4 mr-2" />
                      Abholung wählen
                    </Button>
                    <Button
                      variant={mapMode === 'selecting-destination' ? 'default' : 'outline'}
                      onClick={() => setMapMode('selecting-destination')}
                      className={mapMode === 'selecting-destination' ? 'bg-red-600 hover:bg-red-700' : ''}
                    >
                      <MapPin className="w-4 h-4 mr-2" />
                      Ziel wählen
                    </Button>
                  </div>

                  <Map
                    pickupLocation={pickupLat !== null && pickupLng !== null ? { lat: pickupLat, lng: pickupLng } : undefined}
                    destinationLocation={destLat !== null && destLng !== null ? { lat: destLat, lng: destLng } : undefined}
                    onPickupClick={handlePickupClick}
                    onDestinationClick={handleDestinationClick}
                    mode={mapMode}
                  />

                  <div className="space-y-2">
                    {pickupLat !== null && pickupLng !== null && (
                      <div className="flex items-center gap-2 p-2 bg-green-50 rounded">
                        <MapPin className="w-4 h-4 text-green-600" />
                        <span className="text-sm text-green-800">Abholung: {pickupAddress}</span>
                      </div>
                    )}
                    {destLat !== null && destLng !== null && (
                      <div className="flex items-center gap-2 p-2 bg-red-50 rounded">
                        <MapPin className="w-4 h-4 text-red-600" />
                        <span className="text-sm text-red-800">Ziel: {destAddress}</span>
                      </div>
                    )}
                  </div>

                  <Button 
                    onClick={requestRide}
                    disabled={pickupLat === null || destLat === null || loading}
                    className="w-full bg-blue-600 hover:bg-blue-700"
                  >
                    Taxi buchen
                  </Button>
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
                      <div className="text-gray-400 pt-2">
                        {new Date(ride.created_at).toLocaleString('de-CH')}
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

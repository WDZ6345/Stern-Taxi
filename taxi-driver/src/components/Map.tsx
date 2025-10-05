import { GoogleMap, Marker, Polyline } from '@react-google-maps/api';
import { useCallback, useState } from 'react';

const mapContainerStyle = {
  width: '100%',
  height: '400px',
  borderRadius: '8px'
};

const defaultCenter = {
  lat: 47.1667,
  lng: 8.5167
};

interface RideLocation {
  id: string;
  pickup: { lat: number; lng: number };
  destination: { lat: number; lng: number };
  price: number;
}

interface MapProps {
  availableRides?: RideLocation[];
  activeRide?: {
    pickup: { lat: number; lng: number };
    destination: { lat: number; lng: number };
  };
  driverLocation?: { lat: number; lng: number };
  onRideClick?: (rideId: string) => void;
}

export function Map({
  availableRides,
  activeRide,
  driverLocation,
  onRideClick
}: MapProps) {
  const [isLoaded, setIsLoaded] = useState(false);

  const onLoad = useCallback(() => {
    setIsLoaded(true);
  }, []);

  const onUnmount = useCallback(() => {
    setIsLoaded(false);
  }, []);

  const center = activeRide?.pickup || driverLocation || defaultCenter;

  return (
    <GoogleMap
      mapContainerStyle={mapContainerStyle}
      center={center}
      zoom={13}
      onLoad={onLoad}
      onUnmount={onUnmount}
      options={{
        zoomControl: true,
        streetViewControl: false,
        mapTypeControl: false,
        fullscreenControl: true,
      }}
    >
        {isLoaded && availableRides && availableRides.map((ride) => (
          <Marker
            key={ride.id}
            position={ride.pickup}
            icon={{
              path: google.maps.SymbolPath.CIRCLE,
              scale: 8,
              fillColor: '#f59e0b',
              fillOpacity: 1,
              strokeColor: '#d97706',
              strokeWeight: 2,
            }}
            title={`Fahrt - CHF ${ride.price.toFixed(2)}`}
            onClick={() => onRideClick && onRideClick(ride.id)}
          />
        ))}

        {isLoaded && activeRide && (
          <>
            <Marker
              position={activeRide.pickup}
              icon={{
                path: google.maps.SymbolPath.CIRCLE,
                scale: 10,
                fillColor: '#22c55e',
                fillOpacity: 1,
                strokeColor: '#16a34a',
                strokeWeight: 2,
              }}
              title="Abholung"
            />
            <Marker
              position={activeRide.destination}
              icon={{
                path: google.maps.SymbolPath.CIRCLE,
                scale: 10,
                fillColor: '#ef4444',
                fillOpacity: 1,
                strokeColor: '#dc2626',
                strokeWeight: 2,
              }}
              title="Ziel"
            />
            <Polyline
              path={[activeRide.pickup, activeRide.destination]}
              options={{
                strokeColor: '#3b82f6',
                strokeOpacity: 0.8,
                strokeWeight: 3,
              }}
            />
          </>
        )}

        {isLoaded && driverLocation && (
          <Marker
            position={driverLocation}
            icon={{
              path: google.maps.SymbolPath.CIRCLE,
              scale: 10,
              fillColor: '#3b82f6',
              fillOpacity: 1,
              strokeColor: '#2563eb',
              strokeWeight: 2,
            }}
            title="Ihre Position"
          />
        )}
      </GoogleMap>
  );
}

import { GoogleMap, Marker, Polyline } from '@react-google-maps/api';
import { useCallback } from 'react';

const mapContainerStyle = {
  width: '100%',
  height: '400px',
  borderRadius: '8px'
};

const defaultCenter = {
  lat: 47.1667,
  lng: 8.5167
};

interface MapProps {
  pickupLocation?: { lat: number; lng: number };
  destinationLocation?: { lat: number; lng: number };
  driverLocation?: { lat: number; lng: number };
  onPickupClick?: (location: { lat: number; lng: number }) => void;
  onDestinationClick?: (location: { lat: number; lng: number }) => void;
  mode: 'selecting-pickup' | 'selecting-destination' | 'tracking';
}

export function Map({
  pickupLocation,
  destinationLocation,
  driverLocation,
  onPickupClick,
  onDestinationClick,
  mode
}: MapProps) {
  const onLoad = useCallback(() => {
  }, []);

  const onUnmount = useCallback(() => {
  }, []);

  const handleMapClick = useCallback((e: google.maps.MapMouseEvent) => {
    if (!e.latLng) return;
    
    const lat = e.latLng.lat();
    const lng = e.latLng.lng();

    if (mode === 'selecting-pickup' && onPickupClick) {
      onPickupClick({ lat, lng });
    } else if (mode === 'selecting-destination' && onDestinationClick) {
      onDestinationClick({ lat, lng });
    }
  }, [mode, onPickupClick, onDestinationClick]);

  const center = pickupLocation || driverLocation || defaultCenter;

  return (
    <GoogleMap
      mapContainerStyle={mapContainerStyle}
      center={center}
      zoom={13}
      onLoad={onLoad}
      onUnmount={onUnmount}
      onClick={handleMapClick}
      options={{
        zoomControl: true,
        streetViewControl: false,
        mapTypeControl: false,
        fullscreenControl: true,
      }}
    >
        {pickupLocation && (
          <Marker
            position={pickupLocation}
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
        )}

        {destinationLocation && (
          <Marker
            position={destinationLocation}
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
        )}

        {driverLocation && (
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
            title="Fahrer"
          />
        )}

        {pickupLocation && destinationLocation && (
          <Polyline
            path={[pickupLocation, destinationLocation]}
            options={{
              strokeColor: '#3b82f6',
              strokeOpacity: 0.8,
              strokeWeight: 3,
            }}
          />
        )}
      </GoogleMap>
  );
}

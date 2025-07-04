/* WP Taxi Plugin - Customer Dashboard Script */

let mapCustomer, directionsService, directionsRenderer;
let pickupAutocomplete, dropoffAutocomplete;
let availableDriversMarkers = [];
let customerMarker = null; // Marker for customer's current location (optional)
const defaultZoom = 13;
const zurichCoords = { lat: 47.3769, lng: 8.5417 }; // Default center for the map

// Callback function for Google Maps API load
function initMapCustomer() {
    if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
        console.error("Google Maps API not loaded.");
        jQuery('#customer-map-container').html('<p style="color:red; text-align:center; padding-top: 50px;">Google Maps konnte nicht geladen werden. Bitte überprüfen Sie den API-Key und die Internetverbindung.</p>');
        return;
    }

    mapCustomer = new google.maps.Map(document.getElementById('customer-map-container'), {
        center: zurichCoords,
        zoom: defaultZoom,
        mapTypeControl: false,
        streetViewControl: false,
    });

    directionsService = new google.maps.DirectionsService();
    directionsRenderer = new google.maps.DirectionsRenderer();
    directionsRenderer.setMap(mapCustomer);

    // Initialize Autocomplete for address inputs
    const pickupInput = document.getElementById('pickup-address');
    const dropoffInput = document.getElementById('dropoff-address');

    if (pickupInput && dropoffInput) {
        const autocompleteOptions = {
            // Bias to Switzerland, can be adjusted
            componentRestrictions: { country: 'ch' },
            fields: ["address_components", "geometry", "icon", "name"]
        };

        pickupAutocomplete = new google.maps.places.Autocomplete(pickupInput, autocompleteOptions);
        dropoffAutocomplete = new google.maps.places.Autocomplete(dropoffInput, autocompleteOptions);

        pickupAutocomplete.addListener('place_changed', () => {
            const place = pickupAutocomplete.getPlace();
            if (place.geometry && place.geometry.location) {
                document.getElementById('pickup-lat').value = place.geometry.location.lat();
                document.getElementById('pickup-lng').value = place.geometry.location.lng();
                calculateAndDisplayRoute();
                showCustomerLocationOnMap(place.geometry.location);
            }
        });

        dropoffAutocomplete.addListener('place_changed', () => {
            const place = dropoffAutocomplete.getPlace();
            if (place.geometry && place.geometry.location) {
                document.getElementById('dropoff-lat').value = place.geometry.location.lat();
                document.getElementById('dropoff-lng').value = place.geometry.location.lng();
                calculateAndDisplayRoute();
            }
        });
    } else {
        console.warn("Pickup or dropoff input fields not found.");
    }

    // Try to get user's current location to center map (optional)
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                };
                mapCustomer.setCenter(userLocation);
                // Optionally, prefill pickup with current location (would need reverse geocoding)
                // showCustomerLocationOnMap(userLocation, "Ihr Standort");
            },
            () => {
                console.warn("Geolocation failed or was denied. Defaulting to Zurich.");
                mapCustomer.setCenter(zurichCoords);
            }
        );
    }


    // Load available drivers
    fetchAvailableDrivers();
    // Periodically update driver locations (e.g., every 30 seconds)
    setInterval(fetchAvailableDrivers, 30000);
}

function showCustomerLocationOnMap(location, title = "Abholort") {
    if (customerMarker) {
        customerMarker.setMap(null); // Remove previous marker
    }
    customerMarker = new google.maps.Marker({
        position: location,
        map: mapCustomer,
        title: title,
        // icon: 'path/to/your/customer-icon.png' // Optional custom icon
    });
     mapCustomer.panTo(location);
}


function calculateAndDisplayRoute() {
    const pickupLat = document.getElementById('pickup-lat').value;
    const pickupLng = document.getElementById('pickup-lng').value;
    const dropoffLat = document.getElementById('dropoff-lat').value;
    const dropoffLng = document.getElementById('dropoff-lng').value;

    if (pickupLat && pickupLng && dropoffLat && dropoffLng) {
        const origin = { lat: parseFloat(pickupLat), lng: parseFloat(pickupLng) };
        const destination = { lat: parseFloat(dropoffLat), lng: parseFloat(dropoffLng) };

        document.getElementById('route-distance').textContent = wp_taxi_customer_params.text_calculating_route;
        document.getElementById('route-duration').textContent = '...';
        document.getElementById('route-price').textContent = '...';

        directionsService.route(
            {
                origin: origin,
                destination: destination,
                travelMode: google.maps.TravelMode.DRIVING,
            },
            (response, status) => {
                if (status === 'OK') {
                    directionsRenderer.setDirections(response);
                    const route = response.routes[0].legs[0];
                    const distanceInKm = route.distance.value / 1000;
                    document.getElementById('route-distance').textContent = distanceInKm.toFixed(2);
                    document.getElementById('route-duration').textContent = route.duration.text;

                    // Dummy price calculation (replace with actual logic from backend/settings)
                    // Base fare + price per km. These should come from plugin settings.
                    const baseFare = parseFloat(wp_taxi_customer_params.base_fare || 5.00);
                    const pricePerKm = parseFloat(wp_taxi_customer_params.price_per_km || 2.50);
                    const estimatedPrice = baseFare + (distanceInKm * pricePerKm);
                    document.getElementById('route-price').textContent = estimatedPrice.toFixed(2);

                } else {
                    console.error('Directions request failed due to ' + status);
                    document.getElementById('route-distance').textContent = wp_taxi_customer_params.text_route_not_found;
                    document.getElementById('route-duration').textContent = '---';
                    document.getElementById('route-price').textContent = '---';
                }
            }
        );
    }
}

function fetchAvailableDrivers() {
    if (typeof wp_taxi_customer_params === 'undefined' || !wp_taxi_customer_params.ajax_url) {
        console.error("WP Taxi Customer Params not defined or AJAX URL missing.");
        return;
    }
    jQuery.ajax({
        url: wp_taxi_customer_params.ajax_url,
        type: 'POST',
        data: {
            action: 'wp_taxi_get_available_drivers',
            nonce: wp_taxi_customer_params.nonce_get_drivers
        },
        success: function(response) {
            clearDriverMarkers();
            if (response.success && response.data.length > 0) {
                response.data.forEach(driver => {
                    addDriverMarker(driver);
                });
            } else {
                 // console.log(response.data.message || wp_taxi_customer_params.text_no_drivers_available);
                 // Optionally display a message on the map or dashboard
            }
        },
        error: function(xhr, status, error) {
            console.error("Error fetching available drivers: ", status, error);
        }
    });
}

function addDriverMarker(driver) {
    if (typeof google === 'undefined' || typeof google.maps === 'undefined') return;
    const marker = new google.maps.Marker({
        position: { lat: driver.latitude, lng: driver.longitude },
        map: mapCustomer,
        title: driver.name + (driver.vehicle_model ? ` (${driver.vehicle_model})` : ''),
        icon: { // Simple default icon, can be customized
            path: google.maps.SymbolPath.CIRCLE,
            scale: 8,
            fillColor: "#0073aa", // WordPress blue
            fillOpacity: 1,
            strokeWeight: 1,
            strokeColor: "#FFFFFF"
        }
        // icon: WP_TAXI_PLUGIN_URL + 'assets/images/taxi-icon.png' // Path to your custom taxi icon
    });

    const infowindow = new google.maps.InfoWindow({
        content: `<strong>${driver.name}</strong><br>${driver.vehicle_model || ''}`
    });

    marker.addListener('click', () => {
        infowindow.open(mapCustomer, marker);
    });

    availableDriversMarkers.push(marker);
}

function clearDriverMarkers() {
    availableDriversMarkers.forEach(marker => marker.setMap(null));
    availableDriversMarkers = [];
}


jQuery(document).ready(function($) {
    // Make sure initMapCustomer is globally accessible for the Google Maps callback
    // window.initMapCustomer = initMapCustomer; // Already defined globally

    const requestRideBtn = $('#request-ride-btn');
    const bookingMessageDiv = $('#booking-message');

    requestRideBtn.on('click', function() {
        const pickupAddress = $('#pickup-address').val();
        const dropoffAddress = $('#dropoff-address').val();
        const pickupLat = $('#pickup-lat').val();
        const pickupLng = $('#pickup-lng').val();
        const dropoffLat = $('#dropoff-lat').val();
        const dropoffLng = $('#dropoff-lng').val();
        const estimatedPrice = $('#route-price').text();

        if (!pickupAddress || !dropoffAddress || !pickupLat || !pickupLng ) {
            bookingMessageDiv.text(wp_taxi_customer_params.text_select_pickup_and_dropoff).removeClass('success').addClass('error').show();
            return;
        }

        bookingMessageDiv.text(wp_taxi_customer_params.text_requesting_ride).removeClass('error success').show();
        $(this).prop('disabled', true);

        $.ajax({
            url: wp_taxi_customer_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wp_taxi_request_ride',
                nonce: wp_taxi_customer_params.nonce_request_ride,
                pickup_address: pickupAddress,
                dropoff_address: dropoffAddress,
                pickup_lat: pickupLat,
                pickup_lng: pickupLng,
                dropoff_lat: dropoffLat,
                dropoff_lng: dropoffLng,
                estimated_price: estimatedPrice
            },
            success: function(response) {
                if (response.success) {
                    bookingMessageDiv.text(response.data.message || wp_taxi_customer_params.text_ride_requested_successfully).removeClass('error').addClass('success').show();
                    // Optionally clear form or redirect
                    // $('#pickup-address').val('');
                    // $('#dropoff-address').val('');
                    // directionsRenderer.setDirections({routes: []}); // Clear route from map
                } else {
                    bookingMessageDiv.text(response.data.message || wp_taxi_customer_params.text_ride_request_failed).removeClass('success').addClass('error').show();
                }
            },
            error: function() {
                bookingMessageDiv.text(wp_taxi_customer_params.text_ride_request_failed + ' (AJAX Error)').removeClass('success').addClass('error').show();
            },
            complete: function() {
                requestRideBtn.prop('disabled', false);
            }
        });
    });

    // Trigger map initialization if not using callback=initMapCustomer in script URL
    // (but we are, so this is mostly a fallback or for manual init)
    if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
        if (wp_taxi_customer_params && wp_taxi_customer_params.google_maps_api_key) {
            // This is tricky because the Google script is loaded async with a callback.
            // If it failed to load, initMapCustomer won't be called.
            // The initMapCustomer function itself handles the error if google object is not found.
        } else {
             $('#customer-map-container').html('<p style="color:red; text-align:center; padding-top: 50px;">Google Maps API Key nicht konfiguriert.</p>');
        }
    } else if (!mapCustomer) { // If google maps loaded but our map not yet init
        // initMapCustomer(); // This would be called by Google's callback
    }
});

// Ensure initMapCustomer is globally available for the Google Maps API callback
window.initMapCustomer = initMapCustomer;

/* WP Taxi Plugin - Driver Dashboard Script */

let mapDriver, driverDirectionsService, driverDirectionsRenderer;
let driverLocationMarker = null;
let currentRidePolyline = null;
let watchLocationId = null;
const zurichCoordsDriver = { lat: 47.3769, lng: 8.5417 }; // Default center for the map
let currentAcceptedRide = null; // Store details of the currently accepted ride

// Callback function for Google Maps API load
function initMapDriver() {
    if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
        console.error("Google Maps API not loaded for Driver Dashboard.");
        jQuery('#driver-map-container').html('<p style="color:red; text-align:center; padding-top: 50px;">Google Maps konnte nicht geladen werden.</p>');
        return;
    }

    mapDriver = new google.maps.Map(document.getElementById('driver-map-container'), {
        center: zurichCoordsDriver,
        zoom: 13,
        mapTypeControl: false,
        streetViewControl: false,
    });

    driverDirectionsService = new google.maps.DirectionsService();
    driverDirectionsRenderer = new google.maps.DirectionsRenderer({
        map: mapDriver,
        suppressMarkers: true // We'll use custom markers
    });

    // Initial actions
    fetchPendingRides();
    setInterval(fetchPendingRides, 30000); // Refresh pending rides periodically

    // Update driver's current location on map if available
    if (jQuery('#driver-availability-toggle').is(':checked')) {
        startLocationTracking();
    }

    // Attempt to center map on driver's current location if permission is granted
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                };
                mapDriver.setCenter(userLocation);
                if (driverLocationMarker) driverLocationMarker.setPosition(userLocation);
                else updateDriverMarker(userLocation, "Mein Standort");
            },
            () => {
                console.warn("Geolocation failed or was denied by driver. Defaulting to Zurich for map center.");
            }
        );
    }
}

function updateDriverMarker(location, title = "Mein aktueller Standort") {
    if (typeof google === 'undefined' || typeof google.maps === 'undefined') return;
    if (!driverLocationMarker) {
        driverLocationMarker = new google.maps.Marker({
            position: location,
            map: mapDriver,
            title: title,
            icon: { // Custom icon for the driver
                path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                scale: 7,
                rotation: 0, // Will be updated with heading if available
                fillColor: "#007bff", // Blue
                fillOpacity: 1,
                strokeWeight: 2,
                strokeColor: "#FFFFFF"
            }
        });
    } else {
        driverLocationMarker.setPosition(location);
    }
    // mapDriver.panTo(location); // Optionally pan map to always keep driver centered
}


function startLocationTracking() {
    if (navigator.geolocation && !watchLocationId) {
        watchLocationId = navigator.geolocation.watchPosition(
            (position) => {
                const currentLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                };
                updateDriverMarker(currentLocation);
                sendLocationToServer(currentLocation.lat, currentLocation.lng);

                // If there's an active ride, update map center/zoom to show driver and destination
                if (currentAcceptedRide && currentAcceptedRide.start_lat) { // Assuming start_lat indicates an active ride for pickup
                    const customerLocation = new google.maps.LatLng(currentAcceptedRide.start_lat, currentAcceptedRide.start_lng);
                    const bounds = new google.maps.LatLngBounds();
                    bounds.extend(currentLocation);
                    bounds.extend(customerLocation);
                    mapDriver.fitBounds(bounds);
                } else if (currentAcceptedRide && currentAcceptedRide.end_lat) { // For ride to destination
                     const destinationLocation = new google.maps.LatLng(currentAcceptedRide.end_lat, currentAcceptedRide.end_lng);
                     const bounds = new google.maps.LatLngBounds();
                     bounds.extend(currentLocation);
                     bounds.extend(destinationLocation);
                     mapDriver.fitBounds(bounds);
                } else {
                     mapDriver.setCenter(currentLocation); // Center on driver if no active ride
                }


            },
            (error) => {
                console.warn("Error watching position: ", error.message);
                jQuery('#driver-status-message').text('Standortverfolgung fehlgeschlagen.').css('color', 'red');
            },
            {
                enableHighAccuracy: true,
                timeout: 10000, // 10 seconds
                maximumAge: 0 // Don't use a cached position
            }
        );
        jQuery('#driver-status-message').text('Standortverfolgung aktiv.').css('color', 'green');
    } else if (!navigator.geolocation) {
         jQuery('#driver-status-message').text('Geolocation wird von Ihrem Browser nicht unterstützt.').css('color', 'red');
    }
}

function stopLocationTracking() {
    if (watchLocationId) {
        navigator.geolocation.clearWatch(watchLocationId);
        watchLocationId = null;
        jQuery('#driver-status-message').text('Standortverfolgung gestoppt.').css('color', 'orange');
        // Optionally remove driver marker or change its appearance
        // if (driverLocationMarker) driverLocationMarker.setMap(null);
        // driverLocationMarker = null;
    }
}

function sendLocationToServer(latitude, longitude) {
    jQuery.ajax({
        url: wp_taxi_driver_params.ajax_url,
        type: 'POST',
        data: {
            action: 'wp_taxi_update_driver_location',
            nonce_val: wp_taxi_driver_params.nonce_update_location,
            latitude: latitude,
            longitude: longitude
        },
        success: function(response) {
            // console.log("Location updated on server:", response.data.message);
        },
        error: function(xhr) {
            console.error("Error updating location on server:", xhr.responseText);
        }
    });
}

function fetchPendingRides() {
    const listContainer = jQuery('#pending-rides-list');
    if (currentAcceptedRide) { // Don't fetch new rides if one is active
        // listContainer.html('<p>' + wp_taxi_driver_params.text_no_pending_rides + ' (Aktive Fahrt)</p>');
        return;
    }

    jQuery.ajax({
        url: wp_taxi_driver_params.ajax_url,
        type: 'POST',
        data: {
            action: 'wp_taxi_get_pending_rides',
            nonce: wp_taxi_driver_params.nonce_get_rides
        },
        beforeSend: function() {
            // listContainer.html('<p>Lade verfügbare Fahrten...</p>');
        },
        success: function(response) {
            listContainer.empty();
            if (response.success && response.data.length > 0) {
                response.data.forEach(ride => {
                    const rideHtml = `
                        <div class="ride-item" id="ride-${ride.id}">
                            <h4>Fahrt von: ${ride.start_address}</h4>
                            <p><strong>Nach:</strong> ${ride.end_address || 'Nicht spezifiziert'}</p>
                            <p><strong>Kunde:</strong> ${ride.customer_name || 'Unbekannt'}</p>
                            <p><strong>Geschätzter Preis:</strong> CHF ${ride.estimated_price || 'N/A'}</p>
                            <button class="accept-ride-btn" data-ride-id="${ride.id}" data-start-lat="${ride.start_lat}" data-start-lng="${ride.start_lng}" data-end-lat="${ride.end_lat || ''}" data-end-lng="${ride.end_lng || ''}">Fahrt annehmen</button>
                            <div class="driver-action-message" id="message-ride-${ride.id}"></div>
                        </div>`;
                    listContainer.append(rideHtml);
                });
            } else {
                listContainer.html('<p>' + (response.data.message || wp_taxi_driver_params.text_no_pending_rides) + '</p>');
            }
        },
        error: function(xhr) {
            listContainer.html('<p style="color:red;">' + wp_taxi_driver_params.text_error_loading_rides + '</p>');
            console.error("Error fetching pending rides: ", xhr.responseText);
        }
    });
}

function displayCurrentRideDetails(ride) {
    currentAcceptedRide = ride; // Store the accepted ride details globally
    const detailsDiv = jQuery('#current-ride-details');
    if (!ride || !ride.id) {
        detailsDiv.html('<p>Sie haben derzeit keine aktive Fahrt.</p>');
        if(driverDirectionsRenderer) driverDirectionsRenderer.setDirections({routes: []}); // Clear route
        jQuery('#pending-rides-list').show(); // Show pending rides again
        if (jQuery('#driver-availability-toggle').is(':checked')) { // If driver is available
             startLocationTracking(); // Restart general location tracking
        }
        return;
    }

    jQuery('#pending-rides-list').hide(); // Hide pending rides when one is active

    let html = `
        <h4>Aktive Fahrt #${ride.id}</h4>
        <p><strong>Kunde:</strong> ${ride.customer_name || 'Unbekannt'}</p>
        <p><strong>Abholung:</strong> ${ride.start_address}</p>
        <p><strong>Ziel:</strong> ${ride.end_address || 'Nicht spezifiziert'}</p>
        <div class="ride-actions">`;

    // Determine which buttons to show based on ride.status
    if (ride.status === 'accepted') {
        html += `<button class="start-ride-btn" data-ride-id="${ride.id}">Fahrt beginnen (Kunde aufgenommen)</button>`;
    } else if (ride.status === 'ongoing') {
        html += `<button class="complete-ride-btn" data-ride-id="${ride.id}">Fahrt abschließen</button>`;
    }
    // Always show cancel, but its action might differ or be disabled in some states
    html += ` <button class="cancel-ride-btn" data-ride-id="${ride.id}">Fahrt stornieren</button>`;
    html += `</div>
        <div class="driver-action-message" id="message-current-ride"></div>`;

    detailsDiv.html(html);

    // Display route to customer (pickup location)
    if (ride.start_lat && ride.start_lng && driverLocationMarker) {
        const driverPos = driverLocationMarker.getPosition();
        const customerPos = new google.maps.LatLng(parseFloat(ride.start_lat), parseFloat(ride.start_lng));

        calculateAndDisplayDriverRoute(driverPos, customerPos, "Route zum Kunden");

        // Add marker for customer pickup
        new google.maps.Marker({
            position: customerPos,
            map: mapDriver,
            title: "Kunde abholen: " + ride.start_address,
            icon: {
                url: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png" // Simple blue dot for customer
            }
        });

    } else if (ride.status === 'ongoing' && ride.end_lat && ride.end_lng && driverLocationMarker) {
        // Display route to destination
        const driverPos = driverLocationMarker.getPosition();
        const destinationPos = new google.maps.LatLng(parseFloat(ride.end_lat), parseFloat(ride.end_lng));
        calculateAndDisplayDriverRoute(driverPos, destinationPos, "Route zum Ziel");
         // Add marker for destination
        new google.maps.Marker({
            position: destinationPos,
            map: mapDriver,
            title: "Ziel: " + ride.end_address,
            icon: {
                url: "http://maps.google.com/mapfiles/ms/icons/green-dot.png"
            }
        });
    }
}

function calculateAndDisplayDriverRoute(origin, destination, routeType = "Route") {
    if (typeof google === 'undefined' || !driverDirectionsService || !driverDirectionsRenderer) return;

    driverDirectionsService.route({
        origin: origin,
        destination: destination,
        travelMode: google.maps.TravelMode.DRIVING
    }, (response, status) => {
        if (status === 'OK') {
            driverDirectionsRenderer.setDirections(response);
            // console.log(routeType + " angezeigt.");
            // Fit map to bounds of the route
            const bounds = response.routes[0].bounds;
            if (driverLocationMarker) bounds.extend(driverLocationMarker.getPosition()); // Ensure driver is visible
            mapDriver.fitBounds(bounds);

        } else {
            console.error('Driver directions request failed due to ' + status);
            alert('Route konnte nicht berechnet werden: ' + status);
        }
    });
}


jQuery(document).ready(function($) {
    // window.initMapDriver = initMapDriver; // Already defined globally

    // Handle Availability Toggle
    $('#driver-availability-toggle').on('change', function() {
        const isChecked = $(this).is(':checked');
        const statusMessage = $('#driver-status-message');
        const currentStatusText = $('#current-driver-status-text');

        statusMessage.text('Aktualisiere Status...').css('color', 'inherit');

        $.ajax({
            url: wp_taxi_driver_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wp_taxi_update_driver_status',
                nonce_val: wp_taxi_driver_params.nonce_update_driver_status,
                is_available: isChecked
            },
            success: function(response) {
                if (response.success) {
                    statusMessage.text(response.data.message).css('color', 'green');
                    currentStatusText.text(response.data.new_status_text);
                    if (isChecked) {
                        startLocationTracking();
                        fetchPendingRides(); // Fetch rides when becoming available
                    } else {
                        stopLocationTracking();
                        $('#pending-rides-list').html('<p>Sie sind als "Nicht verfügbar" markiert. Es werden keine Fahrten angezeigt.</p>');
                    }
                } else {
                    statusMessage.text(response.data.message || 'Fehler').css('color', 'red');
                    // Revert toggle if update failed
                    $('#driver-availability-toggle').prop('checked', !isChecked);
                }
            },
            error: function() {
                statusMessage.text('Kommunikationsfehler.').css('color', 'red');
                $('#driver-availability-toggle').prop('checked', !isChecked);
            }
        });
    });

    // Handle Accept Ride Button Click (Event Delegation for dynamically added buttons)
    $('#pending-rides-list').on('click', '.accept-ride-btn', function() {
        const rideId = $(this).data('ride-id');
        const startLat = $(this).data('start-lat');
        const startLng = $(this).data('start-lng');
        // const endLat = $(this).data('end-lat'); // Not needed immediately for acceptance
        // const endLng = $(this).data('end-lng');
        const messageDiv = $('#message-ride-' + rideId);

        messageDiv.text('Fahrt wird angenommen...').removeClass('error success').addClass('info').show();
        $(this).prop('disabled', true);

        $.ajax({
            url: wp_taxi_driver_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wp_taxi_accept_ride',
                nonce_val: wp_taxi_driver_params.nonce_accept_ride,
                ride_id: rideId
            },
            success: function(response) {
                if (response.success) {
                    messageDiv.text(response.data.message || wp_taxi_driver_params.text_ride_accepted).removeClass('error info').addClass('success').fadeOut(3000);
                    displayCurrentRideDetails(response.data.ride_details); // Show in "Current Ride" section
                    $('#driver-availability-toggle').prop('checked', false).trigger('change'); // Mark driver as unavailable
                    stopLocationTracking(); // Stop general tracking
                    startLocationTracking(); // Restart tracking, now focused on the ride
                } else {
                    messageDiv.text(response.data.message || wp_taxi_driver_params.text_ride_accept_error).removeClass('success info').addClass('error');
                    $(this).prop('disabled', false);
                }
            }.bind(this), // Ensure 'this' inside success refers to the button
            error: function() {
                messageDiv.text(wp_taxi_driver_params.text_ride_accept_error + ' (AJAX Error)').removeClass('success info').addClass('error');
                $(this).prop('disabled', false);
            }.bind(this)
        });
    });

    // Handle Current Ride Actions (Start, Complete, Cancel)
    $('#current-ride-details').on('click', 'button', function() {
        const rideId = $(this).data('ride-id');
        let newStatus = '';
        let actionText = '';

        if ($(this).hasClass('start-ride-btn')) {
            newStatus = 'ongoing';
            actionText = 'Fahrt wird gestartet...';
        } else if ($(this).hasClass('complete-ride-btn')) {
            newStatus = 'completed';
            actionText = 'Fahrt wird abgeschlossen...';
        } else if ($(this).hasClass('cancel-ride-btn')) {
            if (!confirm("Möchten Sie diese Fahrt wirklich stornieren?")) return;
            newStatus = 'cancelled_by_driver'; // Or just 'cancelled'
            actionText = 'Fahrt wird storniert...';
        } else {
            return; // Unknown button
        }

        const messageDiv = $('#message-current-ride');
        messageDiv.text(actionText).removeClass('error success').addClass('info').show();
        $(this).closest('.ride-actions').find('button').prop('disabled', true);


        $.ajax({
            url: wp_taxi_driver_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wp_taxi_update_ride_status',
                nonce_val: wp_taxi_driver_params.nonce_update_status,
                ride_id: rideId,
                new_status: newStatus
            },
            success: function(response) {
                if (response.success) {
                    messageDiv.text(response.data.message || wp_taxi_driver_params.text_status_updated).removeClass('error info').addClass('success').fadeOut(3000);
                    // Update UI based on new status
                    if (newStatus === 'completed' || newStatus.startsWith('cancelled')) {
                        currentAcceptedRide = null; // Clear current ride
                        displayCurrentRideDetails(null); // Clear details section
                        $('#driver-availability-toggle').prop('checked', true).trigger('change'); // Make driver available again
                        fetchPendingRides(); // Refresh pending rides
                    } else if (newStatus === 'ongoing' && currentAcceptedRide) {
                        currentAcceptedRide.status = 'ongoing'; // Update local status
                        // Refresh details to show correct buttons (e.g., 'Complete' instead of 'Start')
                        // And update map to show route to destination
                        displayCurrentRideDetails(currentAcceptedRide);
                        if (currentAcceptedRide.end_lat && currentAcceptedRide.end_lng && driverLocationMarker) {
                            const driverPos = driverLocationMarker.getPosition();
                            const destinationPos = new google.maps.LatLng(parseFloat(currentAcceptedRide.end_lat), parseFloat(currentAcceptedRide.end_lng));
                            calculateAndDisplayDriverRoute(driverPos, destinationPos, "Route zum Ziel");
                        }
                    }
                } else {
                    messageDiv.text(response.data.message || wp_taxi_driver_params.text_status_update_error).removeClass('success info').addClass('error');
                }
            },
            error: function() {
                messageDiv.text(wp_taxi_driver_params.text_status_update_error + ' (AJAX Error)').removeClass('success info').addClass('error');
            },
            complete: function() {
                 // Re-enable buttons only if the ride is not completed/cancelled
                if (newStatus !== 'completed' && !newStatus.startsWith('cancelled')) {
                    $('#current-ride-details .ride-actions button').prop('disabled', false);
                }
            }
        });
    });

    // Initial map load check (if not using callback in URL)
    if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
        if (wp_taxi_driver_params && wp_taxi_driver_params.google_maps_api_key) {
            // Google script is loaded async with a callback.
            // initMapDriver will handle the error if google object is not found.
        } else {
             $('#driver-map-container').html('<p style="color:red; text-align:center; padding-top: 50px;">Google Maps API Key nicht konfiguriert.</p>');
        }
    }
});

// Ensure initMapDriver is globally available
window.initMapDriver = initMapDriver;

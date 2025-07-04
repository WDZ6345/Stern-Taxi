/* WP Taxi Plugin - Driver Dashboard Script */

let mapDriver, driverDirectionsRenderer, driverMarker, customerMarkerRoute;
let watchLocationId = null;
const driverDefaultZoom = 14;
const driverZurichCoords = { lat: 47.3769, lng: 8.5417 }; // Default center

// Callback function for Google Maps API load
function initMapDriver() {
    if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
        console.error("Google Maps API not loaded for Driver Dashboard.");
        jQuery('#driver-map-container').html('<p style="color:red; text-align:center; padding-top: 50px;">Google Maps konnte nicht geladen werden.</p>');
        return;
    }

    mapDriver = new google.maps.Map(document.getElementById('driver-map-container'), {
        center: driverZurichCoords,
        zoom: driverDefaultZoom,
        mapTypeControl: false,
        streetViewControl: false,
    });

    driverDirectionsRenderer = new google.maps.DirectionsRenderer();
    driverDirectionsRenderer.setMap(mapDriver);

    // Initial load of current ride and pending rides
    fetchCurrentRide();
    fetchPendingRides();

    // Periodically check for new pending rides (e.g., every 15 seconds)
    setInterval(fetchPendingRides, 15000);
    // Periodically check for current ride status (e.g. every 10 seconds, if a ride is active)
    setInterval(fetchCurrentRide, 10000);


    // Start/Stop location tracking based on availability status
    const availabilitySelect = document.getElementById('driver-availability');
    if (availabilitySelect) {
        if (availabilitySelect.value === '1') {
            startLocationTracking();
        }
        // Event listener for availability change handled by 'update-availability-btn' click
    }
}

function updateDriverMarker(position) {
    if (!mapDriver) return;
    const newPosition = {
        lat: position.coords.latitude,
        lng: position.coords.longitude
    };

    if (driverMarker) {
        driverMarker.setPosition(newPosition);
    } else {
        driverMarker = new google.maps.Marker({
            position: newPosition,
            map: mapDriver,
            title: "Mein Standort",
            icon: { // Custom icon for driver
                path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                scale: 6,
                rotation: 0, // Will be updated with heading if available
                fillColor: "#2980B9",
                fillOpacity: 1,
                strokeWeight: 1,
                strokeColor: "#FFFFFF"
            }
        });
    }
    mapDriver.panTo(newPosition); // Optionally center map on driver

    // Update heading if available (not all devices/browsers provide this)
    if (position.coords.heading !== null && !isNaN(position.coords.heading)) {
        driverMarker.setIcon({
            ...driverMarker.getIcon(),
            rotation: position.coords.heading
        });
    }

    // Send location to server
    jQuery.ajax({
        url: wp_taxi_driver_params.ajax_url,
        type: 'POST',
        data: {
            action: 'wp_taxi_update_driver_status',
            nonce: wp_taxi_driver_params.nonce_update_status,
            latitude: newPosition.lat,
            longitude: newPosition.lng,
            // availability: jQuery('#driver-availability').val() // Already sent on button click
        },
        success: function(response) {
            // console.log("Location updated on server.");
        },
        error: function() {
            console.error("Error updating location on server.");
        }
    });
}

function locationError(error) {
    console.warn(`ERROR(${error.code}): ${error.message}`);
    // Optionally, inform the driver that location tracking isn't working.
    // stopLocationTracking(); // Stop trying if there's an error like permission denied.
}

function startLocationTracking() {
    if (navigator.geolocation && !watchLocationId) {
        watchLocationId = navigator.geolocation.watchPosition(
            updateDriverMarker,
            locationError,
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
        console.log("Location tracking started.");
    }
}

function stopLocationTracking() {
    if (navigator.geolocation && watchLocationId) {
        navigator.geolocation.clearWatch(watchLocationId);
        watchLocationId = null;
        console.log("Location tracking stopped.");
        if (driverMarker) {
            driverMarker.setMap(null);
            driverMarker = null;
        }
    }
}

function fetchPendingRides() {
    if (typeof wp_taxi_driver_params === 'undefined' || !wp_taxi_driver_params.ajax_url) return;
    // Only fetch if not currently in an active ride (status accepted or ongoing)
    const currentRidePanel = jQuery('#current-ride-details');
    if (currentRidePanel.data('has-active-ride') === true) {
        // jQuery('#pending-rides-list').html(`<p>${wp_taxi_driver_params.text_no_pending_rides}</p>`);
        return;
    }

    jQuery.ajax({
        url: wp_taxi_driver_params.ajax_url,
        type: 'POST',
        data: {
            action: 'wp_taxi_get_pending_rides',
            nonce: wp_taxi_driver_params.nonce_get_rides
        },
        success: function(response) {
            const listElement = jQuery('#pending-rides-list');
            listElement.empty();
            if (response.success && response.data.length > 0) {
                response.data.forEach(ride => {
                    const rideHtml = `
                        <div class="ride-item" id="ride-${ride.id}">
                            <h4>${wp_taxi_driver_params.text_ride_request || 'Fahranfrage'} #${ride.id}</h4>
                            <p><strong>${wp_taxi_driver_params.text_customer || 'Kunde'}:</strong> ${ride.customer_name || 'Unbekannt'}</p>
                            <p><strong>${wp_taxi_driver_params.text_from || 'Von'}:</strong> ${ride.start_address}</p>
                            <p><strong>${wp_taxi_driver_params.text_to || 'Nach'}:</strong> ${ride.end_address}</p>
                            <p><strong>${wp_taxi_driver_params.text_price || 'Preis (ca.)'}:</strong> CHF ${parseFloat(ride.price || 0).toFixed(2)}</p>
                            <button class="accept-ride-btn" data-ride-id="${ride.id}">${wp_taxi_driver_params.text_accept_ride || 'Fahrt annehmen'}</button>
                            <div class="ride-action-message" style="display:none;"></div>
                        </div>`;
                    listElement.append(rideHtml);
                });
            } else {
                listElement.html(`<p>${wp_taxi_driver_params.text_no_pending_rides}</p>`);
            }
        },
        error: function() {
            jQuery('#pending-rides-list').html(`<p style="color:red;">${wp_taxi_driver_params.text_error_loading_rides}</p>`);
        }
    });
}

function fetchCurrentRide() {
    if (typeof wp_taxi_driver_params === 'undefined' || !wp_taxi_driver_params.ajax_url) return;
    jQuery.ajax({
        url: wp_taxi_driver_params.ajax_url,
        type: 'POST',
        data: {
            action: 'wp_taxi_get_current_ride',
            nonce: wp_taxi_driver_params.nonce_get_current_ride
        },
        success: function(response) {
            const detailsElement = jQuery('#current-ride-details');
            const currentRidePanel = jQuery('#current-ride-panel');
            if (response.success && response.data) {
                const ride = response.data;
                detailsElement.data('has-active-ride', true); // Mark that there's an active ride
                currentRidePanel.show();
                let actionsHtml = '';
                if (ride.status === 'accepted') {
                    actionsHtml = `<button class="update-ride-status-btn" data-ride-id="${ride.id}" data-new-status="ongoing">${wp_taxi_driver_params.text_start_ride || 'Fahrt starten'}</button>
                                   <button class="update-ride-status-btn" data-ride-id="${ride.id}" data-new-status="cancelled">${wp_taxi_driver_params.text_cancel_ride || 'Fahrt stornieren'}</button>`;
                } else if (ride.status === 'ongoing') {
                    actionsHtml = `<button class="update-ride-status-btn" data-ride-id="${ride.id}" data-new-status="completed">${wp_taxi_driver_params.text_complete_ride || 'Fahrt abschliessen'}</button>
                                   <button class="update-ride-status-btn" data-ride-id="${ride.id}" data-new-status="cancelled">${wp_taxi_driver_params.text_cancel_ride || 'Fahrt stornieren'}</button>`;
                }

                const rideDetailsHtml = `
                    <h4>${wp_taxi_driver_params.text_current_ride_details || 'Details zur aktuellen Fahrt'} #${ride.id}</h4>
                    <p><span class="ride-info-label">${wp_taxi_driver_params.text_customer || 'Kunde'}:</span> ${ride.customer_name || 'Unbekannt'}</p>
                    ${ride.customer_phone ? `<p><span class="ride-info-label">${wp_taxi_driver_params.text_phone || 'Tel'}:</span> <a href="tel:${ride.customer_phone}">${ride.customer_phone}</a></p>` : ''}
                    <p><span class="ride-info-label">${wp_taxi_driver_params.text_from || 'Von'}:</span> ${ride.start_address}</p>
                    <p><span class="ride-info-label">${wp_taxi_driver_params.text_to || 'Nach'}:</span> ${ride.end_address}</p>
                    <p><span class="ride-info-label">${wp_taxi_driver_params.text_status || 'Status'}:</span> <strong style="text-transform:capitalize;">${ride.status}</strong></p>
                    <p><span class="ride-info-label">${wp_taxi_driver_params.text_price || 'Preis (ca.)'}:</span> CHF ${parseFloat(ride.price || 0).toFixed(2)}</p>
                    <div class="ride-actions">${actionsHtml}</div>
                    <div class="ride-action-message" style="display:none; margin-top:10px;"></div>`;
                detailsElement.html(rideDetailsHtml);
                displayRouteOnDriverMap(ride);
                jQuery('#pending-rides-list').empty().html(`<p>${wp_taxi_driver_params.text_on_active_ride || 'Sie sind in einer aktiven Fahrt.'}</p>`);

            } else {
                detailsElement.data('has-active-ride', false);
                detailsElement.html(`<p>${wp_taxi_driver_params.text_no_active_ride || 'Sie haben derzeit keine aktive Fahrt.'}</p>`);
                if(driverDirectionsRenderer) driverDirectionsRenderer.setDirections({routes: []}); // Clear map route
                if(customerMarkerRoute) customerMarkerRoute.setMap(null);
                // currentRidePanel.hide(); // Don't hide, show "no active ride"
            }
        },
        error: function() {
            jQuery('#current-ride-details').html(`<p style="color:red;">${wp_taxi_driver_params.text_error_loading_current_ride || 'Fehler beim Laden der aktuellen Fahrt.'}</p>`);
        }
    });
}

function displayRouteOnDriverMap(ride) {
    if (!mapDriver || !driverDirectionsRenderer || !ride.start_lat || !ride.start_lng) return;

    const origin = new google.maps.LatLng(parseFloat(ride.start_lat), parseFloat(ride.start_lng));
    let destination;
    if (ride.end_lat && ride.end_lng) {
        destination = new google.maps.LatLng(parseFloat(ride.end_lat), parseFloat(ride.end_lng));
    } else { // If no destination, just show pickup
        destination = origin;
    }

    // Add or update customer marker for pickup location
    if (customerMarkerRoute) {
        customerMarkerRoute.setPosition(origin);
    } else {
        customerMarkerRoute = new google.maps.Marker({
            position: origin,
            map: mapDriver,
            title: "Abholort: " + ride.start_address,
            // icon: 'path/to/customer-pickup-icon.png' // Optional custom icon
        });
    }


    if (ride.status === 'accepted' && driverMarker && driverMarker.getPosition()) { // Route from driver to pickup
        driverDirectionsRenderer.setDirections({routes: []}); // Clear previous full route if any
        const driverCurrentPos = driverMarker.getPosition();
        new google.maps.DirectionsService().route({
            origin: driverCurrentPos,
            destination: origin, // Pickup location
            travelMode: google.maps.TravelMode.DRIVING
        }, (response, status) => {
            if (status === 'OK') {
                driverDirectionsRenderer.setDirections(response);
            } else {
                console.warn('Directions request from driver to pickup failed due to ' + status);
                mapDriver.panTo(origin); // Center on pickup if route fails
            }
        });
    } else if (ride.status === 'ongoing' && ride.end_lat && ride.end_lng) { // Route from pickup to destination
         driverDirectionsRenderer.setDirections({routes: []}); // Clear previous route
         new google.maps.DirectionsService().route({
            origin: origin, // Pickup location
            destination: destination, // Final destination
            travelMode: google.maps.TravelMode.DRIVING
        }, (response, status) => {
            if (status === 'OK') {
                driverDirectionsRenderer.setDirections(response);
            } else {
                console.warn('Directions request from pickup to dropoff failed due to ' + status);
                 mapDriver.panTo(destination);
            }
        });
    } else { // No active navigation, just center on pickup
        mapDriver.panTo(origin);
        mapDriver.setZoom(15);
        driverDirectionsRenderer.setDirections({routes: []}); // Clear routes
    }
}


jQuery(document).ready(function($) {
    // window.initMapDriver = initMapDriver; // Already global

    // Update Availability
    $('#update-availability-btn').on('click', function() {
        const newAvailability = $('#driver-availability').val();
        const messageDiv = $('#driver-status-message');
        $(this).prop('disabled', true);
        messageDiv.text(wp_taxi_driver_params.text_updating_status).removeClass('error success').show();

        $.ajax({
            url: wp_taxi_driver_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wp_taxi_update_driver_status',
                nonce: wp_taxi_driver_params.nonce_update_status,
                availability: newAvailability,
                // Location is sent by watchPosition if active
            },
            success: function(response) {
                if (response.success) {
                    messageDiv.text(response.data.message || wp_taxi_driver_params.text_status_updated).removeClass('error').addClass('success');
                    if (newAvailability === '1') {
                        startLocationTracking();
                    } else {
                        stopLocationTracking();
                    }
                } else {
                    messageDiv.text(response.data.message || wp_taxi_driver_params.text_error_updating_status).removeClass('success').addClass('error');
                }
            },
            error: function() {
                messageDiv.text(wp_taxi_driver_params.text_error_updating_status + ' (AJAX Error)').removeClass('success').addClass('error');
            },
            complete: function() {
                 $('#update-availability-btn').prop('disabled', false);
                 setTimeout(() => messageDiv.fadeOut(), 3000);
            }
        });
    });

    // Accept Ride
    $('#pending-rides-list').on('click', '.accept-ride-btn', function() {
        const rideId = $(this).data('ride-id');
        const button = $(this);
        const messageDiv = button.closest('.ride-item').find('.ride-action-message');

        button.prop('disabled', true);
        messageDiv.text(wp_taxi_driver_params.text_accepting_ride).removeClass('error success').show();

        $.ajax({
            url: wp_taxi_driver_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wp_taxi_accept_ride',
                nonce: wp_taxi_driver_params.nonce_accept_ride,
                ride_id: rideId
            },
            success: function(response) {
                if (response.success) {
                    messageDiv.text(response.data.message || wp_taxi_driver_params.text_ride_accepted).removeClass('error').addClass('success');
                    // Refresh current ride and pending rides
                    fetchCurrentRide();
                    fetchPendingRides(); // This will likely show "on active ride"
                    $('#driver-availability').val('0'); // Set driver to unavailable
                    stopLocationTracking(); // Stop general tracking, specific ride tracking will happen
                    startLocationTracking(); // But restart to ensure map marker is updated
                } else {
                    messageDiv.text(response.data.message || wp_taxi_driver_params.text_error_accepting_ride).removeClass('success').addClass('error');
                    button.prop('disabled', false);
                }
            },
            error: function() {
                messageDiv.text(wp_taxi_driver_params.text_error_accepting_ride + ' (AJAX Error)').removeClass('success').addClass('error');
                button.prop('disabled', false);
            },
            complete: function() {
                setTimeout(() => messageDiv.fadeOut(), 4000);
            }
        });
    });

    // Update Ride Status (Start, Complete, Cancel)
    $('#current-ride-details').on('click', '.update-ride-status-btn', function() {
        const rideId = $(this).data('ride-id');
        const newStatus = $(this).data('new-status');
        const button = $(this);
        const messageDiv = button.closest('#current-ride-details').find('.ride-action-message');
        let loadingText = wp_taxi_driver_params.text_updating_status;

        if (newStatus === 'ongoing') loadingText = wp_taxi_driver_params.text_starting_ride;
        else if (newStatus === 'completed') loadingText = wp_taxi_driver_params.text_completing_ride;
        else if (newStatus === 'cancelled') loadingText = wp_taxi_driver_params.text_cancelling_ride;

        $('.update-ride-status-btn').prop('disabled', true); // Disable all action buttons
        messageDiv.text(loadingText).removeClass('error success').show();

        $.ajax({
            url: wp_taxi_driver_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wp_taxi_update_ride_status',
                nonce: wp_taxi_driver_params.nonce_update_ride_status,
                ride_id: rideId,
                new_status: newStatus
            },
            success: function(response) {
                if (response.success) {
                    let successText = response.data.message || wp_taxi_driver_params.text_status_updated;
                    if (newStatus === 'ongoing') successText = wp_taxi_driver_params.text_ride_started;
                    else if (newStatus === 'completed') successText = wp_taxi_driver_params.text_ride_completed;
                    else if (newStatus === 'cancelled') successText = wp_taxi_driver_params.text_ride_cancelled;

                    messageDiv.text(successText).removeClass('error').addClass('success');
                    fetchCurrentRide(); // Refresh details
                    if (newStatus === 'completed' || newStatus === 'cancelled') {
                        fetchPendingRides(); // Check for new rides
                        $('#driver-availability').val('1'); // Set driver back to available
                        startLocationTracking();
                    }
                } else {
                    messageDiv.text(response.data.message || wp_taxi_driver_params.text_error_ride_action).removeClass('success').addClass('error');
                    $('.update-ride-status-btn').prop('disabled', false);
                }
            },
            error: function() {
                 messageDiv.text(wp_taxi_driver_params.text_error_ride_action + ' (AJAX Error)').removeClass('success').addClass('error');
                 $('.update-ride-status-btn').prop('disabled', false);
            },
            complete: function() {
                 setTimeout(() => messageDiv.fadeOut(), 4000);
            }
        });
    });

    // Initial check for availability to start/stop tracking
    // This is also handled by initMapDriver, but good as a fallback.
    // if ($('#driver-availability').val() === '1') {
    //     startLocationTracking();
    // } else {
    //     stopLocationTracking();
    // }
});

// Ensure initMapDriver is globally available for the Google Maps API callback
window.initMapDriver = initMapDriver;

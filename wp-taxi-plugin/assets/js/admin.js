/* WP Taxi Plugin - Admin Script */

let adminMap, adminDriverMarkers = [];
const adminZurichCoords = { lat: 47.3769, lng: 8.5417 };
const adminDefaultZoom = 12;

// Callback for Google Maps API on Admin Live Map page
function initAdminMap() {
    if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
        console.error("Google Maps API not loaded for Admin Map.");
        jQuery('#admin-live-map-container').html('<p style="color:red; text-align:center; padding-top: 50px;">Google Maps konnte nicht geladen werden. API Key prüfen.</p>');
        return;
    }

    adminMap = new google.maps.Map(document.getElementById('admin-live-map-container'), {
        center: adminZurichCoords,
        zoom: adminDefaultZoom,
        mapTypeControl: true,
        streetViewControl: false,
    });

    fetchLiveDriversForMap();
    // Refresh driver locations periodically
    setInterval(fetchLiveDriversForMap, 20000); // Every 20 seconds
}


function fetchLiveDriversForMap() {
    if (typeof wp_taxi_admin_params === 'undefined' || !wp_taxi_admin_params.ajax_url) return;
    jQuery.ajax({
        url: wp_taxi_admin_params.ajax_url,
        type: 'POST',
        data: {
            action: 'wp_taxi_admin_get_live_drivers',
            nonce: wp_taxi_admin_params.nonce_get_live_drivers
        },
        success: function(response) {
            clearAdminDriverMarkers();
            if (response.success && response.data.length > 0) {
                response.data.forEach(driver => {
                    addAdminDriverMarker(driver);
                });
            } else {
                // console.log(response.data.message || wp_taxi_admin_params.text_no_drivers_on_map);
                // Optionally display a message on the map if no drivers
                 if (jQuery('#admin-live-map-container').length && adminDriverMarkers.length === 0) {
                    // jQuery('#admin-live-map-container').append('<p id="no-drivers-msg" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(255,255,255,0.8);padding:10px;border-radius:5px;">' + (response.data.message || wp_taxi_admin_params.text_no_drivers_on_map) + '</p>');
                 }
            }
        },
        error: function(xhr, status, error) {
            console.error("Error fetching live drivers for admin map: ", status, error);
        }
    });
}

function addAdminDriverMarker(driver) {
    if (typeof google === 'undefined' || typeof google.maps === 'undefined' || !adminMap) return;

    const markerColor = driver.is_available_raw === '1' ? '#2ECC71' : '#E74C3C'; // Green for available, Red for unavailable

    const marker = new google.maps.Marker({
        position: { lat: driver.latitude, lng: driver.longitude },
        map: adminMap,
        title: `${driver.name} (${driver.available}) - ${driver.vehicle_model}`,
        icon: {
            path: google.maps.SymbolPath.CIRCLE, // Changed from CAR to CIRCLE for better visibility of color
            scale: 9,
            fillColor: markerColor,
            fillOpacity: 0.9,
            strokeWeight: 1,
            strokeColor: "#FFFFFF"
        }
    });

    const infoWindowContent = `
        <div>
            <strong>${driver.name}</strong><br>
            Fahrzeug: ${driver.vehicle_model || '-'}<br>
            Status: ${driver.available}<br>
            Letzte Aktualisierung: ${driver.last_update}<br>
            Email: ${driver.email}
        </div>`;

    const infowindow = new google.maps.InfoWindow({
        content: infoWindowContent
    });

    marker.addListener('click', () => {
        infowindow.open(adminMap, marker);
    });

    adminDriverMarkers.push(marker);
}

function clearAdminDriverMarkers() {
    adminDriverMarkers.forEach(marker => marker.setMap(null));
    adminDriverMarkers = [];
    jQuery('#no-drivers-msg').remove();
}


jQuery(document).ready(function($) {
    // Driver Approval AJAX
    $('.wp-list-table').on('click', '.approve-driver-btn, .revoke-driver-btn', function(e) {
        e.preventDefault();
        const button = $(this);
        const driverId = button.data('driver-id');
        const action = button.hasClass('approve-driver-btn') ? 'approve' : 'revoke';
        const originalText = button.text();
        const statusCell = button.closest('tr').find('.column-driver_approved span.driver-status-text');

        button.text(action === 'approve' ? wp_taxi_admin_params.text_approving : wp_taxi_admin_params.text_revoking);
        button.prop('disabled', true);

        $.ajax({
            url: wp_taxi_admin_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wp_taxi_admin_approve_driver',
                nonce: wp_taxi_admin_params.nonce_approve_driver,
                driver_id: driverId,
                approve_action: action
            },
            success: function(response) {
                if (response.success) {
                    statusCell.text(response.data.new_status);
                    if (response.data.action_taken === 'approved') {
                        statusCell.removeClass('driver-status-pending driver-status-not-approved').addClass('driver-status-approved');
                        button.removeClass('approve-driver-btn').addClass('revoke-driver-btn').text(wp_taxi_admin_params.text_revoke || 'Genehmigung entziehen');
                    } else { // revoked
                        statusCell.removeClass('driver-status-approved').addClass('driver-status-not-approved');
                         button.removeClass('revoke-driver-btn').addClass('approve-driver-btn').text(wp_taxi_admin_params.text_approve || 'Genehmigen');
                    }
                } else {
                    alert(response.data.message || wp_taxi_admin_params.text_error);
                    button.text(originalText);
                }
            },
            error: function() {
                alert(wp_taxi_admin_params.text_error + ' (AJAX Error)');
                button.text(originalText);
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });

    // Make initAdminMap globally available if it's on the live map page
    if ($('#admin-live-map-container').length > 0) {
        window.initAdminMap = initAdminMap;
    }
});

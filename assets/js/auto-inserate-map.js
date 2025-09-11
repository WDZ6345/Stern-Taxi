/**
 * Google Maps Integration für Auto Inserate Plugin.
 */

/**
 * Initialisiert die Google Map.
 * Diese Funktion wird als Callback von der Google Maps API aufgerufen.
 * Die Daten (lat, lng, zoom, markerTitle) werden via wp_localize_script
 * als Objekt autoInserateMapData übergeben.
 */
function initAutoInserateMap() {
    const mapContainer = document.getElementById('fahrzeug-karte');

    // Prüfen, ob der Kartencontainer auf der Seite existiert und ob die Daten vorhanden sind
    if ( !mapContainer || typeof autoInserateMapData === 'undefined' ) {
        // console.error('Auto Inserate Map: Container oder Daten nicht gefunden.');
        return;
    }

    const fahrzeugLatLng = {
        lat: parseFloat(autoInserateMapData.lat),
        lng: parseFloat(autoInserateMapData.lng)
    };

    const map = new google.maps.Map(mapContainer, {
        zoom: parseInt(autoInserateMapData.zoom) || 15,
        center: fahrzeugLatLng,
        mapTypeId: 'roadmap' // Standard Kartentyp
    });

    new google.maps.Marker({
        position: fahrzeugLatLng,
        map: map,
        title: autoInserateMapData.markerTitle || 'Fahrzeugstandort'
    });
}

// Stellt sicher, dass initAutoInserateMap global verfügbar ist, falls Google Maps API es so erwartet.
// Da der Callback direkt in der API-URL angegeben ist, sollte dies funktionieren.
// window.initAutoInserateMap = initAutoInserateMap;
// Alternativ könnte man das Skript so strukturieren, dass es auf das `defer` Attribut der Google API wartet
// und dann die Karte initialisiert, aber der callback-Weg ist üblich.

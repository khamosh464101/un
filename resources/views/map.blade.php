<!DOCTYPE html>
<html>
<head>
    <title>House Map</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        #map { height: 800px; width: 100%; }
        .house-label {

            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
            color: white;
        }
    </style>
</head>
<body>
<div id="map"></div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
    // Initialize map
    const map = L.map('map').setView([-1.2922, 36.8221], 19);

L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
  attribution: 'Tiles &copy; Esri &mdash; Source: Esri, Maxar, Earthstar Geographics',
  maxZoom: 22
}).addTo(map);

    // Load GeoJSON from Laravel public folder
    fetch('{{ asset("data/houses.json") }}')
        .then(res => res.json())
        .then(data => {

            data.features.forEach(feature => {
                const coords = feature.geometry.coordinates[0].map(c => [c[1], c[0]]);

                // Draw polygon
                const polygon = L.polygon(coords, {
                    color: 'red',
                    weight: 2,
                    fillOpacity: 0.3
                }).addTo(map);

                // Compute centroid
                const latSum = coords.reduce((sum, c) => sum + c[0], 0);
                const lngSum = coords.reduce((sum, c) => sum + c[1], 0);
                const center = [latSum / coords.length, lngSum / coords.length];

                // Add HTML label
                L.marker(center, {
                    icon: L.divIcon({
                        className: 'house-label',
                        html: feature.properties.house_id,
                        iconSize: [100, 30]
                    })
                }).addTo(map);
            });
        });
</script>
</body>
</html>

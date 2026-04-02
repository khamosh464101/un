<!-- resources/views/maps/house-map.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>House Parcel Map</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        #map { height: 768px; width: 1024px; }
        
        /* Custom label styling - exactly like your reference image */
        .house-label {
            background-color: #FF0000;
            color: white;
            font-family: Arial, sans-serif;
            font-size: 14px;
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 4px;
            border: none;
            white-space: nowrap;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            text-align: center;
            min-width: 60px;
            pointer-events: none; /* So labels don't block map interactions */
        }
        
        /* Selected house label */
        .house-label.selected {
            background-color: #FF0000;
            border: 2px solid white;
        }
        
        /* Other houses label */
        .house-label.other {
            background-color: #333333;
            opacity: 0.9;
        }
        
        /* Polygon styles */
        .selected-polygon {
            fill-opacity: 0.3;
            stroke-opacity: 1;
            stroke-width: 3;
        }
        
        .other-polygon {
            fill-opacity: 0.5;
            fill-color: #FFFFFF;
            stroke-opacity: 0.5;
            stroke-width: 1;
            stroke-color: #CCCCCC;
        }
    </style>
</head>
<body>
    <div id="map"></div>

    <script>
        // House data passed from Laravel
        const houses = @json($houses);
        const selectedHouseCode = @json($selectedHouseCode);
        
        // Initialize map with OpenStreetMap satellite layer
        const map = L.map('map').setView([{{ $center['lat'] }}, {{ $center['lng'] }}], 19);
        
        // Add OpenStreetMap satellite layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);
        
        // Process each house
        houses.forEach(house => {
            // Convert GeoJSON to Leaflet layer
            const layer = L.geoJSON(house.geometry, {
                style: function(feature) {
                    const isSelected = (house.house_code === selectedHouseCode);
                    return {
                        color: isSelected ? (house.outline_color || '#FF0000') : '#CCCCCC',
                        weight: isSelected ? (house.outline_thickness || 3) : 1,
                        opacity: isSelected ? 1 : 0.5,
                        fillColor: isSelected ? (house.outline_color || '#FF0000') : '#FFFFFF',
                        fillOpacity: isSelected ? 0.3 : 0.5
                    };
                }
            }).addTo(map);
            
            // Calculate polygon center for label
            const bounds = layer.getBounds();
            const center = bounds.getCenter();
            
            // Create custom label with HTML/CSS
            const labelClass = (house.house_code === selectedHouseCode) ? 'selected' : 'other';
            
            // Create a custom marker with divIcon for the label
            const labelIcon = L.divIcon({
                className: 'house-label ' + labelClass,
                html: house.house_code,
                iconSize: null, // Auto-size based on content
                iconAnchor: [0, 0] // Will be adjusted in CSS
            });
            
            // Add label as a marker at center point
            L.marker(center, {
                icon: labelIcon,
                interactive: false // Label doesn't block clicks
            }).addTo(map);
        });
        
        // Optional: Add scale bar
        L.control.scale().addTo(map);
    </script>
</body>
</html>
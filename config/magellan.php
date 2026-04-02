<?php

return [
    /*
     * The SRIDs that are considered geodetic.
     * Add 4326 (WGS84) to this list.
     */
    'geodetic_srids' => [
        4326, // WGS84 - standard for GPS and GeoJSON
        4978, // WGS84 geocentric
        4979, // WGS84 geodetic
    ],

    /*
     * The default SRID to use when no SRID is specified.
     */
    'default_srid' => 4326,

    /*
     * The database connection to use for spatial operations.
     */
    'database_connection' => env('DB_CONNECTION', 'mysql'),
];
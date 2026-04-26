<?php
return [

    // =========================
// TAX SETTINGS
// =========================
    'tax_rate' => 0.10, // 10%

    // =========================
// DELIVERY TIERS (KM)
// =========================
    'delivery_tiers' => [
        [
            'max_distance' => 3,
            'fee' => 2.00,
        ],
        [
            'max_distance' => 7,
            'fee' => 4.00,
        ],
        [
            'max_distance' => 15,
            'fee' => 6.00,
        ],
        [
            'max_distance' => null, // no limit
            'fee' => 10.00,
        ],
    ],

];
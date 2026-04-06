<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Konfigurasi untuk hierarki rank.
 * Bobot (weight) yang lebih tinggi berarti rank yang lebih tinggi.
 * Beri jarak antar bobot (10, 20, 30) agar mudah menyisipkan rank baru di masa depan.
 */

$config['rank_hierarchies'] = [

    'survival' => [
        'scout'  => 10,
        'forger' => 20,
        'seeker' => 30,
        'ranger' => 40,
        'reaver' => 50,
        'titan'  => 60,
        'warden' => 70,
        'oblivion' => 80,
    ],

    'skyblock' => [
        'squire' => 10,
        'prince'   => 20,
        'king'    => 30,
        'overlord'   => 40,
        'emperor'   => 50,
        'eternal'   => 60,
    ],
    
    
    'acidisland' => [
        'coral' => 10,
        'shell'   => 20,
        'atoll'    => 30,
        'diver'   => 40,
    ],

    'oneblock' => [
        'initiate' => 10,
        'elite' => 20,
        'titan' => 30,
        'legend' => 40,
        'immortal' => 50,
    ]

];

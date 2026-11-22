<?php

return [

    // Default password hashing driver
    'driver' => 'bcrypt',

    // Bcrypt hashing options
    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => true,
    ],

    // Argon hashing options
    'argon' => [
        'memory' => 65536,
        'threads' => 1,
        'time' => 4,
        'verify' => true,
    ],

];

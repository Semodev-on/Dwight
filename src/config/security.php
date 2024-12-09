<?php
// Security configuration
return [
    'password' => [
        'min_length' => 12,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_special' => true,
    ],
    'session' => [
        'lifetime' => 3600,
        'secure' => true,
        'httponly' => true,
        'samesite' => '',
    ],
    'rate_limiting' => [
        'login' => [
            'attempts' => 5,
            'timeframe' => 200, // 200 seconds
        ],
        'password_reset' => [
            'attempts' => 3,
            'timeframe' => 100, // 100 seconds
        ],
    ],
]; 
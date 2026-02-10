<?php

$parseCsv = static function (string $value): array {
    return array_values(array_filter(array_map('trim', explode(',', $value))));
};

$defaultOrigins = [
    'http://localhost:5173',
    'http://localhost:5174',
    'http://localhost:3000',
    'http://localhost.sekouAI.com',
];

$envOrigins = $parseCsv((string) env('CORS_ALLOWED_ORIGINS', ''));
$envOriginPatterns = $parseCsv((string) env('CORS_ALLOWED_ORIGINS_PATTERNS', ''));
$frontendUrl = trim((string) env('FRONTEND_URL', ''));
$frontendOrigins = $frontendUrl !== '' ? [$frontendUrl] : [];

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_unique(array_merge($defaultOrigins, $frontendOrigins, $envOrigins))),

    'allowed_origins_patterns' => $envOriginPatterns,

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];

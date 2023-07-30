<?php

return [
    'otlp' => [
        'endpoint' => env('OTLP_ZIPKIN_ENDPOINT', 'http://localhost:4317'),
    ],
];

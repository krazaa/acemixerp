<?php

return [
    'enabled' => (bool) env('FIREBASE_ENABLED', false),
    'credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/private/firebase-service-account.json')),
    'vapid_key' => env('FIREBASE_VAPID_KEY'),
    'queue_connection' => env('FIREBASE_QUEUE_CONNECTION', 'database'),
    'queue' => 'push',
    'web' => [
        'apiKey' => env('FIREBASE_API_KEY'),
        'authDomain' => env('FIREBASE_AUTH_DOMAIN'),
        'projectId' => env('FIREBASE_PROJECT_ID'),
        'storageBucket' => env('FIREBASE_STORAGE_BUCKET'),
        'messagingSenderId' => env('FIREBASE_MESSAGING_SENDER_ID'),
        'appId' => env('FIREBASE_APP_ID'),
    ],
];

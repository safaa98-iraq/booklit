<?php

return [
    /*
    | Disk used for booklet/question images. Use "s3" in production (any
    | S3-compatible storage: AWS, Cloudflare R2, MinIO, DigitalOcean Spaces…).
    */
    'media_disk' => env('MEDIA_DISK', 'public'),

    // Serve media through short-lived signed URLs (recommended for private buckets).
    'media_signed_urls' => (bool) env('MEDIA_SIGNED_URLS', false),
    'media_url_ttl' => (int) env('MEDIA_URL_TTL', 60),

    // Quiz size used for subject question banks (booklets define their own).
    'default_quiz_size' => (int) env('DEFAULT_QUIZ_SIZE', 10),

    'defaults' => [
        'telegram_url' => env('ADMIN_TELEGRAM_URL', 'https://t.me/'),
    ],

    // Initial admin created by the database seeder.
    'admin' => [
        'name' => env('ADMIN_NAME', 'المدير'),
        'email' => env('ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],
];

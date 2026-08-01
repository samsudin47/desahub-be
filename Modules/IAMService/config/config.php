<?php

return [
    'name' => 'IAMService',

    'superadmin' => [
        'username' => env('IAM_SUPERADMIN_USERNAME', 'superadmin'),
        'email' => env('IAM_SUPERADMIN_EMAIL', 'superadmin@desahub.local'),
        'password' => env('IAM_SUPERADMIN_PASSWORD', 'password123'),
    ],

    'auth' => [
        'public_routes' => [
            'api/v1/iam-services/health',
            'api/v1/iam-services/roles',
            'api/v1/iam-services/auth/login',
            'api/v1/iam-services/auth/register',
            'api/v1/iam-services/auth/forgot-password',
            'api/v1/iam-services/auth/reset-password',
            'api/v1/iam/auth/login',
            'api/v1/iam/auth/register',
            'api/v1/iam/auth/forgot-password',
            'api/v1/iam/auth/reset-password',
            'api/v1/marketplace-umkm-service/midtrans/notification',
        ],
        'frontend_reset_password_url' => env('FRONTEND_RESET_PASSWORD_URL', env('FRONTEND_URL', 'http://localhost:3000').'/reset-password'),
        'inactivity_warning_minutes' => 10,
        'inactivity_expiry_minutes' => 30,
    ],
];

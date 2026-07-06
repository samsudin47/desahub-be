<?php

return [
    'name' => 'IAMService',

    'auth' => [
        'public_routes' => [
            'api/v1/iam-services/health',
            'api/v1/iam-services/auth/login',
            'api/v1/iam-services/auth/register',
            'api/v1/iam-services/auth/reset-password',
            'api/v1/iam/auth/login',
            'api/v1/iam/auth/register',
            'api/v1/iam/auth/reset-password',
        ],
        'inactivity_warning_minutes' => 10,
        'inactivity_expiry_minutes' => 30,
    ],
];

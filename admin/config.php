<?php
if (!defined('IN_ENGINE')) exit(http_response_code(404));

return [
    // Admin credentials
    'admin' => [
        'username' => 'admin',
        'password' => '$2y$10$H1JS/9ts3BvGI0JWG9vdyuf/GFBvykgxpAxOX43ht8Qfj3z0JXH96', // bcrypt hash of 'admin'
    ],

    // Session configuration
    'session' => [
        'name'     => 'HZ_ADMIN_SESSID',
        'lifetime' => 3600, // 1 hour
    ],

    // CSRF configuration
    'csrf' => [
        'token_name' => 'csrf_token',
        'token_ttl'  => 3600, // 1 hour
    ],

    // IP whitelist (empty array = allow all)
    // Example: ['127.0.0.1', '::1']
    'ip_whitelist' => [],

    // Rate limiting
    'rate_limit' => [
        'max_attempts' => 5,
        'window'       => 300, // 5 minutes in seconds
    ],
];

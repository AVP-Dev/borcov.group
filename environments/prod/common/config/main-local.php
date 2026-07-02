<?php

/**
 * This file is a TEMPLATE only.
 * The actual configuration is written by docker/entrypoint.sh at container startup.
 * entrypoint.sh reads DB_HOST, DB_NAME, DB_USER, DB_PASS from environment variables
 * and overwrites this file with proper values before starting nginx/php-fpm.
 */

return [
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'pgsql:host=postgres;dbname=keyword_platform',
            'username' => 'yii2',
            'password' => 'secret',
            'charset' => 'utf8',
        ],
    ],
];

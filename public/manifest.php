<?php

require __DIR__ . '/../src/bootstrap.php';

$app = app_config();
$links = $app['links'];

header('Content-Type: application/manifest+json');

echo json_encode([
    'id'               => '/',
    'name'             => $app['name'],
    'short_name'       => $app['short_name'],
    'description'      => $app['description'],
    'start_url'        => $links['app'],
    'scope'            => '/',
    'display'          => 'standalone',
    'background_color' => $app['background_color'],
    'theme_color'      => $app['theme_color'],
    'icons'            => [
        [
            'src'     => '/assets/icons/icon-192.png',
            'sizes'   => '192x192',
            'type'    => 'image/png',
            'purpose' => 'any maskable',
        ],
        [
            'src'     => '/assets/icons/icon-512.png',
            'sizes'   => '512x512',
            'type'    => 'image/png',
            'purpose' => 'any maskable',
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

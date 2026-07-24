<?php

// Product identity shared by HTML, navigation, and the PWA manifest.
return [
    'name'             => 'Leanplate',
    'short_name'       => 'Leanplate',
    'description'      => 'A small subscription web app.',
    'tagline'          => 'Ship a SaaS in a handful of files',
    'theme_color'      => '#ff4d00',
    'background_color' => '#f4f1ea',

    'links' => [
        'home'     => '/',
        'app'      => '/app',
        'account'  => '/app/account',
        'login'    => '/auth/login',
        'logout'   => '/auth/logout',
        'feedback' => '/feedback',
    ],
];

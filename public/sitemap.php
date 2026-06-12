<?php
require __DIR__ . '/../src/bootstrap.php';

$base  = rtrim((string)(config()['base_url'] ?? ''), '/');
$paths = ['/', '/auth/login'];   // add public, indexable pages here

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($paths as $p) {
    echo '  <url><loc>' . htmlspecialchars($base . $p) . '</loc></url>' . "\n";
}
echo '</urlset>' . "\n";

<?php
declare(strict_types=1);
// Point d'entrée unique sur Vercel : chaque URL de l'application est aiguillée vers son script dans app/.

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
// Repli si le runtime ne transmet pas l'URL d'origine : vercel.json la passe aussi en paramètre.
if (($uri === '/api/index.php' || $uri === '/api') && isset($_GET['__route']) && is_string($_GET['__route'])) {
    $uri = '/' . $_GET['__route'];
}
unset($_GET['__route'], $_REQUEST['__route']);
$path = '/' . trim(rawurldecode($uri), '/');

const ROUTES = [
    '/'           => 'index',
    '/index'      => 'index',
    '/index.php'  => 'index',
    '/login'      => 'login',
    '/login.php'  => 'login',
    '/logout'     => 'logout',
    '/logout.php' => 'logout',
    '/api.php'    => 'api',
    '/upload.php' => 'upload',
    '/file.php'   => 'file',
];

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
header('Cache-Control: private, no-store');

if (!isset(ROUTES[$path])) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Introuvable.';
    exit;
}

require __DIR__ . '/../app/' . ROUTES[$path] . '.php';

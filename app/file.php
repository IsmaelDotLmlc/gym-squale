<?php
declare(strict_types=1);
// Distribue les photos de profil et les pièces jointes (stockées dans MySQL) aux membres connectés uniquement.
require __DIR__ . '/config.php';

function nope(int $code): void
{
    http_response_code($code);
    header('Content-Type: text/plain; charset=utf-8');
    echo $code === 401 ? 'Connexion requise.' : 'Introuvable.';
    exit;
}

if (empty($_SESSION['uid'])) {
    nope(401);
}
$uid = (int) $_SESSION['uid'];
session_write_close();
$t = $_GET['t'] ?? '';
$id = is_numeric($_GET['id'] ?? null) ? (int) $_GET['id'] : 0;

try {
    $pdo = db();
    if ($t === 'a') {
        $st = $pdo->prepare('SELECT avatar_ver,avatar_data FROM users WHERE id=?');
        $st->execute([$id]);
        $r = $st->fetch();
        if (!$r || !(int) $r['avatar_ver'] || $r['avatar_data'] === null) {
            nope(404);
        }
        $bytes = $r['avatar_data'];
        $mime = 'image/jpeg';
        $name = 'avatar.jpg';
        $cache = 'private, max-age=31536000, immutable';   // l'URL change à chaque nouvelle photo (?v=)
    } elseif ($t === 'p') {
        $st = $pdo->prepare('SELECT original_name,mime,user_id,post_id,data FROM post_files WHERE id=?');
        $st->execute([$id]);
        $r = $st->fetch();
        // Visible par tous les membres une fois publiée ; avant, seulement par son auteur.
        if (!$r || ($r['post_id'] === null && (int) $r['user_id'] !== $uid) || $r['data'] === null) {
            nope(404);
        }
        $bytes = $r['data'];
        $mime = $r['mime'];
        $name = $r['original_name'];
        $cache = 'private, max-age=3600';
    } else {
        nope(404);
    }
} catch (PDOException $e) {
    error_log('Gym Squale file : ' . $e->getMessage());
    nope(404);
}
if (is_resource($bytes)) {
    $bytes = (string) stream_get_contents($bytes);
}
if (!isset(UPLOAD_MIMES[$mime])) {
    nope(404);
}

$size = strlen($bytes);
$start = 0;
$end = $size - 1;
$code = 200;
// Les vidéos ont besoin des requêtes « Range » (Safari refuse de les lire sinon).
if (preg_match('/^bytes=(\d*)-(\d*)$/', $_SERVER['HTTP_RANGE'] ?? '', $m) && ($m[1] !== '' || $m[2] !== '')) {
    if ($m[1] === '') {
        $start = max(0, $size - (int) $m[2]);
    } else {
        $start = (int) $m[1];
        if ($m[2] !== '') {
            $end = min($end, (int) $m[2]);
        }
    }
    if ($start > $end || $start >= $size) {
        header('Content-Range: bytes */' . $size);
        nope(416);
    }
    $code = 206;
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
}

http_response_code($code);
header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Accept-Ranges: bytes');
header('Cache-Control: ' . $cache);
header('Vary: Cookie');
header('Content-Length: ' . ($end - $start + 1));
$ascii = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);
header('Content-Disposition: inline; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($name));

echo $start === 0 && $end === $size - 1 ? $bytes : substr($bytes, $start, $end - $start + 1);

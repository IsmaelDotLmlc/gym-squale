<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out(array $d, int $code = 200)
{
    http_response_code($code);
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}
function fail(string $m, int $code = 400)
{
    out(['ok' => false, 'error' => $m], $code);
}

if (empty($_SESSION['uid'])) {
    fail('Session expirée. Reconnecte-toi.', 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Méthode non autorisée.', 405);
}
if (!check_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    fail('Jeton de sécurité invalide. Recharge la page.', 403);
}
$uid = (int) $_SESSION['uid'];
session_write_close();

// Dépassement de post_max_size : PHP vide $_POST et $_FILES
if (empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    fail('Fichier trop volumineux (4 Mo maximum).', 413);
}
$f = $_FILES['file'] ?? null;
if (!is_array($f) || is_array($f['error'] ?? null)) {
    fail('Aucun fichier reçu.');
}
if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
    fail('Fichier trop volumineux (4 Mo maximum).', 413);
}
if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
    fail('L’envoi a échoué, réessaie.');
}
$size = (int) $f['size'];
if ($size < 1 || $size > UPLOAD_MAX) {
    fail('Fichier vide ou trop volumineux (4 Mo maximum).');
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) ?: '';
if (!isset(UPLOAD_MIMES[$mime])) {
    fail('Format non accepté. Autorisés : images (JPG, PNG, WebP, GIF), PDF et vidéo MP4.');
}
$isImage = strpos($mime, 'image/') === 0;
$dims = $isImage ? @getimagesize($f['tmp_name']) : null;
if ($isImage && !$dims) {
    fail('Image illisible ou corrompue.');
}

$kind = $_POST['kind'] ?? '';

try {
    $pdo = db();

    // ---------- Photo de profil : carré 256 px en JPEG, stockée dans la base ----------
    if ($kind === 'avatar') {
        if (!$isImage) {
            fail('La photo de profil doit être une image.');
        }
        if ($dims[0] * $dims[1] > 40000000) {
            fail('Image trop grande (dimensions).');
        }
        if (function_exists('imagecreatefromstring')) {
            // Avec GD : recadrage et ré-encodage côté serveur (même comportement qu'en local).
            $src = @imagecreatefromstring((string) file_get_contents($f['tmp_name']));
            if (!$src) {
                fail('Format d’image non pris en charge par ce PHP.');
            }
            if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
                $ex = @exif_read_data($f['tmp_name']);
                $deg = [3 => 180, 6 => -90, 8 => 90][$ex['Orientation'] ?? 1] ?? 0;
                if ($deg) {
                    $rot = imagerotate($src, $deg, 0);
                    if ($rot) {
                        $src = $rot;
                    }
                }
            }
            $w = imagesx($src);
            $h = imagesy($src);
            $side = min($w, $h);
            $dst = imagecreatetruecolor(256, 256);
            imagefill($dst, 0, 0, imagecolorallocate($dst, 15, 18, 16));
            imagecopyresampled($dst, $src, 0, 0, intdiv($w - $side, 2), intdiv($h - $side, 2), 256, 256, $side, $side);
            ob_start();
            $okJpg = imagejpeg($dst, null, 86);
            $bytes = (string) ob_get_clean();
            if (!$okJpg || $bytes === '') {
                fail('Impossible d’enregistrer la photo.', 500);
            }
        } else {
            // Sans GD (cas de Vercel) : le navigateur a déjà recadré la photo en JPEG 256 px.
            // On n'accepte que ce format précis, vérifié sur le contenu.
            if ($mime !== 'image/jpeg' || $dims[0] > 512 || $dims[1] > 512 || $size > 300 * 1024) {
                fail('Cette photo n’a pas pu être recadrée : réessaie avec une autre image ou un autre navigateur.');
            }
            $bytes = (string) file_get_contents($f['tmp_name']);
        }
        $up = $pdo->prepare('UPDATE users SET avatar_data=?, avatar_ver=avatar_ver+1 WHERE id=?');
        $up->bindValue(1, $bytes, PDO::PARAM_LOB);
        $up->bindValue(2, $uid, PDO::PARAM_INT);
        $up->execute();
        $st = $pdo->prepare('SELECT avatar_ver FROM users WHERE id=?');
        $st->execute([$uid]);
        out(['ok' => true, 'ver' => (int) $st->fetchColumn()]);
    }

    // ---------- Pièce jointe de forum : stockée en attente jusqu'à la publication ----------
    if ($kind !== 'post') {
        fail('Type d’envoi inconnu.');
    }

    // Ménage : fichiers envoyés puis jamais publiés depuis plus d'un jour
    $pdo->prepare('DELETE FROM post_files WHERE user_id=? AND post_id IS NULL AND created_at < (NOW() - INTERVAL 1 DAY)')->execute([$uid]);
    $st = $pdo->prepare('SELECT COUNT(*) FROM post_files WHERE user_id=? AND post_id IS NULL');
    $st->execute([$uid]);
    if ((int) $st->fetchColumn() >= 8) {
        fail('Trop de fichiers en attente : publie ou retire ceux déjà envoyés.', 429);
    }

    $stored = bin2hex(random_bytes(16));
    $bytes = (string) file_get_contents($f['tmp_name']);
    if ($bytes === '') {
        fail('Impossible de lire le fichier.', 500);
    }
    $orig = preg_replace('/[\x00-\x1F\x7F\/\\\\]+/u', '', basename((string) $f['name'])) ?? '';
    $orig = mb_substr(trim($orig), 0, 100);
    if ($orig === '') {
        $orig = 'fichier.' . UPLOAD_MIMES[$mime];
    }
    $ins = $pdo->prepare('INSERT INTO post_files (user_id,stored_name,original_name,mime,size,data) VALUES (?,?,?,?,?,?)');
    $ins->bindValue(1, $uid, PDO::PARAM_INT);
    $ins->bindValue(2, $stored);
    $ins->bindValue(3, $orig);
    $ins->bindValue(4, $mime);
    $ins->bindValue(5, $size, PDO::PARAM_INT);
    $ins->bindValue(6, $bytes, PDO::PARAM_LOB);
    $ins->execute();
    out(['ok' => true, 'file' => ['id' => (int) $pdo->lastInsertId(), 'name' => $orig, 'mime' => $mime, 'size' => $size]]);
} catch (PDOException $e) {
    error_log('Gym Squale upload : ' . $e->getMessage());
    fail('Base de données : vérifie que sql/install.sql est importé (et que max_allowed_packet ≥ 8 Mo).', 500);
}

<?php
declare(strict_types=1);

// --- Connexion MySQL : tout vient des variables d'environnement Vercel (GYM_DB_*) ---
define('DB_HOST', getenv('GYM_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('GYM_DB_PORT') ?: '3306');
define('DB_NAME', getenv('GYM_DB_NAME') ?: 'gym_squale');
define('DB_USER', getenv('GYM_DB_USER') ?: 'root');
define('DB_PASS', getenv('GYM_DB_PASS') !== false ? getenv('GYM_DB_PASS') : '');
define('DB_SSL', in_array(strtolower((string) getenv('GYM_DB_SSL')), ['1', 'true', 'on', 'yes'], true));

// --- Fichiers envoyés : stockés dans MySQL (le disque de Vercel est en lecture seule) ---
// Vercel refuse les requêtes de plus de 4,5 Mo : on limite chaque fichier à 4 Mo.
const UPLOAD_MAX = 4 * 1024 * 1024;
const UPLOAD_PER_POST = 4;
// Types acceptés, contrôlés sur le contenu réel du fichier (pas sur son extension).
const UPLOAD_MIMES = [
    'image/jpeg'      => 'jpg',
    'image/png'       => 'png',
    'image/webp'      => 'webp',
    'image/gif'       => 'gif',
    'application/pdf' => 'pdf',
    'video/mp4'       => 'mp4',
];
const SESSION_TTL = 14 * 86400;   // une session reste valable 14 jours

// --- Repli si l'extension mbstring est désactivée ---
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $s): int { return (int) preg_match_all('/./us', $s); }
}
if (!function_exists('mb_substr')) {
    function mb_substr(string $s, int $start, ?int $len = null): string
    {
        preg_match_all('/./us', $s, $m);
        return implode('', array_slice($m[0], $start, $len));
    }
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $opt = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 8,
        ];
        if (DB_SSL) {
            $ca = (string) getenv('GYM_DB_SSL_CA');
            if ($ca !== '' && $ca[0] !== '/' && is_file(__DIR__ . '/' . $ca)) {
                $ca = __DIR__ . '/' . $ca;
            }
            if ($ca === '' || !is_file($ca)) {
                foreach (['/etc/pki/tls/certs/ca-bundle.crt', '/etc/ssl/certs/ca-certificates.crt'] as $c) {
                    if (is_file($c)) {
                        $ca = $c;
                        break;
                    }
                }
            }
            if ($ca !== '' && is_file($ca)) {
                $opt[PDO::MYSQL_ATTR_SSL_CA] = $ca;
            }
            $opt[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = getenv('GYM_DB_SSL_VERIFY') !== '0';
        }
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            $opt
        );
    }
    return $pdo;
}

// --- Sessions dans MySQL : sur Vercel, chaque requête peut tomber sur une instance différente ---
final class DbSessionHandler implements SessionHandlerInterface
{
    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    // Une base injoignable ne doit jamais lever d'exception ici : on renvoie false,
    // session_start() échoue alors proprement et l'application affiche un message clair.
    public function read(string $id): string|false
    {
        try {
            $st = db()->prepare('SELECT data FROM sessions WHERE id=? AND updated_at > ?');
            $st->execute([$id, time() - SESSION_TTL]);
            $d = $st->fetchColumn();
            return $d === false ? '' : (string) $d;
        } catch (Throwable $e) {
            error_log('Gym Squale session (lecture) : ' . $e->getMessage());
            return false;
        }
    }

    public function write(string $id, string $data): bool
    {
        try {
            db()->prepare('REPLACE INTO sessions (id,data,updated_at) VALUES (?,?,?)')->execute([$id, $data, time()]);
            return true;
        } catch (Throwable $e) {
            error_log('Gym Squale session (écriture) : ' . $e->getMessage());
            return false;
        }
    }

    public function destroy(string $id): bool
    {
        try {
            db()->prepare('DELETE FROM sessions WHERE id=?')->execute([$id]);
            return true;
        } catch (Throwable $e) {
            error_log('Gym Squale session (suppression) : ' . $e->getMessage());
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false
    {
        try {
            $st = db()->prepare('DELETE FROM sessions WHERE updated_at < ?');
            $st->execute([time() - SESSION_TTL]);
            return $st->rowCount();
        } catch (Throwable $e) {
            return false;
        }
    }
}

// Vercel termine le HTTPS en amont : le protocole d'origine est dans X-Forwarded-Proto.
$https = ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' || !empty($_SERVER['HTTPS']);
ini_set('session.use_strict_mode', '1');
ini_set('session.gc_maxlifetime', (string) SESSION_TTL);
session_set_cookie_params([
    'lifetime' => SESSION_TTL,
    'path'     => '/',
    'secure'   => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_set_save_handler(new DbSessionHandler(), true);
$sessionOk = false;
try {
    $sessionOk = @session_start();
} catch (Throwable $e) {
    error_log('Gym Squale session : ' . $e->getMessage());
}
if (!$sessionOk) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Base de données inaccessible. Vérifie les variables GYM_DB_* sur Vercel et que sql/install.sql est importé.';
    exit;
}

// --- Protection CSRF : un jeton par session, vérifié sur toute requête qui modifie des données ---
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function check_csrf($token): bool
{
    return is_string($token) && hash_equals(csrf_token(), $token);
}

// --- Échappement HTML pour tout ce qui est affiché dans une page PHP ---
function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

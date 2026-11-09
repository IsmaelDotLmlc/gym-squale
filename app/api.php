<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const GOALS = ['Hypertrophie', 'Force', 'Endurance', 'Perte de poids'];
const POST_CATS = ['Progression', 'Question', 'Conseil', 'Motivation'];

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

function valid_date($d): bool
{
    if (!is_string($d) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
        return false;
    }
    [$y, $m, $day] = array_map('intval', explode('-', $d));
    return checkdate($m, $day, $y);
}

function exercise_ids(PDO $pdo): array
{
    static $ids = null;
    if ($ids === null) {
        $ids = array_flip($pdo->query('SELECT id FROM exercises')->fetchAll(PDO::FETCH_COLUMN));
    }
    return $ids;
}

function known_exercise(PDO $pdo, $id): bool
{
    return is_string($id) && isset(exercise_ids($pdo)[$id]);
}

// ---------- Contrôles communs : session, méthode, jeton CSRF ----------
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
$in = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($in)) {
    $in = [];
}
$action = $_GET['action'] ?? '';

try {
    $pdo = db();

    switch ($action) {

        // ---------- Chargement initial : référentiel + données de l'utilisateur ----------
        case 'bootstrap':
            $muscles = [];
            foreach ($pdo->query('SELECT id,name,abbr,description FROM muscles ORDER BY position') as $m) {
                $muscles[] = [$m['id'], $m['name'], $m['abbr'], $m['description']];
            }

            $steps = [];
            foreach ($pdo->query('SELECT exercise_id,content FROM exercise_steps ORDER BY exercise_id,position') as $r) {
                $steps[$r['exercise_id']][] = $r['content'];
            }
            $exercises = [];
            foreach ($pdo->query('SELECT id,name,muscle_id,equipment,difficulty,tip FROM exercises ORDER BY position') as $e) {
                $exercises[] = [$e['id'], $e['name'], $e['muscle_id'], $e['equipment'], $e['difficulty'], $steps[$e['id']] ?? [], $e['tip']];
            }

            $st = $pdo->prepare('SELECT id,exercise_id,log_date,note FROM workout_logs WHERE user_id=? ORDER BY log_date,id');
            $st->execute([$uid]);
            $logs = [];
            foreach ($st as $r) {
                $logs[(int) $r['id']] = ['id' => (int) $r['id'], 'ex' => $r['exercise_id'], 'date' => $r['log_date'], 'sets' => [], 'note' => $r['note']];
            }
            if ($logs) {
                $st = $pdo->prepare('SELECT s.log_id,s.weight_kg,s.reps FROM log_sets s JOIN workout_logs l ON l.id=s.log_id WHERE l.user_id=? ORDER BY s.log_id,s.set_number');
                $st->execute([$uid]);
                foreach ($st as $r) {
                    $logs[(int) $r['log_id']]['sets'][] = ['w' => (float) $r['weight_kg'], 'r' => (int) $r['reps']];
                }
            }

            $st = $pdo->prepare('SELECT exercise_id FROM favorites WHERE user_id=?');
            $st->execute([$uid]);
            $favs = $st->fetchAll(PDO::FETCH_COLUMN);

            $st = $pdo->prepare('SELECT id,name,goal FROM programs WHERE user_id=? ORDER BY id');
            $st->execute([$uid]);
            $progs = [];
            foreach ($st as $r) {
                $progs[(int) $r['id']] = ['id' => (int) $r['id'], 'name' => $r['name'], 'goal' => $r['goal'], 'days' => []];
            }
            if ($progs) {
                $days = [];
                $dayProg = [];
                $st = $pdo->prepare('SELECT d.id,d.program_id,d.name FROM program_days d JOIN programs p ON p.id=d.program_id WHERE p.user_id=? ORDER BY d.program_id,d.position');
                $st->execute([$uid]);
                foreach ($st as $r) {
                    $days[(int) $r['id']] = ['n' => $r['name'], 'items' => []];
                    $dayProg[(int) $r['id']] = (int) $r['program_id'];
                }
                $st = $pdo->prepare('SELECT i.day_id,i.exercise_id,i.target_sets,i.target_reps FROM program_items i JOIN program_days d ON d.id=i.day_id JOIN programs p ON p.id=d.program_id WHERE p.user_id=? ORDER BY i.day_id,i.position');
                $st->execute([$uid]);
                foreach ($st as $r) {
                    $days[(int) $r['day_id']]['items'][] = ['ex' => $r['exercise_id'], 's' => (int) $r['target_sets'], 'r' => (int) $r['target_reps']];
                }
                foreach ($days as $id => $d) {
                    $progs[$dayProg[$id]]['days'][] = $d;
                }
            }

            $st = $pdo->prepare('SELECT display_name,goal,onboarding_done,email,avatar_ver FROM users WHERE id=?');
            $st->execute([$uid]);
            $u = $st->fetch();
            if (!$u) {
                fail('Compte introuvable.', 401);
            }

            out([
                'ok'        => true,
                'muscles'   => $muscles,
                'exercises' => $exercises,
                'logs'      => array_values($logs),
                'favs'      => $favs,
                'programs'  => array_values($progs),
                'profile'   => ['uid' => $uid, 'name' => $u['display_name'], 'goal' => $u['goal'] ?? '', 'email' => $u['email'], 'av' => (int) $u['avatar_ver']],
                'seen'      => (int) $u['onboarding_done'],
            ]);

        // ---------- Enregistrer une séance (un exercice, plusieurs séries) ----------
        case 'log_add':
            $ex = $in['ex'] ?? null;
            $date = $in['date'] ?? null;
            $note = mb_substr(trim((string) ($in['note'] ?? '')), 0, 500);
            $sets = $in['sets'] ?? null;
            if (!known_exercise($pdo, $ex)) {
                fail('Exercice inconnu.');
            }
            if (!valid_date($date)) {
                fail('Date invalide.');
            }
            if (!is_array($sets) || count($sets) < 1 || count($sets) > 30) {
                fail('Une séance contient de 1 à 30 séries.');
            }
            $clean = [];
            foreach ($sets as $s) {
                if (!is_array($s) || !is_numeric($s['w'] ?? null) || !is_numeric($s['r'] ?? null)) {
                    fail('Série invalide.');
                }
                $w = round((float) $s['w'], 1);
                $r = (int) $s['r'];
                if ($w < 0 || $w > 1000 || $r < 1 || $r > 1000) {
                    fail('Poids ou répétitions hors limites.');
                }
                $clean[] = [$w, $r];
            }
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (?,?,?,?)')->execute([$uid, $ex, $date, $note]);
            $logId = (int) $pdo->lastInsertId();
            $ins = $pdo->prepare('INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (?,?,?,?)');
            foreach ($clean as $i => [$w, $r]) {
                $ins->execute([$logId, $i + 1, $w, $r]);
            }
            $pdo->commit();
            out(['ok' => true, 'id' => $logId]);

        case 'log_delete':
            $id = $in['id'] ?? null;
            if (is_numeric($id)) {
                $pdo->prepare('DELETE FROM workout_logs WHERE id=? AND user_id=?')->execute([(int) $id, $uid]);
            }
            out(['ok' => true]);

        // ---------- Favoris : ajoute si absent, retire si présent ----------
        case 'fav_toggle':
            $ex = $in['ex'] ?? null;
            if (!known_exercise($pdo, $ex)) {
                fail('Exercice inconnu.');
            }
            $del = $pdo->prepare('DELETE FROM favorites WHERE user_id=? AND exercise_id=?');
            $del->execute([$uid, $ex]);
            if ($del->rowCount() === 0) {
                $pdo->prepare('INSERT INTO favorites (user_id,exercise_id) VALUES (?,?)')->execute([$uid, $ex]);
            }
            out(['ok' => true]);

        // ---------- Programmes : création ou modification complète ----------
        case 'program_save':
            $name = trim((string) ($in['name'] ?? ''));
            if ($name === '' || mb_strlen($name) > 40) {
                fail('Nom du programme invalide (1 à 40 caractères).');
            }
            $goal = in_array($in['goal'] ?? '', GOALS, true) ? $in['goal'] : 'Hypertrophie';
            $days = $in['days'] ?? null;
            if (!is_array($days) || count($days) < 1 || count($days) > 7) {
                fail('Un programme contient de 1 à 7 jours.');
            }
            $cleanDays = [];
            foreach ($days as $d) {
                if (!is_array($d)) {
                    fail('Jour invalide.');
                }
                $dn = mb_substr(trim((string) ($d['n'] ?? '')), 0, 30);
                if ($dn === '') {
                    $dn = 'Jour ' . (count($cleanDays) + 1);
                }
                $items = $d['items'] ?? null;
                if (!is_array($items) || count($items) < 1 || count($items) > 15) {
                    fail('Chaque jour contient de 1 à 15 exercices.');
                }
                $ci = [];
                foreach ($items as $t) {
                    if (!is_array($t) || !known_exercise($pdo, $t['ex'] ?? null) || !is_numeric($t['s'] ?? null) || !is_numeric($t['r'] ?? null)) {
                        fail('Exercice du programme invalide.');
                    }
                    $s = (int) $t['s'];
                    $r = (int) $t['r'];
                    if ($s < 1 || $s > 20 || $r < 1 || $r > 500) {
                        fail('Séries ou répétitions hors limites.');
                    }
                    $ci[] = [$t['ex'], $s, $r];
                }
                $cleanDays[] = [$dn, $ci];
            }

            $pdo->beginTransaction();
            $pid = (isset($in['id']) && is_numeric($in['id'])) ? (int) $in['id'] : 0;
            if ($pid) {
                $chk = $pdo->prepare('SELECT id FROM programs WHERE id=? AND user_id=?');
                $chk->execute([$pid, $uid]);
                if (!$chk->fetchColumn()) {
                    $pdo->rollBack();
                    fail('Programme introuvable.', 404);
                }
                $pdo->prepare('UPDATE programs SET name=?, goal=? WHERE id=?')->execute([$name, $goal, $pid]);
                $pdo->prepare('DELETE FROM program_days WHERE program_id=?')->execute([$pid]);
            } else {
                $pdo->prepare('INSERT INTO programs (user_id,name,goal) VALUES (?,?,?)')->execute([$uid, $name, $goal]);
                $pid = (int) $pdo->lastInsertId();
            }
            $insDay = $pdo->prepare('INSERT INTO program_days (program_id,position,name) VALUES (?,?,?)');
            $insItem = $pdo->prepare('INSERT INTO program_items (day_id,position,exercise_id,target_sets,target_reps) VALUES (?,?,?,?,?)');
            foreach ($cleanDays as $di => [$dn, $ci]) {
                $insDay->execute([$pid, $di + 1, $dn]);
                $dayId = (int) $pdo->lastInsertId();
                foreach ($ci as $ii => [$exId, $s, $r]) {
                    $insItem->execute([$dayId, $ii + 1, $exId, $s, $r]);
                }
            }
            $pdo->commit();
            out(['ok' => true, 'id' => $pid]);

        case 'program_delete':
            $id = $in['id'] ?? null;
            if (is_numeric($id)) {
                $pdo->prepare('DELETE FROM programs WHERE id=? AND user_id=?')->execute([(int) $id, $uid]);
            }
            out(['ok' => true]);

        // ---------- Profil (écran d'accueil) ----------
        case 'profile_save':
            $name = mb_substr(trim((string) ($in['name'] ?? '')), 0, 20);
            $goal = mb_substr(trim((string) ($in['goal'] ?? '')), 0, 40);
            $pdo->prepare('UPDATE users SET display_name=?, goal=?, onboarding_done=1 WHERE id=?')->execute([$name, $goal, $uid]);
            out(['ok' => true]);

        // ---------- Compte : e-mail et mot de passe (mot de passe actuel exigé) ----------
        case 'account_save':
            $email = trim((string) ($in['email'] ?? ''));
            $cur = (string) ($in['current'] ?? '');
            $new = (string) ($in['new'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120) {
                fail('Adresse e-mail invalide.');
            }
            $st = $pdo->prepare('SELECT email,password_hash FROM users WHERE id=?');
            $st->execute([$uid]);
            $u = $st->fetch();
            if (!$u) {
                fail('Compte introuvable.', 401);
            }
            $changeMail = strcasecmp($email, $u['email']) !== 0;
            if ($changeMail || $new !== '') {
                if (!password_verify($cur, $u['password_hash'])) {
                    usleep(400000);
                    fail('Mot de passe actuel incorrect.', 403);
                }
            }
            if ($new !== '' && strlen($new) < 8) {
                fail('Nouveau mot de passe : 8 caractères minimum.');
            }
            try {
                if ($changeMail) {
                    $pdo->prepare('UPDATE users SET email=? WHERE id=?')->execute([$email, $uid]);
                }
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    fail('Cette adresse e-mail est déjà utilisée.');
                }
                throw $e;
            }
            if ($new !== '') {
                $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
                session_regenerate_id(true);
            }
            out(['ok' => true, 'email' => $email]);

        case 'avatar_delete':
            $pdo->prepare('UPDATE users SET avatar_ver=0, avatar_data=NULL WHERE id=?')->execute([$uid]);
            out(['ok' => true]);

        // Retirer une pièce jointe envoyée mais pas encore publiée
        case 'upload_discard':
            if (is_numeric($in['id'] ?? null)) {
                $pdo->prepare('DELETE FROM post_files WHERE id=? AND user_id=? AND post_id IS NULL')->execute([(int) $in['id'], $uid]);
            }
            out(['ok' => true]);

        // ---------- Forum : tous les membres lisent et publient dans le même fil ----------
        case 'forum_list':
            $cat = $in['cat'] ?? '';
            $before = (isset($in['before']) && is_numeric($in['before'])) ? (int) $in['before'] : 0;
            $limit = 12;
            $where = [];
            $args = [];
            if (in_array($cat, POST_CATS, true)) {
                $where[] = 'p.category=?';
                $args[] = $cat;
            }
            if (!empty($in['mine'])) {
                $where[] = 'p.user_id=?';
                $args[] = $uid;
            }
            if ($before > 0) {
                $where[] = 'p.id<?';
                $args[] = $before;
            }
            $sql = 'SELECT p.id,p.user_id,p.category,p.body,p.attachment,UNIX_TIMESTAMP(p.created_at) AS ts,'
                 . 'COALESCE(NULLIF(u.display_name,\'\'),u.username) AS author,u.avatar_ver AS av,'
                 . '(SELECT COUNT(*) FROM post_likes l WHERE l.post_id=p.id) AS likes,'
                 . '(SELECT COUNT(*) FROM post_likes l WHERE l.post_id=p.id AND l.user_id=?) AS liked '
                 . 'FROM posts p JOIN users u ON u.id=p.user_id'
                 . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
                 . ' ORDER BY p.id DESC LIMIT ' . ($limit + 1);
            $st = $pdo->prepare($sql);
            $st->execute(array_merge([$uid], $args));
            $rows = $st->fetchAll();
            $more = count($rows) > $limit;
            $rows = array_slice($rows, 0, $limit);

            $posts = [];
            $owner = [];
            foreach ($rows as $r) {
                $att = $r['attachment'] ? json_decode($r['attachment'], true) : null;
                $owner[(int) $r['id']] = (int) $r['user_id'];
                $posts[(int) $r['id']] = [
                    'id'       => (int) $r['id'],
                    'mine'     => (int) $r['user_id'] === $uid,
                    'uid'      => (int) $r['user_id'],
                    'av'       => (int) $r['av'],
                    'files'    => [],
                    'author'   => $r['author'],
                    'cat'      => $r['category'],
                    'body'     => $r['body'],
                    'att'      => is_array($att) ? $att : null,
                    'ts'       => (int) $r['ts'],
                    'likes'    => (int) $r['likes'],
                    'liked'    => (int) $r['liked'] > 0,
                    'comments' => [],
                ];
            }
            if ($posts) {
                $ids = implode(',', array_map('intval', array_keys($posts)));
                foreach ($pdo->query('SELECT id,post_id,original_name,mime,size FROM post_files WHERE post_id IN (' . $ids . ') ORDER BY id') as $f) {
                    $posts[(int) $f['post_id']]['files'][] = ['id' => (int) $f['id'], 'name' => $f['original_name'], 'mime' => $f['mime'], 'size' => (int) $f['size']];
                }
                $cs = $pdo->query('SELECT c.id,c.post_id,c.user_id,c.body,UNIX_TIMESTAMP(c.created_at) AS ts,u.avatar_ver AS av,'
                    . 'COALESCE(NULLIF(u.display_name,\'\'),u.username) AS author '
                    . 'FROM post_comments c JOIN users u ON u.id=c.user_id '
                    . 'WHERE c.post_id IN (' . $ids . ') ORDER BY c.id');
                foreach ($cs as $c) {
                    $pid = (int) $c['post_id'];
                    $posts[$pid]['comments'][] = [
                        'id'     => (int) $c['id'],
                        'author' => $c['author'],
                        'uid'    => (int) $c['user_id'],
                        'av'     => (int) $c['av'],
                        'body'   => $c['body'],
                        'ts'     => (int) $c['ts'],
                        'del'    => (int) $c['user_id'] === $uid || $owner[$pid] === $uid,
                    ];
                }
            }
            out(['ok' => true, 'posts' => array_values($posts), 'more' => $more]);

        case 'forum_post':
            $body = trim((string) ($in['body'] ?? ''));
            $cat = $in['cat'] ?? 'Progression';
            if (!in_array($cat, POST_CATS, true)) {
                $cat = 'Progression';
            }
            $len = mb_strlen($body);
            $fileIds = [];
            foreach (is_array($in['file_ids'] ?? null) ? $in['file_ids'] : [] as $fid) {
                if (is_numeric($fid)) {
                    $fileIds[(int) $fid] = (int) $fid;
                }
            }
            $fileIds = array_values($fileIds);
            if (count($fileIds) > UPLOAD_PER_POST) {
                fail('Maximum ' . UPLOAD_PER_POST . ' fichiers par publication.');
            }
            if ($len > 1000 || ($len < 1 && !$fileIds)) {
                fail('Une publication contient de 1 à 1000 caractères (ou au moins un fichier).');
            }
            $st = $pdo->prepare('SELECT COUNT(*) FROM posts WHERE user_id=? AND created_at > (NOW() - INTERVAL 5 SECOND)');
            $st->execute([$uid]);
            if ((int) $st->fetchColumn() > 0) {
                fail('Doucement : attends quelques secondes avant de publier à nouveau.', 429);
            }
            $att = null;
            if (isset($in['log_id']) && is_numeric($in['log_id'])) {
                // La séance jointe est relue en base : seul son propriétaire peut la partager.
                $st = $pdo->prepare('SELECT id,exercise_id,log_date FROM workout_logs WHERE id=? AND user_id=?');
                $st->execute([(int) $in['log_id'], $uid]);
                $l = $st->fetch();
                if (!$l) {
                    fail('Séance introuvable.', 404);
                }
                $st = $pdo->prepare('SELECT weight_kg,reps FROM log_sets WHERE log_id=? ORDER BY set_number');
                $st->execute([(int) $l['id']]);
                $sets = [];
                foreach ($st as $r) {
                    $sets[] = ['w' => (float) $r['weight_kg'], 'r' => (int) $r['reps']];
                }
                $att = json_encode(['ex' => $l['exercise_id'], 'date' => $l['log_date'], 'sets' => $sets], JSON_UNESCAPED_UNICODE);
            }
            $pdo->beginTransaction();
            if ($fileIds) {
                // Seuls les fichiers envoyés par cet utilisateur et pas encore publiés peuvent être joints.
                $ph = implode(',', array_fill(0, count($fileIds), '?'));
                $st = $pdo->prepare("SELECT id FROM post_files WHERE user_id=? AND post_id IS NULL AND id IN ($ph)");
                $st->execute(array_merge([$uid], $fileIds));
                if (count($st->fetchAll()) !== count($fileIds)) {
                    $pdo->rollBack();
                    fail('Un fichier joint est introuvable. Renvoie-le.', 404);
                }
            }
            $pdo->prepare('INSERT INTO posts (user_id,category,body,attachment) VALUES (?,?,?,?)')->execute([$uid, $cat, $body, $att]);
            $postId = (int) $pdo->lastInsertId();
            if ($fileIds) {
                $pdo->prepare("UPDATE post_files SET post_id=? WHERE user_id=? AND post_id IS NULL AND id IN ($ph)")
                    ->execute(array_merge([$postId, $uid], $fileIds));
            }
            $pdo->commit();
            out(['ok' => true, 'id' => $postId]);

        case 'forum_delete':
            if (is_numeric($in['id'] ?? null)) {
                // Les fichiers joints sont dans la base : ils partent avec la publication (ON DELETE CASCADE).
                $pdo->prepare('DELETE FROM posts WHERE id=? AND user_id=?')->execute([(int) $in['id'], $uid]);
            }
            out(['ok' => true]);

        // ---------- J'aime : ajoute si absent, retire si présent ----------
        case 'forum_like':
            $pid = is_numeric($in['id'] ?? null) ? (int) $in['id'] : 0;
            $chk = $pdo->prepare('SELECT 1 FROM posts WHERE id=?');
            $chk->execute([$pid]);
            if (!$chk->fetchColumn()) {
                fail('Publication introuvable.', 404);
            }
            $del = $pdo->prepare('DELETE FROM post_likes WHERE post_id=? AND user_id=?');
            $del->execute([$pid, $uid]);
            if ($del->rowCount() === 0) {
                $pdo->prepare('INSERT INTO post_likes (post_id,user_id) VALUES (?,?)')->execute([$pid, $uid]);
            }
            out(['ok' => true]);

        case 'forum_comment':
            $pid = is_numeric($in['post_id'] ?? null) ? (int) $in['post_id'] : 0;
            $body = trim((string) ($in['body'] ?? ''));
            $len = mb_strlen($body);
            if ($len < 1 || $len > 500) {
                fail('Un commentaire contient de 1 à 500 caractères.');
            }
            $chk = $pdo->prepare('SELECT COUNT(*) FROM posts WHERE id=?');
            $chk->execute([$pid]);
            if (!(int) $chk->fetchColumn()) {
                fail('Publication introuvable.', 404);
            }
            $chk = $pdo->prepare('SELECT COUNT(*) FROM post_comments WHERE post_id=?');
            $chk->execute([$pid]);
            if ((int) $chk->fetchColumn() >= 200) {
                fail('Cette publication a atteint la limite de 200 commentaires.');
            }
            $pdo->prepare('INSERT INTO post_comments (post_id,user_id,body) VALUES (?,?,?)')->execute([$pid, $uid, $body]);
            $cid = (int) $pdo->lastInsertId();
            $st = $pdo->prepare('SELECT COALESCE(NULLIF(display_name,\'\'),username) FROM users WHERE id=?');
            $st->execute([$uid]);
            out(['ok' => true, 'id' => $cid, 'author' => $st->fetchColumn()]);

        // L'auteur d'un commentaire, ou celui de la publication, peut le supprimer.
        case 'forum_comment_delete':
            if (is_numeric($in['id'] ?? null)) {
                $pdo->prepare('DELETE c FROM post_comments c JOIN posts p ON p.id=c.post_id WHERE c.id=? AND (c.user_id=? OR p.user_id=?)')
                    ->execute([(int) $in['id'], $uid, $uid]);
            }
            out(['ok' => true]);

        default:
            fail('Action inconnue.', 404);
    }
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($e->getCode() === '42S02') {
        error_log('Gym Squale API : ' . $e->getMessage());
        fail('Tables absentes : importe sql/install.sql dans ta base MySQL.', 500);
    }
    error_log('Gym Squale API : ' . $e->getMessage());
    fail('Erreur de base de données.', 500);
}

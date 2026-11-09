<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

if (!empty($_SESSION['uid'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$mode = ($_POST['mode'] ?? 'login') === 'register' ? 'register' : 'login';
$old = ['username' => '', 'email' => '', 'identifier' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!check_csrf($_POST['csrf'] ?? null)) {
            $errors[] = 'Formulaire expiré. Recharge la page et réessaie.';
        } elseif ($mode === 'register') {
            $username = trim((string) ($_POST['username'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $pw = (string) ($_POST['password'] ?? '');
            $pw2 = (string) ($_POST['password2'] ?? '');
            $old['username'] = $username;
            $old['email'] = $email;

            if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
                $errors[] = 'Nom d’utilisateur : 3 à 20 caractères (lettres, chiffres ou _).';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120) {
                $errors[] = 'Adresse e-mail invalide.';
            }
            if (strlen($pw) < 8) {
                $errors[] = 'Mot de passe : 8 caractères minimum.';
            }
            if ($pw !== $pw2) {
                $errors[] = 'Les deux mots de passe sont différents.';
            }

            if (!$errors) {
                try {
                    $st = db()->prepare('INSERT INTO users (username,email,password_hash) VALUES (?,?,?)');
                    $st->execute([$username, $email, password_hash($pw, PASSWORD_DEFAULT)]);
                    session_regenerate_id(true);
                    $_SESSION['uid'] = (int) db()->lastInsertId();
                    header('Location: index.php');
                    exit;
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        $errors[] = 'Ce nom d’utilisateur ou cet e-mail est déjà utilisé.';
                    } else {
                        throw $e;
                    }
                }
            }
        } else {
            $id = trim((string) ($_POST['identifier'] ?? ''));
            $pw = (string) ($_POST['password'] ?? '');
            $old['identifier'] = $id;

            $st = db()->prepare('SELECT id,password_hash FROM users WHERE username=? OR email=?');
            $st->execute([$id, $id]);
            $u = $st->fetch();
            if ($u && password_verify($pw, $u['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['uid'] = (int) $u['id'];
                header('Location: index.php');
                exit;
            }
            usleep(400000); // ralentit les essais répétés
            $errors[] = 'Identifiant ou mot de passe incorrect.';
        }
    } catch (PDOException $e) {
        error_log('Gym Squale login : ' . $e->getMessage());
        $errors[] = 'Base de données inaccessible. Vérifie les variables GYM_DB_* sur Vercel et que sql/install.sql est importé.';
    }
}
$token = csrf_token();
$titles = [
    'login'    => ['Content de te revoir', 'Connecte-toi pour retrouver tes programmes, tes séances et tes records.'],
    'register' => ['Crée ton compte', 'Une minute suffit pour commencer à suivre tes séances.'],
];
?>
<!doctype html>
<html lang="fr"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0b0d0c">
<title>Connexion – Gym Squale</title>
<link rel="stylesheet" href="assets/style.css?v=<?= h((string) (getenv('VERCEL_DEPLOYMENT_ID') ?: '1')) ?>">
<script>document.documentElement.classList.add('js')</script>
</head><body class="auth">
<svg width="0" height="0" style="position:absolute" aria-hidden="true"><symbol id="fin" viewBox="0 0 32 32"><path d="M4 24 C 9 22 12 14 13 3 C 20 10 26 20 31 24 Z"/><path d="M1 28q3.5-2.4 7 0t7 0 7 0 7 0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol></svg>

<section class="stage" aria-hidden="false">
  <svg class="scene" viewBox="0 0 800 1000" preserveAspectRatio="xMidYMin slice" role="img" aria-label="Un aileron de requin fend la surface de l’eau">
    <defs>
      <linearGradient id="sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#0b0d0c"/><stop offset="1" stop-color="#1a2812"/></linearGradient>
      <linearGradient id="sea" gradientUnits="userSpaceOnUse" x1="0" y1="250" x2="0" y2="1000"><stop offset="0" stop-color="#1f3314"/><stop offset=".25" stop-color="#121b0e"/><stop offset=".6" stop-color="#0d120e"/><stop offset="1" stop-color="#0b0d0c"/></linearGradient>
      <linearGradient id="shaft" gradientUnits="userSpaceOnUse" x1="0" y1="250" x2="0" y2="800"><stop offset="0" stop-color="#b6ff3b" stop-opacity=".2"/><stop offset="1" stop-color="#b6ff3b" stop-opacity="0"/></linearGradient>
    </defs>
    <rect width="800" height="1000" fill="url(#sky)"/>
    <g class="wave"><path d="M-200 250 q50 -9 100 0 t100 0 q50 -9 100 0 t100 0 q50 -9 100 0 t100 0 q50 -9 100 0 t100 0 q50 -9 100 0 t100 0 q50 -9 100 0 t100 0 q50 -9 100 0 t100 0 L1200 1000 L-200 1000 Z" fill="url(#sea)"/></g>
    <polygon points="120,252 300,252 560,900 170,900" fill="url(#shaft)"/>
    <polygon points="380,252 450,252 690,820 520,820" fill="url(#shaft)" opacity=".7"/>
    <g class="sonar" transform="translate(585 650)"><circle r="6"/><circle r="6"/><circle r="6"/></g>
    <circle cx="585" cy="650" r="5" fill="#b6ff3b"/>
    <g class="fin-g"><g class="fin-bob">
      <path d="M-150 266C-70 250 40 246 120 268C40 292-70 292-150 266Z" fill="#b6ff3b" opacity=".1"/>
      <g transform="translate(0 258) scale(3.8) translate(-4 -24)"><path d="M4 24 C 9 22 12 14 13 3 C 20 10 26 20 31 24 Z" fill="#b6ff3b"/></g>
      <path d="M-4 252q-30-5-82 0M6 258q-40 5-96 2" fill="none" stroke="#b6ff3b" stroke-opacity=".6" stroke-width="2" stroke-linecap="round"/>
    </g></g>
    <g class="wave top"><path d="M-200 250 q50 -9 100 0 t100 0 q50 -9 100 0 t100 0 q50 -9 100 0 t100 0 q50 -9 100 0 t100 0 q50 -9 100 0 t100 0 q50 -9 100 0 t100 0 q50 -9 100 0 t100 0" fill="none" stroke="#b6ff3b" stroke-width="2.5"/></g>
  </svg>

  <div class="stage-brand"><svg class="fin" aria-hidden="true"><use href="#fin"/></svg>GYM <b>SQUALE</b></div>

  <div class="stage-copy">
    <h2>Chaque série laisse une trace.</h2>
    <p>Programmes, séances guidées et records : tout ce que tu soulèves, au même endroit.</p>
    <div class="peek" aria-hidden="true"><span class="ico">QUA</span><div><b>Squat</b><span class="s">75 kg × 8 · 3 séries</span></div><span class="rec">Record</span></div>
  </div>
</section>

<main class="side">
  <div class="panel">
    <h1 id="ttl"><?= h($titles[$mode][0]) ?></h1>
    <p class="mu" id="sub"><?= h($titles[$mode][1]) ?></p>

    <div class="tabs" role="tablist" data-mode="<?= h($mode) ?>">
      <span class="ind" aria-hidden="true"></span>
      <button type="button" role="tab" id="tab-login" aria-controls="pane-login" aria-selected="<?= $mode === 'login' ? 'true' : 'false' ?>">Connexion</button>
      <button type="button" role="tab" id="tab-register" aria-controls="pane-register" aria-selected="<?= $mode === 'register' ? 'true' : 'false' ?>">Créer un compte</button>
    </div>

    <form class="pane<?= $mode === 'login' ? ' on' : '' ?>" id="pane-login" role="tabpanel" aria-labelledby="tab-login" method="post" action="login.php">
      <?php if ($mode === 'login'): foreach ($errors as $e): ?><div class="err" role="alert"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7.5v5M12 16h.01"/></svg><span><?= h($e) ?></span></div><?php endforeach; endif; ?>
      <input type="hidden" name="csrf" value="<?= h($token) ?>">
      <input type="hidden" name="mode" value="login">
      <label class="field"><span class="lab">Nom d’utilisateur ou e-mail</span>
        <input id="l-id" name="identifier" value="<?= h($old['identifier']) ?>" autocomplete="username" autocapitalize="none" spellcheck="false" required></label>
      <label class="field"><span class="lab">Mot de passe</span>
        <span class="pw"><input id="l-pw" name="password" type="password" autocomplete="current-password" required><button type="button" class="reveal" aria-pressed="false" aria-controls="l-pw">Afficher</button></span></label>
      <button class="pri cta" type="submit" data-busy="Connexion…">Se connecter</button>
      <button class="ghost" type="button" id="demo">Essayer avec le compte démo</button>
    </form>

    <form class="pane<?= $mode === 'register' ? ' on' : '' ?>" id="pane-register" role="tabpanel" aria-labelledby="tab-register" method="post" action="login.php">
      <?php if ($mode === 'register'): foreach ($errors as $e): ?><div class="err" role="alert"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7.5v5M12 16h.01"/></svg><span><?= h($e) ?></span></div><?php endforeach; endif; ?>
      <input type="hidden" name="csrf" value="<?= h($token) ?>">
      <input type="hidden" name="mode" value="register">
      <label class="field"><span class="lab">Nom d’utilisateur</span>
        <input id="r-user" name="username" value="<?= h($old['username']) ?>" maxlength="20" pattern="[A-Za-z0-9_]{3,20}" title="3 à 20 caractères : lettres, chiffres ou _" autocomplete="username" autocapitalize="none" spellcheck="false" required>
        <span class="hint">3 à 20 caractères : lettres, chiffres ou _</span></label>
      <label class="field"><span class="lab">E-mail</span>
        <input id="r-mail" name="email" type="email" value="<?= h($old['email']) ?>" maxlength="120" autocomplete="email" required></label>
      <label class="field"><span class="lab">Mot de passe</span>
        <span class="pw"><input id="r-pw" name="password" type="password" minlength="8" autocomplete="new-password" required><button type="button" class="reveal" aria-pressed="false" aria-controls="r-pw">Afficher</button></span>
        <span class="meter" id="meter" data-s="0" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
        <span class="hint" id="pwhint" aria-live="polite">8 caractères minimum</span></label>
      <label class="field"><span class="lab">Confirmer le mot de passe</span>
        <input id="r-pw2" name="password2" type="password" minlength="8" autocomplete="new-password" required>
        <span class="hint" id="pw2hint" aria-live="polite"></span></label>
      <button class="pri cta" type="submit" data-busy="Création du compte…">Créer mon compte</button>
    </form>
  </div>
</main>

<script>
(function(){
  var T={login:['Content de te revoir','Connecte-toi pour retrouver tes programmes, tes séances et tes records.'],
         register:['Crée ton compte','Une minute suffit pour commencer à suivre tes séances.']};
  var tabs=document.querySelector('.tabs'),ttl=document.getElementById('ttl'),sub=document.getElementById('sub');
  function show(m,focus){
    tabs.dataset.mode=m;
    ['login','register'].forEach(function(k){
      var on=k===m;
      document.getElementById('pane-'+k).classList.toggle('on',on);
      document.getElementById('tab-'+k).setAttribute('aria-selected',on);
    });
    ttl.textContent=T[m][0];sub.textContent=T[m][1];
    if(focus){var f=document.querySelector('#pane-'+m+' input:not([type=hidden])');if(f)f.focus()}
  }
  ['login','register'].forEach(function(k){document.getElementById('tab-'+k).addEventListener('click',function(){show(k,true)})});

  document.querySelectorAll('.reveal').forEach(function(b){
    b.addEventListener('click',function(){
      var i=document.getElementById(b.getAttribute('aria-controls')),h=i.type==='password';
      i.type=h?'text':'password';b.textContent=h?'Masquer':'Afficher';b.setAttribute('aria-pressed',h);
    });
  });

  var pw=document.getElementById('r-pw'),pw2=document.getElementById('r-pw2'),meter=document.getElementById('meter'),ph=document.getElementById('pwhint'),p2=document.getElementById('pw2hint');
  var L=['8 caractères minimum','Trop court : 8 caractères minimum','Correct','Bon','Solide'];
  function score(p){
    var s=0;if(p.length>=8)s=2;else if(p.length>0)s=1;
    if(s===2&&/[a-z]/.test(p)&&/[A-Z]/.test(p)&&/\d/.test(p))s=3;
    if(s===3&&(p.length>=12||/[^A-Za-z0-9]/.test(p)))s=4;
    return s;
  }
  function check(){
    var s=score(pw.value);meter.dataset.s=s;ph.textContent=L[s];ph.className='hint'+(s>=2?' ok':'');
    if(!pw2.value){p2.textContent='';p2.className='hint'}
    else if(pw2.value===pw.value){p2.textContent='Les mots de passe correspondent';p2.className='hint ok'}
    else{p2.textContent='Les deux mots de passe sont différents';p2.className='hint no'}
  }
  pw.addEventListener('input',check);pw2.addEventListener('input',check);

  document.getElementById('demo').addEventListener('click',function(){
    document.getElementById('l-id').value='demo';document.getElementById('l-pw').value='Demo12345';
    document.getElementById('pane-login').requestSubmit();
  });

  document.querySelectorAll('form.pane').forEach(function(f){
    f.addEventListener('submit',function(){
      var b=f.querySelector('.cta');b.textContent=b.dataset.busy;b.setAttribute('aria-busy','true');
      setTimeout(function(){b.disabled=true},0);
    });
  });
  window.addEventListener('pageshow',function(e){if(e.persisted)document.querySelectorAll('.cta').forEach(function(b){b.disabled=false;b.removeAttribute('aria-busy')})});
})();
</script>
</body></html>

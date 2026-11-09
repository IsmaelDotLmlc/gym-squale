<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

if (empty($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}
$v = getenv('VERCEL_DEPLOYMENT_ID') ?: (getenv('VERCEL_GIT_COMMIT_SHA') ?: '1');   // change à chaque déploiement : le cache du navigateur est renouvelé
$csrf = csrf_token();
?>
<!doctype html>
<html lang="fr"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0b0d0c">
<title>Gym Squale – Coach de musculation</title>
<link rel="stylesheet" href="assets/style.css?v=<?= h((string) $v) ?>">
</head><body>
<svg width="0" height="0" style="position:absolute" aria-hidden="true"><symbol id="fin" viewBox="0 0 32 32"><path d="M4 24C9 22 12 14 13 3C20 10 26 20 31 24Z"/><path d="M1 28q3.5-2.4 7 0t7 0 7 0 7 0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol></svg>
<nav aria-label="Navigation principale">
<a class="brand" href="#/dash" title="Gym Squale" aria-label="Gym Squale, accueil"><svg class="fin" aria-hidden="true"><use href="#fin"/></svg></a>
<div class="nav-main"><a href="#/dash" data-r="dash"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="2"/><rect x="14" y="3" width="7" height="5" rx="2"/><rect x="14" y="12" width="7" height="9" rx="2"/><rect x="3" y="16" width="7" height="5" rx="2"/></svg><span>Dashboard</span></a><a href="#/pg" data-r="pg"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01"/></svg><span>Programmes</span></a><a href="#/muscles" data-r="muscles"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.5 6.5v11M17.5 6.5v11M3 9v6M21 9v6M6.5 12h11"/></svg><span>Muscles</span></a><a href="#/ex" data-r="ex"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20.5 20.5 16 16"/></svg><span>Exercices</span></a><a href="#/hist" data-r="hist"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><span class="ln">Entraînements</span><span class="ls">Séances</span></a><a href="#/prog" data-r="prog"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17l6-6 4 4 8-8M15 7h6v6"/></svg><span>Progression</span></a><a href="#/forum" data-r="forum"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/></svg><span>Forum</span></a></div>
<div class="nav-aux"><a href="#/profile" data-r="profile" title="Mon profil"><span class="navav" id="navav"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg></span><span>Profil</span></a>
<a href="#" data-r="help" onclick="event.preventDefault();tourStart()"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.6 9.2a2.5 2.5 0 1 1 3.5 2.3c-.7.4-1.1 1-1.1 1.7M12 17h.01"/></svg><span>Aide</span></a>
<form method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><button type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg><span>Quitter</span></button></form></div>
</nav>
<header class="mtop"><div class="wm"><svg class="fin" aria-hidden="true"><use href="#fin"/></svg>GYM <b>SQUALE</b></div>
<div class="acts"><button type="button" onclick="location.hash='#/profile'" aria-label="Mon profil"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg></button>
<button type="button" onclick="tourStart()" aria-label="Aide et visite guidée"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.6 9.2a2.5 2.5 0 1 1 3.5 2.3c-.7.4-1.1 1-1.1 1.7M12 17h.01"/></svg></button>
<form method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><button type="submit" aria-label="Se déconnecter"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg></button></form></div></header>
<main id="app"></main><div id="toast" role="status" aria-live="polite"></div>
<script>window.CSRF = <?= json_encode($csrf) ?>;</script>
<script src="assets/app.js?v=<?= h((string) $v) ?>"></script>
</body></html>

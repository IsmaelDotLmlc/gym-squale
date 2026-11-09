# Gym Squale — version Vercel

Cette version est celle du projet **adaptée pour Vercel**. Rien n'est écrit sur le disque : les sessions, les photos de profil et les pièces jointes du forum sont stockées dans MySQL.

```
vercel.json        configuration (runtime PHP, région, routes)
api/index.php      point d'entrée unique : aiguille chaque URL vers app/
api/php.ini        limites d'envoi (4 Mo)
app/               le code PHP (config, pages, API, upload, fichiers)
public/assets/     CSS et JavaScript (servis directement par Vercel)
sql/install.sql    structure de la base + exercices (à importer)
sql/demo_data.sql  compte de démonstration (facultatif)
.env.example       la liste des variables d'environnement
```

## 1. Créer la base MySQL

Vercel n'héberge pas MySQL : il te faut une base MySQL/MariaDB **accessible depuis Internet** (Aiven, TiDB Cloud, Railway, un hébergeur avec accès distant…). Vérifie leurs offres et leurs limites avant de choisir.

- Choisis une région **Europe** : la fonction Vercel tourne à Paris (`cdg1`), plus la base est proche, plus l'app est rapide.
- Note l'hôte, le port, le nom de la base, l'utilisateur et le mot de passe.
- Importe `sql/install.sql` (phpMyAdmin, Adminer ou `mysql -h HOTE -u USER -p NOMBASE < sql/install.sql`), puis `sql/demo_data.sql` si tu veux le compte `demo` / `Demo12345`.
- Attention : `install.sql` **supprime et recrée** les tables. Ne le relance jamais sur une base qui contient des données à garder.
- Les fichiers sont stockés dans la base : vérifie que `max_allowed_packet` fait au moins 8 Mo (c'est le cas par défaut sur la plupart des services).

## 2. Mettre le projet sur GitHub

```bash
cd gym-squale-vercel
git init && git add . && git commit -m "Gym Squale pour Vercel"
# crée un dépôt vide sur GitHub, puis :
git remote add origin https://github.com/TON-COMPTE/gym-squale.git
git branch -M main && git push -u origin main
```

## 3. Déployer sur Vercel

1. Sur vercel.com : **Add New… > Project**, importe le dépôt.
2. **Framework Preset : Other**. Laisse *Build Command* et *Output Directory* vides.
3. Ouvre **Environment Variables** et ajoute (voir `.env.example`) :
   `GYM_DB_HOST`, `GYM_DB_PORT`, `GYM_DB_NAME`, `GYM_DB_USER`, `GYM_DB_PASS`, et `GYM_DB_SSL=1` si ta base impose une connexion chiffrée.
4. **Deploy**. Si le build se plaint de la version de Node, mets **Node.js 22.x** dans *Settings > General*.

Sans GitHub, avec le terminal :

```bash
npm i -g vercel
vercel login
vercel            # premier déploiement (aperçu)
vercel env add GYM_DB_HOST     # répéter pour chaque variable
vercel --prod
```

Après avoir ajouté ou modifié une variable, **redéploie** (Deployments > ⋯ > Redeploy) : elle n'est lue qu'au déploiement.

## 4. Tester à distance

Ouvre l'URL `https://ton-projet.vercel.app`, puis vérifie dans l'ordre :

1. La page de connexion s'affiche, avec le style (sinon voir « Dépannage »).
2. Connexion `demo` / `Demo12345` puis rechargement : tu restes connecté (sessions en base).
3. Lance et enregistre une séance, ouvre l'historique et la progression.
4. Profil : change la photo (elle est recadrée dans le navigateur), recharge, elle doit rester.
5. Forum : publie avec une image, puis avec un PDF ; ouvre le fichier joint.
6. Teste sur téléphone et en navigation privée avec un deuxième compte.

## Ce qui change par rapport à la version XAMPP

| Sujet | XAMPP | Vercel |
|---|---|---|
| Sessions | fichiers PHP | table `sessions` (14 jours) |
| Photos de profil | `storage/avatars/` | colonne `users.avatar_data` |
| Pièces jointes | `storage/files/` | colonne `post_files.data` |
| Taille max d'un fichier | 10 Mo | **4 Mo** (Vercel refuse les requêtes de plus de 4,5 Mo) |
| Recadrage de la photo | GD sur le serveur | dans le navigateur (GD n'existe pas sur le runtime Vercel) ; GD est utilisé s'il est présent |
| Configuration base | `config.php` | variables d'environnement `GYM_DB_*` |
| Cache CSS/JS | date du fichier | identifiant du déploiement |

## Dépannage

- **« Base de données inaccessible » (503)** : variables `GYM_DB_*` fausses ou absentes, base non joignable depuis Internet, ou `install.sql` non importé. Regarde *Deployments > Logs* sur Vercel : le message exact de MySQL y figure.
- **Erreur SSL / « certificate »** : mets `GYM_DB_SSL=1`. Si ton fournisseur donne un certificat CA, dépose-le dans `app/` (par ex. `app/ca.pem`) et ajoute `GYM_DB_SSL_CA=ca.pem`. En dernier recours, `GYM_DB_SSL_VERIFY=0` désactive la vérification du certificat (à éviter en production).
- **Page sans style** : vérifie que le dossier `public/assets/` est bien dans le dépôt, et que le *Output Directory* du projet est resté vide.
- **Déconnecté à chaque page** : la table `sessions` manque ou la base refuse l'écriture. Réimporte `install.sql`.
- **« Fichier trop volumineux »** : limite de 4 Mo. Compresse l'image ou la vidéo.
- **Le déploiement refuse `regions`** : supprime la ligne `"regions": ["cdg1"]` dans `vercel.json` (la région dépend de ton offre Vercel).
- **Runtime PHP** : `vercel-php@0.7.4` (PHP 8.3). Des versions plus récentes existent (PHP 8.4 et 8.5) mais elles dépréciaient des constantes PDO utilisées pour le SSL : reste sur 0.7.4 sauf besoin précis.

## À savoir avant de partager le lien

- Supprime ou change le mot de passe des comptes de démonstration (`demo`, `karim_m`, `salma_fit`, `youssef_bjj`) si le site reste en ligne.
- Le runtime PHP de Vercel est **communautaire** (pas maintenu par Vercel) : parfait pour une démo ou un test, à reconsidérer pour une vraie mise en production.
- Chaque requête ouvre une connexion à MySQL : choisis une base proche de Paris et un plan qui accepte plusieurs connexions simultanées.

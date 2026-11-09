-- Gym Squale : base de données MySQL / MariaDB (XAMPP)
-- Version Vercel : à importer dans la base MySQL créée chez ton hébergeur (sans CREATE DATABASE / USE).
-- Ajoute aussi la table sessions, les photos (users.avatar_data) et les pièces jointes (post_files.data).
SET NAMES utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS sessions, post_files, post_likes, post_comments, posts, program_items, program_days, programs, log_sets, workout_logs, favorites, exercise_steps, exercises, muscles, users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(20) NOT NULL UNIQUE,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(20) NOT NULL DEFAULT '',
  goal VARCHAR(40) NULL,
  onboarding_done TINYINT(1) NOT NULL DEFAULT 0,
  avatar_ver INT UNSIGNED NOT NULL DEFAULT 0,
  avatar_data MEDIUMBLOB NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE muscles (
  id VARCHAR(5) PRIMARY KEY,
  name VARCHAR(40) NOT NULL,
  abbr VARCHAR(5) NOT NULL,
  description VARCHAR(120) NOT NULL,
  position TINYINT UNSIGNED NOT NULL
) ENGINE=InnoDB;

CREATE TABLE exercises (
  id VARCHAR(8) PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  muscle_id VARCHAR(5) NOT NULL,
  equipment VARCHAR(30) NOT NULL,
  difficulty ENUM('Débutant','Intermédiaire','Avancé') NOT NULL,
  tip VARCHAR(255) NOT NULL,
  position SMALLINT UNSIGNED NOT NULL,
  FOREIGN KEY (muscle_id) REFERENCES muscles(id)
) ENGINE=InnoDB;

CREATE TABLE exercise_steps (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  exercise_id VARCHAR(8) NOT NULL,
  position TINYINT UNSIGNED NOT NULL,
  content VARCHAR(255) NOT NULL,
  UNIQUE KEY uq_step (exercise_id, position),
  FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE favorites (
  user_id INT UNSIGNED NOT NULL,
  exercise_id VARCHAR(8) NOT NULL,
  PRIMARY KEY (user_id, exercise_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE workout_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  exercise_id VARCHAR(8) NOT NULL,
  log_date DATE NOT NULL,
  note VARCHAR(500) NOT NULL DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user_date (user_id, log_date),
  KEY idx_user_ex (user_id, exercise_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (exercise_id) REFERENCES exercises(id)
) ENGINE=InnoDB;

CREATE TABLE log_sets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  log_id INT UNSIGNED NOT NULL,
  set_number TINYINT UNSIGNED NOT NULL,
  weight_kg DECIMAL(6,2) NOT NULL,
  reps SMALLINT UNSIGNED NOT NULL,
  UNIQUE KEY uq_set (log_id, set_number),
  CONSTRAINT chk_weight CHECK (weight_kg >= 0),
  CONSTRAINT chk_reps CHECK (reps > 0),
  FOREIGN KEY (log_id) REFERENCES workout_logs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE programs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  name VARCHAR(40) NOT NULL,
  goal VARCHAR(20) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE program_days (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  program_id INT UNSIGNED NOT NULL,
  position TINYINT UNSIGNED NOT NULL,
  name VARCHAR(30) NOT NULL,
  UNIQUE KEY uq_day (program_id, position),
  FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE program_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  day_id INT UNSIGNED NOT NULL,
  position TINYINT UNSIGNED NOT NULL,
  exercise_id VARCHAR(8) NOT NULL,
  target_sets TINYINT UNSIGNED NOT NULL,
  target_reps SMALLINT UNSIGNED NOT NULL,
  UNIQUE KEY uq_item (day_id, position),
  FOREIGN KEY (day_id) REFERENCES program_days(id) ON DELETE CASCADE,
  FOREIGN KEY (exercise_id) REFERENCES exercises(id)
) ENGINE=InnoDB;

-- ===== Forum =====
CREATE TABLE posts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  category ENUM('Progression','Question','Conseil','Motivation') NOT NULL DEFAULT 'Progression',
  body VARCHAR(1000) NOT NULL,
  attachment TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_created (created_at, id),
  KEY idx_user (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE post_comments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  body VARCHAR(500) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_post (post_id, id),
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE post_likes (
  post_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (post_id, user_id),
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE post_files (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  post_id INT UNSIGNED NULL,
  stored_name CHAR(32) NOT NULL,
  original_name VARCHAR(120) NOT NULL,
  mime VARCHAR(40) NOT NULL,
  size INT UNSIGNED NOT NULL,
  data MEDIUMBLOB NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_post (post_id),
  KEY idx_user_pending (user_id, post_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sessions PHP stockées en base (le disque de Vercel n'est pas persistant)
CREATE TABLE sessions (
  id VARCHAR(128) NOT NULL PRIMARY KEY,
  data MEDIUMBLOB NOT NULL,
  updated_at INT UNSIGNED NOT NULL,
  KEY idx_updated (updated_at)
) ENGINE=InnoDB;

-- ===== Données de référence =====
INSERT INTO muscles (id,name,abbr,description,position) VALUES
('pec','Pectoraux','PEC','Grand et petit pectoral',0),
('dos','Dos','DOS','Grand dorsal, trapèzes, rhomboïdes',1),
('epa','Épaules','ÉPA','Deltoïdes antérieur, latéral, postérieur',2),
('bic','Biceps','BIC','Biceps brachial, brachial',3),
('tri','Triceps','TRI','Les trois chefs du triceps',4),
('avb','Avant-bras','AVB','Fléchisseurs et extenseurs',5),
('abs','Abdominaux','ABS','Grand droit, obliques, transverse',6),
('qua','Quadriceps','QUA','Face avant de la cuisse',7),
('isc','Ischio-jambiers','ISC','Face arrière de la cuisse',8),
('fes','Fessiers','FES','Grand, moyen et petit fessier',9),
('mol','Mollets','MOL','Gastrocnémien, soléaire',10);

INSERT INTO exercises (id,name,muscle_id,equipment,difficulty,tip,position) VALUES
('dc','Développé couché','pec','Barre','Intermédiaire','Garde les poignets alignés avec les avant-bras. Utilise des pareurs ou un rack de sécurité.',0),
('di','Développé incliné haltères','pec','Haltères','Intermédiaire','Ne fais pas claquer les haltères en haut.',1),
('pom','Pompes','pec','Poids du corps','Débutant','Variante débutant : pompes sur genoux ou mains surélevées.',2),
('ec','Écartés à la poulie','pec','Poulie','Débutant','Garde l’angle des coudes constant.',3),
('tr','Tractions','dos','Poids du corps','Avancé','Variante débutant : élastique ou tractions négatives.',4),
('tv','Tirage vertical','dos','Machine','Débutant','Pense à tirer avec les coudes, pas avec les mains.',5),
('rb','Rowing barre','dos','Barre','Intermédiaire','Si le dos s’arrondit, allège la charge.',6),
('rh','Rowing haltère','dos','Haltères','Débutant','Évite de tourner le buste.',7),
('dm','Développé militaire','epa','Barre','Intermédiaire','Ne cambre pas le bas du dos.',8),
('el','Élévations latérales','epa','Haltères','Débutant','Charge légère, pas d’élan.',9),
('fp','Face pull','epa','Poulie','Débutant','Excellent pour la posture et l’arrière d’épaule.',10),
('cb','Curl barre','bic','Barre','Débutant','Pas d’élan avec le dos.',11),
('cm','Curl marteau','bic','Haltères','Débutant','Sollicite aussi le brachial et l’avant-bras.',12),
('tp','Extension triceps à la poulie','tri','Poulie','Débutant','Seuls les avant-bras bougent.',13),
('bf','Barre au front','tri','Barre','Intermédiaire','Charge modérée pour protéger les coudes.',14),
('ca','Curl poignets','avb','Haltères','Débutant','Amplitude complète, charge légère.',15),
('cp','Crunch à la poulie','abs','Poulie','Débutant','Ne tire pas avec les bras.',16),
('rj','Relevés de jambes','abs','Poids du corps','Intermédiaire','Variante débutant : genoux fléchis.',17),
('ga','Gainage','abs','Poids du corps','Débutant','Qualité avant durée.',18),
('sq','Squat','qua','Barre','Intermédiaire','Utilise un rack de sécurité.',19),
('pc','Presse à cuisses','qua','Machine','Débutant','Ne laisse pas le bassin se décoller.',20),
('fe','Fentes','qua','Haltères','Intermédiaire','Genou aligné avec le pied.',21),
('sdt','Soulevé de terre roumain','isc','Barre','Intermédiaire','Mouvement de charnière de hanche.',22),
('lc','Leg curl','isc','Machine','Débutant','Ne soulève pas le bassin.',23),
('ht','Hip thrust','fes','Barre','Intermédiaire','Menton rentré, côtes basses.',24),
('md','Mollets debout','mol','Machine','Débutant','Amplitude complète, tempo lent.',25);

INSERT INTO exercise_steps (exercise_id,position,content) VALUES
('dc',1,'Allonge-toi, yeux sous la barre, pieds au sol, omoplates serrées.'),
('dc',2,'Prise un peu plus large que les épaules, décroche la barre.'),
('dc',3,'Descends contrôlé jusqu’au bas des pectoraux (inspire).'),
('dc',4,'Pousse jusqu’à l’extension sans verrouiller brutalement (expire).'),
('di',1,'Banc incliné à 30°, haltères au niveau des épaules.'),
('di',2,'Pousse en rapprochant légèrement les haltères.'),
('di',3,'Redescends en 2-3 s jusqu’à l’étirement.'),
('pom',1,'Mains un peu plus larges que les épaules, corps gainé.'),
('pom',2,'Descends la poitrine près du sol.'),
('pom',3,'Remonte en poussant le sol.'),
('ec',1,'Poulies hautes, un pas en avant, coudes légèrement fléchis.'),
('ec',2,'Rapproche les mains devant la poitrine.'),
('ec',3,'Reviens lentement en gardant la tension.'),
('tr',1,'Suspends-toi, prise un peu plus large que les épaules.'),
('tr',2,'Tire les coudes vers les hanches, menton au-dessus de la barre.'),
('tr',3,'Redescends bras tendus de façon contrôlée.'),
('tv',1,'Cuisses calées, prise large.'),
('tv',2,'Tire la barre vers le haut de la poitrine.'),
('tv',3,'Remonte lentement sans te balancer.'),
('rb',1,'Buste incliné à 45°, dos plat, genoux souples.'),
('rb',2,'Tire la barre vers le nombril.'),
('rb',3,'Redescends sans arrondir le dos.'),
('rh',1,'Une main et un genou sur le banc, dos plat.'),
('rh',2,'Tire l’haltère vers la hanche.'),
('rh',3,'Descends jusqu’à l’étirement complet.'),
('dm',1,'Debout, barre à hauteur de clavicules, abdos et fessiers serrés.'),
('dm',2,'Pousse la barre au-dessus de la tête.'),
('dm',3,'Redescends sous contrôle.'),
('el',1,'Haltères légers, coudes souples.'),
('el',2,'Monte les bras jusqu’à l’horizontale.'),
('el',3,'Redescends en 2-3 s.'),
('fp',1,'Poulie haute avec corde, recule d’un pas.'),
('fp',2,'Tire vers le visage, coudes hauts.'),
('fp',3,'Reviens lentement.'),
('cb',1,'Debout, coudes collés au corps.'),
('cb',2,'Monte la barre vers les épaules.'),
('cb',3,'Redescends lentement sans balancer.'),
('cm',1,'Prise neutre (paumes face à face).'),
('cm',2,'Fléchis les coudes sans bouger les épaules.'),
('cm',3,'Redescends contrôlé.'),
('tp',1,'Coudes collés aux côtes.'),
('tp',2,'Étends complètement les bras vers le bas.'),
('tp',3,'Remonte jusqu’à 90° du coude.'),
('bf',1,'Allongé, barre EZ bras tendus.'),
('bf',2,'Fléchis les coudes pour amener la barre vers le front.'),
('bf',3,'Étends sans écarter les coudes.'),
('ca',1,'Avant-bras posés sur les cuisses, paumes vers le haut.'),
('ca',2,'Fléchis les poignets.'),
('ca',3,'Redescends lentement.'),
('cp',1,'À genoux, corde derrière la tête.'),
('cp',2,'Enroule la colonne en rapprochant les côtes du bassin.'),
('cp',3,'Reviens lentement.'),
('rj',1,'Suspendu ou allongé, jambes jointes.'),
('rj',2,'Monte les jambes en basculant le bassin.'),
('rj',3,'Redescends sans balancer.'),
('ga',1,'Avant-bras au sol, corps aligné.'),
('ga',2,'Serre abdos et fessiers.'),
('ga',3,'Tiens la position (note les secondes en « répétitions »).'),
('sq',1,'Barre sur les trapèzes, pieds largeur d’épaules.'),
('sq',2,'Descends en poussant les genoux vers l’extérieur, dos neutre.'),
('sq',3,'Remonte en poussant dans les talons.'),
('pc',1,'Dos plaqué, pieds largeur d’épaules.'),
('pc',2,'Descends jusqu’à ~90° sans décoller le bassin.'),
('pc',3,'Pousse sans verrouiller les genoux.'),
('fe',1,'Grand pas en avant, buste droit.'),
('fe',2,'Descends jusqu’à ce que le genou arrière frôle le sol.'),
('fe',3,'Reviens en poussant sur le pied avant.'),
('sdt',1,'Barre devant les cuisses, genoux légèrement fléchis.'),
('sdt',2,'Recule les hanches, dos plat, barre près des jambes.'),
('sdt',3,'Remonte en poussant les hanches.'),
('lc',1,'Allongé, rouleau au-dessus des talons.'),
('lc',2,'Ramène les talons vers les fessiers.'),
('lc',3,'Redescends lentement.'),
('ht',1,'Haut du dos sur un banc, barre sur les hanches.'),
('ht',2,'Pousse les hanches vers le haut.'),
('ht',3,'Serre les fessiers en haut, puis redescends.'),
('md',1,'Pointes de pieds sur la plateforme.'),
('md',2,'Monte sur la pointe, marque une pause.'),
('md',3,'Redescends en étirant complètement.');

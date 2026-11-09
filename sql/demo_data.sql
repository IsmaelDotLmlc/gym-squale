-- Données de démonstration : compte  demo  /  mot de passe  Demo12345
-- (le forum contient aussi 3 comptes fictifs : karim_m, salma_fit, youssef_bjj, même mot de passe)
-- À importer APRÈS database.sql (phpMyAdmin > Importer). Les dates sont relatives à aujourd'hui.
SET NAMES utf8mb4;

DELETE FROM users WHERE username IN ('demo','karim_m','salma_fit','youssef_bjj');
INSERT INTO users (username,email,password_hash,display_name,goal,onboarding_done) VALUES ('demo','demo@gym-squale.ma','$2y$10$GcSMm4ik63IYsNBsERGFxen79wgYfDe0xhD31GoYe1Xfq/nbLnXpm','Ismael','Prendre du muscle',1);
SET @uid = LAST_INSERT_ID();

INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'dc',CURDATE() - INTERVAL 28 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,60.0,10), (@l,2,60.0,10), (@l,3,60.0,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'dm',CURDATE() - INTERVAL 28 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,35.0,10), (@l,2,35.0,8), (@l,3,35.0,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'tp',CURDATE() - INTERVAL 28 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,25.0,12), (@l,2,25.0,12), (@l,3,25.0,10);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'rb',CURDATE() - INTERVAL 26 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,50.0,10), (@l,2,50.0,10), (@l,3,50.0,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'tv',CURDATE() - INTERVAL 26 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,45.0,12), (@l,2,45.0,10), (@l,3,45.0,10);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'cb',CURDATE() - INTERVAL 26 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,25.0,10), (@l,2,25.0,10), (@l,3,25.0,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'sq',CURDATE() - INTERVAL 24 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,70.0,8), (@l,2,70.0,8), (@l,3,70.0,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'pc',CURDATE() - INTERVAL 24 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,120.0,12), (@l,2,120.0,10), (@l,3,120.0,10);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'md',CURDATE() - INTERVAL 24 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,60.0,15), (@l,2,60.0,15), (@l,3,60.0,12);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'dc',CURDATE() - INTERVAL 23 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,60.0,10), (@l,2,60.0,10), (@l,3,60.0,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'dm',CURDATE() - INTERVAL 23 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,35.0,10), (@l,2,35.0,8), (@l,3,35.0,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'tp',CURDATE() - INTERVAL 23 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,25.0,12), (@l,2,25.0,12), (@l,3,25.0,10);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'rb',CURDATE() - INTERVAL 21 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,50.0,10), (@l,2,50.0,10), (@l,3,50.0,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'tv',CURDATE() - INTERVAL 21 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,45.0,12), (@l,2,45.0,10), (@l,3,45.0,10);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'cb',CURDATE() - INTERVAL 21 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,25.0,10), (@l,2,25.0,10), (@l,3,25.0,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'sq',CURDATE() - INTERVAL 19 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,70.0,8), (@l,2,70.0,8), (@l,3,70.0,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'pc',CURDATE() - INTERVAL 19 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,120.0,12), (@l,2,120.0,10), (@l,3,120.0,10);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'md',CURDATE() - INTERVAL 19 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,60.0,15), (@l,2,60.0,15), (@l,3,60.0,12);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'dc',CURDATE() - INTERVAL 17 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,60.0,10), (@l,2,60.0,10), (@l,3,60.0,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'dm',CURDATE() - INTERVAL 17 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,35.0,10), (@l,2,35.0,8), (@l,3,35.0,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'tp',CURDATE() - INTERVAL 17 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,25.0,12), (@l,2,25.0,12), (@l,3,25.0,10);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'rb',CURDATE() - INTERVAL 16 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,52.5,10), (@l,2,52.5,10), (@l,3,52.5,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'tv',CURDATE() - INTERVAL 16 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,47.5,12), (@l,2,47.5,10), (@l,3,47.5,10);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'cb',CURDATE() - INTERVAL 16 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,27.5,10), (@l,2,27.5,10), (@l,3,27.5,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'sq',CURDATE() - INTERVAL 14 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,72.5,8), (@l,2,72.5,8), (@l,3,72.5,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'pc',CURDATE() - INTERVAL 14 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,122.5,12), (@l,2,122.5,10), (@l,3,122.5,10);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'md',CURDATE() - INTERVAL 14 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,62.5,15), (@l,2,62.5,15), (@l,3,62.5,12);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'dc',CURDATE() - INTERVAL 12 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,62.5,10), (@l,2,62.5,10), (@l,3,62.5,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'dm',CURDATE() - INTERVAL 12 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,37.5,10), (@l,2,37.5,8), (@l,3,37.5,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'tp',CURDATE() - INTERVAL 12 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,27.5,12), (@l,2,27.5,12), (@l,3,27.5,10);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'rb',CURDATE() - INTERVAL 10 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,52.5,10), (@l,2,52.5,10), (@l,3,52.5,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'tv',CURDATE() - INTERVAL 10 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,47.5,12), (@l,2,47.5,10), (@l,3,47.5,10);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'cb',CURDATE() - INTERVAL 10 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,27.5,10), (@l,2,27.5,10), (@l,3,27.5,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'sq',CURDATE() - INTERVAL 9 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,72.5,8), (@l,2,72.5,8), (@l,3,72.5,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'pc',CURDATE() - INTERVAL 9 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,122.5,12), (@l,2,122.5,10), (@l,3,122.5,10);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'md',CURDATE() - INTERVAL 9 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,62.5,15), (@l,2,62.5,15), (@l,3,62.5,12);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'dc',CURDATE() - INTERVAL 7 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,62.5,10), (@l,2,62.5,10), (@l,3,62.5,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'dm',CURDATE() - INTERVAL 7 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,37.5,10), (@l,2,37.5,8), (@l,3,37.5,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'tp',CURDATE() - INTERVAL 7 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,27.5,12), (@l,2,27.5,12), (@l,3,27.5,10);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'rb',CURDATE() - INTERVAL 5 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,52.5,10), (@l,2,52.5,10), (@l,3,52.5,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'tv',CURDATE() - INTERVAL 5 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,47.5,12), (@l,2,47.5,10), (@l,3,47.5,10);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'cb',CURDATE() - INTERVAL 5 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,27.5,10), (@l,2,27.5,10), (@l,3,27.5,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'sq',CURDATE() - INTERVAL 4 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,75.0,8), (@l,2,75.0,8), (@l,3,75.0,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'pc',CURDATE() - INTERVAL 4 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,125.0,12), (@l,2,125.0,10), (@l,3,125.0,10);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'md',CURDATE() - INTERVAL 4 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,65.0,15), (@l,2,65.0,15), (@l,3,65.0,12);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'dc',CURDATE() - INTERVAL 2 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,65.0,10), (@l,2,65.0,10), (@l,3,65.0,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'dm',CURDATE() - INTERVAL 2 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,40.0,10), (@l,2,40.0,8), (@l,3,40.0,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'tp',CURDATE() - INTERVAL 2 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,30.0,12), (@l,2,30.0,12), (@l,3,30.0,10);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'rb',CURDATE() - INTERVAL 1 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,55.0,10), (@l,2,55.0,10), (@l,3,55.0,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'tv',CURDATE() - INTERVAL 1 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,50.0,12), (@l,2,50.0,10), (@l,3,50.0,10);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'cb',CURDATE() - INTERVAL 1 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,30.0,10), (@l,2,30.0,10), (@l,3,30.0,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'sq',CURDATE() - INTERVAL 0 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,75.0,8), (@l,2,75.0,8), (@l,3,75.0,8);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'pc',CURDATE() - INTERVAL 0 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,125.0,12), (@l,2,125.0,10), (@l,3,125.0,10);
INSERT INTO workout_logs (user_id,exercise_id,log_date,note) VALUES (@uid,'md',CURDATE() - INTERVAL 0 DAY,''); SET @l = LAST_INSERT_ID();
INSERT INTO log_sets (log_id,set_number,weight_kg,reps) VALUES (@l,1,65.0,15), (@l,2,65.0,15), (@l,3,65.0,12);

INSERT INTO favorites (user_id,exercise_id) VALUES (@uid,'dc'),(@uid,'sq'),(@uid,'tr');
INSERT INTO programs (user_id,name,goal) VALUES (@uid,'Push Pull Legs','Hypertrophie'); SET @p = LAST_INSERT_ID();
INSERT INTO program_days (program_id,position,name) VALUES (@p,1,'Push'); SET @d = LAST_INSERT_ID();
INSERT INTO program_items (day_id,position,exercise_id,target_sets,target_reps) VALUES (@d,1,'dc',3,10), (@d,2,'dm',3,10), (@d,3,'tp',3,10);
INSERT INTO program_days (program_id,position,name) VALUES (@p,2,'Pull'); SET @d = LAST_INSERT_ID();
INSERT INTO program_items (day_id,position,exercise_id,target_sets,target_reps) VALUES (@d,1,'rb',3,10), (@d,2,'tv',3,10), (@d,3,'cb',3,10);
INSERT INTO program_days (program_id,position,name) VALUES (@p,3,'Legs'); SET @d = LAST_INSERT_ID();
INSERT INTO program_items (day_id,position,exercise_id,target_sets,target_reps) VALUES (@d,1,'sq',3,10), (@d,2,'pc',3,10), (@d,3,'md',3,10);

-- ===== Forum : membres fictifs, publications, commentaires et j'aime =====
INSERT INTO users (username,email,password_hash,display_name,goal,onboarding_done) VALUES
 ('karim_m','karim@gym-squale.ma','$2y$10$GcSMm4ik63IYsNBsERGFxen79wgYfDe0xhD31GoYe1Xfq/nbLnXpm','Karim','Gagner en force',1),
 ('salma_fit','salma@gym-squale.ma','$2y$10$GcSMm4ik63IYsNBsERGFxen79wgYfDe0xhD31GoYe1Xfq/nbLnXpm','Salma','Prendre du muscle',1),
 ('youssef_bjj','youssef@gym-squale.ma','$2y$10$GcSMm4ik63IYsNBsERGFxen79wgYfDe0xhD31GoYe1Xfq/nbLnXpm','Youssef','Améliorer mon endurance',1);
SET @karim = (SELECT id FROM users WHERE username='karim_m');
SET @salma = (SELECT id FROM users WHERE username='salma_fit');
SET @youssef = (SELECT id FROM users WHERE username='youssef_bjj');

INSERT INTO posts (user_id,category,body,attachment,created_at) VALUES
 (@karim,'Progression','Nouveau record au squat après 6 semaines de programme force. Les 5x5 payent enfin !',CONCAT('{"ex":"sq","date":"',CURDATE() - INTERVAL 1 DAY,'","sets":[{"w":100,"r":5},{"w":100,"r":5},{"w":100,"r":5},{"w":100,"r":5},{"w":105,"r":3}]}'),NOW() - INTERVAL 26 HOUR);
SET @p1 = LAST_INSERT_ID();
INSERT INTO posts (user_id,category,body,attachment,created_at) VALUES
 (@salma,'Question','Vous faites combien de séries pour les fessiers par semaine ? Je plafonne sur le hip thrust depuis un mois.',NULL,NOW() - INTERVAL 9 HOUR);
SET @p2 = LAST_INSERT_ID();
INSERT INTO posts (user_id,category,body,attachment,created_at) VALUES
 (@youssef,'Conseil','Entre le jiu-jitsu et la muscu, je garde un jour complet de repos avant les gros entraînements. Mon dos me remercie.',NULL,NOW() - INTERVAL 5 HOUR);
SET @p3 = LAST_INSERT_ID();
INSERT INTO posts (user_id,category,body,attachment,created_at) VALUES
 (@uid,'Progression','4 semaines de Push Pull Legs : +15 kg au développé couché. Le suivi dans l’appli aide vraiment à rester régulier.',CONCAT('{"ex":"dc","date":"',CURDATE() - INTERVAL 2 DAY,'","sets":[{"w":65,"r":10},{"w":65,"r":10},{"w":65,"r":8}]}'),NOW() - INTERVAL 2 HOUR);
SET @p4 = LAST_INSERT_ID();

INSERT INTO post_comments (post_id,user_id,body,created_at) VALUES
 (@p1,@salma,'Bravo, ça donne envie de passer sur du 5x5 !',NOW() - INTERVAL 20 HOUR),
 (@p1,@uid,'Énorme progression, continue comme ça.',NOW() - INTERVAL 18 HOUR),
 (@p2,@karim,'Je fais 10 à 12 séries réparties sur 2 séances, avec une charge qui monte doucement.',NOW() - INTERVAL 7 HOUR),
 (@p2,@youssef,'Pense à varier : fentes bulgares en plus du hip thrust.',NOW() - INTERVAL 6 HOUR),
 (@p4,@karim,'Beau travail, la régularité fait tout.',NOW() - INTERVAL 1 HOUR);
INSERT INTO post_likes (post_id,user_id) VALUES
 (@p1,@salma),(@p1,@youssef),(@p1,@uid),(@p2,@karim),(@p3,@salma),(@p3,@karim),(@p3,@uid),(@p4,@karim),(@p4,@salma),(@p4,@youssef);

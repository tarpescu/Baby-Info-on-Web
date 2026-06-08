--  10 familii, 50 useri, 15 copii
--  Parola pentru toti: Test1234!

-- USERS
INSERT INTO users (id, first_name, last_name, email, password_hash, role, avatar_color) VALUES
(1,  'Ana',       'Ionescu',    'ana.ionescu@mail.ro',       '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner',     'c1'),
(2,  'Mihai',     'Ionescu',    'mihai.ionescu@mail.ro',     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coparent',  'c4'),
(3,  'Maria',     'Ionescu',    'bunica.maria@mail.ro',      '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer',    'c2'),
(4,  'Ion',       'Ionescu',    'bunic.ion@mail.ro',         '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer',    'c4'),
(5,  'Elena',     'Popa',       'bona.elena@mail.ro',        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'caregiver', 'c3'),
(6,  'Ioana',     'Popescu',    'ioana.popescu@mail.ro',     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner',     'c2'),
(7,  'Andrei',    'Popescu',    'andrei.popescu@mail.ro',    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coparent',  'c5'),
(8,  'Rodica',    'Marin',      'rodica.marin@mail.ro',      '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer',    'c1'),
(9,  'Gheorghe',  'Marin',      'gheorghe.marin@mail.ro',    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer',    'c4'),
(10, 'Simona',    'Dinu',       'bona.simona@mail.ro',       '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'caregiver', 'c6'),
(11, 'Laura',     'Constantin', 'laura.constantin@mail.ro',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner',     'c3'),
(12, 'Bogdan',    'Constantin', 'bogdan.constantin@mail.ro', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coparent',  'c1'),
(13, 'Valentina', 'Stan',       'valentina.stan@mail.ro',    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer',    'c2'),
(14, 'Nicolae',   'Stan',       'nicolae.stan@mail.ro',      '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer',    'c5'),
(15, 'Alina',     'Radu',       'bona.alina@mail.ro',        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'caregiver', 'c3'),
(16, 'Cristina',  'Dumitrescu', 'cristina.d@mail.ro',        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner',     'c5'),
(17, 'Florin',    'Dumitrescu', 'florin.d@mail.ro',          '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coparent',  'c4'),
(18, 'Maricica',  'Dumitrescu', 'maricica.d@mail.ro',        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer',    'c1'),
(19, 'Vasile',    'Dumitrescu', 'vasile.d@mail.ro',          '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer',    'c6'),
(20, 'Mihaela',   'Toma',       'bona.mihaela@mail.ro',      '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'caregiver', 'c2'),
(21, 'Daniela',   'Popa',       'daniela.popa@mail.ro',      '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner',     'c6'),
(22, 'Catalin',   'Popa',       'catalin.popa@mail.ro',      '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coparent',  'c3'),
(23, 'Elisabeta', 'Gheorghe',   'elisabeta.g@mail.ro',       '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer',    'c5'),
(24, 'Dumitru',   'Gheorghe',   'dumitru.g@mail.ro',         '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer',    'c2'),
(25, 'Roxana',    'Neagu',      'bona.roxana@mail.ro',       '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'caregiver', 'c4'),
(26, 'Gabriela',  'Stanescu',   'gabriela.s@mail.ro',        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner',     'c1'),
(27, 'Razvan',    'Stanescu',   'razvan.s@mail.ro',          '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coparent',  'c6'),
(28, 'Cornelia',  'Voicu',      'cornelia.v@mail.ro',        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer',    'c3'),
(29, 'Traian',    'Voicu',      'traian.v@mail.ro',          '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer',    'c4'),
(30, 'Andreea',   'Ilie',       'bona.andreea@mail.ro',      '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'caregiver', 'c5'),
(31, 'Raluca',    'Moldovan',   'raluca.m@mail.ro',          '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner',     'c2'),
(32, 'Sorin',     'Moldovan',   'sorin.m@mail.ro',           '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coparent',  'c1'),
(33, 'Doina',     'Crisan',     'doina.c@mail.ro',           '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer',    'c6'),
(34, 'Petru',     'Crisan',     'petru.c@mail.ro',           '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer',    'c3'),
(35, 'Lidia',     'Bota',       'bona.lidia@mail.ro',        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'caregiver', 'c2'),
(36, 'Carmen',    'Nistor',     'carmen.n@mail.ro',          '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner',     'c4'),
(37, 'Marius',    'Nistor',     'marius.n@mail.ro',          '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coparent',  'c5'),
(38, 'Viorica',   'Badea',      'viorica.b@mail.ro',         '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer',    'c1'),
(39, 'Costica',   'Badea',      'costica.b@mail.ro',         '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer',    'c2'),
(40, 'Florentina','Matei',      'bona.florentina@mail.ro',   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'caregiver', 'c6'),
(41, 'Oana',      'Serban',     'oana.serban@mail.ro',       '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner',     'c3'),
(42, 'Alexandru', 'Serban',     'alex.serban@mail.ro',       '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coparent',  'c6'),
(43, 'Lucretia',  'Florescu',   'lucretia.f@mail.ro',        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer',    'c4'),
(44, 'Aurel',     'Florescu',   'aurel.f@mail.ro',           '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer',    'c5'),
(45, 'Mariana',   'Pascu',      'bona.mariana@mail.ro',      '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'caregiver', 'c1'),
(46, 'Teodora',   'Draghici',   'teodora.d@mail.ro',         '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner',     'c5'),
(47, 'Octavian',  'Draghici',   'octavian.d@mail.ro',        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coparent',  'c2'),
(48, 'Paraschiva','Nedelcu',    'paraschiva.n@mail.ro',      '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer',    'c3'),
(49, 'Grigore',   'Nedelcu',    'grigore.n@mail.ro',         '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer',    'c6'),
(50, 'Nicoleta',  'Coman',      'bona.nicoleta@mail.ro',     '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'caregiver', 'c4'),
(51, 'Sergiu',    'Tarpescu',   'tarpescu@email.com',        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner',     'c1')
ON CONFLICT (id) DO NOTHING;

UPDATE users
SET password_hash = '$2y$12$eMrC4k0bsfEy9ykvljO9Ve8RJhl0rQLs4fvSk2u/Ga0Wie5fdY2vO';

-- Cont super-admin dedicat (email: tarpescu@email.com, parola: Sergiu123!)
-- Rulat dupa UPDATE-ul global ca sa nu fie suprascris.
UPDATE users
SET is_superadmin = TRUE,
    password_hash = '$2y$12$/O/J51Aej4GkdsiBjq7E1uIK0SStLkuSLTCC1hxEjpIODIVvxTB/m'
WHERE email = 'tarpescu@email.com';

-- CHILDREN
INSERT INTO children (id, first_name, last_name, date_of_birth, gender, blood_type, avatar_color, created_by) VALUES
(1,  'Mila',    'Ionescu',    '2024-10-12', 'F', 'A+',  'c1', 1),
(2,  'Alex',    'Ionescu',    '2022-06-03', 'M', 'A+',  'c4', 1),
(3,  'Sofia',   'Popescu',    '2025-01-20', 'F', 'B+',  'c2', 6),
(4,  'Luca',    'Constantin', '2024-03-15', 'M', 'O+',  'c3', 11),
(5,  'Emma',    'Constantin', '2022-11-28', 'F', 'O+',  'c5', 11),
(6,  'David',   'Dumitrescu', '2024-07-04', 'M', 'AB+', 'c6', 16),
(7,  'Sara',    'Popa',       '2024-12-01', 'F', 'A-',  'c2', 21),
(8,  'Matei',   'Popa',       '2023-04-18', 'M', 'A-',  'c4', 21),
(9,  'Daria',   'Stanescu',   '2025-02-14', 'F', 'B-',  'c1', 26),
(10, 'Victor',  'Moldovan',   '2024-05-22', 'M', 'O-',  'c3', 31),
(11, 'Ines',    'Moldovan',   '2022-09-10', 'F', 'O-',  'c6', 31),
(12, 'Patrick', 'Nistor',     '2024-08-30', 'M', 'A+',  'c5', 36),
(13, 'Iris',    'Serban',     '2025-03-05', 'F', 'B+',  'c2', 41),
(14, 'Rares',   'Draghici',   '2024-01-17', 'M', 'AB-', 'c4', 46),
(15, 'Zoe',     'Draghici',   '2023-07-25', 'F', 'AB-', 'c1', 46)
ON CONFLICT (id) DO NOTHING;

-- FAMILY MEMBERS
INSERT INTO family_members (child_id, user_id, permission) VALUES
(1,1,'owner'),(1,2,'coparent'),(1,3,'viewer'),(1,4,'viewer'),(1,5,'caregiver'),
(2,1,'owner'),(2,2,'coparent'),(2,3,'viewer'),(2,4,'viewer'),
(3,6,'owner'),(3,7,'coparent'),(3,8,'viewer'),(3,9,'viewer'),(3,10,'caregiver'),
(4,11,'owner'),(4,12,'coparent'),(4,13,'viewer'),(4,14,'viewer'),(4,15,'caregiver'),
(5,11,'owner'),(5,12,'coparent'),(5,13,'viewer'),
(6,16,'owner'),(6,17,'coparent'),(6,18,'viewer'),(6,19,'viewer'),(6,20,'caregiver'),
(7,21,'owner'),(7,22,'coparent'),(7,23,'viewer'),(7,24,'viewer'),(7,25,'caregiver'),
(8,21,'owner'),(8,22,'coparent'),(8,23,'viewer'),
(9,26,'owner'),(9,27,'coparent'),(9,28,'viewer'),(9,29,'viewer'),(9,30,'caregiver'),
(10,31,'owner'),(10,32,'coparent'),(10,33,'viewer'),(10,34,'viewer'),(10,35,'caregiver'),
(11,31,'owner'),(11,32,'coparent'),(11,33,'viewer'),
(12,36,'owner'),(12,37,'coparent'),(12,38,'viewer'),(12,39,'viewer'),(12,40,'caregiver'),
(13,41,'owner'),(13,42,'coparent'),(13,43,'viewer'),(13,44,'viewer'),(13,45,'caregiver'),
(14,46,'owner'),(14,47,'coparent'),(14,48,'viewer'),(14,49,'viewer'),(14,50,'caregiver'),
(15,46,'owner'),(15,47,'coparent'),(15,48,'viewer')
ON CONFLICT (child_id, user_id) DO NOTHING;

-- FEEDINGS — Mila (child_id=1)
INSERT INTO feedings (child_id, logged_by, type, side, duration_min, amount_ml, food_desc, fed_at) VALUES
(1,1,'breast','L',18,NULL,NULL,NOW() - INTERVAL '13 days'),
(1,2,'bottle',NULL,NULL,120,NULL,NOW() - INTERVAL '13 days' + INTERVAL '3 hours'),
(1,1,'solids',NULL,NULL,NULL,'Piure morcov',NOW() - INTERVAL '13 days' + INTERVAL '5 hours'),
(1,1,'breast','R',14,NULL,NULL,NOW() - INTERVAL '13 days' + INTERVAL '7 hours'),
(1,2,'bottle',NULL,NULL,100,NULL,NOW() - INTERVAL '13 days' + INTERVAL '10 hours'),
(1,1,'breast','L',20,NULL,NULL,NOW() - INTERVAL '13 days' + INTERVAL '14 hours'),
(1,1,'breast','R',16,NULL,NULL,NOW() - INTERVAL '11 days'),
(1,2,'bottle',NULL,NULL,130,NULL,NOW() - INTERVAL '11 days' + INTERVAL '3 hours'),
(1,1,'solids',NULL,NULL,NULL,'Piure cartof dulce + para',NOW() - INTERVAL '11 days' + INTERVAL '5 hours'),
(1,1,'breast','L',18,NULL,NULL,NOW() - INTERVAL '11 days' + INTERVAL '8 hours'),
(1,5,'bottle',NULL,NULL,110,NULL,NOW() - INTERVAL '11 days' + INTERVAL '10 hours'),
(1,1,'breast','R',22,NULL,NULL,NOW() - INTERVAL '11 days' + INTERVAL '14 hours'),
(1,1,'breast','L',15,NULL,NULL,NOW() - INTERVAL '9 days'),
(1,2,'bottle',NULL,NULL,120,NULL,NOW() - INTERVAL '9 days' + INTERVAL '3 hours'),
(1,1,'solids',NULL,NULL,NULL,'Ovaz cu banana',NOW() - INTERVAL '9 days' + INTERVAL '5 hours'),
(1,1,'breast','R',17,NULL,NULL,NOW() - INTERVAL '9 days' + INTERVAL '8 hours'),
(1,5,'bottle',NULL,NULL,100,NULL,NOW() - INTERVAL '9 days' + INTERVAL '10 hours'),
(1,1,'breast','L',19,NULL,NULL,NOW() - INTERVAL '9 days' + INTERVAL '14 hours'),
(1,1,'breast','R',14,NULL,NULL,NOW() - INTERVAL '7 days'),
(1,2,'bottle',NULL,NULL,140,NULL,NOW() - INTERVAL '7 days' + INTERVAL '3 hours'),
(1,1,'solids',NULL,NULL,NULL,'Piure dovlecel',NOW() - INTERVAL '7 days' + INTERVAL '5 hours'),
(1,1,'breast','L',16,NULL,NULL,NOW() - INTERVAL '7 days' + INTERVAL '8 hours'),
(1,5,'bottle',NULL,NULL,120,NULL,NOW() - INTERVAL '7 days' + INTERVAL '10 hours'),
(1,1,'breast','R',21,NULL,NULL,NOW() - INTERVAL '7 days' + INTERVAL '14 hours'),
(1,1,'breast','L',18,NULL,NULL,NOW() - INTERVAL '5 days'),
(1,2,'bottle',NULL,NULL,120,NULL,NOW() - INTERVAL '5 days' + INTERVAL '3 hours'),
(1,1,'solids',NULL,NULL,NULL,'Piure broccoli',NOW() - INTERVAL '5 days' + INTERVAL '5 hours'),
(1,1,'breast','R',14,NULL,NULL,NOW() - INTERVAL '5 days' + INTERVAL '8 hours'),
(1,5,'bottle',NULL,NULL,100,NULL,NOW() - INTERVAL '5 days' + INTERVAL '10 hours'),
(1,1,'breast','L',20,NULL,NULL,NOW() - INTERVAL '5 days' + INTERVAL '14 hours'),
(1,1,'breast','R',17,NULL,NULL,NOW() - INTERVAL '3 days'),
(1,2,'bottle',NULL,NULL,130,NULL,NOW() - INTERVAL '3 days' + INTERVAL '3 hours'),
(1,1,'solids',NULL,NULL,NULL,'Piure mere',NOW() - INTERVAL '3 days' + INTERVAL '5 hours'),
(1,1,'breast','L',15,NULL,NULL,NOW() - INTERVAL '3 days' + INTERVAL '8 hours'),
(1,5,'bottle',NULL,NULL,110,NULL,NOW() - INTERVAL '3 days' + INTERVAL '10 hours'),
(1,1,'breast','R',22,NULL,NULL,NOW() - INTERVAL '3 days' + INTERVAL '14 hours'),
(1,1,'breast','L',18,NULL,NULL,NOW() - INTERVAL '1 days'),
(1,2,'bottle',NULL,NULL,120,NULL,NOW() - INTERVAL '1 days' + INTERVAL '3 hours'),
(1,1,'solids',NULL,NULL,NULL,'Piure spanac + linte',NOW() - INTERVAL '1 days' + INTERVAL '5 hours'),
(1,1,'breast','R',16,NULL,NULL,NOW() - INTERVAL '1 days' + INTERVAL '8 hours'),
(1,5,'bottle',NULL,NULL,100,NULL,NOW() - INTERVAL '1 days' + INTERVAL '10 hours'),
(1,1,'breast','L',19,NULL,NULL,NOW() - INTERVAL '1 days' + INTERVAL '14 hours'),
(1,1,'breast','R',15,NULL,NULL,NOW()),
(1,2,'bottle',NULL,NULL,120,NULL,NOW() + INTERVAL '3 hours'),
(1,1,'solids',NULL,NULL,NULL,'Piure morcov + cartof',NOW() + INTERVAL '5 hours');

-- FEEDINGS — Sofia (child_id=3)
INSERT INTO feedings (child_id, logged_by, type, side, duration_min, amount_ml, fed_at) VALUES
(3,6,'breast','L',20,NULL,NOW() - INTERVAL '5 days'),
(3,6,'breast','R',18,NULL,NOW() - INTERVAL '5 days' + INTERVAL '3 hours'),
(3,7,'bottle',NULL,NULL,80,NOW() - INTERVAL '5 days' + INTERVAL '6 hours'),
(3,6,'breast','L',22,NULL,NOW() - INTERVAL '5 days' + INTERVAL '9 hours'),
(3,6,'breast','R',19,NULL,NOW() - INTERVAL '5 days' + INTERVAL '13 hours'),
(3,6,'breast','L',17,NULL,NOW() - INTERVAL '2 days'),
(3,6,'breast','R',20,NULL,NOW() - INTERVAL '2 days' + INTERVAL '3 hours'),
(3,7,'bottle',NULL,NULL,90,NOW() - INTERVAL '2 days' + INTERVAL '6 hours'),
(3,6,'breast','L',18,NULL,NOW() - INTERVAL '2 days' + INTERVAL '9 hours'),
(3,6,'breast','R',21,NULL,NOW() - INTERVAL '2 days' + INTERVAL '13 hours');

-- SLEEP LOGS — Mila (child_id=1)
INSERT INTO sleep_logs (child_id, logged_by, type, started_at, ended_at, quality) VALUES
(1,1,'night',NOW() - INTERVAL '14 days',NOW() - INTERVAL '13 days' + INTERVAL '5 hours',4),
(1,1,'nap',  NOW() - INTERVAL '13 days' + INTERVAL '3 hours',NOW() - INTERVAL '13 days' + INTERVAL '4 hours 30 minutes',5),
(1,1,'nap',  NOW() - INTERVAL '13 days' + INTERVAL '7 hours',NOW() - INTERVAL '13 days' + INTERVAL '8 hours 15 minutes',4),
(1,1,'night',NOW() - INTERVAL '13 days' + INTERVAL '14 hours',NOW() - INTERVAL '12 days' + INTERVAL '5 hours',5),
(1,1,'nap',  NOW() - INTERVAL '12 days' + INTERVAL '3 hours',NOW() - INTERVAL '12 days' + INTERVAL '4 hours 30 minutes',4),
(1,1,'nap',  NOW() - INTERVAL '12 days' + INTERVAL '7 hours',NOW() - INTERVAL '12 days' + INTERVAL '8 hours 15 minutes',5),
(1,1,'night',NOW() - INTERVAL '12 days' + INTERVAL '14 hours',NOW() - INTERVAL '11 days' + INTERVAL '5 hours',3),
(1,1,'nap',  NOW() - INTERVAL '11 days' + INTERVAL '3 hours',NOW() - INTERVAL '11 days' + INTERVAL '4 hours 30 minutes',4),
(1,1,'nap',  NOW() - INTERVAL '11 days' + INTERVAL '8 hours',NOW() - INTERVAL '11 days' + INTERVAL '9 hours 15 minutes',4),
(1,1,'night',NOW() - INTERVAL '11 days' + INTERVAL '14 hours',NOW() - INTERVAL '10 days' + INTERVAL '5 hours 45 minutes',5),
(1,1,'nap',  NOW() - INTERVAL '10 days' + INTERVAL '3 hours',NOW() - INTERVAL '10 days' + INTERVAL '4 hours 30 minutes',5),
(1,1,'nap',  NOW() - INTERVAL '10 days' + INTERVAL '7 hours',NOW() - INTERVAL '10 days' + INTERVAL '8 hours',3),
(1,1,'night',NOW() - INTERVAL '10 days' + INTERVAL '14 hours',NOW() - INTERVAL '9 days' + INTERVAL '5 hours 30 minutes',4),
(1,1,'nap',  NOW() - INTERVAL '9 days' + INTERVAL '3 hours',NOW() - INTERVAL '9 days' + INTERVAL '4 hours 15 minutes',4),
(1,1,'night',NOW() - INTERVAL '9 days' + INTERVAL '14 hours',NOW() - INTERVAL '8 days' + INTERVAL '6 hours',5),
(1,1,'nap',  NOW() - INTERVAL '8 days' + INTERVAL '3 hours 30 minutes',NOW() - INTERVAL '8 days' + INTERVAL '5 hours',5),
(1,1,'nap',  NOW() - INTERVAL '8 days' + INTERVAL '8 hours',NOW() - INTERVAL '8 days' + INTERVAL '9 hours',4),
(1,1,'night',NOW() - INTERVAL '8 days' + INTERVAL '14 hours',NOW() - INTERVAL '7 days' + INTERVAL '5 hours 15 minutes',4),
(1,1,'nap',  NOW() - INTERVAL '7 days' + INTERVAL '3 hours',NOW() - INTERVAL '7 days' + INTERVAL '4 hours 30 minutes',5),
(1,1,'nap',  NOW() - INTERVAL '7 days' + INTERVAL '7 hours',NOW() - INTERVAL '7 days' + INTERVAL '8 hours 15 minutes',4),
(1,1,'night',NOW() - INTERVAL '7 days' + INTERVAL '14 hours',NOW() - INTERVAL '6 days' + INTERVAL '5 hours 30 minutes',5),
(1,1,'night',NOW() - INTERVAL '6 days' + INTERVAL '14 hours',NOW() - INTERVAL '5 days' + INTERVAL '5 hours',3),
(1,1,'nap',  NOW() - INTERVAL '5 days' + INTERVAL '3 hours',NOW() - INTERVAL '5 days' + INTERVAL '4 hours 30 minutes',4),
(1,1,'night',NOW() - INTERVAL '5 days' + INTERVAL '14 hours',NOW() - INTERVAL '4 days' + INTERVAL '5 hours 30 minutes',5),
(1,1,'nap',  NOW() - INTERVAL '4 days' + INTERVAL '3 hours',NOW() - INTERVAL '4 days' + INTERVAL '4 hours 30 minutes',4),
(1,1,'night',NOW() - INTERVAL '4 days' + INTERVAL '14 hours',NOW() - INTERVAL '3 days' + INTERVAL '5 hours 15 minutes',4),
(1,1,'nap',  NOW() - INTERVAL '3 days' + INTERVAL '3 hours',NOW() - INTERVAL '3 days' + INTERVAL '4 hours 30 minutes',5),
(1,1,'nap',  NOW() - INTERVAL '3 days' + INTERVAL '7 hours',NOW() - INTERVAL '3 days' + INTERVAL '8 hours',4),
(1,1,'night',NOW() - INTERVAL '3 days' + INTERVAL '14 hours',NOW() - INTERVAL '2 days' + INTERVAL '5 hours 45 minutes',5),
(1,1,'night',NOW() - INTERVAL '2 days' + INTERVAL '14 hours',NOW() - INTERVAL '1 days' + INTERVAL '5 hours 30 minutes',4),
(1,1,'nap',  NOW() - INTERVAL '1 days' + INTERVAL '3 hours',NOW() - INTERVAL '1 days' + INTERVAL '4 hours 30 minutes',5),
(1,1,'nap',  NOW() - INTERVAL '1 days' + INTERVAL '7 hours',NOW() - INTERVAL '1 days' + INTERVAL '8 hours',3),
(1,1,'night',NOW() - INTERVAL '1 days' + INTERVAL '14 hours',NOW() + INTERVAL '5 hours 15 minutes',5),
(1,1,'nap',  NOW() + INTERVAL '3 hours',NOW() + INTERVAL '4 hours 30 minutes',4);

-- GROWTH — Mila (child_id=1)
INSERT INTO growth (child_id, logged_by, weight_kg, height_cm, head_cm, measured_at) VALUES
(1,1,3.4,50.0,34.0,CURRENT_DATE - INTERVAL '210 days'),
(1,1,4.2,53.5,36.5,CURRENT_DATE - INTERVAL '180 days'),
(1,1,5.1,57.0,38.0,CURRENT_DATE - INTERVAL '150 days'),
(1,1,5.9,60.0,39.5,CURRENT_DATE - INTERVAL '120 days'),
(1,1,6.7,62.5,40.5,CURRENT_DATE - INTERVAL '90 days'),
(1,1,7.4,65.0,41.5,CURRENT_DATE - INTERVAL '60 days'),
(1,1,8.0,67.0,42.0,CURRENT_DATE - INTERVAL '30 days'),
(1,1,8.4,68.0,42.5,CURRENT_DATE);

-- GROWTH — ceilalti copii
INSERT INTO growth (child_id, logged_by, weight_kg, height_cm, measured_at) VALUES
(2,1,8.5,68.0,CURRENT_DATE - INTERVAL '365 days'),
(2,1,9.8,72.0,CURRENT_DATE - INTERVAL '300 days'),
(2,1,11.2,78.0,CURRENT_DATE - INTERVAL '240 days'),
(2,1,12.0,82.0,CURRENT_DATE - INTERVAL '180 days'),
(2,1,12.8,86.0,CURRENT_DATE - INTERVAL '90 days'),
(2,1,13.5,90.0,CURRENT_DATE),
(3,6,3.2,50.0,CURRENT_DATE - INTERVAL '112 days'),
(3,6,4.5,55.0,CURRENT_DATE - INTERVAL '82 days'),
(3,6,5.8,59.0,CURRENT_DATE - INTERVAL '52 days'),
(3,6,6.5,62.0,CURRENT_DATE - INTERVAL '22 days'),
(4,11,5.0,58.0,CURRENT_DATE - INTERVAL '426 days'),
(4,11,7.0,65.0,CURRENT_DATE - INTERVAL '366 days'),
(4,11,8.5,70.0,CURRENT_DATE - INTERVAL '306 days'),
(4,11,9.8,75.0,CURRENT_DATE - INTERVAL '246 days'),
(4,11,10.5,78.0,CURRENT_DATE - INTERVAL '186 days'),
(6,16,3.8,52.0,CURRENT_DATE - INTERVAL '312 days'),
(6,16,5.5,59.0,CURRENT_DATE - INTERVAL '282 days'),
(6,16,7.0,64.0,CURRENT_DATE - INTERVAL '252 days'),
(6,16,8.2,68.0,CURRENT_DATE - INTERVAL '222 days'),
(10,31,4.5,56.0,CURRENT_DATE - INTERVAL '356 days'),
(10,31,6.2,62.0,CURRENT_DATE - INTERVAL '296 days'),
(10,31,7.8,67.0,CURRENT_DATE - INTERVAL '236 days'),
(10,31,9.0,72.0,CURRENT_DATE - INTERVAL '176 days'),
(12,36,3.6,51.0,CURRENT_DATE - INTERVAL '256 days'),
(12,36,5.2,58.0,CURRENT_DATE - INTERVAL '226 days'),
(12,36,6.8,63.0,CURRENT_DATE - INTERVAL '196 days'),
(12,36,8.0,67.0,CURRENT_DATE - INTERVAL '166 days');

-- MOMENTS
INSERT INTO moments (child_id, logged_by, type, title, body, is_pinned, is_shared, happened_at) VALUES
(1,1,'milestone','Prima data cand a tarât','Mila a tarât pentru prima dată! A petrecut 4 minute împingându-se înapoi pe covor, s-a uitat în jur de parcă ar fi zis wait, asta-i direcția greșită.',1,1,NOW() - INTERVAL '1 days'),
(1,2,'food','A incercat morcov piure','Trei lingurițe complete înainte să refuze politicos. Are opinii acum.',0,0,NOW() - INTERVAL '2 days'),
(1,1,'medical','Control 7 luni — Dr. Popescu','Greutate 8.4kg percentila 62, înălțime 68cm. Toate vaccinurile la zi.',0,1,NOW() - INTERVAL '5 days'),
(1,2,'photo','Prima baie in cada mare','Pură bucurie. A bătut atât de tare din picioare că podeaua băii a luat și ea un duș.',0,1,NOW() - INTERVAL '7 days'),
(1,1,'milestone','A stat in sezut fara sprijin','30 de secunde! Apoi a căzut ușor pe câine, care a acceptat situația cu demnitate.',1,1,NOW() - INTERVAL '10 days'),
(1,2,'friends','A cunoscut-o pe Sofia','Prima întâlnire cu Sofia Popescu. S-au privit în tăcere 5 minute.',0,1,NOW() - INTERVAL '12 days'),
(1,1,'voice','A zis ceva care semana cu mama','Înregistrare atașată. Lingviștii sunt bineveniți.',0,0,NOW() - INTERVAL '14 days'),
(1,1,'milestone','Primii dinti','Primul dinte a apărut în sfârșit. Multe bave și zâmbete strâmbe.',1,1,NOW() - INTERVAL '20 days'),
(1,2,'food','Prima gustare cu ovaz','A mâncat ovăz cu banană. Fața a zis nu, dar lingurița s-a golit.',0,0,NOW() - INTERVAL '25 days'),
(1,1,'milestone','Prima noapte de 6 ore','A dormit 6 ore consecutive! Am stat amândoi treji să o urmărim pe monitor.',1,1,NOW() - INTERVAL '30 days'),
(1,1,'photo','Prima plimbare in parc','Soare, 20 grade, prima plimbare serioasă în parcul de lângă casă.',0,1,NOW() - INTERVAL '35 days'),
(1,2,'medical','Vaccin PCV doza 2','Fara reactii adverse. A plâns 30 secunde exact, apoi a adormit.',0,0,NOW() - INTERVAL '40 days'),
(2,1,'milestone','Primii pasi singur','Alex a facut 5 pasi singur astazi! A cazut la al 6-lea dar a ras.',1,1,NOW() - INTERVAL '30 days'),
(2,2,'food','Mananca singur cu lingura','Jumatate ajunge in gura, jumatate pe tricou. Progres!',0,1,NOW() - INTERVAL '45 days'),
(2,1,'milestone','Primul cuvant clar','Alex a zis clar apa astazi. Nu mama, nu tata. Apa.',0,1,NOW() - INTERVAL '60 days'),
(3,6,'milestone','Prima zambire sociala','Sofia a zambit pentru prima data la mama! Nu era gaze, era real.',1,1,NOW() - INTERVAL '60 days'),
(3,7,'photo','Sofia la 3 luni','Cea mai serioasa fata din lume. Analizeaza totul.',0,1,NOW() - INTERVAL '72 days'),
(4,11,'milestone','A rostit primul cuvant','Luca a zis clar papa astazi. Tata a plans.',1,1,NOW() - INTERVAL '15 days'),
(4,12,'food','Prima pizza zdrobita','Am zdrobit putin pizza cu rosii. A mancat tot.',0,0,NOW() - INTERVAL '20 days'),
(6,16,'milestone','Ridica capul singur','David ridica capul 45 grade la tummy time. Campion!',0,1,NOW() - INTERVAL '8 days'),
(7,21,'milestone','Prima reactie la muzica','Sara s-a oprit din plans cand a auzit Vivaldi. Om de cultura.',1,1,NOW() - INTERVAL '5 days'),
(10,31,'milestone','A urcat singur scarile','Victor a urcat toate cele 12 trepte singur. Jos si sus si jos si sus.',0,1,NOW() - INTERVAL '3 days'),
(12,36,'photo','Patrick zambeste','Prima fotografie in care zambeste cu adevarat.',1,1,NOW() - INTERVAL '10 days'),
(14,46,'milestone','Prima zi la cresa','Rares a intrat singur la cresa fara sa planga. Noi am plans.',1,1,NOW() - INTERVAL '45 days');

-- MEDICAL RECORDS
INSERT INTO medical_records (child_id, logged_by, type, title, doctor_name, clinic_name, date_at, next_date) VALUES
(1,1,'vaccine','Vaccin Hepatita B doza 1','Dr. Ionescu Maria','Clinica Sf. Maria',CURRENT_DATE - INTERVAL '210 days',NULL),
(1,1,'vaccine','Vaccin BCG','Dr. Ionescu Maria','Clinica Sf. Maria',CURRENT_DATE - INTERVAL '210 days',NULL),
(1,1,'visit','Control 1 luna','Dr. Popescu Ana','Clinica Sf. Maria',CURRENT_DATE - INTERVAL '180 days',NULL),
(1,1,'vaccine','Vaccin Rotavirus doza 1','Dr. Popescu Ana','Clinica Sf. Maria',CURRENT_DATE - INTERVAL '150 days',NULL),
(1,1,'vaccine','Vaccin DTaP-IPV-Hib doza 1','Dr. Popescu Ana','Clinica Sf. Maria',CURRENT_DATE - INTERVAL '150 days',NULL),
(1,1,'visit','Control 2 luni','Dr. Popescu Ana','Clinica Sf. Maria',CURRENT_DATE - INTERVAL '150 days',NULL),
(1,1,'vaccine','Vaccin PCV doza 1','Dr. Popescu Ana','Clinica Sf. Maria',CURRENT_DATE - INTERVAL '120 days',NULL),
(1,1,'visit','Control 4 luni','Dr. Popescu Ana','Clinica Sf. Maria',CURRENT_DATE - INTERVAL '90 days',NULL),
(1,1,'vaccine','Vaccin PCV doza 2','Dr. Popescu Ana','Clinica Sf. Maria',CURRENT_DATE - INTERVAL '40 days',NULL),
(1,1,'visit','Control 7 luni','Dr. Popescu Ana','Clinica Sf. Maria',CURRENT_DATE - INTERVAL '5 days',CURRENT_DATE + INTERVAL '25 days'),
(2,1,'visit','Control 2 ani','Dr. Popescu Ana','Clinica Sf. Maria',CURRENT_DATE - INTERVAL '90 days',NULL),
(2,1,'vaccine','Vaccin MMR','Dr. Popescu Ana','Clinica Sf. Maria',CURRENT_DATE - INTERVAL '90 days',NULL),
(3,6,'visit','Control 2 luni','Dr. Marin Radu','Clinica Providenta',CURRENT_DATE - INTERVAL '60 days',NULL),
(3,6,'vaccine','Vaccin Hepatita B doza 2','Dr. Marin Radu','Clinica Providenta',CURRENT_DATE - INTERVAL '60 days',NULL),
(4,11,'visit','Control 14 luni','Dr. Stan Vasile','Spitalul de Copii',CURRENT_DATE - INTERVAL '20 days',CURRENT_DATE + INTERVAL '100 days'),
(6,16,'visit','Control 10 luni','Dr. Toma Ion','Clinica Pediatrica',CURRENT_DATE - INTERVAL '7 days',CURRENT_DATE + INTERVAL '53 days'),
(10,31,'visit','Control 12 luni','Dr. Crisan Petru','Cabinet Dr. Crisan',CURRENT_DATE - INTERVAL '30 days',NULL),
(12,36,'vaccine','Vaccin BCG','Dr. Matei Florentina','Maternitatea',CURRENT_DATE - INTERVAL '256 days',NULL),
(14,46,'visit','Control 16 luni','Dr. Nedelcu Grigore','Clinica Sf. Maria',CURRENT_DATE - INTERVAL '14 days',CURRENT_DATE + INTERVAL '76 days');

-- RELATIONSHIPS
INSERT INTO relationships (child_id, name, relationship, group_type, age_years, avatar_color, added_by) VALUES
(1,'Leo','Var primar','family',1,'c4',1),
(1,'Maya','Vara primara','family',2,'c2',1),
(1,'Ezra','Coleg cresa','daycare',1,'c5',1),
(1,'Sofia','Prietena','friends',0,'c2',1),
(1,'Theo','Var primar','family',3,'c6',1),
(1,'Emma','Vecina','friends',1,'c3',1),
(2,'Leo','Frate','family',1,'c4',1),
(2,'Rares','Coleg gradinita','daycare',3,'c1',1),
(2,'Victor','Coleg gradinita','daycare',2,'c3',1),
(3,'Mila','Prietena','friends',0,'c1',6),
(3,'Daria','Vecina','friends',0,'c5',6),
(4,'Emma','Sora','family',2,'c5',11),
(4,'Alex','Prieten','friends',2,'c4',11),
(10,'Ines','Sora','family',2,'c6',31),
(10,'Victor2','Coleg cresa','daycare',1,'c3',31),
(14,'Zoe','Sora','family',1,'c1',46),
(15,'Rares','Frate','family',2,'c4',46);

-- UPDATE SERIAL SEQUENCES
-- Aceasta sectiune este vitala in Postgres cand inserezi manual inregistrari cu ID explicit (id=1, id=2 etc.)
-- pentru a preveni erori la viitoarele inserturi fara id completat.
SELECT setval('users_id_seq', (SELECT COALESCE(MAX(id), 1) FROM users));
SELECT setval('children_id_seq', (SELECT COALESCE(MAX(id), 1) FROM children));
SELECT setval('family_members_id_seq', (SELECT COALESCE(MAX(id), 1) FROM family_members));
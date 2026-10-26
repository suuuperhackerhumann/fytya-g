<?php
// Clés PayDunya — ne jamais commiter ce fichier dans un dépôt public.
// Protégé par .htaccess comme config.php.
//
// IMPORTANT : sur InfinityFree, putenv() est désactivé côté serveur, donc on
// définit les constantes PAYDUNYA_* directement ici plutôt que de passer par
// putenv()/getenv().
//
// PAYDUNYA_MODE pilote automatiquement l'URL de l'API (voir config.php) :
//   'test' -> https://app.paydunya.com/sandbox-api/v1  (clés test_*)
//   'live' -> https://app.paydunya.com/api/v1          (clés live_*)
// Les clés doivent TOUJOURS correspondre au mode, sinon PayDunya renvoie une
// erreur d'authentification.

define('PAYDUNYA_MODE',        'test');

// --- Clé principale (identique en test et en production) ---
define('PAYDUNYA_MASTER_KEY',  'A_REMPLIR');

// --- Clés de TEST (mode sandbox, actives) ---
define('PAYDUNYA_PUBLIC_KEY',  'A_REMPLIR');
define('PAYDUNYA_PRIVATE_KEY', 'A_REMPLIR');
define('PAYDUNYA_TOKEN',       'A_REMPLIR');

// --- Passage en PRODUCTION ---
// 1. Mettre PAYDUNYA_MODE ci-dessus sur 'live'
// 2. Commenter les 3 clés de test et décommenter les 3 lignes ci-dessous
// define('PAYDUNYA_PUBLIC_KEY',  'live_public_...');
// define('PAYDUNYA_PRIVATE_KEY', 'live_private_...');
// define('PAYDUNYA_TOKEN',       '...');
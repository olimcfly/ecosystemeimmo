<?php
/**
 * config.php
 * Constantes de configuration — aucun code ex&eacute;cutable ici
 * Connexion PDO : utiliser ce fichier dans les modules via new PDO(...)
 */

// ── Base de donn&eacute;es ────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'tasq5564_ecosystemeimmolocal');
define('DB_USER',    'tasq5564_occolas14');
define('DB_PASS',    '#oFOSk7~T0p^');
define('DB_CHARSET', 'utf8mb4');

// ── Application ───────────────────────────────────────────────
define('APP_NAME',    'ECOSYST&Egrave IMMO LOCAL');
define('APP_URL',     'https://ecosystemeimmo.fr');
define('APP_VERSION', '1.0.0');

// ── Emails ────────────────────────────────────────────────────
define('ADMIN_EMAIL',  'contact@ecosystemeimmo.fr');
define('NOREPLY_EMAIL','noreply@ecosystemeimmo.fr');
define('SMTP_HOST',    'mail.ecosystemeimmo.fr');
define('SMTP_PORT',    465);
define('SMTP_USER',    'contact@ecosystemeimmo.fr');
define('SMTP_PASS',    'VOTRE_MOT_DE_PASSE_SMTP');

// ── S&eacute;curit&eacute; ──────────────────────────────────────────────────
define('SECRET_KEY', 'CHANGEZ_CETTE_CLE_ALEATOIRE_32CHARS');

// ── Environnement ─────────────────────────────────────────────
define('APP_ENV',   'production'); // 'development' | 'production'
define('APP_DEBUG', false);

// ── Chemins absolus ───────────────────────────────────────────
define('ROOT_PATH',     $_SERVER['DOCUMENT_ROOT']);
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('UPLOADS_PATH',  ROOT_PATH . '/uploads');
define('LOGS_PATH',     ROOT_PATH . '/logs');

// ── Zones r&eacute;serv&eacute;es (utilis&eacute; dans villes-pilotes.php) ────────
define('VILLES_RESERVEES', serialize([
    'Bordeaux', 'Nantes', 'Nandy', 'Lannion'
]));
define('VILLES_DISCUSSION', serialize([
    'Aix-en-Provence'
]));
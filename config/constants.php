<?php
// config/constants.php
// Configuration globale et constantes de l'application

if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}

// Version de l'application
define('APP_NAME', 'Philadelphie Church');
define('APP_VERSION', '2.0.0');

// Chemins absolus sur le système de fichiers
define('ROOT_PATH', realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR);
define('CONFIG_PATH', ROOT_PATH . 'config' . DIRECTORY_SEPARATOR);
define('INCLUDES_PATH', ROOT_PATH . 'includes' . DIRECTORY_SEPARATOR);
define('TEMPLATES_PATH', ROOT_PATH . 'templates' . DIRECTORY_SEPARATOR);
define('UPLOADS_PATH', ROOT_PATH . 'uploads' . DIRECTORY_SEPARATOR);

// Détection dynamique de l'URL de base
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$scriptDir = str_replace('\\', '/', $scriptDir);
if ($scriptDir === '.' || $scriptDir === '/') {
    $scriptDir = '';
}

// Gestion de l'URL de base propre
if (basename($scriptDir) === 'admin') {
    $baseUrl = $protocol . $host . dirname($scriptDir);
} else {
    $baseUrl = $protocol . $host . ($scriptDir ? '/' . ltrim($scriptDir, '/') : '');
}
$baseUrl = rtrim($baseUrl, '/');

define('BASE_URL', $baseUrl);
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', BASE_URL . '/uploads');
define('ADMIN_URL', BASE_URL . '/admin');

// Rôles utilisateurs — hiérarchie : developpeur > responsable > media > secretaire
define('ROLE_DEVELOPPEUR', 'developpeur');
define('ROLE_RESPONSABLE', 'responsable');
define('ROLE_MEDIA',       'media');       // Équipe Média : annonces, vidéos, prestations chorales
define('ROLE_SECRETAIRE',  'secretaire');

// Sécurité des sessions
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if ($protocol === 'https://') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

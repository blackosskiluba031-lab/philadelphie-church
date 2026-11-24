<?php
// admin/logout.php
// Déconnexion propre de la session d'administration

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_helpers.php';

if (is_logged_in()) {
    $user = current_user();
    log_admin_action($user['id'], 'Déconnexion manuelle');
    logout_user();
}

set_flash('info', 'Vous avez été déconnecté avec succès.');
header('Location: ' . ADMIN_URL . '/login.php');
exit;

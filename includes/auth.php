<?php
// includes/auth.php
// Gestion de l'authentification administrateur, sessions et contrôle d'accès basé sur les rôles (RBAC)

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/functions.php';

/**
 * Vérifie si l'utilisateur est actuellement connecté en tant qu'administrateur
 */
function is_logged_in(): bool {
    return !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_role']);
}

/**
 * Récupère les données de session de l'administrateur connecté
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'       => (int)$_SESSION['admin_id'],
        'nom'      => $_SESSION['admin_nom'] ?? '',
        'username' => $_SESSION['admin_username'] ?? '',
        'email'    => $_SESSION['admin_email'] ?? '',
        'role'     => $_SESSION['admin_role'] ?? ROLE_SECRETAIRE,
    ];
}

/**
 * Vérifie si l'administrateur possède un rôle spécifique ou supérieur
 * Hiérarchie : developpeur > responsable > media > secretaire
 */
function has_role(string|array $allowedRoles): bool {
    if (!is_logged_in()) {
        return false;
    }

    $currentRole = $_SESSION['admin_role'];

    // Le développeur a accès à tout
    if ($currentRole === ROLE_DEVELOPPEUR) {
        return true;
    }

    if (is_string($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }

    // Hiérarchie : developpeur > responsable > media > secretaire
    // Le responsable a accès aux fonctions media et secrétaire
    if ($currentRole === ROLE_RESPONSABLE && (in_array(ROLE_MEDIA, $allowedRoles, true) || in_array(ROLE_SECRETAIRE, $allowedRoles, true))) {
        return true;
    }

    // Le rôle media a accès aux fonctions secrétaire
    if ($currentRole === ROLE_MEDIA && in_array(ROLE_SECRETAIRE, $allowedRoles, true)) {
        return true;
    }

    return in_array($currentRole, $allowedRoles, true);
}

/**
 * Protège une page admin : redirige vers le login si non connecté
 */
function require_login(): void {
    if (!is_logged_in()) {
        set_flash('warning', 'Veuillez vous connecter pour accéder à l\'espace d\'administration.');
        header('Location: ' . ADMIN_URL . '/login.php');
        exit;
    }
}

/**
 * Exige un ou plusieurs rôles spécifiques
 */
function require_role(string|array $roles): void {
    require_login();
    if (!has_role($roles)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Accès Refusé</title></head><body style="background:#0F172A;color:#F8FAFC;font-family:sans-serif;padding:40px;text-align:center;">';
        echo '<h1 style="color:#D97706;">Accès Refusé (403)</h1>';
        echo '<p>Votre rôle ne vous autorise pas à accéder à cette section administrative.</p>';
        echo '<p><a href="' . ADMIN_URL . '/index.php" style="color:#F59E0B;">Retour au tableau de bord</a></p>';
        echo '</body></html>';
        exit;
    }
}

/**
 * Connecte un utilisateur et régénère son ID de session (anti-session fixation)
 */
function login_user(array $admin): void {
    session_regenerate_id(true);

    $_SESSION['admin_id']       = (int)$admin['id'];
    $_SESSION['admin_nom']      = $admin['nom'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_email']    = $admin['email'] ?? '';
    $_SESSION['admin_role']     = $admin['role'];
    $_SESSION['logged_at']      = time();

    // Mise à jour de la dernière connexion en BDD
    try {
        $stmt = db()->prepare("UPDATE admins SET derniere_connexion = NOW() WHERE id = ?");
        $stmt->execute([$admin['id']]);
    } catch (Exception $e) {
        error_log('Erreur update derniere_connexion: ' . $e->getMessage());
    }
}

/**
 * Déconnecte l'utilisateur et détruit la session proprement
 */
function logout_user(): void {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}

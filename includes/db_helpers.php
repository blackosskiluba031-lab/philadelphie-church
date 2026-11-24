<?php
// includes/db_helpers.php
// Fonctions d'accès aux données réutilisables, sécurité brute-force et audit

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Récupère les informations générales de l'église
 */
function get_church_info(): array {
    static $church = null;
    if ($church === null) {
        try {
            $stmt = db()->query("SELECT * FROM eglise LIMIT 1");
            $church = $stmt->fetch() ?: [
                'nom'          => 'Église de la Grâce',
                'slogan'       => 'Une communauté de foi et d\'amour',
                'adresse'      => 'Adresse non configurée',
                'telephone'    => '',
                'email'        => '',
                'logo'         => null,
                'a_propos'     => '',
                'facebook_url' => '',
                'youtube_url'  => '',
                'tiktok_url'   => '',
                'whatsapp'     => '',
            ];
        } catch (Exception $e) {
            error_log('Erreur get_church_info: ' . $e->getMessage());
            $church = [];
        }
    }
    return $church;
}

/**
 * Récupère la valeur d'un paramètre système
 */
function get_param(string $key, ?string $default = null): ?string {
    try {
        $stmt = db()->prepare("SELECT valeur FROM parametres WHERE cle = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (Exception $e) {
        error_log('Erreur get_param: ' . $e->getMessage());
        return $default;
    }
}

/**
 * Définit ou met à jour un paramètre système
 */
function set_param(string $key, ?string $value, ?string $description = null): bool {
    try {
        $stmt = db()->prepare("
            INSERT INTO parametres (cle, valeur, description, updated_at) 
            VALUES (?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE valeur = VALUES(valeur), description = COALESCE(VALUES(description), description), updated_at = NOW()
        ");
        return $stmt->execute([$key, $value, $description]);
    } catch (Exception $e) {
        error_log('Erreur set_param: ' . $e->getMessage());
        return false;
    }
}

/**
 * Journalise une action d'un administrateur dans admin_logs
 */
function log_admin_action(int $adminId, string $action, ?array $details = null): void {
    try {
        $ip = get_client_ip();
        $userAgent = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        $detailsJson = $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;

        $stmt = db()->prepare("INSERT INTO admin_logs (admin_id, action, ip, user_agent, details, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$adminId, $action, $ip, $userAgent, $detailsJson]);
    } catch (Exception $e) {
        error_log('Erreur log_admin_action: ' . $e->getMessage());
    }
}

/**
 * Enregistre une tentative de connexion (sécurité brute-force)
 */
function record_login_attempt(string $username, bool $success): void {
    try {
        $ip = get_client_ip();
        $statut = $success ? 'succes' : 'echec';
        $userAgent = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

        $stmt = db()->prepare("INSERT INTO tentatives_connexion (ip, username, statut, user_agent, date_tentative) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$ip, $username, $statut, $userAgent]);
    } catch (Exception $e) {
        error_log('Erreur record_login_attempt: ' . $e->getMessage());
    }
}

/**
 * Vérifie si l'adresse IP est bloquée suite à trop d'échecs récents
 */
function is_ip_rate_limited(string $ip, int $maxAttempts = 5, int $decayMinutes = 15): bool {
    try {
        $stmt = db()->prepare("
            SELECT COUNT(*) FROM tentatives_connexion 
            WHERE ip = ? AND statut = 'echec' 
            AND date_tentative >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
        ");
        $stmt->execute([$ip, $decayMinutes]);
        $echecs = (int)$stmt->fetchColumn();
        return $echecs >= $maxAttempts;
    } catch (Exception $e) {
        error_log('Erreur is_ip_rate_limited: ' . $e->getMessage());
        return false;
    }
}

/**
 * Enregistre une vue analytique et incrémente le compteur correspondant
 */
function record_view(string $type, int $contentId): void {
    try {
        $ip = get_client_ip();
        $ipHash = hash('sha256', $ip . '_eglise_salt');
        $userAgent = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

        // Enregistre dans statistiques_vues
        $stmt = db()->prepare("INSERT INTO statistiques_vues (type_contenu, contenu_id, ip_hash, user_agent, date_vue) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$type, $contentId, $ipHash, $userAgent]);

        // Incrémente le compteur de vues de la table
        if ($type === 'video') {
            db()->prepare("UPDATE videos SET vues_count = vues_count + 1 WHERE id = ?")->execute([$contentId]);
        } elseif ($type === 'annonce') {
            db()->prepare("UPDATE annonces SET vues_count = vues_count + 1 WHERE id = ?")->execute([$contentId]);
        }
    } catch (Exception $e) {
        error_log('Erreur record_view: ' . $e->getMessage());
    }
}

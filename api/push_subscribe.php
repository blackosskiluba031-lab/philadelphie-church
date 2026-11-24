<?php
// api/push_subscribe.php
// Endpoint de réception et d'enregistrement des abonnements Web Push PWA

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (empty($data['endpoint']) || empty($data['p256dh']) || empty($data['auth'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Données de souscription incomplètes.']);
    exit;
}

$endpoint  = trim($data['endpoint']);
$p256dh    = trim($data['p256dh']);
$auth      = trim($data['auth']);
$userAgent = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
$fideleId  = !empty($data['fidele_id']) ? (int)$data['fidele_id'] : null;

try {
    // Vérifie si l'endpoint existe déjà
    $stmtCheck = db()->prepare("SELECT id FROM push_subscriptions WHERE endpoint = ? LIMIT 1");
    $stmtCheck->execute([$endpoint]);
    $existing = $stmtCheck->fetch();

    if ($existing) {
        $stmtUpdate = db()->prepare("
            UPDATE push_subscriptions 
            SET p256dh = ?, auth_token = ?, user_agent = ?, fidele_id = COALESCE(?, fidele_id) 
            WHERE id = ?
        ");
        $stmtUpdate->execute([$p256dh, $auth, $userAgent, $fideleId, $existing['id']]);
    } else {
        $stmtInsert = db()->prepare("
            INSERT INTO push_subscriptions (fidele_id, endpoint, p256dh, auth_token, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmtInsert->execute([$fideleId, $endpoint, $p256dh, $auth, $userAgent]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Souscription aux notifications enregistrée avec succès.'
    ]);
} catch (Exception $e) {
    error_log('Erreur push_subscribe: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'enregistrement de la souscription.']);
}

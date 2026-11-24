<?php
// scripts/get_or_create_accounts.php
// Prépare les 3 comptes officiels : Développeur, Pasteur, Équipe Média

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

$pdo = db();

echo "=== Comptes Administrateurs Philadelphie Church ===\n\n";

// 1. Développeur / Super-Admin
// Compte dev par défaut défini dans migration/seeds
$devUser = 'admin_dev';
$devPass = 'Philadelphie2026!';
$devHash = password_hash($devPass, PASSWORD_BCRYPT, ['cost' => 12]);

$stmtDev = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
$stmtDev->execute([$devUser]);
if ($devId = $stmtDev->fetchColumn()) {
    $pdo->prepare("UPDATE admins SET mot_de_passe_hash = ?, role = 'developpeur', actif = 1 WHERE id = ?")->execute([$devHash, $devId]);
} else {
    $pdo->prepare("INSERT INTO admins (nom, username, email, mot_de_passe_hash, role, actif, created_at) VALUES ('Développeur Système', ?, 'dev@philadelphie.cd', ?, 'developpeur', 1, NOW())")->execute([$devUser, $devHash]);
}

// 2. Pasteur (Rôle Responsable)
$pasteurUser = 'pasteur';
$pasteurPass = 'Pasteur2026!';
$pasteurHash = password_hash($pasteurPass, PASSWORD_BCRYPT, ['cost' => 12]);

$stmtPast = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
$stmtPast->execute([$pasteurUser]);
if ($pastId = $stmtPast->fetchColumn()) {
    $pdo->prepare("UPDATE admins SET nom = 'Pasteur Philadelphie', mot_de_passe_hash = ?, role = 'responsable', actif = 1 WHERE id = ?")->execute([$pasteurHash, $pastId]);
} else {
    $pdo->prepare("INSERT INTO admins (nom, username, email, mot_de_passe_hash, role, actif, created_at) VALUES ('Pasteur Philadelphie', ?, 'pasteur@philadelphie.cd', ?, 'responsable', 1, NOW())")->execute([$pasteurUser, $pasteurHash]);
}

// 3. Équipe Média (Rôle Média)
$mediaUser = 'media';
$mediaPass = 'Media2026!';
$mediaHash = password_hash($mediaPass, PASSWORD_BCRYPT, ['cost' => 12]);

$stmtMed = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
$stmtMed->execute([$mediaUser]);
if ($medId = $stmtMed->fetchColumn()) {
    $pdo->prepare("UPDATE admins SET nom = 'Équipe Média CFCG', mot_de_passe_hash = ?, role = 'media', actif = 1 WHERE id = ?")->execute([$mediaHash, $medId]);
} else {
    $pdo->prepare("INSERT INTO admins (nom, username, email, mot_de_passe_hash, role, actif, created_at) VALUES ('Équipe Média CFCG', ?, 'media@philadelphie.cd', ?, 'media', 1, NOW())")->execute([$mediaUser, $mediaHash]);
}

// Affichage
$admins = $pdo->query("SELECT id, nom, username, email, role, actif FROM admins ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($admins as $a) {
    echo "ID: {$a['id']} | Nom: {$a['nom']} | Username: {$a['username']} | Role: {$a['role']} | Statut: " . ($a['actif'] ? 'Actif' : 'Inactif') . "\n";
}

echo "\nComptes prêts et configurés !\n";

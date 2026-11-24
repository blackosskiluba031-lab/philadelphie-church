<?php
// scripts/creer_developpeur.php
// Création sécurisée d'un compte développeur / super-administrateur
// Aucun mot de passe en clair dans les fichiers de configuration ou SQL.

if (php_sapi_name() !== 'cli') {
    die("Ce script doit être exécuté uniquement en ligne de commande (CLI).\n");
}

$host = '127.0.0.1';
$db   = 'eglise_app';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage() . "\n");
}

echo "========================================================\n";
echo "   CRÉATION SÉCURISÉE DU COMPTE DÉVELOPPEUR (eglise_app)\n";
echo "========================================================\n\n";

// Parsing des arguments CLI éventuels (ex: --username=... --email=...)
$options = getopt('', ['username:', 'nom:', 'email:', 'password:', 'auto-gen']);

function askInput($prompt, $default = '') {
    echo $prompt . ($default ? " [$default]" : "") . ": ";
    $line = trim(fgets(STDIN));
    return $line !== '' ? $line : $default;
}

$username = $options['username'] ?? null;
$nom      = $options['nom'] ?? null;
$email    = $options['email'] ?? null;
$password = $options['password'] ?? null;

if (!$username) {
    $username = askInput("Nom d'utilisateur (username)", "dev_admin");
}
if (!$nom) {
    $nom = askInput("Nom complet", "Développeur Principal");
}
if (!$email) {
    $email = askInput("Adresse e-mail", "dev@eglise.local");
}

if (!$password) {
    if (isset($options['auto-gen'])) {
        $password = bin2hex(random_bytes(6)) . '@' . rand(100, 999) . '!';
    } else {
        echo "\nOptions de mot de passe :\n";
        echo "1. Saisir mon propre mot de passe\n";
        echo "2. Générer automatiquement un mot de passe fort (Recommandé)\n";
        $choix = askInput("Votre choix (1 ou 2)", "2");

        if ($choix === '1') {
            $password = askInput("Entrez le mot de passe (min 10 caractères)");
            while (strlen($password) < 10) {
                echo "[!] Le mot de passe est trop court (minimum 10 caractères).\n";
                $password = askInput("Entrez un mot de passe robuste");
            }
        } else {
            // Génération d'un mot de passe fort
            $charsUpper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
            $charsLower = 'abcdefghijkmnopqrstuvwxyz';
            $charsDigits = '23456789';
            $charsSpecial = '!@#$%^&*()-_=+';
            
            $pwdParts = [
                $charsUpper[random_int(0, strlen($charsUpper) - 1)],
                $charsUpper[random_int(0, strlen($charsUpper) - 1)],
                $charsLower[random_int(0, strlen($charsLower) - 1)],
                $charsLower[random_int(0, strlen($charsLower) - 1)],
                $charsDigits[random_int(0, strlen($charsDigits) - 1)],
                $charsDigits[random_int(0, strlen($charsDigits) - 1)],
                $charsSpecial[random_int(0, strlen($charsSpecial) - 1)],
                $charsSpecial[random_int(0, strlen($charsSpecial) - 1)],
            ];
            
            $allChars = $charsUpper . $charsLower . $charsDigits . $charsSpecial;
            for ($i = 0; $i < 8; $i++) {
                $pwdParts[] = $allChars[random_int(0, strlen($allChars) - 1)];
            }
            shuffle($pwdParts);
            $password = implode('', $pwdParts);
        }
    }
}

// Hachage sécurisé BCRYPT avec coût 12
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Vérification si le compte existe déjà
$stmtCheck = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
$stmtCheck->execute([$username]);
$existing = $stmtCheck->fetch();

if ($existing) {
    $stmtUpdate = $pdo->prepare("UPDATE admins SET nom = ?, email = ?, mot_de_passe_hash = ?, role = 'developpeur', actif = 1, updated_at = NOW() WHERE id = ?");
    $stmtUpdate->execute([$nom, $email, $hash, $existing['id']]);
    $adminId = $existing['id'];
    $actionMsg = "Mise à jour du mot de passe développeur";
} else {
    $stmtInsert = $pdo->prepare("INSERT INTO admins (nom, username, email, mot_de_passe_hash, role, actif, created_at) VALUES (?, ?, ?, ?, 'developpeur', 1, NOW())");
    $stmtInsert->execute([$nom, $username, $email, $hash]);
    $adminId = $pdo->lastInsertId();
    $actionMsg = "Création du compte développeur";
}

// Enregistrement dans admin_logs
$stmtLog = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, ip, user_agent, details, created_at) VALUES (?, ?, '127.0.0.1', 'CLI Script', ?, NOW())");
$stmtLog->execute([$adminId, $actionMsg, json_encode(['username' => $username, 'role' => 'developpeur'])]);

echo "\n--------------------------------------------------------\n";
echo "[SUCCÈS] Compte Développeur configuré avec succès !\n";
echo "--------------------------------------------------------\n";
echo "ID Admin       : " . $adminId . "\n";
echo "Nom            : " . $nom . "\n";
echo "Username       : " . $username . "\n";
echo "Email          : " . $email . "\n";
echo "Rôle           : developpeur\n";
echo "Mot de passe   : " . $password . "\n";
echo "Hash BCRYPT    : " . substr($hash, 0, 20) . "...\n";
echo "--------------------------------------------------------\n";
echo "[IMPORTANT] Veuillez copier ce mot de passe dès maintenant.\n";
echo "Il n'est enregistré nulle part en clair dans la base de données.\n\n";

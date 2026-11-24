<?php
// includes/functions.php
// Utilitaires généraux, sécurité XSS/CSRF, formatage et notifications flash

/**
 * Échappement sécurisé des sorties HTML contre les failles XSS
 */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Génération d'un jeton CSRF unique stocké en session
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Génère le champ input masqué pour les formulaires POST
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Vérification du jeton CSRF lors de la soumission d'un formulaire
 */
function verify_csrf(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Enregistre un message flash en session (success, danger, warning, info)
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash_message'] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Récupère et supprime le message flash actuel
 */
function get_flash(): ?array {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Formatage d'une date en français (ex: Dimanche 29 Novembre 2026)
 */
function format_date_fr(?string $dateStr, bool $withTime = false): string {
    if (empty($dateStr)) {
        return '-';
    }

    $timestamp = strtotime($dateStr);
    if (!$timestamp) {
        return $dateStr;
    }

    $jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    $mois  = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

    $jourSemaine = $jours[(int)date('w', $timestamp)];
    $jourMois    = date('d', $timestamp);
    $moisNom     = $mois[(int)date('n', $timestamp)];
    $annee       = date('Y', $timestamp);

    $dateFormatee = sprintf('%s %s %s %s', $jourSemaine, $jourMois, $moisNom, $annee);

    if ($withTime) {
        $heure = date('H\hi', $timestamp);
        $dateFormatee .= ' à ' . $heure;
    }

    return $dateFormatee;
}

/**
 * Formatage monétaire selon la devise (RDC : FC & USD par défaut)
 */
function format_currency(float $amount, string $currency = 'FC'): string {
    $curr = strtoupper(trim($currency));
    if ($curr === 'FC' || $curr === 'CDF') {
        return number_format($amount, 0, ',', ' ') . ' FC';
    } elseif ($curr === 'USD' || $curr === '$') {
        return '$ ' . number_format($amount, 2, '.', ' ');
    } elseif ($curr === 'EUR' || $curr === '€') {
        return number_format($amount, 2, ',', ' ') . ' €';
    }
    return number_format($amount, 0, ',', ' ') . ' ' . $currency;
}

/**
 * Récupère l'adresse IP du client en toute sécurité
 */
function get_client_ip(): string {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // En cas de proxy ou load-balancer
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ipList[0]);
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
}

/**
 * Tronque un texte proprement à une longueur donnée
 */
function truncate_text(string $text, int $limit = 120): string {
    if (mb_strlen($text) <= $limit) {
        return $text;
    }
    return mb_substr($text, 0, $limit) . '...';
}

/**
 * Convertit un identifiant YouTube (ou URL) en lien embed sécurisé
 */
function get_youtube_embed_url(string $url): ?string {
    if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
        return 'https://www.youtube-nocookie.com/embed/' . $matches[1];
    }
    return null;
}

/**
 * Génère le composant Hero cinématique Church X pour chaque page
 * Récupère dynamiquement l'image d'arrière-plan configurée en base si disponible
 */
function render_church_hero(
    string $tag,
    string $title,
    string $description,
    string $bgImage = '',
    array $buttons = [],
    string $bgKey = ''
): string {
    if (!empty($bgKey) && function_exists('get_param')) {
        $dbImg = get_param($bgKey);
        if (!empty($dbImg)) {
            $bgImage = $dbImg;
        }
    } elseif (str_starts_with($bgImage, 'hero_bg_') && function_exists('get_param')) {
        $dbImg = get_param($bgImage);
        if (!empty($dbImg)) {
            $bgImage = $dbImg;
        }
    }

    // Association automatique des pages/visuels par défaut avec les paramètres DB
    if (function_exists('get_param')) {
        if (empty($bgImage) || str_contains($bgImage, 'photo-1438232992991-995b7058bbb3')) {
            $bgImage = get_param('hero_bg_accueil', $bgImage ?: 'https://images.unsplash.com/photo-1438232992991-995b7058bbb3?auto=format&fit=crop&w=1920&q=80');
        } elseif (str_contains($bgImage, 'photo-1510525009512-ad7fc13eefab')) {
            $bgImage = get_param('hero_bg_programme', $bgImage);
        } elseif (str_contains($bgImage, 'photo-1519491050282-cf00c82424b4')) {
            $bgImage = get_param('hero_bg_videos', $bgImage);
        } elseif (str_contains($bgImage, 'photo-1469571486292-0ba58a3f068b')) {
            $bgImage = get_param('hero_bg_communaute', $bgImage);
        } elseif (str_contains($bgImage, 'photo-1544717305-2782549b5136')) {
            $bgImage = get_param('hero_bg_pasteur', $bgImage);
        }
    }

    if (empty($bgImage)) {
        $bgImage = 'https://images.unsplash.com/photo-1438232992991-995b7058bbb3?auto=format&fit=crop&w=1920&q=80';
    }

    $buttonsHtml = '';
    foreach ($buttons as $btn) {
        $class = $btn['class'] ?? 'btn-church-white';
        $href = e($btn['href'] ?? '#');
        $label = e($btn['label'] ?? 'Action');
        $buttonsHtml .= "<a href=\"{$href}\" class=\"{$class}\">{$label}</a>\n";
    }

    return <<<HTML
<section class="church-hero" style="background-image: url('{$bgImage}'); min-height: 48vh; padding-top: 2rem;">
    <div class="church-hero-overlay"></div>
    <div class="church-hero-radial"></div>
    <div class="container church-hero-content" style="max-width: 1280px; margin: 0 auto; width: 100%; padding: 3rem 1.5rem;">
        <span class="hero-tag">{$tag}</span>
        <h1 class="hero-main-title" style="font-size: clamp(2rem, 5vw, 3.25rem); margin-bottom: 1rem;">
            {$title}
        </h1>
        <p class="hero-description" style="margin-bottom: 1.5rem; max-width: 620px;">
            {$description}
        </p>
        <div class="hero-buttons-row">
            {$buttonsHtml}
        </div>
    </div>
</section>
HTML;
}

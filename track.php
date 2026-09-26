<?php
declare(strict_types=1);

/**
 * MICRO-TRACKER DE VISITES DU SITE — DR REMUS
 * Léger, non-bloquant, respectueux de la vie privée.
 */

// Si appelé via balise <img>, renvoyer immédiatement un pixel GIF 1x1 transparent
$isImage = isset($_GET['img']) || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'image/'));
if ($isImage) {
    header('Content-Type: image/gif');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
} else {
    header('Content-Type: application/json; charset=UTF-8');
}

require_once __DIR__ . '/admin/includes/db.php';

$pdo = getDatabaseConnection();
if (!$pdo) {
    if (!$isImage) echo json_encode(['success' => false]);
    exit;
}

// 1. Récupération des données de la requête
$rawPage = $_GET['p'] ?? $_GET['page'] ?? $_SERVER['HTTP_REFERER'] ?? 'index.html';
$page = basename((string)parse_url((string)$rawPage, PHP_URL_PATH));
if ($page === '' || $page === '/') $page = 'index.html';

$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250);
$referrer = substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 250);

// Détection de l'appareil
$uaLower = strtolower($userAgent);
$device = 'desktop';
if (str_contains($uaLower, 'mobile') || str_contains($uaLower, 'android') || str_contains($uaLower, 'iphone')) {
    $device = 'mobile';
} elseif (str_contains($uaLower, 'tablet') || str_contains($uaLower, 'ipad')) {
    $device = 'tablet';
}

// 2. Enregistrement en base de données
try {
    $stmt = $pdo->prepare("INSERT INTO `site_visits` (page, ip_address, device, user_agent, referrer, visited_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$page, $ip, $device, $userAgent, $referrer]);

    if (!$isImage) {
        echo json_encode(['success' => true]);
    }
} catch (Throwable $e) {
    if (!$isImage) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

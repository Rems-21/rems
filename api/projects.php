<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, must-revalidate');

require_once dirname(__DIR__) . '/admin/includes/db.php';

$pdo = getDatabaseConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Impossible de se connecter à la base de données.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $pdo->query("SELECT * FROM `projects` WHERE is_visible = 1 ORDER BY `ordre` ASC, `id` ASC");
    $rawProjects = $stmt->fetchAll();

    $projects = [];
    foreach ($rawProjects as $p) {
        $stack = $p['tech_stack'];
        if (is_string($stack)) {
            $decoded = json_decode($stack, true);
            if (is_array($decoded)) {
                $stack = $decoded;
            } else {
                // Si séparé par des virgules
                $stack = array_filter(array_map('trim', explode(',', $stack)));
            }
        } elseif (!is_array($stack)) {
            $stack = [];
        }

        $projects[] = [
            'id'            => (int)$p['id'],
            'ordre'         => (int)$p['ordre'],
            'badge'         => $p['badge'] ?? 'Projet',
            'title'         => $p['title'] ?? '',
            'description'   => $p['description'] ?? '',
            'demo_url'      => !empty($p['demo_url']) ? $p['demo_url'] : '#',
            'github_url'    => !empty($p['github_url']) ? $p['github_url'] : '#',
            'tech_stack'    => array_values($stack),
            'preview_svg'   => $p['preview_svg'] ?? '',
            'preview_image' => $p['preview_image'] ?? null,
            'is_visible'    => (int)$p['is_visible']
        ];
    }

    echo json_encode([
        'success'  => true,
        'count'    => count($projects),
        'projects' => $projects
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des projets : ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

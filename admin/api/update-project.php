<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once dirname(__DIR__) . '/includes/auth.php';
requireAdminAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

$pdo = getDatabaseConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Impossible de se connecter à la base de données.']);
    exit;
}

$action = trim((string)($_POST['action'] ?? 'update'));

// Helper pour formater la tech stack
function formatTechStack($input): string {
    if (is_array($input)) {
        return json_encode(array_values(array_filter(array_map('trim', $input))), JSON_UNESCAPED_UNICODE);
    }
    $str = trim((string)$input);
    if ($str === '') return '[]';
    // Si c'est déjà un JSON valide
    $decoded = json_decode($str, true);
    if (is_array($decoded)) {
        return json_encode(array_values(array_filter(array_map('trim', $decoded))), JSON_UNESCAPED_UNICODE);
    }
    // Sinon on découpe par virgules
    $parts = array_filter(array_map('trim', explode(',', $str)));
    return json_encode(array_values($parts), JSON_UNESCAPED_UNICODE);
}

// ── 1. CRÉATION D'UN NOUVEAU PROJET ──
if ($action === 'create') {
    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Le titre du projet est requis.']);
        exit;
    }

    $badge = trim((string)($_POST['badge'] ?? 'Projet Web'));
    $description = trim((string)($_POST['description'] ?? ''));
    $demoUrl = trim((string)($_POST['demo_url'] ?? '#'));
    $githubUrl = trim((string)($_POST['github_url'] ?? '#'));
    $techStack = formatTechStack($_POST['tech_stack'] ?? '');
    $previewSvg = trim((string)($_POST['preview_svg'] ?? ''));
    $previewImage = trim((string)($_POST['preview_image'] ?? ''));
    $ordre = isset($_POST['ordre']) ? (int)$_POST['ordre'] : 1;
    $isVisible = isset($_POST['is_visible']) ? (int)(bool)$_POST['is_visible'] : 1;

    try {
        $ins = $pdo->prepare("INSERT INTO `projects` 
            (`ordre`, `badge`, `title`, `description`, `demo_url`, `github_url`, `tech_stack`, `preview_svg`, `preview_image`, `is_visible`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([
            $ordre,
            $badge,
            $title,
            $description,
            $demoUrl !== '' ? $demoUrl : '#',
            $githubUrl !== '' ? $githubUrl : '#',
            $techStack,
            $previewSvg,
            $previewImage !== '' ? $previewImage : null,
            $isVisible
        ]);
        $newId = (int)$pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Projet créé avec succès !',
            'id' => $newId
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la création : ' . $e->getMessage()]);
        exit;
    }
}

// ── 2. MISE À JOUR OU BASCULEMENT DE VISIBILITÉ D'UN PROJET EXISTANT ──
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ID de projet invalide.']);
    exit;
}

// Vérifier l'existence
$stmt = $pdo->prepare("SELECT * FROM `projects` WHERE id = ?");
$stmt->execute([$id]);
$project = $stmt->fetch();
if (!$project) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Projet introuvable.']);
    exit;
}

$updates = [];
$params = [];

if (isset($_POST['title']) && trim((string)$_POST['title']) !== '') {
    $updates[] = "`title` = ?";
    $params[] = trim((string)$_POST['title']);
}

if (isset($_POST['badge'])) {
    $updates[] = "`badge` = ?";
    $params[] = trim((string)$_POST['badge']);
}

if (isset($_POST['ordre'])) {
    $updates[] = "`ordre` = ?";
    $params[] = (int)$_POST['ordre'];
}

if (isset($_POST['description'])) {
    $updates[] = "`description` = ?";
    $params[] = trim((string)$_POST['description']);
}

if (isset($_POST['demo_url'])) {
    $val = trim((string)$_POST['demo_url']);
    $updates[] = "`demo_url` = ?";
    $params[] = $val !== '' ? $val : '#';
}

if (isset($_POST['github_url'])) {
    $val = trim((string)$_POST['github_url']);
    $updates[] = "`github_url` = ?";
    $params[] = $val !== '' ? $val : '#';
}

if (isset($_POST['tech_stack'])) {
    $updates[] = "`tech_stack` = ?";
    $params[] = formatTechStack($_POST['tech_stack']);
}

if (isset($_POST['preview_svg'])) {
    $updates[] = "`preview_svg` = ?";
    $params[] = trim((string)$_POST['preview_svg']);
}

if (isset($_POST['preview_image'])) {
    $val = trim((string)$_POST['preview_image']);
    $updates[] = "`preview_image` = ?";
    $params[] = $val !== '' ? $val : null;
}

if (isset($_POST['is_visible'])) {
    $updates[] = "`is_visible` = ?";
    $params[] = (int)(bool)$_POST['is_visible'];
}

if (empty($updates)) {
    echo json_encode(['success' => true, 'message' => 'Aucune modification transmise.']);
    exit;
}

$updates[] = "`updated_at` = NOW()";
$params[] = $id;

try {
    $sql = "UPDATE `projects` SET " . implode(', ', $updates) . " WHERE id = ?";
    $upd = $pdo->prepare($sql);
    $upd->execute($params);

    $freshStmt = $pdo->prepare("SELECT * FROM `projects` WHERE id = ?");
    $freshStmt->execute([$id]);
    $fresh = $freshStmt->fetch();

    echo json_encode([
        'success' => true,
        'message' => 'Projet mis à jour avec succès.',
        'project' => $fresh
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de mise à jour : ' . $e->getMessage()]);
}

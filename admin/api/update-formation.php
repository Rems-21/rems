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

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ID de formation invalide.']);
    exit;
}

$pdo = getDatabaseConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Impossible de se connecter à la base de données.']);
    exit;
}

// Récupérer la formation existante
$stmt = $pdo->prepare("SELECT * FROM `formations` WHERE id = ?");
$stmt->execute([$id]);
$formation = $stmt->fetch();
if (!$formation) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Formation introuvable.']);
    exit;
}

$updates = [];
$params = [];

if (isset($_POST['places_disponibles'])) {
    $placesDispo = max(0, (int)$_POST['places_disponibles']);
    $updates[] = "`places_disponibles` = ?";
    $params[] = $placesDispo;
}

if (isset($_POST['places_total'])) {
    $placesTotal = max(1, (int)$_POST['places_total']);
    $updates[] = "`places_total` = ?";
    $params[] = $placesTotal;
}

if (isset($_POST['statut'])) {
    $statut = trim((string)$_POST['statut']);
    $allowed = ['actif', 'ouvert', 'prochainement', 'complet', 'termine', 'archive'];
    if (in_array($statut, $allowed, true)) {
        $updates[] = "`statut` = ?";
        $params[] = $statut;
    }
}

if (isset($_POST['titre']) && trim((string)$_POST['titre']) !== '') {
    $updates[] = "`titre` = ?";
    $params[] = trim((string)$_POST['titre']);
}

if (isset($_POST['date_debut'])) {
    $updates[] = "`date_debut` = ?";
    $params[] = trim((string)$_POST['date_debut']);
}

if (empty($updates)) {
    echo json_encode(['success' => true, 'message' => 'Aucune modification transmise.']);
    exit;
}

$updates[] = "`updated_at` = NOW()";
$params[] = $id;

$sql = "UPDATE `formations` SET " . implode(', ', $updates) . " WHERE id = ?";
$upd = $pdo->prepare($sql);
$upd->execute($params);

// Re-sélectionner les données à jour
$stmt = $pdo->prepare("SELECT id, titre, places_total, places_disponibles, statut FROM `formations` WHERE id = ?");
$stmt->execute([$id]);
$fresh = $stmt->fetch();

echo json_encode([
    'success'   => true,
    'message'   => 'Formation mise à jour avec succès.',
    'formation' => $fresh
]);

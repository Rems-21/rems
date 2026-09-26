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
$statut = trim((string)($_POST['statut'] ?? ''));
$action = trim((string)($_POST['action'] ?? ''));

if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ID de réservation invalide.']);
    exit;
}

$pdo = getDatabaseConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Impossible de se connecter à la base de données.']);
    exit;
}

// 1. Action de suppression
if ($action === 'delete') {
    $stmt = $pdo->prepare("DELETE FROM `reservations` WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'Réservation supprimée.']);
    exit;
}

// 2. Mise à jour du statut
$allowedStatus = ['nouvelle', 'confirmee', 'terminee', 'annulee'];
if (!in_array($statut, $allowedStatus, true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Statut non reconnu.']);
    exit;
}

$stmt = $pdo->prepare("UPDATE `reservations` SET `statut` = ?, `updated_at` = NOW() WHERE id = ?");
$stmt->execute([$statut, $id]);

echo json_encode([
    'success' => true,
    'message' => 'Statut mis à jour avec succès.',
    'statut'  => $statut
]);

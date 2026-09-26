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
    echo json_encode(['success' => false, 'message' => 'ID de projet invalide.']);
    exit;
}

$pdo = getDatabaseConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Impossible de se connecter à la base de données.']);
    exit;
}

try {
    $del = $pdo->prepare("DELETE FROM `projects` WHERE id = ?");
    $del->execute([$id]);

    echo json_encode([
        'success' => true,
        'message' => 'Projet supprimé définitivement avec succès.'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression : ' . $e->getMessage()]);
}

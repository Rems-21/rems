<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

require_once dirname(__DIR__) . '/admin/includes/db.php';

$pdo = getDatabaseConnection();
if (!$pdo) {
    // Réponse de secours si la base de données n'est pas encore connectée
    echo json_encode([
        'success' => true,
        'formation' => [
            'titre' => "Créer un site web avec l'IA",
            'date_debut' => "15 octobre 2026",
            'places_total' => 10,
            'places_disponibles' => 7,
            'statut' => "Inscriptions ouvertes"
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $pdo->query("SELECT * FROM `formations` WHERE is_active_cohort = 1 LIMIT 1");
    $formation = $stmt->fetch();

    if (!$formation) {
        $stmt = $pdo->query("SELECT * FROM `formations` WHERE statut = 'ouvert' ORDER BY id ASC LIMIT 1");
        $formation = $stmt->fetch();
    }

    if ($formation) {
        echo json_encode([
            'success'   => true,
            'formation' => [
                'id'                 => (int)$formation['id'],
                'titre'              => $formation['titre'],
                'date_debut'         => $formation['date_debut'],
                'places_total'       => (int)$formation['places_total'],
                'places_disponibles' => (int)$formation['places_disponibles'],
                'statut'             => $formation['statut']
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => 'Aucune cohorte active configurée.']);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

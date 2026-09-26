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

$action = trim((string)($_POST['action'] ?? 'update_status'));

// ── 1. Émission / Création d'un nouveau certificat ────────────────
if ($action === 'create' || $action === 'issue') {
    $nom = trim((string)($_POST['nom_apprenant'] ?? ''));
    $email = trim((string)($_POST['email_apprenant'] ?? ''));
    $formationTitre = trim((string)($_POST['formation_titre'] ?? ''));
    $cohorte = trim((string)($_POST['cohorte'] ?? ''));
    $mention = trim((string)($_POST['mention'] ?? 'Mention Très Bien'));
    $dateEmission = trim((string)($_POST['date_emission'] ?? date('Y-m-d')));
    $competences = trim((string)($_POST['competences'] ?? ''));
    $customId = trim((string)($_POST['cert_id'] ?? ''));

    // Nouveaux champs pour le style officiel
    $duree = trim((string)($_POST['duree'] ?? '30 heures'));
    $periode = trim((string)($_POST['periode'] ?? '15 Juin 2026 – 15 Juillet 2026'));
    $niveau = trim((string)($_POST['niveau'] ?? 'Débutant → Intermédiaire'));
    $lieu = trim((string)($_POST['lieu'] ?? 'À Douala, Cameroun'));
    $formateur = trim((string)($_POST['formateur'] ?? 'Dr Remus'));
    $descDefault = "Cette formation a couvert les notions essentielles et les compétences pratiques pour concevoir, développer et déployer un site web professionnel à l'aide des outils d'IA.";
    $descriptionCert = trim((string)($_POST['description_cert'] ?? $descDefault));
    if ($descriptionCert === '') $descriptionCert = $descDefault;

    if (mb_strlen($nom) < 2) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => "Le nom complet de l'apprenant est obligatoire."]);
        exit;
    }
    if (empty($formationTitre)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => "Le titre de la formation est obligatoire."]);
        exit;
    }

    // Génération automatique d'un identifiant unique si non fourni (ex: CERT-2026-0159)
    if (empty($customId)) {
        $year = date('Y');
        $stmtMax = $pdo->query("SELECT COUNT(*) FROM certificats");
        $nextNum = ((int)$stmtMax->fetchColumn()) + 158;
        $customId = sprintf("CERT-%s-%04d", $year, $nextNum);
    }

    // Vérifier l'unicité
    $stmtCheck = $pdo->prepare("SELECT id FROM certificats WHERE cert_id = ?");
    $stmtCheck->execute([$customId]);
    if ($stmtCheck->fetch()) {
        $customId .= '-' . strtoupper(substr(md5(uniqid()), 0, 3));
    }

    $qrHash = hash('sha256', $customId . $nom . $dateEmission);

    $sql = "INSERT INTO certificats (cert_id, nom_apprenant, email_apprenant, formation_titre, description_cert, cohorte, mention, duree, periode, niveau, lieu, formateur, date_emission, competences, statut, qr_code_hash)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'valide', ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $customId,
        $nom,
        $email,
        $formationTitre,
        $descriptionCert,
        $cohorte,
        $mention,
        $duree,
        $periode,
        $niveau,
        $lieu,
        $formateur,
        $dateEmission,
        $competences,
        $qrHash
    ]);

    $newId = (int)$pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => "Certificat officiel #{$customId} généré avec succès.",
        'cert_id' => $customId,
        'id'      => $newId
    ]);
    exit;
}

// ── 2. Suppression d'un certificat ────────────────────────────────
if ($action === 'delete') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'ID invalide.']);
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM certificats WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'Certificat supprimé.']);
    exit;
}

// ── 3. Mise à jour du statut (valide / revoque / archive) ──────────
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$statut = trim((string)($_POST['statut'] ?? ''));

if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ID de certificat invalide.']);
    exit;
}

$allowed = ['valide', 'revoque', 'archive'];
if (!in_array($statut, $allowed, true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Statut non autorisé.']);
    exit;
}

$stmt = $pdo->prepare("UPDATE certificats SET statut = ? WHERE id = ?");
$stmt->execute([$statut, $id]);

echo json_encode([
    'success' => true,
    'message' => 'Statut du certificat mis à jour.',
    'statut'  => $statut
]);

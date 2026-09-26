<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 1. Chargement du fichier .env ─────────────────────────────────
function loadEnvFile(string $path): array {
    if (!file_exists($path) || !is_readable($path)) return [];
    $env = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $env[trim($parts[0])] = trim(trim($parts[1]), "\"'\t\n\r");
        }
    }
    return $env;
}

$env = loadEnvFile(__DIR__ . '/.env');

$brevoApiKey   = $env['BREVO_API_KEY']   ?? getenv('BREVO_API_KEY')   ?: '';
$mailFromEmail = $env['MAIL_FROM_EMAIL'] ?? getenv('MAIL_FROM_EMAIL') ?: '';
$mailFromName  = $env['MAIL_FROM_NAME']  ?? getenv('MAIL_FROM_NAME')  ?: 'Dr Remus · Formations';
$mailToEmail   = $env['MAIL_TO_EMAIL']   ?? getenv('MAIL_TO_EMAIL')   ?: 'dsonkouatremus@gmail.com';
$mailToName    = $env['MAIL_TO_NAME']    ?? getenv('MAIL_TO_NAME')    ?: 'Dr Remus';

// ── 2. Lecture des données POST ou JSON ───────────────────────────
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    if ($raw) {
        $json = json_decode($raw, true);
        if (is_array($json)) $input = $json;
    }
}

// Honeypot anti-bot
if (!empty($input['website_hp'])) {
    echo json_encode(['success' => true, 'message' => 'Demande transmise avec succès.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 3. Extraction et nettoyage des champs ─────────────────────────
$prenom      = trim((string)($input['prenom']    ?? ''));
$nom         = trim((string)($input['nom']       ?? ''));
$email       = trim((string)($input['email']     ?? ''));
$indicatif   = trim((string)($input['indicatif'] ?? ''));
$whatsappRaw = trim((string)($input['whatsapp']  ?? ''));
$formation   = trim((string)($input['formation'] ?? 'Créer un site web avec l\'IA'));
$message     = trim((string)($input['message']   ?? ''));

$fullName    = $prenom . ($nom ? ' ' . $nom : '');

// Traitement du numéro WhatsApp international
if ($whatsappRaw !== '') {
    if (!str_starts_with($whatsappRaw, '+') && !empty($indicatif) && $indicatif !== '+') {
        $numPart = $whatsappRaw;
        // Supprimer le premier 0 pour les indicatifs européens et nord-africains si présent
        if (in_array($indicatif, ['+33', '+32', '+41', '+44', '+49', '+39', '+34', '+212', '+216', '+213'], true) && str_starts_with($numPart, '0')) {
            $numPart = substr($numPart, 1);
        }
        $whatsapp = $indicatif . ' ' . $numPart;
    } else {
        $whatsapp = $whatsappRaw;
    }
} else {
    $whatsapp = 'Non renseigné';
}

// ── 4. Validation ─────────────────────────────────────────────────
if (mb_strlen($prenom) < 2) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Veuillez saisir votre prénom (au moins 2 caractères).'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Adresse email invalide.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 5. Vérification de la clé API Brevo ───────────────────────────
if (empty($brevoApiKey) || str_starts_with($brevoApiKey, 'xkeysib-votre_cle')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Clé API Brevo non configurée.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 6. Métadonnées ────────────────────────────────────────────────
$dateStr    = date('d/m/Y à H:i') . ' (UTC' . date('P') . ')';
$visitorIp  = $_SERVER['REMOTE_ADDR'] ?? 'Non détectée';
$emailSafe  = str_replace(["\r", "\n"], '', $email);
$nameSafe   = str_replace(["\r", "\n"], '', $fullName);
$cleanPhone = preg_replace('/[^0-9]/', '', $whatsapp);
$hasWa      = !empty($cleanPhone) && strlen($cleanPhone) >= 8;
$waLink     = $hasWa ? "https://wa.me/{$cleanPhone}" : '';

// ── 7. Email ADMIN (pour Dr Remus) ────────────────────────────────
$adminSubject = "RESERVATION · {$nameSafe} — {$formation}";

$adminHtml = '<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>' . htmlspecialchars($adminSubject, ENT_QUOTES, 'UTF-8') . '</title></head>
<body style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif; background:#0a0a0c; color:#fff; margin:0; padding:30px 20px;">
  <div style="max-width:640px; margin:0 auto; background:#141418; border:1px solid #27272a; border-radius:16px; overflow:hidden; box-shadow:0 12px 40px rgba(0,0,0,0.6);">
    <div style="background:linear-gradient(135deg,#1f1f24 0%,#121215 100%); padding:26px 30px; border-bottom:1px solid #27272a;">
      <div style="display:inline-block; background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2); color:#fff; font-size:11px; font-weight:700; letter-spacing:0.12em; padding:4px 10px; border-radius:4px; margin-bottom:10px;">
        NOUVELLE RESERVATION · FORMATION
      </div>
      <h2 style="margin:0; font-size:22px; color:#fff; letter-spacing:-0.02em;">DR REMUS · CANDIDATURE FORMATION</h2>
      <p style="margin:6px 0 0; font-size:13px; color:#a1a1aa;">Un visiteur vient de soumettre une demande de réservation de place.</p>
    </div>
    <div style="padding:30px;">
      <table style="width:100%; border-collapse:collapse; margin-bottom:24px;">
        <tr>
          <td style="padding:11px 0; border-bottom:1px solid #27272a; color:#a1a1aa; font-size:13px; width:160px;"><strong>Formation</strong></td>
          <td style="padding:11px 0; border-bottom:1px solid #27272a; color:#fff; font-size:15px; font-weight:700;">' . htmlspecialchars($formation, ENT_QUOTES, 'UTF-8') . '</td>
        </tr>
        <tr>
          <td style="padding:11px 0; border-bottom:1px solid #27272a; color:#a1a1aa; font-size:13px;"><strong>Prénom</strong></td>
          <td style="padding:11px 0; border-bottom:1px solid #27272a; color:#fff; font-size:15px; font-weight:700;">' . htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8') . '</td>
        </tr>
        <tr>
          <td style="padding:11px 0; border-bottom:1px solid #27272a; color:#a1a1aa; font-size:13px;"><strong>Nom</strong></td>
          <td style="padding:11px 0; border-bottom:1px solid #27272a; color:#fff; font-size:15px;">' . htmlspecialchars($nom ?: '—', ENT_QUOTES, 'UTF-8') . '</td>
        </tr>
        <tr>
          <td style="padding:11px 0; border-bottom:1px solid #27272a; color:#a1a1aa; font-size:13px;"><strong>Email</strong></td>
          <td style="padding:11px 0; border-bottom:1px solid #27272a; font-size:15px;"><a href="mailto:' . htmlspecialchars($emailSafe, ENT_QUOTES, 'UTF-8') . '" style="color:#60a5fa; text-decoration:underline;">' . htmlspecialchars($emailSafe, ENT_QUOTES, 'UTF-8') . '</a></td>
        </tr>
        <tr>
          <td style="padding:11px 0; border-bottom:1px solid #27272a; color:#a1a1aa; font-size:13px;"><strong>WhatsApp</strong></td>
          <td style="padding:11px 0; border-bottom:1px solid #27272a; color:#fff; font-size:15px;">
            ' . htmlspecialchars($whatsapp, ENT_QUOTES, 'UTF-8') . '
            ' . ($hasWa ? ' <a href="' . $waLink . '" style="display:inline-block; margin-left:8px; color:#25D366; font-size:12px; font-weight:bold; text-decoration:none;">(Ouvrir discussion)</a>' : '') . '
          </td>
        </tr>
        <tr>
          <td style="padding:11px 0; border-bottom:1px solid #27272a; color:#a1a1aa; font-size:13px;"><strong>Date de soumission</strong></td>
          <td style="padding:11px 0; border-bottom:1px solid #27272a; color:#71717a; font-size:13px;">' . htmlspecialchars($dateStr, ENT_QUOTES, 'UTF-8') . '</td>
        </tr>
        <tr>
          <td style="padding:11px 0; color:#a1a1aa; font-size:13px;"><strong>Adresse IP</strong></td>
          <td style="padding:11px 0; color:#71717a; font-size:13px;">' . htmlspecialchars($visitorIp, ENT_QUOTES, 'UTF-8') . '</td>
        </tr>
      </table>

      ' . ($message ? '
      <div style="background:#0c0c0e; border:1px solid #27272a; border-radius:12px; padding:22px; margin-top:20px;">
        <h4 style="margin:0 0 12px; font-size:12px; text-transform:uppercase; color:#a1a1aa; letter-spacing:0.08em;">Message du candidat :</h4>
        <div style="font-size:15px; line-height:1.75; color:#f4f4f5; white-space:pre-wrap;">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>
      </div>' : '') . '
    </div>

    <div style="background:#18181b; padding:20px 30px; border-top:1px solid #27272a; text-align:center;">
      <a href="mailto:' . htmlspecialchars($emailSafe, ENT_QUOTES, 'UTF-8') . '?subject=Re:%20Votre%20réservation%20—%20' . rawurlencode($formation) . '" style="display:inline-block; background:#fff; color:#000; padding:12px 24px; border-radius:999px; text-decoration:none; font-weight:bold; font-size:14px; margin:4px 6px;">Répondre par Email</a>
      ' . ($hasWa ? '<a href="' . $waLink . '" style="display:inline-block; background:#25D366; color:#fff; padding:12px 24px; border-radius:999px; text-decoration:none; font-weight:bold; font-size:14px; margin:4px 6px;">Écrire sur WhatsApp</a>' : '') . '
    </div>
  </div>
</body>
</html>';

// ── 8. Email VISITEUR (confirmation de réception) ─────────────────
$visitorSubject = 'Reservation bien reçue — Dr Remus Formations';

$visitorHtml = '<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>' . htmlspecialchars($visitorSubject, ENT_QUOTES, 'UTF-8') . '</title></head>
<body style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif; background:#0a0a0c; color:#fff; margin:0; padding:30px 20px;">
  <div style="max-width:600px; margin:0 auto; background:#141418; border:1px solid #27272a; border-radius:16px; overflow:hidden; box-shadow:0 12px 40px rgba(0,0,0,0.6);">
    <div style="background:#18181b; padding:24px 30px; border-bottom:1px solid #27272a; text-align:center;">
      <h2 style="margin:0; font-size:20px; color:#fff; letter-spacing:0.02em;">DR REMUS</h2>
      <p style="margin:4px 0 0; font-size:12px; text-transform:uppercase; color:#a1a1aa; letter-spacing:0.12em;">Formations &amp; Accompagnement</p>
    </div>
    <div style="padding:32px 30px;">
      <h3 style="margin:0 0 16px; font-size:18px; color:#fff;">Bonjour ' . htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8') . ',</h3>
      <p style="font-size:15px; line-height:1.6; color:#d4d4d8; margin:0 0 16px;">
        Votre demande de réservation pour la formation <strong style="color:#fff;">' . htmlspecialchars($formation, ENT_QUOTES, 'UTF-8') . '</strong> a bien été reçue.
      </p>
      <div style="background:rgba(255,255,255,0.04); border-left:3px solid #fff; padding:14px 18px; border-radius:0 8px 8px 0; margin:20px 0;">
        <p style="margin:0; font-size:14px; color:#e4e4e7;">
          <strong>Prochaine étape :</strong> Je reviendrai vers vous dans les <strong>48 heures</strong> avec les informations de confirmation et les détails pratiques pour votre intégration dans la cohorte.
        </p>
      </div>
      <div style="background:#0c0c0e; border:1px solid #27272a; border-radius:12px; padding:20px; margin:24px 0;">
        <h4 style="margin:0 0 10px; font-size:12px; text-transform:uppercase; color:#a1a1aa; letter-spacing:0.06em;">Récapitulatif de votre demande :</h4>
        <table style="width:100%; border-collapse:collapse;">
          <tr>
            <td style="padding:7px 0; border-bottom:1px solid #27272a; color:#a1a1aa; font-size:13px; width:120px;">Formation</td>
            <td style="padding:7px 0; border-bottom:1px solid #27272a; color:#fff; font-size:14px; font-weight:600;">' . htmlspecialchars($formation, ENT_QUOTES, 'UTF-8') . '</td>
          </tr>
          <tr>
            <td style="padding:7px 0; border-bottom:1px solid #27272a; color:#a1a1aa; font-size:13px;">Prénom</td>
            <td style="padding:7px 0; border-bottom:1px solid #27272a; color:#fff; font-size:14px;">' . htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8') . '</td>
          </tr>
          <tr>
            <td style="padding:7px 0; border-bottom:1px solid #27272a; color:#a1a1aa; font-size:13px;">Nom</td>
            <td style="padding:7px 0; border-bottom:1px solid #27272a; color:#fff; font-size:14px;">' . htmlspecialchars($nom ?: '—', ENT_QUOTES, 'UTF-8') . '</td>
          </tr>
          <tr>
            <td style="padding:7px 0; border-bottom:1px solid #27272a; color:#a1a1aa; font-size:13px;">Email</td>
            <td style="padding:7px 0; border-bottom:1px solid #27272a; color:#fff; font-size:14px;">' . htmlspecialchars($emailSafe, ENT_QUOTES, 'UTF-8') . '</td>
          </tr>
          <tr>
            <td style="padding:7px 0; color:#a1a1aa; font-size:13px;">WhatsApp</td>
            <td style="padding:7px 0; color:#fff; font-size:14px;">' . htmlspecialchars($whatsapp, ENT_QUOTES, 'UTF-8') . '</td>
          </tr>
        </table>
      </div>
      <p style="font-size:14px; line-height:1.6; color:#a1a1aa; margin:0 0 20px;">
        Besoin d\'un échange immédiat ? Vous pouvez me joindre directement via WhatsApp :
      </p>
      <div style="text-align:center; margin:24px 0 10px;">
        <a href="https://wa.me/237693290135" style="display:inline-block; background:#25D366; color:#fff; padding:12px 24px; border-radius:999px; text-decoration:none; font-weight:700; font-size:14px; box-shadow:0 4px 14px rgba(37,211,102,0.35);">
          WhatsApp (+237 693 29 01 35)
        </a>
      </div>
    </div>
    <div style="background:#18181b; padding:18px 30px; border-top:1px solid #27272a; text-align:center; font-size:12px; color:#71717a;">
      <p style="margin:0;"><strong>Dr Remus</strong> · Formateur &amp; Développeur Full-Stack</p>
      <p style="margin:4px 0 0;">Douala, Cameroun · Formation 100 % en ligne</p>
    </div>
  </div>
</body>
</html>';

// ── 9. Fonction d'appel Brevo ─────────────────────────────────────
function callBrevoApi(string $apiKey, array $payload): array {
    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'accept: application/json',
            'api-key: ' . $apiKey,
            'content-type: application/json'
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_THROW_ON_ERROR),
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);
    return [
        'success'  => empty($curlErr) && ($httpCode === 200 || $httpCode === 201),
        'httpCode' => $httpCode,
        'response' => (string)$response,
        'error'    => $curlErr
    ];
}

// ── 10. Envoi de l'email à Dr Remus ──────────────────────────────
$adminRes = callBrevoApi($brevoApiKey, [
    'sender'      => ['name' => $mailFromName, 'email' => $mailFromEmail],
    'to'          => [['name' => $mailToName, 'email' => $mailToEmail]],
    'replyTo'     => ['name' => $nameSafe, 'email' => $emailSafe],
    'subject'     => $adminSubject,
    'htmlContent' => $adminHtml
]);

if (!$adminRes['success']) {
    $curlErr = $adminRes['error'];
    if (!empty($curlErr)) {
        http_response_code(502);
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion avec le serveur email : ' . $curlErr], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $resData  = json_decode($adminRes['response'], true);
    $errorMsg = $resData['message'] ?? 'Erreur lors de l\'envoi (code ' . $adminRes['httpCode'] . ').';
    http_response_code($adminRes['httpCode'] >= 400 && $adminRes['httpCode'] < 600 ? $adminRes['httpCode'] : 500);
    echo json_encode(['success' => false, 'message' => $errorMsg], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 11. Envoi de la confirmation au candidat ─────────────────────
callBrevoApi($brevoApiKey, [
    'sender'      => ['name' => $mailFromName, 'email' => $mailFromEmail],
    'to'          => [['name' => $nameSafe, 'email' => $emailSafe]],
    'replyTo'     => ['name' => $mailToName, 'email' => $mailToEmail],
    'subject'     => $visitorSubject,
    'htmlContent' => $visitorHtml
]);

// ── 11b. Enregistrement en base de données MySQL ─────────────────
try {
    require_once __DIR__ . '/admin/includes/db.php';
    $pdo = getDatabaseConnection();
    if ($pdo) {
        // Insertion de la réservation
        $stmtIns = $pdo->prepare("INSERT INTO `reservations` 
            (formation_titre, prenom, nom, email, whatsapp, message, statut, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'nouvelle', ?, NOW())");
        $stmtIns->execute([
            $formation,
            $prenom,
            $nom,
            $emailSafe,
            $whatsapp,
            $message,
            $visitorIp
        ]);

        // Décrémenter les places disponibles de la cohorte active si > 0
        $stmtDec = $pdo->prepare("UPDATE `formations` 
            SET `places_disponibles` = GREATEST(0, `places_disponibles` - 1),
                `statut` = CASE WHEN `places_disponibles` - 1 <= 0 THEN 'complet' ELSE `statut` END,
                `updated_at` = NOW()
            WHERE `titre` = ? OR `is_active_cohort` = 1
            LIMIT 1");
        $stmtDec->execute([$formation]);
    }
} catch (Throwable $e) {
    error_log("Erreur enregistrement DB réservation : " . $e->getMessage());
}

// ── 12. Réponse de succès ─────────────────────────────────────────
echo json_encode([
    'success' => true,
    'message' => 'Demande transmise avec succès ! Un email de confirmation vous a été envoyé à ' . htmlspecialchars($emailSafe, ENT_QUOTES, 'UTF-8') . '.'
], JSON_UNESCAPED_UNICODE);

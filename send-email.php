<?php
declare(strict_types=1);

// En-têtes JSON et sécurité
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

// Méthode POST obligatoire
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée. Seules les requêtes POST sont acceptées.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 1. Chargement et parsing sécurisé du fichier .env ─────────────
function loadEnvFile(string $path): array {
    if (!file_exists($path) || !is_readable($path)) {
        return [];
    }
    $env = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key   = trim($parts[0]);
            $value = trim($parts[1]);
            $value = trim($value, "\"'\t\n\r");
            $env[$key] = $value;
        }
    }
    return $env;
}

$env = loadEnvFile(__DIR__ . '/.env');

$brevoApiKey   = $env['BREVO_API_KEY']   ?? getenv('BREVO_API_KEY')   ?: '';
$mailFromEmail = $env['MAIL_FROM_EMAIL'] ?? getenv('MAIL_FROM_EMAIL') ?: '';
$mailFromName  = $env['MAIL_FROM_NAME']  ?? getenv('MAIL_FROM_NAME')  ?: 'Portfolio Dr Remus';
$mailToEmail   = $env['MAIL_TO_EMAIL']   ?? getenv('MAIL_TO_EMAIL')   ?: 'dsonkouatremus@gmail.com';
$mailToName    = $env['MAIL_TO_NAME']    ?? getenv('MAIL_TO_NAME')    ?: 'Dr Remus';

// ── 2. Récupération et Nettoyage des Données du Formulaire ────────
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    if ($raw) {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $input = $json;
        }
    }
}

// Piège anti-robot (Honeypot) : si rempli, c'est un bot
if (!empty($input['website_hp'])) {
    echo json_encode([
        'success' => true,
        'message' => 'Message envoyé avec succès.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$name        = trim((string)($input['name'] ?? $input['cName'] ?? ''));
$email       = trim((string)($input['email'] ?? $input['cEmail'] ?? ''));
$indicatif   = trim((string)($input['indicatif'] ?? ''));
$whatsappRaw = trim((string)($input['whatsapp'] ?? $input['cWhatsapp'] ?? ''));
$type        = trim((string)($input['type'] ?? $input['cType'] ?? 'Projet général'));
$message     = trim((string)($input['message'] ?? $input['cMsg'] ?? ''));

// Numéro WhatsApp internationalisé
if ($whatsappRaw !== '') {
    if (!str_starts_with($whatsappRaw, '+') && !empty($indicatif) && $indicatif !== '+') {
        $numPart = $whatsappRaw;
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

// ── 3. Validation des Champs ──────────────────────────────────────
if ($name === '' || mb_strlen($name) < 2) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Veuillez saisir votre nom ou celui de votre entreprise.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Veuillez saisir une adresse email valide.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($message === '' || mb_strlen($message) < 5) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Votre message doit comporter au moins 5 caractères.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 4. Vérification de la configuration Brevo ─────────────────────
if (empty($brevoApiKey) || str_starts_with($brevoApiKey, 'xkeysib-votre_cle')) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'La clé API Brevo n\'est pas encore configurée dans le fichier .env.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Sécurisation contre le Header Injection
$emailSafe = str_replace(["\r", "\n"], '', $email);
$nameSafe  = str_replace(["\r", "\n"], '', $name);

// ── 5. Préparation des Métadonnées et Actions ─────────────────────
$dateStr   = date('d/m/Y à H:i') . ' (UTC' . (date('P')) . ')';
$visitorIp = $_SERVER['REMOTE_ADDR'] ?? 'Non détectée';

// Nettoyage du numéro WhatsApp pour lien direct wa.me
$cleanPhone = preg_replace('/[^0-9]/', '', $whatsapp);
$hasWa = !empty($cleanPhone) && strlen($cleanPhone) >= 8;
$waLink = $hasWa ? "https://wa.me/{$cleanPhone}" : "";

// ── 6. Construction de l'Email pour DR REMUS (Toutes les infos prospect) ──
$subject = "🚨 [NOUVEAU LEAD] {$nameSafe} — {$type}";

$htmlBody = '<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>' . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . '</title></head>
<body style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif; background-color:#0a0a0c; color:#ffffff; margin:0; padding:30px 20px;">
  <div style="max-width:620px; margin:0 auto; background:#141418; border:1px solid #27272a; border-radius:16px; overflow:hidden; box-shadow:0 12px 40px rgba(0,0,0,0.6);">
    
    <!-- Entête Admin -->
    <div style="background:linear-gradient(135deg, #1f1f24 0%, #121215 100%); padding:26px 30px; border-bottom:1px solid #27272a;">
      <div style="display:inline-block; background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2); color:#ffffff; font-size:11px; font-weight:700; letter-spacing:0.12em; padding:4px 10px; border-radius:4px; margin-bottom:10px;">
        FICHE PROSPECT CLIENT · PORTFOLIO
      </div>
      <h2 style="margin:0; font-size:22px; color:#ffffff; letter-spacing:-0.02em;">DR REMUS · NOUVELLE DEMANDE</h2>
      <p style="margin:6px 0 0; font-size:13px; color:#a1a1aa;">Un visiteur vient de soumettre le formulaire de contact.</p>
    </div>

    <!-- Tableau récapitulatif complet de toutes les données saisies -->
    <div style="padding:30px;">
      <table style="width:100%; border-collapse:collapse; margin-bottom:24px;">
        <tr>
          <td style="padding:11px 0; border-bottom:1px solid #27272a; color:#a1a1aa; font-size:14px; width:150px;"><strong>Nom / Entreprise</strong></td>
          <td style="padding:11px 0; border-bottom:1px solid #27272a; color:#ffffff; font-size:15px; font-weight:700;">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</td>
        </tr>
        <tr>
          <td style="padding:11px 0; border-bottom:1px solid #27272a; color:#a1a1aa; font-size:14px;"><strong>Adresse Email</strong></td>
          <td style="padding:11px 0; border-bottom:1px solid #27272a; font-size:15px;"><a href="mailto:' . htmlspecialchars($emailSafe, ENT_QUOTES, 'UTF-8') . '" style="color:#60a5fa; text-decoration:underline;">' . htmlspecialchars($emailSafe, ENT_QUOTES, 'UTF-8') . '</a></td>
        </tr>
        <tr>
          <td style="padding:11px 0; border-bottom:1px solid #27272a; color:#a1a1aa; font-size:14px;"><strong>Numéro WhatsApp</strong></td>
          <td style="padding:11px 0; border-bottom:1px solid #27272a; font-size:15px; color:#ffffff;">
            ' . htmlspecialchars($whatsapp, ENT_QUOTES, 'UTF-8') . '
            ' . ($hasWa ? ' <a href="' . $waLink . '" style="display:inline-block; margin-left:8px; color:#25D366; font-size:12px; font-weight:bold; text-decoration:none;">(💬 Ouvrir discussion)</a>' : '') . '
          </td>
        </tr>
        <tr>
          <td style="padding:11px 0; border-bottom:1px solid #27272a; color:#a1a1aa; font-size:14px;"><strong>Nature du Projet</strong></td>
          <td style="padding:11px 0; border-bottom:1px solid #27272a; color:#ffffff; font-size:15px; font-weight:600;">' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '</td>
        </tr>
        <tr>
          <td style="padding:11px 0; border-bottom:1px solid #27272a; color:#a1a1aa; font-size:13px;"><strong>Date de Soumission</strong></td>
          <td style="padding:11px 0; border-bottom:1px solid #27272a; color:#71717a; font-size:13px;">' . htmlspecialchars($dateStr, ENT_QUOTES, 'UTF-8') . '</td>
        </tr>
        <tr>
          <td style="padding:11px 0; color:#a1a1aa; font-size:13px;"><strong>Adresse IP</strong></td>
          <td style="padding:11px 0; color:#71717a; font-size:13px;">' . htmlspecialchars($visitorIp, ENT_QUOTES, 'UTF-8') . '</td>
        </tr>
      </table>

      <!-- Message complet du visiteur -->
      <div style="background:#0c0c0e; border:1px solid #27272a; border-radius:12px; padding:22px; margin-top:20px;">
        <h4 style="margin:0 0 12px; font-size:12px; text-transform:uppercase; color:#a1a1aa; letter-spacing:0.08em;">Message Transmis :</h4>
        <div style="font-size:15px; line-height:1.75; color:#f4f4f5; white-space:pre-wrap;">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>
      </div>
    </div>

    <!-- Boutons d\'action rapide pour Dr Remus -->
    <div style="background:#18181b; padding:20px 30px; border-top:1px solid #27272a; text-align:center;">
      <a href="mailto:' . htmlspecialchars($emailSafe, ENT_QUOTES, 'UTF-8') . '?subject=Re:%20Votre%20demande%20sur%20mon%20portfolio" style="display:inline-block; background:#ffffff; color:#000000; padding:12px 24px; border-radius:999px; text-decoration:none; font-weight:bold; font-size:14px; margin:4px 6px;">
        ✉ Répondre par Email
      </a>
      ' . ($hasWa ? '<a href="' . $waLink . '" style="display:inline-block; background:#25D366; color:#ffffff; padding:12px 24px; border-radius:999px; text-decoration:none; font-weight:bold; font-size:14px; margin:4px 6px;">💬 Écrire sur WhatsApp</a>' : '') . '
    </div>
  </div>
</body>
</html>';

$textBody = "NOUVELLE DEMANDE PROSPECT (DR REMUS)\n"
          . "=====================================\n\n"
          . "Nom / Entreprise : {$name}\n"
          . "Email            : {$emailSafe}\n"
          . "WhatsApp         : {$whatsapp}\n"
          . "Nature du projet : {$type}\n"
          . "Date             : {$dateStr}\n"
          . "Adresse IP       : {$visitorIp}\n\n"
          . "MESSAGE DU VISITEUR :\n"
          . "-------------------------------------\n"
          . "{$message}\n\n"
          . "=====================================";

// ── 5. Fonction d'Envoi Sécurisé vers l'API Brevo ─────────────────
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

// ── 6. Construction et Envoi de l'Email pour Dr Remus ─────────────
$adminPayload = [
    'sender' => [
        'name'  => $mailFromName,
        'email' => $mailFromEmail
    ],
    'to' => [
        [
            'name'  => $mailToName,
            'email' => $mailToEmail
        ]
    ],
    'replyTo' => [
        'name'  => $nameSafe,
        'email' => $emailSafe
    ],
    'subject'     => $subject,
    'htmlContent' => $htmlBody,
    'textContent' => $textBody
];

$adminRes = callBrevoApi($brevoApiKey, $adminPayload);

if (!$adminRes['success']) {
    if (!empty($adminRes['error'])) {
        http_response_code(502);
        echo json_encode([
            'success' => false,
            'message' => 'Erreur de connexion avec le serveur d\'email : ' . $adminRes['error']
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $resData = json_decode($adminRes['response'], true);
    $errorMsg = $resData['message'] ?? 'Une erreur est survenue lors de l\'envoi via Brevo (Code ' . $adminRes['httpCode'] . ').';
    http_response_code($adminRes['httpCode'] >= 400 && $adminRes['httpCode'] < 600 ? $adminRes['httpCode'] : 500);
    echo json_encode([
        'success' => false,
        'message' => $errorMsg
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 7. Construction et Envoi de l'Accusé de Réception au Visiteur ─
$visitorSubject = "✓ Message bien reçu — Dr Remus";

$visitorHtmlBody = '<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>' . htmlspecialchars($visitorSubject, ENT_QUOTES, 'UTF-8') . '</title></head>
<body style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif; background-color:#0a0a0c; color:#ffffff; margin:0; padding:30px 20px;">
  <div style="max-width:600px; margin:0 auto; background:#141418; border:1px solid #27272a; border-radius:16px; overflow:hidden; box-shadow:0 12px 40px rgba(0,0,0,0.6);">
    <div style="background:#18181b; padding:24px 30px; border-bottom:1px solid #27272a; text-align:center;">
      <h2 style="margin:0; font-size:20px; color:#ffffff; letter-spacing:0.02em;">DR REMUS</h2>
      <p style="margin:4px 0 0; font-size:12px; text-transform:uppercase; color:#a1a1aa; letter-spacing:0.12em;">Automatisme Industriel &amp; Développement Full-Stack</p>
    </div>
    <div style="padding:32px 30px;">
      <h3 style="margin:0 0 16px; font-size:18px; color:#ffffff;">Bonjour ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</h3>
      
      <p style="font-size:15px; line-height:1.6; color:#d4d4d8; margin:0 0 16px;">
        Merci d\'avoir pris contact via mon portfolio ! J\'ai bien reçu votre demande relative à votre projet : 
        <strong style="color:#ffffff;">' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '</strong>.
      </p>

      <div style="background:rgba(255,255,255,0.04); border-left:3px solid #ffffff; padding:14px 18px; border-radius:0 8px 8px 0; margin:20px 0;">
        <p style="margin:0; font-size:14px; color:#e4e4e7;">
          ⚡ <strong>Délai de réponse :</strong> Je prends actuellement connaissance des éléments transmis et je reviendrai vers vous dans un délai maximal de <strong>24 heures</strong>.
        </p>
      </div>

      <div style="background:#0c0c0e; border:1px solid #27272a; border-radius:12px; padding:20px; margin:24px 0;">
        <h4 style="margin:0 0 10px; font-size:12px; text-transform:uppercase; color:#a1a1aa; letter-spacing:0.06em;">Récapitulatif de votre message :</h4>
        <div style="font-size:14px; line-height:1.6; color:#a1a1aa; white-space:pre-wrap;">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>
      </div>

      <p style="font-size:14px; line-height:1.6; color:#a1a1aa; margin:0 0 20px;">
        Votre besoin nécessite un échange immédiat ou direct ? Vous pouvez également me joindre directement via WhatsApp :
      </p>

      <div style="text-align:center; margin:24px 0 10px;">
        <a href="https://wa.me/237693290135" style="display:inline-block; background:#25D366; color:#ffffff; padding:12px 24px; border-radius:999px; text-decoration:none; font-weight:700; font-size:14px; box-shadow:0 4px 14px rgba(37,211,102,0.35);">
          Contacter sur WhatsApp (+237 693 29 01 35)
        </a>
      </div>
    </div>

    <div style="background:#18181b; padding:18px 30px; border-top:1px solid #27272a; text-align:center; font-size:12px; color:#71717a;">
      <p style="margin:0;"><strong>Dr Remus</strong> · Technicien en Automatisme &amp; Développeur Full-Stack</p>
      <p style="margin:4px 0 0;">Douala, Cameroun · Mobilité &amp; Télétravail · <a href="https://github.com/Rems-21" style="color:#a1a1aa; text-decoration:underline;">github.com/Rems-21</a></p>
    </div>
  </div>
</body>
</html>';

$visitorTextBody = "BONJOUR " . mb_strtoupper($name, 'UTF-8') . ",\n\n"
                 . "Merci d'avoir pris contact via mon portfolio !\n"
                 . "J'ai bien reçu votre demande relative à votre projet : {$type}.\n\n"
                 . "Je prends actuellement connaissance des éléments transmis et je reviens vers vous sous 24 heures.\n\n"
                 . "RÉCAPITULATIF DE VOTRE MESSAGE :\n"
                 . "-------------------------------------\n"
                 . "{$message}\n"
                 . "-------------------------------------\n\n"
                 . "Besoin d'un échange direct ? Vous pouvez également me joindre sur WhatsApp au +237 693 29 01 35 : https://wa.me/237693290135\n\n"
                 . "Cordialement,\n"
                 . "Dr Remus — Technicien en Automatisme & Développeur Full-Stack\n"
                 . "Douala, Cameroun | https://github.com/Rems-21";

$visitorPayload = [
    'sender' => [
        'name'  => $mailFromName,
        'email' => $mailFromEmail
    ],
    'to' => [
        [
            'name'  => $nameSafe,
            'email' => $emailSafe
        ]
    ],
    'replyTo' => [
        'name'  => $mailToName,
        'email' => $mailToEmail
    ],
    'subject'     => $visitorSubject,
    'htmlContent' => $visitorHtmlBody,
    'textContent' => $visitorTextBody
];

// Envoi de l'accusé de réception
callBrevoApi($brevoApiKey, $visitorPayload);

// ── 11b. Enregistrement en base de données MySQL ─────────────────
try {
    require_once __DIR__ . '/admin/includes/db.php';
    $pdo = getDatabaseConnection();
    if ($pdo) {
        $stmtIns = $pdo->prepare("INSERT INTO `reservations` 
            (formation_titre, prenom, nom, email, whatsapp, message, statut, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'nouvelle', ?, NOW())");
        $stmtIns->execute([
            'Message Contact : ' . ($type ?: 'Général'),
            $name,
            '',
            $emailSafe,
            $whatsapp,
            $message,
            $visitorIp ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1')
        ]);
    }
} catch (Throwable $e) {
    error_log("Erreur insertion contact DB : " . $e->getMessage());
}

// Réponse JSON de succès confirmant les deux envois
echo json_encode([
    'success' => true,
    'message' => 'Merci ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ', votre message a bien été envoyé ! Un e-mail de confirmation vous a également été transmis.'
], JSON_UNESCAPED_UNICODE);


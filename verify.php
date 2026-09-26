<?php
declare(strict_types=1);

require_once __DIR__ . '/admin/includes/db.php';

$pdo = getDatabaseConnection();

$certQuery = trim((string)($_GET['cert'] ?? $_GET['id'] ?? ''));
$certificate = null;
$searched = false;
$errorMsg = '';

if ($certQuery !== '') {
    $searched = true;
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM certificats WHERE cert_id = :q OR qr_code_hash = :q LIMIT 1");
            $stmt->execute([':q' => $certQuery]);
            $certificate = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$certificate) {
                $errorMsg = "Aucun certificat correspondant à la référence « " . htmlspecialchars($certQuery) . " » n'a été trouvé dans le registre officiel.";
            }
        } catch (PDOException $e) {
            $errorMsg = "Erreur de connexion au registre des certificats. Veuillez réessayer ultérieurement.";
        }
    } else {
        $errorMsg = "Base de données inaccessible actuellement.";
    }
}

// Formatage de la date en français
function formatFrenchDate(string $dateSql): string {
    if (empty($dateSql)) return 'Non daté';
    $t = strtotime($dateSql);
    if (!$t) return $dateSql;
    $mois = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
    return date('j', $t) . ' ' . $mois[(int)date('n', $t) - 1] . ' ' . date('Y', $t);
}

$pageTitle = $certificate 
    ? "Certificat Officiel #" . htmlspecialchars($certificate['cert_id']) . " — Dr Remus" 
    : "Vérificateur Public de Certificats — Dr Remus";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?></title>
  <meta name="description" content="Plateforme officielle de vérification d'authenticité des diplômes et attestations délivrés par Dr Remus.">
  <link rel="icon" href="logo.jpg" type="image/jpeg">
  <link rel="stylesheet" href="css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Great+Vibes&family=Playfair+Display:ital,wght@0,700;0,800;0,900;1,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">

  <style>
    /* ── BASE & CONTAINER POUR VERIFY ────────────────────────────── */
    :root {
      --gold-primary: #c89f3b;
      --gold-light: #fef08a;
      --gold-dark: #92690d;
      --gold-gradient: linear-gradient(135deg, #c59b27 0%, #fef08a 45%, #b48618 70%, #fef9c3 85%, #92690d 100%);
      --dark-graphite: #0c0d12;
      --dark-surface: #14151c;
      --border-subtle: rgba(255, 255, 255, 0.1);
    }

    body {
      background-color: #08090d;
      color: #e4e4e7;
      font-family: 'Plus Jakarta Sans', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      margin: 0;
      padding: 0;
      overflow-x: hidden;
      -webkit-font-smoothing: antialiased;
    }

    /* ── TOP NAV INSTITUTIONNELLE ────────────────────────────────── */
    .v-nav {
      background: rgba(12, 13, 18, 0.88);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .v-nav-inner {
      max-width: 1180px;
      margin: 0 auto;
      padding: 14px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .v-nav-brand {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
      color: #ffffff;
    }

    .v-nav-logo {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      object-fit: cover;
      border: 1.5px solid rgba(255, 255, 255, 0.2);
    }

    .v-nav-title {
      font-size: 1.05rem;
      font-weight: 800;
      letter-spacing: 0.02em;
      line-height: 1.2;
    }

    .v-nav-sub {
      font-size: 0.72rem;
      color: #a1a1aa;
      font-family: 'JetBrains Mono', monospace;
    }

    .v-nav-links {
      display: flex;
      gap: 14px;
      align-items: center;
    }

    .v-nav-link {
      font-size: 0.84rem;
      color: #a1a1aa;
      text-decoration: none;
      transition: color 0.2s;
    }
    .v-nav-link:hover { color: #ffffff; }

    .v-nav-btn {
      font-size: 0.82rem;
      font-weight: 700;
      background: #ffffff;
      color: #000000;
      padding: 8px 14px;
      border-radius: 8px;
      text-decoration: none;
      transition: all 0.2s;
    }
    .v-nav-btn:hover { background: #e4e4e7; transform: translateY(-1px); }

    /* ── CORPS DE LA PAGE ────────────────────────────────────────── */
    .v-main {
      flex: 1;
      max-width: 1140px;
      margin: 0 auto;
      width: 100%;
      padding: 30px 20px 80px;
      box-sizing: border-box;
    }

    /* ── SECTION RECHERCHE HERO (QUAND PAS DE CERTIFICAT) ───────── */
    .v-hero-box {
      text-align: center;
      max-width: 680px;
      margin: 40px auto;
    }

    .v-hero-badge {
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 0.1em;
      color: #c89f3b;
      background: rgba(200, 159, 59, 0.1);
      border: 1px solid rgba(200, 159, 59, 0.28);
      padding: 5px 14px;
      border-radius: 999px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 18px;
    }

    .v-hero-title {
      font-size: 2.3rem;
      font-weight: 900;
      color: #ffffff;
      line-height: 1.2;
      margin-bottom: 14px;
      letter-spacing: -0.02em;
    }

    .v-hero-desc {
      font-size: 1rem;
      color: #a1a1aa;
      line-height: 1.6;
      margin-bottom: 30px;
    }

    /* Formulaire de recherche */
    .v-search-form {
      display: flex;
      gap: 10px;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.16);
      border-radius: 14px;
      padding: 6px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
      max-width: 560px;
      margin: 0 auto 20px;
      transition: border-color 0.2s;
    }

    .v-search-form:focus-within {
      border-color: #ffffff;
      box-shadow: 0 0 20px rgba(255, 255, 255, 0.1);
    }

    .v-search-input {
      flex: 1;
      background: transparent;
      border: none;
      outline: none;
      color: #ffffff;
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.95rem;
      padding: 12px 16px;
    }

    .v-search-input::placeholder {
      color: #71717a;
      font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .v-search-btn {
      background: #ffffff;
      color: #000000;
      border: none;
      border-radius: 10px;
      font-family: inherit;
      font-size: 0.88rem;
      font-weight: 700;
      padding: 12px 22px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.2s;
    }
    .v-search-btn:hover {
      background: #e4e4e7;
      transform: translateY(-1px);
    }

    .v-quick-samples {
      font-size: 0.84rem;
      color: #71717a;
    }
    .v-quick-samples a {
      color: #c89f3b;
      text-decoration: underline;
      font-family: 'JetBrains Mono', monospace;
      margin: 0 6px;
    }

    .v-error-box {
      max-width: 560px;
      margin: 30px auto;
      background: rgba(239, 68, 68, 0.08);
      border: 1px solid rgba(239, 68, 68, 0.25);
      border-radius: 14px;
      padding: 20px;
      text-align: center;
      color: #fca5a5;
      font-size: 0.9rem;
    }

    /* ── BARRE D'ACTIONS AU-DESSUS DU CERTIFICAT ─────────────────── */
    .v-actions-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
      flex-wrap: wrap;
      gap: 12px;
    }

    .v-status-indicator {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.78rem;
      font-weight: 700;
      padding: 6px 14px;
      border-radius: 999px;
    }

    .v-status-valide {
      color: #22c55e;
      background: rgba(34, 197, 94, 0.12);
      border: 1px solid rgba(34, 197, 94, 0.3);
    }

    .v-status-revoque {
      color: #ef4444;
      background: rgba(239, 68, 68, 0.12);
      border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .v-actions-btns {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .v-action-btn {
      font-size: 0.82rem;
      font-weight: 600;
      padding: 8px 14px;
      border-radius: 8px;
      border: 1px solid rgba(255, 255, 255, 0.15);
      background: rgba(255, 255, 255, 0.05);
      color: #ffffff;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      text-decoration: none;
      transition: all 0.2s;
    }

    .v-action-btn:hover {
      background: rgba(255, 255, 255, 0.12);
      border-color: rgba(255, 255, 255, 0.3);
    }

    .v-action-btn--primary {
      background: #c89f3b;
      color: #000000;
      border-color: #c89f3b;
      font-weight: 700;
    }
    .v-action-btn--primary:hover {
      background: #dfb54d;
      color: #000000;
    }

    /* ═══════════════════════════════════════════════════════════════
       LE CERTIFICAT OFFICIEL EXACT (STYLE DR ACADEMY)
       ═══════════════════════════════════════════════════════════════ */
    .cert-frame-wrapper {
      max-width: 1040px;
      margin: 0 auto;
      position: relative;
    }

    .cert-paper {
      background: #ffffff;
      color: #111111;
      position: relative;
      border-radius: 6px;
      box-shadow: 0 25px 80px rgba(0, 0, 0, 0.75), 0 0 40px rgba(200, 159, 59, 0.15);
      overflow: hidden;
      padding: 42px 46px 36px 46px;
      box-sizing: border-box;
      min-height: 690px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    /* ── CADRE DORÉ INTÉRIEUR ───────────────────────────────────── */
    .cert-gold-border-inset {
      position: absolute;
      inset: 12px;
      border: 1.5px solid #d4af37;
      pointer-events: none;
      border-radius: 4px;
      z-index: 5;
    }

    /* ── COINS GÉOMÉTRIQUES NOIR & OR (SVG OVERLAYS) ─────────────── */
    .cert-corner-top-left {
      position: absolute;
      top: 0;
      left: 0;
      width: 320px;
      height: 180px;
      pointer-events: none;
      z-index: 6;
    }

    .cert-corner-bottom-right {
      position: absolute;
      bottom: 0;
      right: 0;
      width: 160px;
      height: 120px;
      pointer-events: none;
      z-index: 6;
    }

    /* ── ONDULATIONS GUILLOCHE EN ARRIÈRE-PLAN ──────────────────── */
    .cert-waves-bg {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: 1;
      opacity: 0.7;
    }

    /* ── LOGO & BRANDING DANS LE COIN NOIR HAUT-GAUCHE ───────────── */
    .cert-brand-corner {
      position: absolute;
      top: 18px;
      left: 22px;
      z-index: 10;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      gap: 3px;
    }

    .cert-wolf-logo {
      width: 48px;
      height: 48px;
      object-fit: contain;
      filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.4));
    }

    .cert-brand-title {
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 13px;
      font-weight: 800;
      letter-spacing: 0.12em;
      color: #ffffff;
      line-height: 1.1;
      margin-top: 4px;
    }

    .cert-brand-tagline {
      font-family: 'JetBrains Mono', monospace;
      font-size: 6.8px;
      font-weight: 600;
      letter-spacing: 0.16em;
      color: #d4af37;
      text-transform: uppercase;
    }

    /* ── RUBAN ET MÉDAILLE DORÉE GAUCHE ─────────────────────────── */
    .cert-seal-ribbon-wrap {
      position: absolute;
      top: 135px;
      left: 36px;
      z-index: 12;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    /* Rubans noirs suspendus avec découpe V */
    .cert-ribbon-tails {
      position: absolute;
      top: 40px;
      display: flex;
      gap: 10px;
      z-index: 1;
    }

    .cert-ribbon-tail {
      width: 28px;
      height: 80px;
      background: #111217;
      clip-path: polygon(0 0, 100% 0, 100% 100%, 50% calc(100% - 15px), 0 100%);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    }

    /* Médaille ronde dorée */
    .cert-gold-medal {
      width: 110px;
      height: 110px;
      position: relative;
      z-index: 2;
      filter: drop-shadow(0 6px 14px rgba(0, 0, 0, 0.35));
    }

    /* ── EN-TÊTE SUPÉRIEUR DROIT : NUMÉRO DE CERTIFICAT ─────────── */
    .cert-top-header {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      position: relative;
      z-index: 8;
      padding-top: 2px;
      padding-right: 12px;
    }

    .cert-number {
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 13.5px;
      font-weight: 700;
      color: #27272a;
      letter-spacing: 0.04em;
    }

    /* ── CORPS CENTRAL DU CERTIFICAT ────────────────────────────── */
    .cert-main-content {
      text-align: center;
      position: relative;
      z-index: 8;
      margin: 4px auto 0;
      max-width: 660px;
      width: 100%;
    }

    /* Titre CERTIFICAT DE FORMATION */
    .cert-title-big {
      font-family: 'Cinzel', 'Playfair Display', serif;
      font-size: 46px;
      font-weight: 900;
      letter-spacing: 0.1em;
      color: #0a0a0a;
      line-height: 1;
      margin: 0;
      text-transform: uppercase;
    }

    .cert-sub-bar {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 16px;
      margin: 10px 0 16px;
    }

    .cert-sub-bar-line {
      width: 90px;
      height: 1.5px;
      background: #c89f3b;
    }

    .cert-sub-bar-text {
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 16px;
      font-weight: 800;
      letter-spacing: 0.28em;
      color: #c89f3b;
      text-transform: uppercase;
    }

    .cert-attestation-txt {
      font-size: 14px;
      color: #52525b;
      margin: 0 0 4px;
      font-weight: 500;
    }

    /* Nom de l'apprenant en calligraphie élégante */
    .cert-recipient-name {
      font-family: 'Great Vibes', cursive;
      font-size: 56px;
      color: #111217;
      line-height: 1.15;
      margin: 2px 0 0;
      font-weight: 400;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .cert-name-underline {
      width: 380px;
      height: 1.5px;
      background: linear-gradient(90deg, transparent 0%, #c89f3b 25%, #c89f3b 75%, transparent 100%);
      margin: 6px auto 14px;
    }

    .cert-course-intro {
      font-size: 13px;
      color: #52525b;
      margin: 0 0 6px;
    }

    /* Titre de la formation */
    .cert-course-title {
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 27px;
      font-weight: 800;
      color: #09090b;
      margin: 0 0 8px;
      letter-spacing: -0.01em;
    }

    .cert-course-desc {
      font-size: 12.5px;
      color: #52525b;
      line-height: 1.55;
      max-width: 630px;
      margin: 0 auto 14px;
    }

    /* ── BADGE DE MENTION / DISTINCTION HONORIFIQUE ─────────────── */
    .cert-mention-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: linear-gradient(135deg, rgba(200, 159, 59, 0.12) 0%, rgba(254, 240, 138, 0.24) 50%, rgba(200, 159, 59, 0.12) 100%);
      border: 1px solid rgba(200, 159, 59, 0.5);
      border-radius: 9999px;
      padding: 4px 18px 4px 16px;
      margin: 0 auto 12px;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 11.5px;
      font-weight: 800;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: #854d0e;
      box-shadow: 0 2px 6px rgba(200, 159, 59, 0.08);
    }

    .cert-mention-star {
      color: #c89f3b;
      font-size: 11px;
      line-height: 1;
    }

    /* ── BOÎTE RECAPITULATIF (3 COLONNES DANS CONTAINER CLAIR) ──── */
    .cert-meta-pill-card {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 10px 22px;
      max-width: 670px;
      margin: 0 auto 16px;
      display: grid;
      grid-template-columns: 1.4fr 1fr 1.3fr;
      align-items: center;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
    }

    .cert-pill-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 0 8px;
    }

    .cert-pill-item:not(:last-child) {
      border-right: 1px solid #e2e8f0;
    }

    .cert-pill-icon {
      width: 24px;
      height: 24px;
      color: #d97706;
      flex-shrink: 0;
    }

    .cert-pill-content {
      text-align: left;
    }

    .cert-pill-label {
      font-size: 10px;
      color: #71717a;
      text-transform: uppercase;
      font-weight: 700;
      letter-spacing: 0.04em;
      line-height: 1.1;
      margin-bottom: 2px;
    }

    .cert-pill-val {
      font-size: 12.5px;
      color: #18181b;
      font-weight: 700;
      line-height: 1.2;
    }

    /* ── PIED DE PAGE : DATE & LIEU | SIGNATURE | QR CODE ───────── */
    .cert-footer-row {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      position: relative;
      z-index: 8;
      padding: 0 35px 4px 35px;
      margin-top: 8px;
    }

    .cert-foot-left {
      text-align: left;
      font-size: 12px;
      color: #18181b;
      font-weight: 600;
      line-height: 1.6;
    }

    .cert-foot-center {
      text-align: center;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .cert-signature-svg {
      width: 155px;
      height: 50px;
      object-fit: contain;
    }

    .cert-sign-line {
      width: 160px;
      height: 1.5px;
      background: #c89f3b;
      margin: 4px 0 6px;
    }

    .cert-sign-name {
      font-size: 13px;
      font-weight: 700;
      color: #18181b;
      line-height: 1.2;
    }

    .cert-sign-role {
      font-size: 11px;
      color: #71717a;
      line-height: 1.2;
    }

    .cert-foot-right {
      text-align: center;
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-right: 20px;
    }

    .cert-qr-box {
      width: 70px;
      height: 70px;
      background: #ffffff;
      padding: 3px;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }

    .cert-qr-img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    .cert-qr-label {
      font-size: 9.5px;
      color: #71717a;
      margin-top: 4px;
      font-weight: 500;
    }

    .cert-qr-url {
      font-size: 9px;
      color: #0284c7;
      font-family: 'JetBrains Mono', monospace;
      font-weight: 600;
      text-decoration: none;
    }

    /* ── FOOTER SIMPLE DE LA PAGE ────────────────────────────────── */
    .v-footer {
      border-top: 1px solid rgba(255, 255, 255, 0.06);
      padding: 24px 20px;
      text-align: center;
      font-size: 0.8rem;
      color: #71717a;
      background: #08090b;
    }

    /* ── IMPRESSION PROPRE HAUTE RÉSOLUTION (@MEDIA PRINT) ──────── */
    @media print {
      @page {
        size: 297mm 210mm;
        margin: 0mm !important;
      }
      *, *::before, *::after {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }
      html, body {
        background: #ffffff !important;
        color: #000000 !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 297mm !important;
        height: 210mm !important;
        max-width: 297mm !important;
        max-height: 210mm !important;
        overflow: hidden !important;
      }
      .v-nav, .v-actions-bar, .v-footer, .v-hero-box, .v-search-form, .v-quick-samples, .v-error-box {
        display: none !important;
      }
      .v-main {
        padding: 0 !important;
        margin: 0 !important;
        width: 297mm !important;
        height: 210mm !important;
        max-width: 297mm !important;
        max-height: 210mm !important;
        overflow: hidden !important;
      }
      .cert-frame-wrapper {
        width: 297mm !important;
        height: 210mm !important;
        max-width: 297mm !important;
        max-height: 210mm !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: hidden !important;
      }
      .cert-paper {
        box-shadow: none !important;
        border-radius: 0 !important;
        width: 297mm !important;
        height: 210mm !important;
        max-width: 297mm !important;
        max-height: 210mm !important;
        min-height: 210mm !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
        padding: 10mm 14mm 8mm 14mm !important;
        page-break-after: avoid !important;
        page-break-before: avoid !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
      }
      .cert-seal-ribbon-wrap {
        position: absolute !important;
        top: 36mm !important;
        left: 10mm !important;
        display: flex !important;
        flex-direction: column !important;
      }
      .cert-footer-row {
        display: flex !important;
        flex-direction: row !important;
        justify-content: space-between !important;
        align-items: flex-end !important;
        padding: 0 10mm 2mm 10mm !important;
      }
    }

    @media screen and (max-width: 820px) {
      .cert-paper {
        padding: 24px 16px;
        min-height: auto;
      }
      .cert-corner-top-left {
        width: 220px;
        height: 120px;
      }
      .cert-seal-ribbon-wrap {
        position: static;
        margin: 10px auto;
      }
      .cert-title-big {
        font-size: 32px;
      }
      .cert-recipient-name {
        font-size: 38px;
      }
      .cert-course-title {
        font-size: 20px;
      }
      .cert-meta-pill-card {
        grid-template-columns: 1fr;
        gap: 10px;
      }
      .cert-pill-item:not(:last-child) {
        border-right: none;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 8px;
      }
      .cert-footer-row {
        flex-direction: column;
        align-items: center;
        gap: 16px;
        text-align: center;
        padding: 0;
      }
      .cert-foot-left {
        text-align: center;
      }
    }
  </style>
</head>
<body>

  <!-- ═══ NAVIGATION HAUTE ═══════════════════════════════════════════ -->
  <header class="v-nav">
    <div class="v-nav-inner">
      <a href="index.html" class="v-nav-brand">
        <img src="logo.jpg" alt="Dr Remus" class="v-nav-logo">
        <div>
          <div class="v-nav-title">Dr Remus · Certification</div>
          <div class="v-nav-sub">REGISTRE PUBLIC OFFICIEL</div>
        </div>
      </a>
      <div class="v-nav-links">
        <a href="formation.html" class="v-nav-link">Formations</a>
        <a href="index.html#contact" class="v-nav-link">Contact</a>
        <a href="index.html" class="v-nav-btn">← Portfolio</a>
      </div>
    </div>
  </header>

  <!-- ═══ CONTENU PRINCIPAL ═════════════════════════════════════════ -->
  <main class="v-main">

    <?php if (!$certificate): ?>
      <!-- ── VUE DE RECHERCHE / ACCUEIL VÉRIFICATEUR ───────────────── -->
      <div class="v-hero-box">
        <div class="v-hero-badge">
          <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          <span>REGISTRE OFFICIEL VÉRIFIÉ</span>
        </div>
        <h1 class="v-hero-title">Vérificateur Public d'Authenticité</h1>
        <p class="v-hero-desc">
          Entrez le numéro d'identification unique figurant sur le diplôme ou scannez le QR code de l'attestation pour contrôler son authenticité en direct sur la base de données officielle.
        </p>

        <form action="verify.php" method="GET" class="v-search-form">
          <input 
            type="text" 
            name="cert" 
            class="v-search-input" 
            placeholder="Ex : CERT-2026-0158 ou REMUS-2026-001" 
            value="<?= htmlspecialchars($certQuery) ?>" 
            required 
            autocomplete="off"
            autofocus
          >
          <button type="submit" class="v-search-btn">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            Vérifier
          </button>
        </form>

        <div class="v-quick-samples">
          Exemples de certificats vérifiés : 
          <a href="verify.php?cert=CERT-2026-0158">CERT-2026-0158</a> ·
          <a href="verify.php?cert=REMUS-2026-001">REMUS-2026-001</a>
        </div>
      </div>

      <?php if ($errorMsg !== ''): ?>
        <div class="v-error-box">
          <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="#ef4444" stroke-width="2" style="margin-bottom:8px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <p><?= $errorMsg ?></p>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <!-- ── VUE DU DIPLÔME OFFICIEL VÉRIFIÉ (STYLE EXACT DU MODÈLE) ─ -->
      <?php
        $certIdClean = htmlspecialchars($certificate['cert_id']);
        $recipientClean = htmlspecialchars($certificate['nom_apprenant']);
        $trainingClean = htmlspecialchars($certificate['formation_titre']);
        
        $descClean = htmlspecialchars(!empty($certificate['description_cert']) 
            ? $certificate['description_cert'] 
            : "Cette formation a couvert les notions essentielles et les compétences pratiques pour concevoir, développer et déployer un site web professionnel à l'aide des outils d'IA.");
        
        $periodeClean = htmlspecialchars(!empty($certificate['periode']) ? $certificate['periode'] : '15 Juin 2026 – 15 Juillet 2026');
        $dureeClean = htmlspecialchars(!empty($certificate['duree']) ? $certificate['duree'] : '30 heures');
        $niveauClean = htmlspecialchars(!empty($certificate['niveau']) ? $certificate['niveau'] : 'Débutant → Intermédiaire');
        $lieuClean = htmlspecialchars(!empty($certificate['lieu']) ? $certificate['lieu'] : 'À Douala, Cameroun');
        $formateurClean = htmlspecialchars(!empty($certificate['formateur']) ? $certificate['formateur'] : 'Dr Remus');
        $mentionClean = htmlspecialchars(!empty($certificate['mention']) ? $certificate['mention'] : 'Mention Très Bien');
        
        $dateClean = formatFrenchDate($certificate['date_emission']);
        $isRevoked = ($certificate['statut'] === 'revoque');

        // URL complète pour le partage et le QR code
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $currentHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $fullCertUrl = "{$protocol}://{$currentHost}/portfolio/verify.php?cert=" . urlencode($certificate['cert_id']);
        $shortCertUrl = "dr-academy.com/verify/" . htmlspecialchars(substr($certificate['cert_id'], -4));

        // URL du QR code (service universel haute fidélité)
        $qrCodeApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&margin=0&data=" . urlencode($fullCertUrl);

        // Lien LinkedIn Add Certification
        $issueYear = date('Y', strtotime($certificate['date_emission']));
        $issueMonth = date('n', strtotime($certificate['date_emission']));
        $linkedInUrl = "https://www.linkedin.com/profile/add?startTask=CERTIFICATION_NAME&name=" . urlencode($certificate['formation_titre']) . "&organizationName=" . urlencode("DR ACADEMY") . "&issueYear={$issueYear}&issueMonth={$issueMonth}&certUrl=" . urlencode($fullCertUrl) . "&certId=" . urlencode($certificate['cert_id']);
      ?>

      <div class="cert-frame-wrapper">

        <!-- Barre d'actions -->
        <div class="v-actions-bar">
          <div>
            <?php if (!$isRevoked): ?>
              <span class="v-status-indicator v-status-valide">
                <span class="status-dot" style="background:#22c55e;"></span> CERTIFICAT AUTHENTIQUE &amp; VÉRIFIÉ
              </span>
            <?php else: ?>
              <span class="v-status-indicator v-status-revoque">
                <span class="status-dot" style="background:#ef4444;"></span> STATUT : RÉVOQUÉ / INVALIDÉ
              </span>
            <?php endif; ?>
          </div>

          <div class="v-actions-btns">
            <button type="button" class="v-action-btn" onclick="copyCertLink()">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              <span id="copyLinkTxt">Copier le lien</span>
            </button>
            <a href="<?= $linkedInUrl ?>" target="_blank" rel="noopener" class="v-action-btn">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
              LinkedIn
            </a>
            <button type="button" class="v-action-btn v-action-btn--primary" onclick="window.print()">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
              Imprimer / PDF
            </button>
            <a href="verify.php" class="v-action-btn">
              Rechercher un autre
            </a>
          </div>
        </div>

        <?php if ($isRevoked): ?>
          <div class="v-error-box" style="margin-bottom: 24px;">
            <strong>ATTENTION :</strong> Ce certificat officiel a été révoqué par l'organisme émetteur pour non-respect des critères ou expiration des prérequis.
          </div>
        <?php endif; ?>

        <!-- ═══════════════════════════════════════════════════════════
             DIPLÔME PHYSIQUE — RENDU EXACT DU MODÈLE
             ═══════════════════════════════════════════════════════════ -->
        <article class="cert-paper">

          <!-- 1. Cadre intérieur doré -->
          <div class="cert-gold-border-inset" aria-hidden="true"></div>

          <!-- 2. Arrière-plan ondes guilloche douces -->
          <svg class="cert-waves-bg" viewBox="0 0 1000 700" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
            <g stroke="rgba(0,0,0,0.045)" stroke-width="1" fill="none">
              <path d="M-50,450 C250,550 450,300 1050,420" />
              <path d="M-50,470 C250,570 450,320 1050,440" />
              <path d="M-50,490 C250,590 450,340 1050,460" />
              <path d="M-50,510 C250,610 450,360 1050,480" />
              <path d="M-50,530 C250,630 450,380 1050,500" />
              <path d="M-50,550 C250,650 450,400 1050,520" />
              <path d="M-50,570 C250,670 450,420 1050,540" />
              <path d="M-50,590 C250,690 450,440 1050,560" />
              <path d="M-50,610 C250,710 450,460 1050,580" />
              <path d="M-50,630 C250,730 450,480 1050,600" />
              <!-- Ondulations secondaires supérieures droites -->
              <path d="M400,-50 C650,150 800,250 1050,220" />
              <path d="M420,-50 C670,150 820,250 1050,240" />
              <path d="M440,-50 C690,150 840,250 1050,260" />
              <path d="M460,-50 C710,150 860,250 1050,280" />
            </g>
          </svg>

          <!-- 3. Géométrie Coin Haut-Gauche (Noir & Or) -->
          <svg class="cert-corner-top-left" viewBox="0 0 320 180" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <linearGradient id="goldGradTop" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#c59b27"/>
                <stop offset="50%" stop-color="#fef08a"/>
                <stop offset="100%" stop-color="#92690d"/>
              </linearGradient>
            </defs>
            <!-- Ligne d'accent or sous la tranche -->
            <polygon points="0,0 316,0 188,168 0,168" fill="url(#goldGradTop)" />
            <!-- Grand polygone noir mat principal -->
            <polygon points="0,0 310,0 182,164 0,164" fill="#0d0e13" />
            <!-- Petit coin d'accent or interne -->
            <polygon points="0,0 55,0 0,55" fill="url(#goldGradTop)" opacity="0.65" />
            <polygon points="0,0 48,0 0,48" fill="#000000" />
          </svg>

          <!-- Branding dans le coin noir : Wolf Logo + DR ACADEMY -->
          <div class="cert-brand-corner">
            <img src="logo-wolf.jpg" alt="DR Academy" class="cert-wolf-logo">
            <div class="cert-brand-title">DR ACADEMY</div>
            <div class="cert-brand-tagline">APPRENDRE · CRÉER · ÉVOLUER</div>
          </div>

          <!-- 4. Médaille d'or avec rubans noirs suspendus -->
          <div class="cert-seal-ribbon-wrap">
            <!-- Queues de ruban noir découpées en V -->
            <div class="cert-ribbon-tails">
              <div class="cert-ribbon-tail"></div>
              <div class="cert-ribbon-tail" style="transform: rotate(4deg);"></div>
            </div>

            <!-- Médaille d'or vectorielle scintillante -->
            <svg class="cert-gold-medal" viewBox="0 0 140 140" xmlns="http://www.w3.org/2000/svg">
              <defs>
                <linearGradient id="goldSealGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" stop-color="#c59b27"/>
                  <stop offset="25%" stop-color="#fef08a"/>
                  <stop offset="50%" stop-color="#b48618"/>
                  <stop offset="75%" stop-color="#fef9c3"/>
                  <stop offset="100%" stop-color="#92690d"/>
                </linearGradient>
                <radialGradient id="goldRadial" cx="50%" cy="50%" r="50%">
                  <stop offset="0%" stop-color="#fef08a"/>
                  <stop offset="70%" stop-color="#c59b27"/>
                  <stop offset="100%" stop-color="#854d0e"/>
                </radialGradient>
              </defs>

              <!-- Couronne étoilée crantée extérieure (36 dents d'or) -->
              <g transform="translate(70,70)">
                <?php for ($i = 0; $i < 36; $i++): $deg = $i * 10; ?>
                  <polygon points="-6,-65 6,-65 0,-70" fill="url(#goldSealGrad)" transform="rotate(<?= $deg ?>)" />
                <?php endfor; ?>
                <!-- Cercle d'or extérieur -->
                <circle cx="0" cy="0" r="64" fill="url(#goldSealGrad)" />
                <!-- Cercle cranté intérieur or -->
                <circle cx="0" cy="0" r="58" fill="#18181b" stroke="url(#goldSealGrad)" stroke-width="2" />
                <circle cx="0" cy="0" r="54" fill="none" stroke="url(#goldSealGrad)" stroke-width="1" stroke-dasharray="2,2" />

                <!-- Toque / Chapeau de diplômé (Mortarboard) -->
                <g transform="translate(-18, -32) scale(0.85)">
                  <polygon points="21,4 40,12 21,20 2,12" fill="url(#goldSealGrad)" />
                  <path d="M7,15.5 L7,24 C7,24 13,28 21,28 C29,28 35,24 35,24 L35,15.5" fill="url(#goldSealGrad)" />
                  <!-- Pompon / Cordon -->
                  <path d="M36,14 L39,26" stroke="url(#goldSealGrad)" stroke-width="1.8" fill="none" />
                  <circle cx="39" cy="27" r="2.2" fill="url(#goldSealGrad)" />
                </g>

                <!-- Texte FORMATION CERTIFIÉE -->
                <text x="0" y="5" font-family="'Plus Jakarta Sans', sans-serif" font-size="9" font-weight="900" fill="url(#goldSealGrad)" text-anchor="middle" letter-spacing="0.1em">FORMATION</text>
                <text x="0" y="18" font-family="'Plus Jakarta Sans', sans-serif" font-size="9" font-weight="900" fill="url(#goldSealGrad)" text-anchor="middle" letter-spacing="0.1em">CERTIFIÉE</text>

                <!-- 3 Étoiles d'or -->
                <g fill="url(#goldSealGrad)" transform="translate(-14, 28) scale(0.7)">
                  <polygon points="5,0 6.5,3.5 10,4 7.5,6.5 8,10 5,8 2,10 2.5,6.5 0,4 3.5,3.5" />
                </g>
                <g fill="url(#goldSealGrad)" transform="translate(-4, 26) scale(0.85)">
                  <polygon points="5,0 6.5,3.5 10,4 7.5,6.5 8,10 5,8 2,10 2.5,6.5 0,4 3.5,3.5" />
                </g>
                <g fill="url(#goldSealGrad)" transform="translate(8, 28) scale(0.7)">
                  <polygon points="5,0 6.5,3.5 10,4 7.5,6.5 8,10 5,8 2,10 2.5,6.5 0,4 3.5,3.5" />
                </g>
              </g>
            </svg>
          </div>

          <!-- 5. Géométrie Coin Bas-Droit (Noir & Or) -->
          <svg class="cert-corner-bottom-right" viewBox="0 0 160 120" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <linearGradient id="goldGradBottom" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#92690d"/>
                <stop offset="50%" stop-color="#fef08a"/>
                <stop offset="100%" stop-color="#c59b27"/>
              </linearGradient>
            </defs>
            <!-- Ligne or diagonale -->
            <polygon points="0,120 160,12 160,120" fill="url(#goldGradBottom)" />
            <!-- Polygone noir principal -->
            <polygon points="8,120 160,18 160,120" fill="#0d0e13" />
            <!-- Facette géométrique accent -->
            <polygon points="80,120 160,65 160,120" fill="#181920" />
            <polygon points="120,120 160,92 160,120" fill="url(#goldGradBottom)" opacity="0.6" />
          </svg>

          <!-- 6. En-tête : Numéro de certificat haut-droit -->
          <div class="cert-top-header">
            <div class="cert-number">N° <?= $certIdClean ?></div>
          </div>

          <!-- 7. Corps central de l'attestation -->
          <div class="cert-main-content">
            <h1 class="cert-title-big">CERTIFICAT</h1>
            
            <div class="cert-sub-bar">
              <span class="cert-sub-bar-line"></span>
              <span class="cert-sub-bar-text">DE FORMATION</span>
              <span class="cert-sub-bar-line"></span>
            </div>

            <p class="cert-attestation-txt">Nous attestons par la présente que</p>
            
            <div class="cert-recipient-name"><?= $recipientClean ?></div>
            <div class="cert-name-underline"></div>

            <p class="cert-course-intro">a suivi avec succès la formation en ligne intitulée :</p>
            <h2 class="cert-course-title"><?= $trainingClean ?></h2>
            
            <?php if (!empty($mentionClean)): ?>
              <div class="cert-mention-badge">
                <svg viewBox="0 0 24 24" width="11" height="11" fill="currentColor" style="color:#c89f3b; flex-shrink:0;" aria-hidden="true"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
                <span><?= $mentionClean ?></span>
                <svg viewBox="0 0 24 24" width="11" height="11" fill="currentColor" style="color:#c89f3b; flex-shrink:0;" aria-hidden="true"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
              </div>
            <?php endif; ?>

            <p class="cert-course-desc"><?= $descClean ?></p>

            <!-- Boîte 3 Colonnes : Période | Durée | Niveau -->
            <div class="cert-meta-pill-card">
              <!-- Colonne 1 : Période -->
              <div class="cert-pill-item">
                <svg class="cert-pill-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                  <line x1="16" y1="2" x2="16" y2="6"/>
                  <line x1="8" y1="2" x2="8" y2="6"/>
                  <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <div class="cert-pill-content">
                  <div class="cert-pill-label">Période de formation</div>
                  <div class="cert-pill-val"><?= $periodeClean ?></div>
                </div>
              </div>

              <!-- Colonne 2 : Durée -->
              <div class="cert-pill-item">
                <svg class="cert-pill-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"/>
                  <polyline points="12 6 12 12 16 14"/>
                </svg>
                <div class="cert-pill-content">
                  <div class="cert-pill-label">Durée</div>
                  <div class="cert-pill-val"><?= $dureeClean ?></div>
                </div>
              </div>

              <!-- Colonne 3 : Niveau -->
              <div class="cert-pill-item">
                <svg class="cert-pill-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="18" y1="20" x2="18" y2="10"/>
                  <line x1="12" y1="20" x2="12" y2="4"/>
                  <line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
                <div class="cert-pill-content">
                  <div class="cert-pill-label">Niveau</div>
                  <div class="cert-pill-val"><?= $niveauClean ?></div>
                </div>
              </div>
            </div>
          </div>

          <!-- 8. Ligne inférieure : Date/Lieu | Signature | QR Code -->
          <div class="cert-footer-row">
            <!-- Date et Lieu à gauche -->
            <div class="cert-foot-left">
              <div>Délivré le <?= $dateClean ?></div>
              <div><?= $lieuClean ?></div>
            </div>

            <!-- Signature au centre -->
            <div class="cert-foot-center">
              <!-- Signature manuscrite exécutive officielle Dr Remus -->
              <svg class="cert-signature-svg" viewBox="0 0 240 75" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g transform="rotate(-2 120 37)">
                  <!-- D majuscule avec panache et boucle supérieure -->
                  <path d="M 30 48 C 26 34, 28 16, 38 8 C 44 3, 50 6, 48 16 C 45 28, 32 46, 25 52 C 20 56, 32 55, 48 48 C 66 40, 78 24, 74 14 C 70 5, 54 6, 48 18 C 45 27, 52 38, 66 42" 
                        stroke="#0f172a" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                  <!-- r. -->
                  <path d="M 72 34 C 76 28, 83 29, 85 35 C 86 40, 83 44, 88 43" 
                        stroke="#0f172a" stroke-width="2.1" stroke-linecap="round"/>
                  <circle cx="94" cy="43" r="1.8" fill="#0f172a"/>

                  <!-- 'Remus' avec grand élan diagonal -->
                  <path d="M 108 50 L 112 10 C 113 6, 109 6, 106 10" 
                        stroke="#0f172a" stroke-width="2.6" stroke-linecap="round"/>
                  <path d="M 111 11 C 122 5, 142 6, 146 16 C 150 25, 142 33, 128 35 C 120 36, 113 36, 111 36 C 119 40, 130 50, 138 53 C 143 54, 148 51, 151 43" 
                        stroke="#0f172a" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/>
                  
                  <!-- Lettres cursives 'emus' -->
                  <path d="M 151 43 C 153 37, 160 35, 163 39 C 165 43, 159 47, 156 47 C 161 47, 167 38, 172 36 C 176 34, 177 42, 178 46 C 181 40, 187 36, 191 36 C 195 36, 196 42, 197 46 C 200 39, 207 36, 211 38 C 215 40, 214 46, 217 46 C 221 40, 227 37, 231 39 C 233 41, 230 48, 224 50" 
                        stroke="#0f172a" stroke-width="2.0" stroke-linecap="round" stroke-linejoin="round"/>

                  <!-- Paraphe sous-jacent fluide -->
                  <path d="M 98 56 C 135 53, 180 50, 228 43" stroke="#0f172a" stroke-width="2.4" stroke-linecap="round"/>
                  <path d="M 232 44 C 218 57, 165 67, 110 68 C 65 69, 32 64, 42 60 C 58 55, 105 55, 165 56" stroke="#0f172a" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
                </g>
              </svg>
              <div class="cert-sign-line"></div>
              <div class="cert-sign-name"><?= $formateurClean ?></div>
              <div class="cert-sign-role">Formateur &amp; Fondateur</div>
            </div>

            <!-- QR Code à droite -->
            <div class="cert-foot-right">
              <div class="cert-qr-box">
                <img src="<?= $qrCodeApiUrl ?>" alt="QR Code d'authenticité" class="cert-qr-img" loading="eager">
              </div>
              <div class="cert-qr-label">Vérification du certificat</div>
              <a href="<?= $fullCertUrl ?>" class="cert-qr-url" target="_blank"><?= $shortCertUrl ?></a>
            </div>
          </div>

        </article>

      </div>
    <?php endif; ?>

  </main>

  <!-- ═══ PIED DE PAGE ═══════════════════════════════════════════════ -->
  <footer class="v-footer">
    <div class="container">
      <p>© 2026 DR ACADEMY · Registre Officiel des Certifications Techniques. Tous droits réservés.</p>
    </div>
  </footer>

  <script>
    function copyCertLink() {
      var url = window.location.href;
      navigator.clipboard.writeText(url).then(function() {
        var txt = document.getElementById('copyLinkTxt');
        if (txt) {
          var old = txt.textContent;
          txt.textContent = 'Copié !';
          setTimeout(function() { txt.textContent = old; }, 2000);
        }
      }).catch(function() {
        alert("Lien du certificat : " + window.location.href);
      });
    }
  </script>

</body>
</html>

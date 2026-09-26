<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
initAdminSession();

// Redirection si déjà connecté
if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = (string)($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    $result = attemptAdminLogin($usernameOrEmail, $password);
    if ($result['success']) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = $result['message'] ?? 'Identifiants invalides.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion · Dr Remus Operations</title>
  <link rel="icon" href="../logo-wolf.jpg" type="image/jpeg">
  <link rel="stylesheet" href="css/admin-grafana.css?v=3.0">
  <style>
    .adm-login-canvas {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: radial-gradient(circle at 50% 30%, #151821 0%, #08090c 75%);
      padding: 24px;
    }
    .adm-login-card {
      background: #111319;
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      width: 100%;
      max-width: 400px;
      padding: 38px 32px;
      box-shadow: 0 24px 70px rgba(0,0,0,0.9), 0 0 0 1px rgba(255,255,255,0.03);
    }
    .adm-login-brand {
      text-align: center;
      margin-bottom: 28px;
    }
    .adm-login-wolf {
      width: 58px;
      height: 58px;
      margin: 0 auto 16px;
      border-radius: 14px;
      border: 1px solid rgba(255, 255, 255, 0.16);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.6);
      display: block;
      object-fit: cover;
    }
    .adm-login-title {
      font-size: 20px;
      font-weight: 800;
      color: #fff;
      letter-spacing: -0.02em;
    }
    .adm-login-sub {
      font-size: 12px;
      color: var(--adm-text-muted);
      margin-top: 4px;
    }
    .adm-form-group {
      margin-bottom: 18px;
    }
    .adm-form-label {
      display: block;
      font-size: 11px;
      font-weight: 700;
      color: #c7c9cf;
      margin-bottom: 6px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    .adm-login-btn {
      width: 100%;
      padding: 12px;
      font-size: 13px;
      justify-content: center;
      background: #ffffff;
      border-color: #ffffff;
      color: #0b0c10;
      font-weight: 700;
      margin-top: 10px;
      border-radius: 9px;
    }
    .adm-login-btn:hover {
      background: #e4e4e7;
      color: #000;
      box-shadow: 0 4px 18px rgba(255, 255, 255, 0.22);
    }
    .adm-alert {
      background: rgba(239, 68, 68, 0.12);
      border: 1px solid rgba(239, 68, 68, 0.3);
      color: #ef4444;
      padding: 11px 16px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 600;
      margin-bottom: 20px;
    }
    .adm-login-foot {
      text-align: center;
      margin-top: 26px;
      font-size: 11px;
      color: #5f6368;
    }
  </style>
</head>
<body class="adm-body-root">
  <div class="adm-login-canvas">
    <div class="adm-login-card">
      <div class="adm-login-brand">
        <img src="../logo-wolf.jpg" alt="Dr Remus Logo" class="adm-login-wolf">
        <h1 class="adm-login-title">Dr Remus Operations</h1>
        <p class="adm-login-sub">Système d'Administration &amp; Supervision</p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="adm-alert">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="index.php" autocomplete="off">
        <div class="adm-form-group">
          <label class="adm-form-label" for="username">Identifiant ou Email</label>
          <input class="adm-input" type="text" id="username" name="username" placeholder="admin ou email" required autofocus>
        </div>

        <div class="adm-form-group">
          <label class="adm-form-label" for="password">Mot de passe</label>
          <input class="adm-input" type="password" id="password" name="password" placeholder="••••••••••••" required>
        </div>

        <button type="submit" class="adm-btn adm-login-btn">
          Ouvrir le tableau de bord →
        </button>
      </form>

      <div class="adm-login-foot">
        Dr Remus Portfolio · 100% Production Backend
      </div>
    </div>
  </div>
</body>
</html>

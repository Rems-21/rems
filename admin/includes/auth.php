<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function initAdminSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $env = loadEnvConfig();
        $sessionName = $env['ADMIN_SESSION_NAME'] ?? 'dr_remus_admin_sess';
        session_name($sessionName);
        
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax'
        ]);
    }
}

function isAdminLoggedIn(): bool {
    initAdminSession();
    return !empty($_SESSION['admin_logged']) && !empty($_SESSION['admin_user']);
}

function requireAdminAuth(): void {
    initAdminSession();
    if (!isAdminLoggedIn()) {
        $loginUrl = 'index.php';
        header("Location: {$loginUrl}");
        exit;
    }
}

function attemptAdminLogin(string $usernameOrEmail, string $password): array {
    initAdminSession();

    $usernameOrEmail = trim($usernameOrEmail);
    if ($usernameOrEmail === '' || $password === '') {
        return ['success' => false, 'message' => 'Veuillez saisir votre identifiant et mot de passe.'];
    }

    $pdo = getDatabaseConnection();
    $env = loadEnvConfig();

    // 1. Vérification en base de données si PDO disponible
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT * FROM `admin_users` WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['admin_logged'] = true;
            $_SESSION['admin_user'] = $user['username'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['admin_login_time'] = time();
            return ['success' => true];
        }
    }

    // 2. Vérification de secours via les variables d'environnement .env
    $adminEnvUser = $env['ADMIN_USER'] ?? 'admin';
    $adminEnvPass = $env['ADMIN_PASS'] ?? 'DrRemus2026!';
    $adminEnvEmail = $env['ADMIN_EMAIL'] ?? 'dsonkouatremus@gmail.com';

    if (($usernameOrEmail === $adminEnvUser || $usernameOrEmail === $adminEnvEmail) && $password === $adminEnvPass) {
        $_SESSION['admin_logged'] = true;
        $_SESSION['admin_user'] = $adminEnvUser;
        $_SESSION['admin_email'] = $adminEnvEmail;
        $_SESSION['admin_login_time'] = time();
        return ['success' => true];
    }

    return ['success' => false, 'message' => 'Identifiant ou mot de passe incorrect.'];
}

function logoutAdmin(): void {
    initAdminSession();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

function getCsrfToken(): string {
    initAdminSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool {
    initAdminSession();
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

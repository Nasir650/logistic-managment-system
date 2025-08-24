<?php
// auth.php
require_once __DIR__ . '/config.php';

function is_logged_in(): bool {
  return isset($_SESSION['user']);
}

function current_user(): ?array {
  return $_SESSION['user'] ?? null;
}

function require_login(): void {
  if (!is_logged_in()) {
    header('Location: ' . base_path('/login.php'));
    exit;
  }
}

function require_role(string $role): void {
  require_login();
  if ($_SESSION['user']['role'] !== $role) {
    http_response_code(403);
    exit('Forbidden');
  }
}

function login_user(array $user): void {
  $_SESSION['user'] = $user;
}

function refresh_session_user(PDO $pdo): void {
  if (!is_logged_in()) return;
  $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->execute([$_SESSION['user']['id']]);
  $user = $stmt->fetch();
  if ($user) $_SESSION['user'] = $user;
}

function logout_user(): void {
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

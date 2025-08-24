<?php
require_once __DIR__ . '/../auth.php';
require_role('driver');
$availability = (int)($_POST['availability'] ?? 1);
$stmt = $pdo->prepare("UPDATE driver_profiles SET availability = ? WHERE user_id = ?");
$stmt->execute([$availability, current_user()['id']]);
header('Location: ' . base_path('/driver/index.php'));
exit;

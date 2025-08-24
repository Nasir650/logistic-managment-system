<?php
require_once __DIR__ . '/auth.php';
logout_user();
header('Location: ' . base_path('/'));
exit;

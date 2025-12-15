<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$role = strtolower(trim($_SESSION['role_pengguna'] ?? ''));

echo json_encode([
  'success'  => true,
  'role'     => $role,
  'is_admin' => ($role === 'admin'),
  'username' => $_SESSION['username'] ?? null
]);

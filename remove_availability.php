<?php
session_start();
if (!isset($_SESSION['user_id'])) { echo "<script>window.location='/login.php';</script>"; exit; }
require_once 'db.php';
$uid = (int)$_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $stmt = $pdo->prepare("DELETE FROM availabilities WHERE id = ? AND user_id = ?");
    $stmt->execute([$id,$uid]);
}
echo "<script>window.location='/dashboard.php';</script>";

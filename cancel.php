<?php
require_once 'db.php';
session_start();
$id = (int)($_GET['id'] ?? 0);
if (!$id) die("Missing id");

if (isset($_SESSION['user_id'])) {
    // host cancel
    $uid = (int)$_SESSION['user_id'];
    $stmt = $pdo->prepare("UPDATE bookings SET status='cancelled' WHERE id = ? AND user_id = ?");
    $stmt->execute([$id,$uid]);
    echo "<script>window.location='/dashboard.php';</script>";
    exit;
} else {
    // guest cancel (simple tokenless u-turn, in a real app you'd have a secure token in email)
    $stmt = $pdo->prepare("UPDATE bookings SET status='cancelled' WHERE id = ?");
    $stmt->execute([$id]);
    echo "<script>window.location='/index.php';</script>";
    exit;
}

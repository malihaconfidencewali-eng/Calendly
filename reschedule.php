<?php
require_once 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) { echo "<script>window.location='/login.php';</script>"; exit; }
$uid = (int)$_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);
if (!$id) die("Missing id");
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ?");
$stmt->execute([$id,$uid]);
$booking = $stmt->fetch();
if (!$booking) die("Booking not found or not yours");

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start = $_POST['start_datetime'] ?? '';
    $end = $_POST['end_datetime'] ?? '';
    if (!$start || !$end) { $err = "Provide start and end"; }
    else {
        // check conflicts
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM bookings WHERE user_id = ? AND id != ? AND ((start_datetime <= ? AND end_datetime > ?) OR (start_datetime < ? AND end_datetime >= ?)) AND status='confirmed'");
        $stmt->execute([$uid,$id,$start,$start,$end,$end]);
        $c = $stmt->fetch();
        if ($c && $c['cnt'] > 0) $err = "Time conflicts with another booking.";
        else {
            $stmt = $pdo->prepare("UPDATE bookings SET start_datetime = ?, end_datetime = ?, status='rescheduled' WHERE id = ?");
            $stmt->execute([$start,$end,$id]);
            echo "<script>window.location='/dashboard.php';</script>";
            exit;
        }
    }
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Reschedule</title><meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{font-family:Inter,Arial;background:#f7fbff;margin:0;padding:20px}
.card{max-width:720px;margin:20px auto;background:#fff;padding:16px;border-radius:12px;box-shadow:0 12px 30px rgba(16,24,40,0.06)}
input{padding:8px;border-radius:8px;border:1px solid #eef6ff;width:100%}
.btn{background:#1e88e5;color:#fff;padding:8px 12px;border-radius:8px;border:none}
.err{color:#b02a37}
.small{color:#6b7280}
</style>
</head><body>
<div class="card">
  <h3>Reschedule booking for <?php echo htmlspecialchars($booking['guest_name']); ?></h3>
  <?php if($err): ?><div class="err"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
  <form method="post" style="margin-top:10px">
    <label class="small">New start (YYYY-MM-DD HH:MM:SS)</label>
    <input type="text" name="start_datetime" value="<?php echo htmlspecialchars($booking['start_datetime']); ?>" required>
    <label class="small" style="margin-top:8px">New end (YYYY-MM-DD HH:MM:SS)</label>
    <input type="text" name="end_datetime" value="<?php echo htmlspecialchars($booking['end_datetime']); ?>" required>
    <div style="margin-top:10px">
      <button class="btn">Save</button>
      <button type="button" onclick="window.location='/dashboard.php'" style="margin-left:8px;padding:8px 12px;border-radius:8px">Cancel</button>
    </div>
  </form>
</div>
</body></html>

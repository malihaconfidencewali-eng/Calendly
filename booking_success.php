<?php
require_once 'db.php';
$id = (int)($_GET['id'] ?? 0);
if (!$id) die("Missing id");
$stmt = $pdo->prepare("SELECT b.*, u.name as host_name FROM bookings b JOIN users u ON u.id=b.user_id WHERE b.id = ?");
$stmt->execute([$id]);
$b = $stmt->fetch();
if (!$b) die("Booking not found");
?>
<!doctype html><html><head><meta charset="utf-8"><title>Booking confirmed</title><meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{font-family:Inter,Arial;background:#f7fbff;margin:0;padding:20px}
.card{max-width:680px;margin:24px auto;background:#fff;padding:18px;border-radius:12px;box-shadow:0 12px 30px rgba(16,24,40,0.06)}
.btn{background:#1e88e5;color:#fff;padding:8px 12px;border-radius:10px;border:none}
.small{color:#6b7280}
</style></head><body>
<div class="card">
  <h2>Booking confirmed</h2>
  <div class="small">Your meeting with <?php echo htmlspecialchars($b['host_name']); ?> is scheduled.</div>
  <div style="margin-top:12px">
    <strong>When:</strong> <?php echo htmlspecialchars($b['start_datetime']); ?> — <?php echo htmlspecialchars($b['end_datetime']); ?><br>
    <strong>Guest:</strong> <?php echo htmlspecialchars($b['guest_name']); ?> (<?php echo htmlspecialchars($b['guest_email']); ?>)
  </div>
  <div style="margin-top:14px">
    <button class="btn" onclick="window.location='/index.php'">Back to home</button>
  </div>
</div>
</body></html>

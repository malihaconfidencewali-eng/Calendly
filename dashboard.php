<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location='/login.php';</script>";
    exit;
}
require_once 'db.php';
$uid = (int)$_SESSION['user_id'];
// Load user
$stmt = $pdo->prepare("SELECT id,name,email,timezone FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();
if (!$user) { echo "<script>window.location='/logout.php';</script>"; exit; }

// Handle availability form
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_availability'])) {
    $day = (int)$_POST['day_of_week'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $slot = (int)$_POST['slot_minutes'];
    if ($start && $end && $slot > 0 && $day >=0 && $day <=6) {
        $stmt = $pdo->prepare("INSERT INTO availabilities (user_id,day_of_week,start_time,end_time,slot_minutes) VALUES (?,?,?,?,?)");
        $stmt->execute([$uid,$day,$start,$end,$slot]);
        $success = "Availability added.";
    } else {
        $success = "Invalid input.";
    }
}

// Load availabilities
$stmt = $pdo->prepare("SELECT * FROM availabilities WHERE user_id = ? ORDER BY day_of_week, start_time");
$stmt->execute([$uid]);
$avails = $stmt->fetchAll();

// Load upcoming bookings
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE user_id = ? AND status='confirmed' AND start_datetime >= NOW() ORDER BY start_datetime ASC LIMIT 50");
$stmt->execute([$uid]);
$upcoming = $stmt->fetchAll();

?>
<!doctype html><html><head>
<meta charset="utf-8"><title>Dashboard — Schedly</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{font-family:Inter,Arial;background:#f4f7fb;margin:0;padding:20px}
.wrap{max-width:1000px;margin:24px auto}
.header{display:flex;justify-content:space-between;align-items:center}
h2{margin:0}
.btn{background:#1e88e5;color:#fff;padding:8px 12px;border-radius:10px;border:none;cursor:pointer}
.card{background:#fff;padding:14px;border-radius:10px;box-shadow:0 8px 24px rgba(16,24,40,0.05);margin-top:14px}
.form-row{display:flex;gap:8px;align-items:center}
input,select{padding:8px;border-radius:8px;border:1px solid #e6eef8}
.list{margin-top:12px}
.small{color:#6b7280;font-size:13px}
.badge{display:inline-block;padding:6px 8px;border-radius:8px;background:#eef7ff;color:#1e88e5}
.row{display:flex;justify-content:space-between;align-items:center}
a.link{color:#1e88e5;text-decoration:none}
.table{width:100%;border-collapse:collapse;margin-top:10px}
.table th{font-weight:600;text-align:left;padding:8px;border-bottom:1px solid #eef2ff}
.table td{padding:8px;border-bottom:1px solid #fbfcff}
small.note{color:#6b7280}
</style>
</head><body>
<div class="wrap">
  <div class="header">
    <div>
      <h2>Dashboard</h2>
      <div class="small">Welcome, <?php echo htmlspecialchars($user['name']); ?> — share your booking link:</div>
      <div style="margin-top:6px"><span class="badge"><?php echo htmlspecialchars("https://your-site.com/schedule.php?u=".$user['id']); ?></span></div>
    </div>
    <div>
      <button class="btn" onclick="window.location='/index.php'">Home</button>
      <button class="btn" style="background:#f3f4f6;color:#111;margin-left:8px" onclick="window.location='/logout.php'">Logout</button>
    </div>
  </div>

  <div class="card">
    <h3 style="margin-top:0">Set weekly availability</h3>
    <?php if($success): ?><div class="small" style="margin-bottom:8px;color:green"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
    <form method="post" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <div style="min-width:150px">
        <label class="small">Day</label>
        <select name="day_of_week" required>
          <option value="0">Sunday</option>
          <option value="1">Monday</option>
          <option value="2">Tuesday</option>
          <option value="3">Wednesday</option>
          <option value="4">Thursday</option>
          <option value="5">Friday</option>
          <option value="6">Saturday</option>
        </select>
      </div>

      <div>
        <label class="small">Start</label>
        <input type="time" name="start_time" required>
      </div>

      <div>
        <label class="small">End</label>
        <input type="time" name="end_time" required>
      </div>

      <div>
        <label class="small">Slot (minutes)</label>
        <input type="number" name="slot_minutes" value="30" min="5" required>
      </div>

      <div style="align-self:end">
        <input type="hidden" name="add_availability" value="1">
        <button class="btn">Add</button>
      </div>
    </form>

    <div class="list">
      <h4 style="margin-top:12px">Your availabilities</h4>
      <?php if(empty($avails)): ?>
        <div class="small">No availability set yet.</div>
      <?php else: ?>
        <table class="table">
          <thead><tr><th>Day</th><th>From</th><th>To</th><th>Slot</th><th>Action</th></tr></thead>
          <tbody>
          <?php
            $days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
            foreach($avails as $a):
          ?>
            <tr>
              <td><?php echo $days[$a['day_of_week']]; ?></td>
              <td><?php echo htmlspecialchars($a['start_time']); ?></td>
              <td><?php echo htmlspecialchars($a['end_time']); ?></td>
              <td><?php echo htmlspecialchars($a['slot_minutes']); ?> min</td>
              <td><a class="link" href="/remove_availability.php?id=<?php echo $a['id']; ?>">Delete</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <h3 style="margin:0">Upcoming bookings</h3>
    <div class="small" style="margin-top:8px">Manage your meetings below.</div>
    <div style="margin-top:12px">
      <?php if(empty($upcoming)): ?>
        <div class="small">No upcoming meetings.</div>
      <?php else: ?>
        <table class="table">
          <thead><tr><th>Guest</th><th>When</th><th>Duration</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach($upcoming as $b): ?>
              <tr>
                <td><?php echo htmlspecialchars($b['guest_name'])."<br><small class='small'>".htmlspecialchars($b['guest_email'])."</small>"; ?></td>
                <td><?php echo htmlspecialchars($b['start_datetime']); ?></td>
                <td><?php echo intval($b['duration_minutes'])." min"; ?></td>
                <td><?php echo htmlspecialchars($b['status']); ?></td>
                <td>
                  <a class="link" href="/cancel.php?id=<?php echo $b['id']; ?>&u=<?php echo $uid; ?>" onclick="return confirm('Cancel this meeting?')">Cancel</a>
                  &nbsp;|&nbsp;
                  <a class="link" href="/reschedule.php?id=<?php echo $b['id']; ?>">Reschedule</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

</div>
</body></html>

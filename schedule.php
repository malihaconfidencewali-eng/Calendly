<?php
// schedule.php
require_once 'db.php';
$uid = (int)($_GET['u'] ?? 0);
if (!$uid) {
    die("Missing user id. Use ?u=USER_ID");
}
// load user
$stmt = $pdo->prepare("SELECT id,name,email,timezone FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();
if (!$user) die("Host not found.");

// Load weekly availabilities
$stmt = $pdo->prepare("SELECT * FROM availabilities WHERE user_id = ? ORDER BY day_of_week, start_time");
$stmt->execute([$uid]);
$avails = $stmt->fetchAll();
// For simplicity, we will show next 14 days and compute available slots based on weekly availability and existing bookings
$today = new DateTime('now', new DateTimeZone($user['timezone'] ?? 'Asia/Karachi'));
$daysToShow = 14;
$slotsData = [];
for ($i=0;$i<$daysToShow;$i++){
    $d = clone $today;
    $d->modify("+{$i} days");
    $dow = (int)$d->format('w'); // 0-6
    // find avail rows for this day of week
    foreach ($avails as $a) {
        if ((int)$a['day_of_week'] !== $dow) continue;
        // slot generation
        $start = DateTime::createFromFormat('H:i:s', $a['start_time'], new DateTimeZone($user['timezone']));
        $end = DateTime::createFromFormat('H:i:s', $a['end_time'], new DateTimeZone($user['timezone']));
        // align with the date
        $start->setDate($d->format('Y'), $d->format('m'), $d->format('d'));
        $end->setDate($d->format('Y'), $d->format('m'), $d->format('d'));
        $slotMin = (int)$a['slot_minutes'];
        $curr = clone $start;
        while ($curr < $end) {
            $slotStart = clone $curr;
            $slotEnd = clone $curr;
            $slotEnd->modify("+{$slotMin} minutes");
            if ($slotEnd > $end) break;
            $slotsData[] = [
                'user_id'=>$uid,
                'date'=>$d->format('Y-m-d'),
                'start'=>$slotStart->format('Y-m-d H:i:s'),
                'end'=>$slotEnd->format('Y-m-d H:i:s'),
                'minutes'=>$slotMin
            ];
            $curr->modify("+{$slotMin} minutes");
        }
    }
}

// Filter out slots that are in the past or conflict with existing bookings or exceptions
$availableSlots = [];
foreach ($slotsData as $slot) {
    // skip past
    $now = new DateTime('now', new DateTimeZone($user['timezone']));
    $slotStart = new DateTime($slot['start'], new DateTimeZone($user['timezone']));
    if ($slotStart < $now) continue;

    // check exceptions (full day blocks)
    $d = substr($slot['start'],0,10);
    $stmt = $pdo->prepare("SELECT * FROM availability_exceptions WHERE user_id = ? AND date = ?");
    $stmt->execute([$uid,$d]);
    $ex = $stmt->fetch();
    if ($ex) {
        if ($ex['is_full_day_block']) continue;
        if ($ex['start_time'] && $ex['end_time']) {
            $s = new DateTime($d.' '.$ex['start_time'], new DateTimeZone($user['timezone']));
            $e = new DateTime($d.' '.$ex['end_time'], new DateTimeZone($user['timezone']));
            $slotStartDT = new DateTime($slot['start'], new DateTimeZone($user['timezone']));
            $slotEndDT = new DateTime($slot['end'], new DateTimeZone($user['timezone']));
            if ($slotStartDT < $e && $slotEndDT > $s) {
                continue; // conflict with special exception hours
            }
        }
    }

    // check existing bookings
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM bookings WHERE user_id = ? AND ((start_datetime <= ? AND end_datetime > ?) OR (start_datetime < ? AND end_datetime >= ?)) AND status='confirmed'");
    $stmt->execute([$uid, $slot['start'], $slot['start'], $slot['end'], $slot['end']]);
    $c = $stmt->fetch();
    if ($c && $c['cnt'] > 0) continue;

    $availableSlots[] = $slot;
}

// Render page showing available slots and booking form
?>
<!doctype html><html><head>
<meta charset="utf-8"><title>Book with <?php echo htmlspecialchars($user['name']); ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{font-family:Inter,Arial;background:#f7fbff;margin:0;padding:18px}
.wrap{max-width:920px;margin:20px auto}
.card{background:#fff;padding:16px;border-radius:12px;box-shadow:0 12px 30px rgba(16,24,40,0.06)}
.header{display:flex;justify-content:space-between;align-items:center}
.btn{background:#1e88e5;color:#fff;padding:8px 12px;border-radius:10px;border:none}
.slot{display:flex;justify-content:space-between;padding:10px;border-radius:8px;border:1px solid #eef6ff;margin-bottom:8px;align-items:center}
.slot .time{font-weight:700}
.form{margin-top:12px}
input,textarea{width:100%;padding:8px;border-radius:8px;border:1px solid #eef6ff;margin-top:8px}
.small{color:#6b7280;font-size:13px}
.flex{display:flex;gap:8px}
@media(max-width:700px){.flex{flex-direction:column}}
</style>
</head><body>
<div class="wrap">
  <div class="card">
    <div class="header">
      <div>
        <h2 style="margin:0">Book with <?php echo htmlspecialchars($user['name']); ?></h2>
        <div class="small">Timezone: <?php echo htmlspecialchars($user['timezone']); ?></div>
      </div>
      <div>
        <a class="small" href="/index.php">Home</a>
      </div>
    </div>

    <div style="margin-top:12px">
      <h4 style="margin:0 0 8px">Available slots (next <?php echo count(array_unique(array_column($availableSlots,'date'))); ?> days)</h4>
      <?php if(empty($availableSlots)): ?>
        <div class="small">No available slots. Try another day or contact the host.</div>
      <?php else: ?>
        <div id="slots">
          <?php foreach($availableSlots as $s): ?>
            <div class="slot">
              <div>
                <div class="time"><?php echo htmlspecialchars(date('D, M j, Y H:i', strtotime($s['start']))); ?> - <?php echo htmlspecialchars(date('H:i', strtotime($s['end']))); ?></div>
                <div class="small"><?php echo intval($s['minutes']); ?> minutes</div>
              </div>
              <div>
                <button class="btn" onclick="selectSlot('<?php echo $s['start']; ?>','<?php echo $s['end']; ?>',<?php echo $s['minutes']; ?>)">Book</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div id="bookingForm" class="card" style="margin-top:12px;display:none">
      <h4 style="margin:0 0 8px">Complete your booking</h4>
      <form method="post" action="/book.php" class="form">
        <input type="hidden" name="user_id" id="user_id" value="<?php echo $user['id']; ?>">
        <input type="hidden" name="start_datetime" id="start_datetime">
        <input type="hidden" name="end_datetime" id="end_datetime">
        <input type="hidden" name="duration_minutes" id="duration_minutes">
        <div class="flex">
          <div style="flex:1">
            <label class="small">Your name</label>
            <input type="text" name="guest_name" required>
          </div>
          <div style="flex:1">
            <label class="small">Your email</label>
            <input type="email" name="guest_email" required>
          </div>
        </div>
        <label class="small">Notes (optional)</label>
        <textarea name="notes" rows="3"></textarea>
        <div style="margin-top:8px">
          <button class="btn">Confirm booking</button>
          <button type="button" onclick="document.getElementById('bookingForm').style.display='none';" style="margin-left:8px;padding:8px 10px;border-radius:8px">Cancel</button>
        </div>
      </form>
    </div>

  </div>
</div>

<script>
function selectSlot(start,end,mins){
  document.getElementById('bookingForm').style.display = 'block';
  document.getElementById('start_datetime').value = start;
  document.getElementById('end_datetime').value = end;
  document.getElementById('duration_minutes').value = mins;
  window.scrollTo({top:document.getElementById('bookingForm').offsetTop,behavior:'smooth'});
}
</script>
</body></html>

<?php
session_start();
$user = null;
if (isset($_SESSION['user_id'])) {
    // Lazy load simple user info
    require_once 'db.php';
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Schedly — Simple Calendly Clone</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
/* Inline internal CSS - polished look */
:root{--accent:#1e88e5;--muted:#6b7280;--card:#fff}
*{box-sizing:border-box;font-family:Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;}
body{margin:0;background:linear-gradient(180deg,#f5f7fb,#fff);color:#111}
.container{max-width:1100px;margin:40px auto;padding:20px}
.header{display:flex;justify-content:space-between;align-items:center;gap:16px}
.brand{display:flex;align-items:center;gap:12px}
.logo{width:56px;height:56px;border-radius:12px;background:var(--accent);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:20px}
h1{margin:8px 0 0;font-size:28px}
.lead{color:var(--muted);margin-top:8px}
.ctas{display:flex;gap:12px}
.btn{padding:10px 16px;border-radius:10px;border:none;background:var(--accent);color:#fff;font-weight:600;cursor:pointer}
.ghost{background:transparent;border:1px solid rgba(30,136,229,0.12);color:var(--accent)}
.card{background:var(--card);border-radius:12px;padding:20px;box-shadow:0 6px 24px rgba(16,24,40,0.06);margin-top:22px}
.grid{display:grid;grid-template-columns:1fr 420px;gap:20px;margin-top:20px}
.small{color:var(--muted);font-size:14px}
.footer{margin-top:28px;color:var(--muted);font-size:13px;text-align:center}
.link{color:var(--accent);text-decoration:none}
@media(max-width:900px){.grid{grid-template-columns:1fr} .header{flex-direction:column;align-items:flex-start}}
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <div class="brand">
      <div class="logo">S</div>
      <div>
        <h1>Schedly</h1>
        <div class="small">Effortless scheduling for meetings — host, share, book.</div>
      </div>
    </div>

    <div class="ctas">
      <?php if($user): ?>
        <div class="small">Hello, <?php echo htmlspecialchars($user['name']); ?></div>
        <button class="btn" onclick="window.location='/dashboard.php'">Dashboard</button>
        <button class="btn ghost" onclick="window.location='/logout.php'">Logout</button>
      <?php else: ?>
        <button class="btn" onclick="window.location='/register.php'">Sign up</button>
        <button class="btn ghost" onclick="window.location='/login.php'">Log in</button>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="grid">
      <div>
        <h2>Make scheduling easy — share a personal booking link</h2>
        <p class="small">Create availability, share your personal booking link, and let guests pick a time that works. The calendar prevents double-booking and sends confirmations.</p>

        <h3 style="margin-top:16px;">How it works</h3>
        <ol class="small">
          <li>Create an account and set weekly availability.</li>
          <li>Share your booking page URL (example: <code>/schedule.php?u=USER_ID</code>).</li>
          <li>Guests pick a slot and book — both of you get confirmation emails.</li>
        </ol>

        <div style="margin-top:16px;">
          <button class="btn" onclick="goToBooking()">Book a Meeting</button>
        </div>
      </div>

      <div>
        <div style="background:linear-gradient(180deg,#fff,#f9fbff);padding:18px;border-radius:10px">
          <h3 style="margin:0 0 8px">Quick demo</h3>
          <p class="small">If you don't have an account yet, click <a class="link" href="/register.php">Sign up</a>. To preview scheduling for a host, copy a user ID from /dashboard after registering and open <code>/schedule.php?u=USER_ID</code>.</p>
        </div>

        <div style="margin-top:12px;background:#fff;padding:12px;border-radius:10px;border:1px solid #eef2ff">
          <div class="small">Developer notes</div>
          <div class="small" style="margin-top:8px">DB name & credentials are pre-filled in <code>db.php</code>. Import <code>schema.sql</code> first.</div>
        </div>
      </div>
    </div>
  </div>

  <div class="footer">Made with ♥ — Place files in web root and import schema. Contact dev for customizations.</div>
</div>

<script>
function goToBooking(){
  // if user logged in, go to dashboard; else show schedule page chooser
  <?php if($user): ?>
    window.location = '/dashboard.php';
  <?php else: ?>
    // generic demo: go to register first
    window.location = '/register.php';
  <?php endif; ?>
}
</script>
</body>
</html>

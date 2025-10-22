<?php
session_start();
require_once 'db.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    if (!$email || !$password) {
        $err = "Provide email and password";
    } else {
        $stmt = $pdo->prepare("SELECT id,password_hash FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            echo "<script>window.location='/dashboard.php';</script>";
            exit;
        } else {
            $err = "Invalid credentials";
        }
    }
}
?>
<!doctype html><html><head>
<meta charset="utf-8"><title>Login — Schedly</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{font-family:Inter,Arial;background:#f7f9fc;margin:0;padding:20px}
.wrapper{max-width:440px;margin:36px auto;background:#fff;padding:22px;border-radius:12px;box-shadow:0 12px 30px rgba(16,24,40,0.06)}
h2{margin:0 0 8px}
input{width:100%;padding:10px;margin-top:6px;border-radius:8px;border:1px solid #e6eef8}
.btn{margin-top:16px;padding:10px;border-radius:10px;background:#1e88e5;color:#fff;border:none}
.err{background:#ffecec;border:1px solid #f5c6c6;padding:8px;border-radius:8px;color:#b02a37;margin-top:10px}
.link{color:#1e88e5;text-decoration:none}
</style>
</head><body>
<div class="wrapper">
  <h2>Log in</h2>
  <?php if($err): ?><div class="err"><?php echo htmlspecialchars($err);?></div><?php endif; ?>
  <form method="post">
    <label>Email</label>
    <input type="email" name="email" required>
    <label style="margin-top:8px">Password</label>
    <input type="password" name="password" required>
    <button class="btn">Login</button>
  </form>
  <div style="margin-top:10px">Don't have account? <a class="link" href="/register.php">Sign up</a></div>
</div>
</body></html>

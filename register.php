<?php
session_start();
require_once 'db.php';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    if (!$name) $errors[] = "Enter your name";
    if (!$email) $errors[] = "Enter a valid email";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
    if (empty($errors)) {
        // check exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "Email already registered. Try logging in.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name,email,password_hash) VALUES (?,?,?)");
            $stmt->execute([$name,$email,$hash]);
            $id = $pdo->lastInsertId();
            $_SESSION['user_id'] = $id;
            // JS redirect per request (no PHP header)
            echo "<script>window.location='/dashboard.php';</script>";
            exit;
        }
    }
}
?>
<!doctype html>
<html><head>
<meta charset="utf-8"><title>Register — Schedly</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{font-family:Inter,Arial;background:#f7f9fc;margin:0;padding:20px}
.wrapper{max-width:480px;margin:36px auto;background:#fff;padding:22px;border-radius:12px;box-shadow:0 12px 30px rgba(16,24,40,0.06)}
h2{margin:0 0 8px}
.small{color:#6b7280;font-size:14px}
label{display:block;margin-top:12px;font-weight:600}
input[type="text"], input[type="email"], input[type="password"]{width:100%;padding:10px;border-radius:8px;border:1px solid #e6eef8;margin-top:6px}
.btn{margin-top:16px;padding:10px 14px;border-radius:10px;border:none;background:#1e88e5;color:#fff;font-weight:700;cursor:pointer}
.err{background:#ffecec;border:1px solid #f5c6c6;padding:8px;border-radius:8px;color:#b02a37;margin-top:10px}
.note{font-size:13px;color:#6b7280;margin-top:12px}
a.link{color:#1e88e5;text-decoration:none}
</style>
</head><body>
<div class="wrapper">
  <h2>Create your account</h2>
  <div class="small">Sign up to create your booking link and availability.</div>

  <?php if(!empty($errors)): ?>
    <div class="err"><?php echo implode('<br>', array_map('htmlspecialchars',$errors)); ?></div>
  <?php endif; ?>

  <form method="post" style="margin-top:12px">
    <label>Name</label>
    <input type="text" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>

    <label>Email</label>
    <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>

    <label>Password</label>
    <input type="password" name="password" required>

    <button class="btn">Create account</button>
  </form>

  <div class="note">Already have an account? <a class="link" href="/login.php">Log in</a></div>
</div>
</body></html>

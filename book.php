<?php
require_once 'db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Method not allowed");
}
$user_id = (int)($_POST['user_id'] ?? 0);
$guest_name = trim($_POST['guest_name'] ?? '');
$guest_email = filter_var(trim($_POST['guest_email'] ?? ''), FILTER_VALIDATE_EMAIL);
$start = $_POST['start_datetime'] ?? '';
$end = $_POST['end_datetime'] ?? '';
$duration = (int)($_POST['duration_minutes'] ?? 0);
$notes = trim($_POST['notes'] ?? '');

if (!$user_id || !$guest_name || !$guest_email || !$start || !$end || !$duration) {
    die("Missing booking data");
}

// Check host exists
$stmt = $pdo->prepare("SELECT id,name,email,timezone FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) die("Host not found");

// Check slot still free
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM bookings WHERE user_id = ? AND ((start_datetime <= ? AND end_datetime > ?) OR (start_datetime < ? AND end_datetime >= ?)) AND status='confirmed'");
$stmt->execute([$user_id, $start, $start, $end, $end]);
$c = $stmt->fetch();
if ($c && $c['cnt'] > 0) {
    die("Sorry, the slot was just booked by someone else. Please try another slot.");
}

// Insert booking
$stmt = $pdo->prepare("INSERT INTO bookings (user_id,guest_name,guest_email,start_datetime,end_datetime,duration_minutes,notes) VALUES (?,?,?,?,?,?,?)");
$stmt->execute([$user_id,$guest_name,$guest_email,$start,$end,$duration,$notes]);
$booking_id = $pdo->lastInsertId();

// Send simple confirmation emails (configure PHP mail or SMTP on server)
$subject_host = "New booking: {$guest_name} on {$start}";
$subject_guest = "Booking confirmation with {$user['name']} on {$start}";
$msg_host = "You have a new booking.\n\nGuest: {$guest_name}\nEmail: {$guest_email}\nWhen: {$start} to {$end}\nNotes: {$notes}\n\nManage: https://your-site.com/dashboard.php";
$msg_guest = "Thanks {$guest_name}!\n\nYour booking with {$user['name']} is confirmed for {$start} to {$end}.\n\nIf you need to cancel/reschedule, contact the host.\n\nThank you.";
$headers = "From: no-reply@your-site.com\r\nReply-To: no-reply@your-site.com\r\n";

// Attempt to send (may require proper SMTP)
@mail($user['email'],$subject_host,$msg_host,$headers);
@mail($guest_email,$subject_guest,$msg_guest,$headers);

// Optionally create notification entry
$stmt = $pdo->prepare("INSERT INTO notifications (booking_id,user_id,type,payload) VALUES (?,?,?,?)");
$stmt->execute([$booking_id,$user_id,'booking_created',json_encode(['guest'=>$guest_name,'email'=>$guest_email,'start'=>$start])]);

// redirect to a simple confirmation page using JS redirection
echo "<script>window.location='/booking_success.php?id={$booking_id}';</script>";

<?php
require_once 'config.php';
require_once 'user.php';
$error_message = '';
$success_message = '';
$user_id = $_GET['user_id'] ?? null;
if (!$user_id) {
    Utils::redirect('register.php');
}
$database = new Database();
$db = $database->getConnection();
$user = new User($db);
// Handle OTP submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    $otp = trim($_POST['otp']);
    if ($user->verifyOtp($user_id, $otp)) {
        $user->clearOtp($user_id);
        $success_message = 'Your email has been verified! You can now <a href="login.php">login</a>.';
    } else {
        $error_message = 'Invalid or expired OTP.';
    }
}
// Handle resend OTP
if (isset($_POST['resend'])) {
    $otp = rand(100000, 999999);
    $user->setOtp($user_id, $otp);
    // Get user email
    $stmt = $db->prepare('SELECT email FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    if ($row) {
        $subject = 'Your OTP Code - Online Voting System';
        $message = 'Your OTP code is: <b>' . $otp . '</b><br>This code will expire in 10 minutes.';
        Utils::sendEmail($row['email'], $subject, $message);
        $success_message = 'A new OTP has been sent to your email.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Online Voting System</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .container { background: white; padding: 40px 32px 32px 32px; border-radius: 18px; box-shadow: 0 8px 32px rgba(102, 126, 234, 0.15); width: 100%; max-width: 400px; position: relative; text-align: center; }
        .container::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 5px; background: linear-gradient(90deg, #667eea, #764ba2); border-radius: 18px 18px 0 0; }
        .title { color: #333; font-size: 1.5em; font-weight: 700; margin-bottom: 18px; }
        .alert-error { background-color: #fee; border: 1px solid #fbb; color: #c33; padding: 12px 16px; border-radius: 7px; margin-bottom: 18px; font-size: 15px; }
        .alert-success { background-color: #efe; border: 1px solid #bfb; color: #363; padding: 12px 16px; border-radius: 7px; margin-bottom: 18px; font-size: 15px; }
        .otp-input { width: 100%; padding: 12px 14px; border: 2px solid #e1e5e9; border-radius: 7px; font-size: 16px; margin-bottom: 18px; }
        .submit-btn, .resend-btn { width: 100%; padding: 13px; background: linear-gradient(135deg, #28a745 0%, #667eea 100%); color: white; border: none; border-radius: 7px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; margin-bottom: 10px; }
        .resend-btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin-bottom: 0; }
        .submit-btn:hover, .resend-btn:hover { opacity: 0.85; }
    </style>
</head>
<body>
    <div class="container">
        <div class="title">Verify Your Email (OTP)</div>
        <?php if (!empty($error_message)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (empty($success_message)): ?>
        <form method="POST" autocomplete="off">
            <input type="text" name="otp" class="otp-input" maxlength="6" required placeholder="Enter OTP">
            <button type="submit" class="submit-btn">Verify OTP</button>
        </form>
        <form method="POST" style="margin-top:10px;">
            <input type="hidden" name="resend" value="1">
            <button type="submit" class="resend-btn">Resend OTP</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html> 
<?php
require_once 'config.php';
require_once 'user.php';
$token = $_GET['token'] ?? '';
$success = false;
if ($token) {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    if ($user->verifyByToken($token)) {
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Online Voting System</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .container { background: white; padding: 40px 32px 32px 32px; border-radius: 18px; box-shadow: 0 8px 32px rgba(102, 126, 234, 0.15); width: 100%; max-width: 400px; position: relative; text-align: center; }
        .container::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 5px; background: linear-gradient(90deg, #667eea, #764ba2); border-radius: 18px 18px 0 0; }
        .title { color: #333; font-size: 1.5em; font-weight: 700; margin-bottom: 18px; }
        .success { color: #28a745; font-size: 1.1em; margin-bottom: 18px; }
        .fail { color: #c33; font-size: 1.1em; margin-bottom: 18px; }
        a { color: #667eea; text-decoration: none; font-weight: 500; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="title">Email Verification</div>
        <?php if ($success): ?>
            <div class="success">Your email has been verified! You can now <a href="login.php">login</a>.</div>
        <?php else: ?>
            <div class="fail">Invalid or expired verification link.</div>
        <?php endif; ?>
    </div>
</body>
</html> 
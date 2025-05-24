<?php
require_once 'config.php';
require_once 'user.php';
$error_message = '';
$success_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = Utils::sanitize($_POST['username'] ?? '');
    $email = Utils::sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $full_name = Utils::sanitize($_POST['full_name'] ?? '');
    $voter_id = Utils::sanitize($_POST['voter_id'] ?? '');
    $database = new Database();
    $db = $database->getConnection();
    // Check if username or email already exists
    $stmt = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$username, $email]);
    if ($stmt->rowCount() > 0) {
        $error_message = 'Username or email already exists.';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare('INSERT INTO users (username, email, password, full_name, voter_id, is_verified, is_admin, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, 1, NOW(), NOW())');
        try {
            $stmt->execute([$username, $email, $hashed_password, $full_name, $voter_id]);
            $success_message = 'Admin registration successful! You can now <a href="login.php">login</a>.';
        } catch (PDOException $e) {
            $error_message = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - Online Voting System</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-container { background: white; padding: 40px 32px 32px 32px; border-radius: 18px; box-shadow: 0 8px 32px rgba(102, 126, 234, 0.15); width: 100%; max-width: 400px; position: relative; }
        .login-container::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 5px; background: linear-gradient(90deg, #667eea, #764ba2); border-radius: 18px 18px 0 0; }
        .logo { text-align: center; margin-bottom: 28px; }
        .logo h1 { color: #333; font-size: 2em; margin-bottom: 8px; font-weight: 700; }
        .logo p { color: #666; font-size: 0.95em; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; color: #333; font-weight: 500; margin-bottom: 7px; }
        .form-group input { width: 100%; padding: 12px 14px; border: 2px solid #e1e5e9; border-radius: 7px; font-size: 16px; transition: all 0.3s ease; }
        .form-group input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.13); }
        .login-btn { width: 100%; padding: 13px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 7px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; margin-bottom: 18px; }
        .login-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(102, 126, 234, 0.18); }
        .login-btn:active { transform: translateY(0); }
        .register-link { text-align: center; color: #666; margin-top: 10px; }
        .register-link a { color: #667eea; text-decoration: none; font-weight: 500; }
        .register-link a:hover { text-decoration: underline; }
        .alert { padding: 12px 16px; border-radius: 7px; margin-bottom: 18px; font-size: 15px; }
        .alert-error { background-color: #fee; border: 1px solid #fbb; color: #c33; }
        .alert-success { background-color: #efe; border: 1px solid #bfb; color: #363; }
        @media (max-width: 480px) { .login-container { padding: 28px 10px; margin: 10px; } .logo h1 { font-size: 1.4em; } }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🗳️ VoteSecure</h1>
            <p>Admin Registration</p>
        </div>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required placeholder="Enter your username" autofocus>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
            </div>
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required placeholder="Enter your full name">
            </div>
            <div class="form-group">
                <label for="voter_id">Voter ID</label>
                <input type="text" id="voter_id" name="voter_id" required placeholder="Enter your voter ID">
            </div>
            <button type="submit" class="login-btn">Register as Admin</button>
        </form>
        <div class="register-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</body>
</html> 
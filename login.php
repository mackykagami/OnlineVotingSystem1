<?php
require_once 'config.php';
require_once 'user.php';
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = Utils::sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        $user = new User($db);
        $login_result = $user->login($username, $password);
        if (is_array($login_result) && isset($login_result['not_verified'])) {
            $error_message = 'Your email is not verified. Please check your email.';
        } elseif ($login_result === true) {
            if (Utils::isAdmin()) {
                Utils::redirect('admin_dashboard.php');
            } else {
                Utils::redirect('dashboard.php');
            }
        } else {
            $error_message = $login_result;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Online Voting System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #e6f3ff;
            margin: 0;
            padding: 15px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: #f5f9ff;
            padding: 30px;
            border-radius: 18px;
            box-shadow: 0 0 20px rgba(0, 123, 255, 0.08);
            width: 100%;
            max-width: 400px;
            position: relative;
        }
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: #007bff;
            border-radius: 18px 18px 0 0;
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 6px;
        }
        .logo-image {
            width: 45px;
            height: 45px;
        }
        .logo h1 {
            color: #007bff;
            font-size: 2.2em;
            margin: 0;
            font-weight: 700;
        }
        .logo p {
            color: #666;
            font-size: 1em;
            margin-top: 4px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            color: #007bff;
            font-weight: 500;
            margin-bottom: 6px;
        }
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #cce4ff;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f5f9ff;
        }
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
            background: #f5f9ff;
        }
        .login-btn {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 16px;
        }
        .login-btn:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }
        .register-link {
            text-align: center;
            color: #666;
            margin-top: 12px;
        }
        .register-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 0.95em;
            line-height: 1.4;
        }
        .alert-error {
            background: #f5f9ff;
            border: 2px solid #007bff;
            color: #007bff;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 0.95em;
            line-height: 1.4;
        }
        @media (max-width: 480px) {
            .login-container {
                padding: 25px 20px;
            }
            .logo h1 {
                font-size: 1.8em;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🗳️ VoteSecure</h1>
            <p>Secure Online Voting System</p>
        </div>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" required placeholder="Enter your username or email" autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
            </div>
            <button type="submit" class="login-btn">Login</button>
        </form>
        <div class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</body>
</html>
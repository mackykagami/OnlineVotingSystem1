<?php
require_once 'config.php';
require_once 'user.php';
$error_message = '';
$success_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = Utils::sanitize($_POST['username'] ?? '');
    $email = Utils::sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $voter_id = Utils::sanitize($_POST['voter_id'] ?? '');
    if (empty($username) || empty($email) || empty($password) || empty($voter_id)) {
        $error_message = 'All fields are required.';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        $user = new User($db);
        // Check if username, email, or voter_id already exists
        $stmt = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ? OR voter_id = ?');
        $stmt->execute([$username, $email, $voter_id]);
        if ($stmt->rowCount() > 0) {
            $error_message = 'Username, email, or voter ID already exists.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $otp = rand(100000, 999999);
            $stmt = $db->prepare('INSERT INTO users (username, email, password, voter_id, is_verified, otp_code, otp_expires, created_at, updated_at) VALUES (?, ?, ?, ?, 0, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), NOW(), NOW())');
            try {
                $stmt->execute([$username, $email, $hashed_password, $voter_id, $otp]);
                // Send OTP email
                $subject = 'Your OTP Code - Online Voting System';
                $message = 'Your OTP code is: <b>' . $otp . '</b><br>This code will expire in 10 minutes.';
                Utils::sendEmail($email, $subject, $message);
                // Get user id
                $user_id = $db->lastInsertId();
                // Redirect to OTP verification page
                header('Location: verify_otp.php?user_id=' . $user_id);
                exit();
            } catch (PDOException $e) {
                $error_message = 'Registration failed: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Online Voting System</title>
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
        .register-container {
            background: #f5f9ff;
            padding: 30px;
            border-radius: 18px;
            box-shadow: 0 0 20px rgba(0, 123, 255, 0.08);
            width: 100%;
            max-width: 500px;
            position: relative;
        }
        .logo {
            text-align: center;
            margin-bottom: 28px;
        }
        .logo h1 {
            color: #007bff;
            font-size: 2em;
            margin-bottom: 8px;
            font-weight: 700;
        }
        .logo p {
            color: #666;
            font-size: 0.95em;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            color: #007bff;
            font-weight: 500;
            margin-bottom: 7px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #cce4ff;
            border-radius: 7px;
            font-size: 16px;
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
            padding: 13px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 7px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 18px;
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0, 123, 255, 0.18);
        }
        .login-btn:active {
            transform: translateY(0);
        }
        .register-link {
            text-align: center;
            color: #666;
            margin-top: 10px;
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
            border-radius: 7px;
            margin-bottom: 18px;
            font-size: 15px;
        }
        .alert-error {
            background-color: #f5f9ff;
            border: 1px solid #cce4ff;
            color: #007bff;
        }
        .alert-success {
            background-color: #e6f3ff;
            border: 1px solid #cce4ff;
            color: #007bff;
        }
        @media (max-width: 480px) {
            .register-container {
                padding: 28px 10px;
                margin: 10px;
            }
            .logo h1 {
                font-size: 1.4em;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <h1>🗳️ VoteSecure</h1>
            <p>Secure Online Voting System</p>
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
                <label for="voter_id">Voter ID</label>
                <div style="display:flex;gap:8px;align-items:center;">
                    <input type="text" id="voter_id" name="voter_id" required placeholder="Enter your voter ID" style="flex:1;">
                    <button type="button" onclick="generateVoterId()" style="padding:10px 14px;background:#667eea;color:#fff;border:none;border-radius:6px;font-weight:600;cursor:pointer;">Generate</button>
                </div>
            </div>
            <button type="submit" class="login-btn">Register</button>
        </form>
        <div class="register-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
    <script>
    function generateVoterId() {
        // Generate a random VOTE-XXXXXX id
        const random = Math.floor(100000 + Math.random() * 900000);
        document.getElementById('voter_id').value = 'VOTE-' + random;
    }
    </script>
</body>
</html>

<?php
require_once 'config.php';
class User {
    private $conn;
    private $table = 'users';
    public function __construct($db) {
        $this->conn = $db;
    }
    public function register($data) {
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            return 'All fields are required.';
        }
        $username = Utils::sanitize($data['username']);
        $email = Utils::sanitize($data['email']);
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));
        $sql = "INSERT INTO users (username, email, password, verification_token) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        try {
            $stmt->execute([$username, $email, $password, $token]);
            return true;
        } catch (PDOException $e) {
            return 'Registration failed: ' . $e->getMessage();
        }
    }
    public function login($usernameOrEmail, $password) {
        $sql = "SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            if (isset($user['is_verified']) && !$user['is_verified']) {
                return ['not_verified' => true];
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['is_admin'] = $user['is_admin'] ?? 0;
            return true;
        }
        return 'Invalid username/email or password.';
    }
    public function verifyByToken($token) {
        $sql = "UPDATE users SET is_verified = 1, verification_token = NULL WHERE verification_token = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$token]);
        return $stmt->rowCount() > 0;
    }
    public function getByVerificationToken($token) {
        $sql = "SELECT * FROM users WHERE verification_token = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$token]);
        return $stmt->fetch();
    }
    public function getByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    public function setPasswordResetToken($user_id, $token) {
        $sql = "UPDATE users SET password_reset_token = ?, password_reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$token, $user_id]);
    }
    public function resetPasswordByToken($token, $new_password) {
        $sql = "UPDATE users SET password = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE password_reset_token = ? AND password_reset_expires > NOW()";
        $stmt = $this->conn->prepare($sql);
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        return $stmt->execute([$hashed, $token]);
    }
    public function setOtp($user_id, $otp) {
        $sql = "UPDATE users SET otp_code = ?, otp_expires = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$otp, $user_id]);
    }
    public function verifyOtp($user_id, $otp) {
        $sql = "SELECT * FROM users WHERE id = ? AND otp_code = ? AND otp_expires > NOW()";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$user_id, $otp]);
        return $stmt->fetch();
    }
    public function clearOtp($user_id) {
        $sql = "UPDATE users SET otp_code = NULL, otp_expires = NULL, is_verified = 1 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$user_id]);
    }
}

?>
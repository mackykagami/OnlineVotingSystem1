<?php
/**
 * Database Configuration File
 * Contains database connection settings and system configuration
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'online_voting_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// System Configuration
define('SITE_URL', 'http://localhost/Online_Voting_System/');
define('SITE_TITLE', 'Online Voting System');
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutes in seconds

// Security Configuration
define('ENCRYPTION_KEY', 'your-secret-encryption-key-change-this');
define('HASH_ALGORITHM', 'sha256');

// File Upload Configuration
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_EXTENSIONS', array('jpg', 'jpeg', 'png', 'gif'));

// Email Configuration (for notifications)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'wazalak1213@gmail.com');
define('SMTP_PASSWORD', 'uaku vaoy hxvw cuud');
if (!defined('FROM_EMAIL')) define('FROM_EMAIL', 'your-gmail@gmail.com');
if (!defined('FROM_NAME')) define('FROM_NAME', 'Online Voting System');

// Database Connection Class
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;

    /**
     * Get database connection
     * @return PDO
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                )
            );
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            die("Database connection failed. Please try again later.");
        }
        
        return $this->conn;
    }
}

// Utility Functions
class Utils {
    /**
     * Sanitize input data
     * @param string $data
     * @return string
     */
    public static function sanitize($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }

    /**
     * Generate secure hash
     * @param string $data
     * @return string
     */
    public static function generateHash($data) {
        return hash(HASH_ALGORITHM, $data . ENCRYPTION_KEY);
    }

    /**
     * Generate random token
     * @param int $length
     * @return string
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Validate email address
     * @param string $email
     * @return bool
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Get client IP address
     * @return string
     */
    public static function getClientIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, 
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Log audit activity
     * @param int $userId
     * @param string $action
     * @param string $description
     */
    public static function logActivity($userId, $action, $description = '') {
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            $query = "INSERT INTO audit_logs (user_id, action, description, ip_address, user_agent) 
                     VALUES (:user_id, :action, :description, :ip_address, :user_agent)";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':ip_address', self::getClientIP());
            $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '');
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Audit log error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send email notification using PHPMailer and Gmail SMTP
     * @param string $to
     * @param string $subject
     * @param string $message
     * @return bool
     */
    public static function sendEmail($to, $subject, $message) {
        require_once __DIR__ . '/vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            $mail->setFrom(FROM_EMAIL, FROM_NAME);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('PHPMailer Error: ' . $mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Check if user is logged in
     * @return bool
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Check if user is admin
     * @return bool
     */
    public static function isAdmin() {
        return self::isLoggedIn() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
    }

    /**
     * Redirect to URL
     * @param string $url
     */
    public static function redirect($url) {
        header("Location: " . $url);
        exit();
    }

    /**
     * Format date for display
     * @param string $date
     * @param string $format
     * @return string
     */
    public static function formatDate($date, $format = 'Y-m-d H:i:s') {
        return date($format, strtotime($date));
    }

    /**
     * Get base URL for email links
     * @return string
     */
    public static function getBaseUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        return $protocol . $host . $dir;
    }
}

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
session_start();

// Check session timeout
if (Utils::isLoggedIn() && isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        Utils::redirect('login.php?timeout=1');
    }
}

// Update last activity time
if (Utils::isLoggedIn()) {
    $_SESSION['last_activity'] = time();
}

?>
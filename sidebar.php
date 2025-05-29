<?php
require_once 'config.php';
$current_page = basename($_SERVER['PHP_SELF']);
$user_id = $_SESSION['user_id'] ?? null;

// Get user info if logged in
$user_info = null;
if ($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user_info = $stmt->fetch();
}
?>

<style>
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    width: 250px;
    background: #f5f9ff;
    box-shadow: 2px 0 10px rgba(0, 123, 255, 0.1);
    padding: 20px;
    display: flex;
    flex-direction: column;
    z-index: 1000;
}

.sidebar-logo {
    text-align: center;
    padding: 20px 0;
    border-bottom: 2px solid #e6f3ff;
    margin-bottom: 20px;
}

.sidebar-logo h1 {
    color: #007bff;
    font-size: 1.8em;
    margin: 0;
    font-weight: 700;
}

.sidebar-logo p {
    color: #666;
    font-size: 0.9em;
    margin: 5px 0 0;
}

.user-info {
    text-align: center;
    padding: 15px;
    background: #e6f3ff;
    border-radius: 10px;
    margin-bottom: 20px;
}

.user-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #007bff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin: 0 auto 10px;
}

.user-name {
    font-weight: 600;
    color: #007bff;
    margin-bottom: 5px;
}

.user-role {
    color: #666;
    font-size: 0.9em;
}

.sidebar-menu {
    flex-grow: 1;
}

.menu-item {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    color: #666;
    text-decoration: none;
    border-radius: 8px;
    margin-bottom: 5px;
    transition: all 0.3s ease;
}

.menu-item:hover {
    background: #e6f3ff;
    color: #007bff;
}

.menu-item.active {
    background: #007bff;
    color: white;
}

.menu-item i {
    margin-right: 10px;
    font-size: 1.2em;
}

.sidebar-footer {
    padding-top: 20px;
    border-top: 2px solid #e6f3ff;
}

.logout-btn {
    width: 100%;
    padding: 12px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1em;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.logout-btn:hover {
    background: #0056b3;
}

.content-wrapper {
    margin-left: 250px;
    padding: 20px;
    min-height: 100vh;
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }

    .sidebar.active {
        transform: translateX(0);
    }

    .content-wrapper {
        margin-left: 0;
    }

    .menu-toggle {
        display: block;
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1001;
        background: #007bff;
        color: white;
        border: none;
        padding: 10px;
        border-radius: 5px;
        cursor: pointer;
    }
}
</style>

<?php if ($user_id): ?>
    <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    <div class="sidebar">
        <div class="sidebar-logo">
            <h1>🗳️ VoteSecure</h1>
            <p>Online Voting System</p>
        </div>
        
        <div class="user-info">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user_info['username'], 0, 1)); ?>
            </div>
            <div class="user-name"><?php echo htmlspecialchars($user_info['username']); ?></div>
            <div class="user-role"><?php echo $user_info['is_admin'] ? 'Administrator' : 'Voter'; ?></div>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                <i>📊</i> Dashboard
            </a>
            <a href="#" class="menu-item <?php echo $current_page === 'my_votes.php' ? 'active' : ''; ?>">
                <i>📝</i> My Votes
            </a>
            <a href="#" class="menu-item <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
                <i>👤</i> Profile
            </a>
            <?php if ($user_info['is_admin']): ?>
                <a href="admin_dashboard.php" class="menu-item">
                    <i>⚙️</i> Admin Panel
                </a>
            <?php endif; ?>
        </div>
        
        <div class="sidebar-footer">
            <form method="post" action="logout.php">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>
    </div>

    <script>
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('active');
    }
    </script>
<?php endif; ?> 
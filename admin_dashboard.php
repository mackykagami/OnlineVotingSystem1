<?php
require_once 'config.php';
if (!Utils::isLoggedIn() || !Utils::isAdmin()) {
    Utils::redirect('login.php');
}
$database = new Database();
$db = $database->getConnection();
// Fetch admin info
$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();
if (!$admin) {
    // User not found, session may be invalid or user deleted
    session_unset();
    session_destroy();
    Utils::redirect('login.php?error=notfound');
    exit;
}
// Fetch summary stats
$total_users = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
$total_elections = $db->query('SELECT COUNT(*) FROM elections')->fetchColumn();
$total_votes = $db->query('SELECT COUNT(*) FROM votes')->fetchColumn();
$total_candidates = $db->query('SELECT COUNT(*) FROM candidates')->fetchColumn();
// Fetch all elections with candidate count
$elections = $db->query('SELECT e.*, (SELECT COUNT(*) FROM candidates c WHERE c.election_id = e.id) as candidate_count FROM elections e ORDER BY start_date DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Online Voting System</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .dashboard-container { background: white; padding: 40px 32px 32px 32px; border-radius: 18px; box-shadow: 0 8px 32px rgba(102, 126, 234, 0.15); width: 100%; max-width: 800px; position: relative; }
        .dashboard-container::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 5px; background: linear-gradient(90deg, #667eea, #764ba2); border-radius: 18px 18px 0 0; }
        .logo { text-align: center; margin-bottom: 28px; }
        .logo h1 { color: #333; font-size: 2em; margin-bottom: 8px; font-weight: 700; }
        .logo p { color: #666; font-size: 0.95em; }
        .welcome { text-align: center; margin-bottom: 18px; font-size: 1.1em; color: #333; }
        .summary { display: flex; justify-content: space-between; margin-bottom: 24px; gap: 10px; flex-wrap: wrap; }
        .summary-card { background: #f7f8fa; border-radius: 8px; padding: 18px 22px; flex: 1 1 22%; text-align: center; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.07); min-width: 120px; }
        .summary-title { color: #667eea; font-size: 1.1em; font-weight: 600; margin-bottom: 6px; }
        .summary-value { font-size: 1.5em; color: #333; font-weight: 700; }
        .elections-list { margin-bottom: 18px; }
        .election-card { background: #f7f8fa; border-radius: 8px; padding: 16px; margin-bottom: 12px; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.07); }
        .election-title { font-weight: 600; color: #667eea; margin-bottom: 4px; }
        .election-desc { color: #444; margin-bottom: 6px; }
        .election-dates { color: #888; font-size: 0.95em; }
        .election-status { font-size: 0.95em; font-weight: 600; color: #fff; background: #667eea; border-radius: 5px; padding: 2px 10px; display: inline-block; margin-left: 8px; }
        .candidate-count { font-size: 0.95em; color: #333; margin-top: 4px; }
        .election-actions { margin-top: 10px; }
        .election-actions a, .election-actions form { display: inline-block; margin-right: 8px; }
        .action-btn { padding: 6px 14px; border: none; border-radius: 5px; font-size: 0.98em; font-weight: 500; cursor: pointer; background: #667eea; color: #fff; transition: background 0.2s; text-decoration: none; }
        .action-btn.edit { background: #28a745; }
        .action-btn.delete { background: #dc3545; }
        .action-btn.candidates { background: #764ba2; }
        .action-btn:hover { opacity: 0.85; }
        .logout-btn { width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 7px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; margin-top: 18px; }
        .logout-btn:hover { background: linear-gradient(135deg, #764ba2 0%, #667eea 100%); }
        .add-election-btn { display: block; width: 100%; margin-bottom: 18px; padding: 13px; background: linear-gradient(135deg, #28a745 0%, #667eea 100%); color: white; border: none; border-radius: 7px; font-size: 16px; font-weight: 600; cursor: pointer; text-align: center; text-decoration: none; }
        .add-election-btn:hover { background: linear-gradient(135deg, #667eea 0%, #28a745 100%); }
        @media (max-width: 900px) { .dashboard-container { padding: 28px 8px; margin: 10px; } .logo h1 { font-size: 1.4em; } .summary { flex-direction: column; gap: 12px; } }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="logo">
            <h1>🗳️ VoteSecure</h1>
            <p>Admin Dashboard</p>
        </div>
        <div class="welcome">Welcome, <?php echo htmlspecialchars($admin['username']); ?>!</div>
        <div class="summary">
            <div class="summary-card">
                <div class="summary-title">Total Users</div>
                <div class="summary-value"><?php echo $total_users; ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-title">Total Elections</div>
                <div class="summary-value"><?php echo $total_elections; ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-title">Total Votes</div>
                <div class="summary-value"><?php echo $total_votes; ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-title">Total Candidates</div>
                <div class="summary-value"><?php echo $total_candidates; ?></div>
            </div>
        </div>
        <a href="add_election.php" class="add-election-btn">+ Add Election</a>
        <div class="elections-list">
            <h3 style="color:#333; margin-bottom:10px;">All Elections</h3>
            <?php if (count($elections) === 0): ?>
                <div style="color:#888;">No elections found.</div>
            <?php else: ?>
                <?php foreach ($elections as $election): ?>
                    <div class="election-card">
                        <div class="election-title"><?php echo htmlspecialchars($election['title']); ?>
                            <span class="election-status" style="background:<?php
                                if ($election['status'] === 'active') echo '#28a745';
                                elseif ($election['status'] === 'completed') echo '#6c757d';
                                else echo '#667eea';
                            ?>;">
                                <?php echo ucfirst($election['status']); ?>
                            </span>
                        </div>
                        <div class="election-desc"><?php echo htmlspecialchars($election['description']); ?></div>
                        <div class="election-dates">From <?php echo date('M d, Y H:i', strtotime($election['start_date'])); ?> to <?php echo date('M d, Y H:i', strtotime($election['end_date'])); ?></div>
                        <div class="candidate-count">Candidates: <?php echo $election['candidate_count']; ?></div>
                        <div class="election-actions">
                            <a href="edit_election.php?id=<?php echo $election['id']; ?>" class="action-btn edit">Edit</a>
                            <form action="delete_election.php" method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo $election['id']; ?>">
                                <button type="submit" class="action-btn delete" onclick="return confirm('Are you sure you want to delete this election?');">Delete</button>
                            </form>
                            <a href="manage_candidates.php?election_id=<?php echo $election['id']; ?>" class="action-btn candidates">Manage Candidates</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <form method="post" action="logout.php">
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>
</body>
</html> 
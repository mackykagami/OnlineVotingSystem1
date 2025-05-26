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
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #e6f3ff;
            margin: 0;
            padding: 15px;
            min-height: 100vh;
        }
        .dashboard-container { 
            background: #f5f9ff; 
            padding: 30px;
            width: 95%;
            max-width: 1400px;
            margin: 0 auto;
            border-radius: 18px; 
            box-shadow: 0 0 20px rgba(0, 123, 255, 0.08);
            position: relative; 
        }
        .dashboard-container::before { 
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
        .welcome { 
            text-align: center; 
            margin-bottom: 20px; 
            font-size: 1.1em; 
            color: #007bff; 
        }
        .summary { 
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 25px;
        }
        .summary-card { 
            background: #f5f9ff; 
            border-radius: 12px; 
            padding: 20px; 
            text-align: center; 
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.1);
        }
        .summary-title { 
            color: #007bff; 
            font-size: 1.1em; 
            font-weight: 600; 
            margin-bottom: 8px; 
        }
        .summary-value { 
            font-size: 1.8em; 
            color: #333; 
            font-weight: 700; 
        }
        .elections-list { 
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 16px;
            margin: 20px 0;
        }
        .election-card { 
            background: #f5f9ff; 
            border-radius: 12px; 
            padding: 16px; 
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.1);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .election-title { 
            font-weight: 600; 
            color: #007bff; 
            margin-bottom: 6px;
            font-size: 1.1em;
        }
        .election-desc { 
            color: #444; 
            margin-bottom: 8px; 
            flex-grow: 1; 
            font-size: 0.95em;
        }
        .election-dates { 
            color: #666; 
            font-size: 0.9em; 
            margin-bottom: 8px; 
        }
        .election-status { 
            font-size: 0.95em; 
            font-weight: 600; 
            color: #fff; 
            background: #007bff; 
            border-radius: 5px; 
            padding: 4px 12px; 
            display: inline-block; 
            margin-left: 8px; 
        }
        .candidate-count { font-size: 0.95em; color: #333; margin: 8px 0; }
        .election-actions { 
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }
        .action-btn { 
            padding: 8px 12px; 
            border: none; 
            border-radius: 6px; 
            font-size: 0.95em; 
            font-weight: 500; 
            cursor: pointer; 
            background: #007bff; 
            color: #fff; 
            transition: all 0.2s; 
            text-decoration: none;
            flex: 1;
            text-align: center;
            min-width: 90px;
        }
        .action-btn.edit { background: #28a745; }
        .action-btn.delete { background: #dc3545; }
        .action-btn.candidates { background: #007bff; }
        .action-btn:hover { 
            opacity: 0.9; 
            transform: translateY(-1px); 
        }
        .logout-btn { 
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
            margin-top: 20px; 
        }
        .logout-btn:hover { 
            background: #0056b3;
            transform: translateY(-1px); 
        }
        .add-election-btn { 
            display: block; 
            width: 100%; 
            margin-bottom: 20px; 
            padding: 12px; 
            background: #007bff;
            color: white; 
            border: none; 
            border-radius: 8px; 
            font-size: 1.1em; 
            font-weight: 600; 
            cursor: pointer; 
            text-align: center; 
            text-decoration: none; 
            transition: all 0.3s ease;
        }
        .add-election-btn:hover { 
            background: #0056b3;
            transform: translateY(-1px); 
        }
        @media (max-width: 768px) { 
            .dashboard-container { 
                padding: 20px;
                margin: 0;
                width: auto;
            }
            .elections-list {
                grid-template-columns: 1fr;
            }
            .summary {
                grid-template-columns: repeat(2, 1fr);
            }
            .logo h1 {
                font-size: 1.8em;
            }
        }
        @media (max-width: 480px) {
            .summary {
                grid-template-columns: 1fr;
            }
            .election-actions {
                flex-direction: column;
            }
            .action-btn {
                width: 100%;
            }
            .logo h1 {
                font-size: 1.6em;
            }
            .dashboard-container {
                padding: 15px;
            }
        }
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
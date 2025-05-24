<?php
require_once 'config.php';
$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];
if (!Utils::isLoggedIn()) {
    Utils::redirect('login.php');
}
// Fetch user info
$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();
// Fetch summary stats for user
$total_elections = $db->query('SELECT COUNT(*) FROM elections')->fetchColumn();
$total_votes = $db->prepare('SELECT COUNT(*) FROM votes WHERE user_id = ?');
$total_votes->execute([$user_id]);
$total_votes = $total_votes->fetchColumn();
$upcoming_elections = $db->query("SELECT COUNT(*) FROM elections WHERE status = 'upcoming'")->fetchColumn();
// Fetch all elections with candidate count
$elections = $db->query('SELECT e.*, (SELECT COUNT(*) FROM candidates c WHERE c.election_id = e.id) as candidate_count FROM elections e ORDER BY start_date DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Online Voting System</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .dashboard-container { background: white; padding: 40px 32px 32px 32px; border-radius: 18px; box-shadow: 0 8px 32px rgba(102, 126, 234, 0.15); width: 100%; max-width: 800px; position: relative; }
        .dashboard-container::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 5px; background: linear-gradient(90deg, #667eea, #764ba2); border-radius: 18px 18px 0 0; }
        .logo { text-align: center; margin-bottom: 28px; }
        .logo h1 { color: #333; font-size: 2em; margin-bottom: 8px; font-weight: 700; }
        .logo p { color: #666; font-size: 0.95em; }
        .welcome { text-align: center; margin-bottom: 18px; font-size: 1.1em; color: #333; }
        .how-to-vote { background: #f7f8fa; border-left: 5px solid #28a745; padding: 14px 18px; border-radius: 8px; color: #333; margin-bottom: 22px; font-size: 1.08em; }
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
        .action-btn.vote { background: #28a745; }
        .action-btn.candidates { background: #764ba2; }
        .action-btn.results { background: #6c757d; }
        .action-btn.disabled, .action-btn[disabled] { background: #ccc; cursor: not-allowed; }
        .action-btn:hover { opacity: 0.85; }
        .logout-btn { width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 7px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; margin-top: 18px; }
        .logout-btn:hover { background: linear-gradient(135deg, #764ba2 0%, #667eea 100%); }
        @media (max-width: 900px) { .dashboard-container { padding: 28px 8px; margin: 10px; } .logo h1 { font-size: 1.4em; } .summary { flex-direction: column; gap: 12px; } }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="logo">
            <h1>🗳️ VoteSecure</h1>
            <p>User Dashboard</p>
        </div>
        <div class="welcome">Welcome, <?php echo htmlspecialchars($user['username']); ?>!</div>
        <div class="how-to-vote">
            <strong>How to Vote:</strong> Find an <span style="color:#28a745;font-weight:600;">active</span> election below and click the <span style="color:#28a745;font-weight:600;">Vote</span> button. You can only vote once per election. To see candidates, click <span style="color:#764ba2;font-weight:600;">View Candidates</span>. For completed elections, click <span style="color:#6c757d;font-weight:600;">View Results</span>.
        </div>
        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert-error" style="background:#fee;border:1px solid #fbb;color:#c33;padding:12px 16px;border-radius:7px;margin-bottom:18px;font-size:15px;">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        <div class="summary">
            <div class="summary-card">
                <div class="summary-title">Total Elections</div>
                <div class="summary-value"><?php echo $total_elections; ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-title">Votes Cast</div>
                <div class="summary-value"><?php echo $total_votes; ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-title">Upcoming Elections</div>
                <div class="summary-value"><?php echo $upcoming_elections; ?></div>
            </div>
        </div>
        <div class="elections-list">
            <h3 style="color:#333; margin-bottom:10px;">All Elections</h3>
            <?php if (count($elections) === 0): ?>
                <div style="color:#888;">No elections found.</div>
            <?php else: ?>
                <?php
                $has_active = false;
                foreach ($elections as $election) {
                    if ($election['status'] === 'active') {
                        $has_active = true;
                        break;
                    }
                }
                ?>
                <?php if (!$has_active): ?>
                    <div style="color:#888; margin-bottom:18px;">No active elections available for voting at this time.</div>
                <?php endif; ?>
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
                            <a href="view_candidates.php?election_id=<?php echo $election['id']; ?>" class="action-btn candidates">View Candidates</a>
                            <?php
                            // Check if user has already voted in this election
                            $vote_stmt = $db->prepare('SELECT id FROM votes WHERE user_id = ? AND election_id = ?');
                            $vote_stmt->execute([$user_id, $election['id']]);
                            $has_voted = $vote_stmt->rowCount() > 0;
                            ?>
                            <?php if ($election['status'] === 'active' && !$has_voted): ?>
                                <a href="vote.php?election_id=<?php echo $election['id']; ?>" class="action-btn vote">Vote</a>
                            <?php elseif ($election['status'] === 'active' && $has_voted): ?>
                                <button class="action-btn disabled" disabled>Voted</button>
                            <?php endif; ?>
                            <?php if ($election['status'] === 'completed'): ?>
                                <a href="view_results.php?election_id=<?php echo $election['id']; ?>" class="action-btn results">View Results</a>
                            <?php endif; ?>
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
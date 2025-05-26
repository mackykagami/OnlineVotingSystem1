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
        .how-to-vote { 
            background: #f0f7ff; 
            border-left: 5px solid #007bff; 
            padding: 16px 20px; 
            border-radius: 12px; 
            color: #007bff; 
            margin-bottom: 25px; 
            font-size: 1em;
            line-height: 1.5;
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
        .action-btn.vote { 
            background: #28a745;
        }
        .action-btn.candidates { 
            background: #fff; 
            color: #007bff; 
            border: 2px solid #007bff;
        }
        .action-btn.results { background: #6c757d; }
        .action-btn.disabled, 
        .action-btn[disabled] { 
            background: #ccc; 
            cursor: not-allowed; 
            opacity: 0.7;
        }
        .action-btn:hover:not(.disabled):not([disabled]) { 
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
        .alert-error {
            background: #f0f7ff;
            border: 2px solid #007bff;
            color: #007bff;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 1em;
            line-height: 1.4;
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
            .how-to-vote {
                padding: 14px 18px;
                font-size: 0.95em;
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
<?php
require_once 'config.php';
if (!Utils::isLoggedIn() || !Utils::isAdmin()) {
    Utils::redirect('login.php');
}
$election_id = $_GET['election_id'] ?? null;
if (!$election_id) {
    Utils::redirect('admin_dashboard.php');
}
$database = new Database();
$db = $database->getConnection();
// Fetch election info
$stmt = $db->prepare('SELECT * FROM elections WHERE id = ?');
$stmt->execute([$election_id]);
$election = $stmt->fetch();
if (!$election) {
    Utils::redirect('admin_dashboard.php');
}
// Fetch candidates
$candidates = $db->prepare('SELECT * FROM candidates WHERE election_id = ? ORDER BY position, name');
$candidates->execute([$election_id]);
$candidates = $candidates->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Candidates - Admin</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .container { background: white; padding: 40px 32px 32px 32px; border-radius: 18px; box-shadow: 0 8px 32px rgba(102, 126, 234, 0.15); width: 100%; max-width: 600px; position: relative; }
        .container::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 5px; background: linear-gradient(90deg, #667eea, #764ba2); border-radius: 18px 18px 0 0; }
        .title { text-align: center; color: #333; font-size: 1.5em; font-weight: 700; margin-bottom: 18px; }
        .candidates-list { margin-bottom: 18px; }
        .candidate-card { background: #f7f8fa; border-radius: 8px; padding: 16px; margin-bottom: 12px; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.07); display: flex; justify-content: space-between; align-items: center; }
        .candidate-info { flex: 1; }
        .candidate-name { font-weight: 600; color: #667eea; margin-bottom: 4px; }
        .candidate-party { color: #444; margin-bottom: 6px; }
        .candidate-actions { display: flex; gap: 8px; }
        .action-btn { padding: 6px 14px; border: none; border-radius: 5px; font-size: 0.98em; font-weight: 500; cursor: pointer; background: #667eea; color: #fff; transition: background 0.2s; text-decoration: none; }
        .action-btn.edit { background: #28a745; }
        .action-btn.delete { background: #dc3545; }
        .action-btn:hover { opacity: 0.85; }
        .add-candidate-btn { display: block; width: 100%; margin-bottom: 18px; padding: 13px; background: linear-gradient(135deg, #28a745 0%, #667eea 100%); color: white; border: none; border-radius: 7px; font-size: 16px; font-weight: 600; cursor: pointer; text-align: center; text-decoration: none; }
        .add-candidate-btn:hover { background: linear-gradient(135deg, #667eea 0%, #28a745 100%); }
        @media (max-width: 700px) { .container { padding: 28px 8px; margin: 10px; } .title { font-size: 1.2em; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="title">Manage Candidates for: <?php echo htmlspecialchars($election['title']); ?></div>
        <a href="add_candidate.php?election_id=<?php echo $election_id; ?>" class="add-candidate-btn">+ Add Candidate</a>
        <div class="candidates-list">
            <?php if (count($candidates) === 0): ?>
                <div style="color:#888;">No candidates found.</div>
            <?php else: ?>
                <?php foreach ($candidates as $candidate): ?>
                    <div class="candidate-card">
                        <div class="candidate-info">
                            <div class="candidate-name"><?php echo htmlspecialchars($candidate['name']); ?></div>
                            <div class="candidate-party">Party: <?php echo htmlspecialchars($candidate['party']); ?></div>
                        </div>
                        <div class="candidate-actions">
                            <a href="edit_candidate.php?id=<?php echo $candidate['id']; ?>&election_id=<?php echo $election_id; ?>" class="action-btn edit">Edit</a>
                            <form action="delete_candidate.php" method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo $candidate['id']; ?>">
                                <input type="hidden" name="election_id" value="<?php echo $election_id; ?>">
                                <button type="submit" class="action-btn delete" onclick="return confirm('Are you sure you want to delete this candidate?');">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <a href="admin_dashboard.php" class="action-btn" style="background:#888;">Back to Dashboard</a>
    </div>
</body>
</html> 
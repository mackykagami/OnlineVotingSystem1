<?php
require_once 'config.php';
if (!Utils::isLoggedIn()) {
    Utils::redirect('login.php');
}
$election_id = $_GET['election_id'] ?? null;
$user_id = $_SESSION['user_id'];
if (!$election_id) {
    Utils::redirect('dashboard.php');
}
$database = new Database();
$db = $database->getConnection();
$stmt = $db->prepare('SELECT is_verified FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user['is_verified']) {
    // Optionally set a session message
    $_SESSION['error_message'] = 'You must verify your email before voting.';
    Utils::redirect('dashboard.php');
}
// Check if user already voted
$stmt = $db->prepare('SELECT id FROM votes WHERE user_id = ? AND election_id = ?');
$stmt->execute([$user_id, $election_id]);
if ($stmt->rowCount() > 0) {
    Utils::redirect('dashboard.php');
}
// Fetch election info
$stmt = $db->prepare('SELECT * FROM elections WHERE id = ? AND status = "active"');
$stmt->execute([$election_id]);
$election = $stmt->fetch();
if (!$election) {
    Utils::redirect('dashboard.php');
}
// Fetch candidates
$candidates = $db->prepare('SELECT * FROM candidates WHERE election_id = ?');
$candidates->execute([$election_id]);
$candidates = $candidates->fetchAll();
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $candidate_id = $_POST['candidate_id'] ?? null;
    if (!$candidate_id) {
        $error_message = 'Please select a candidate.';
    } else {
        // Insert vote
        $vote_hash = Utils::generateHash($user_id . $election_id . $candidate_id . time());
        $stmt = $db->prepare('INSERT INTO votes (user_id, election_id, candidate_id, vote_hash, ip_address, user_agent, voted_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([
            $user_id,
            $election_id,
            $candidate_id,
            $vote_hash,
            Utils::getClientIP(),
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        Utils::redirect('dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote - Online Voting System</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .form-container { background: white; padding: 40px 32px 32px 32px; border-radius: 18px; box-shadow: 0 8px 32px rgba(102, 126, 234, 0.15); width: 100%; max-width: 400px; position: relative; }
        .form-container::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 5px; background: linear-gradient(90deg, #667eea, #764ba2); border-radius: 18px 18px 0 0; }
        .form-title { text-align: center; color: #333; font-size: 1.5em; font-weight: 700; margin-bottom: 18px; }
        .election-title { color: #667eea; font-size: 1.1em; font-weight: 600; margin-bottom: 8px; text-align: center; }
        .form-group { margin-bottom: 18px; }
        .candidate-list { list-style: none; padding: 0; margin: 0; }
        .candidate-item { margin-bottom: 12px; background: #f7f8fa; border-radius: 7px; padding: 10px 12px; display: flex; align-items: center; }
        .candidate-radio { margin-right: 12px; }
        .submit-btn { width: 100%; padding: 13px; background: linear-gradient(135deg, #28a745 0%, #667eea 100%); color: white; border: none; border-radius: 7px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; margin-top: 8px; }
        .submit-btn:hover { background: linear-gradient(135deg, #667eea 0%, #28a745 100%); }
        .alert-error { background-color: #fee; border: 1px solid #fbb; color: #c33; padding: 12px 16px; border-radius: 7px; margin-bottom: 18px; font-size: 15px; }
        @media (max-width: 480px) { .form-container { padding: 28px 10px; margin: 10px; } .form-title { font-size: 1.2em; } }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-title">Vote</div>
        <div class="election-title"><?php echo htmlspecialchars($election['title']); ?></div>
        <?php if (!empty($error_message)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <ul class="candidate-list">
                <?php foreach ($candidates as $candidate): ?>
                    <li class="candidate-item">
                        <input type="radio" class="candidate-radio" name="candidate_id" value="<?php echo $candidate['id']; ?>" id="candidate_<?php echo $candidate['id']; ?>">
                        <label for="candidate_<?php echo $candidate['id']; ?>"><?php echo htmlspecialchars($candidate['name']); ?> (<?php echo htmlspecialchars($candidate['party']); ?>)</label>
                    </li>
                <?php endforeach; ?>
            </ul>
            <button type="submit" class="submit-btn">Submit Vote</button>
        </form>
    </div>
</body>
</html> 
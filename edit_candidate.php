<?php
require_once 'config.php';
if (!Utils::isLoggedIn() || !Utils::isAdmin()) {
    Utils::redirect('login.php');
}
$candidate_id = $_GET['id'] ?? null;
$election_id = $_GET['election_id'] ?? null;
if (!$candidate_id || !$election_id) {
    Utils::redirect('admin_dashboard.php');
}
$database = new Database();
$db = $database->getConnection();
// Fetch candidate data
$stmt = $db->prepare('SELECT * FROM candidates WHERE id = ?');
$stmt->execute([$candidate_id]);
$candidate = $stmt->fetch();
if (!$candidate) {
    Utils::redirect('manage_candidates.php?election_id=' . $election_id);
}
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = Utils::sanitize($_POST['name'] ?? '');
    $party = Utils::sanitize($_POST['party'] ?? '');
    $biography = Utils::sanitize($_POST['biography'] ?? '');
    $stmt = $db->prepare('UPDATE candidates SET name=?, party=?, biography=?, updated_at=NOW() WHERE id=?');
    try {
        $stmt->execute([$name, $party, $biography, $candidate_id]);
        Utils::redirect('manage_candidates.php?election_id=' . $election_id);
    } catch (PDOException $e) {
        $error_message = 'Failed to update candidate: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Candidate - Admin</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .form-container { background: white; padding: 40px 32px 32px 32px; border-radius: 18px; box-shadow: 0 8px 32px rgba(102, 126, 234, 0.15); width: 100%; max-width: 400px; position: relative; }
        .form-container::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 5px; background: linear-gradient(90deg, #667eea, #764ba2); border-radius: 18px 18px 0 0; }
        .form-title { text-align: center; color: #333; font-size: 1.5em; font-weight: 700; margin-bottom: 18px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; color: #333; font-weight: 500; margin-bottom: 7px; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px 14px; border: 2px solid #e1e5e9; border-radius: 7px; font-size: 16px; transition: all 0.3s ease; }
        .form-group textarea { resize: vertical; min-height: 60px; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.13); }
        .submit-btn { width: 100%; padding: 13px; background: linear-gradient(135deg, #28a745 0%, #667eea 100%); color: white; border: none; border-radius: 7px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; margin-top: 8px; }
        .submit-btn:hover { background: linear-gradient(135deg, #667eea 0%, #28a745 100%); }
        .alert-error { background-color: #fee; border: 1px solid #fbb; color: #c33; padding: 12px 16px; border-radius: 7px; margin-bottom: 18px; font-size: 15px; }
        @media (max-width: 480px) { .form-container { padding: 28px 10px; margin: 10px; } .form-title { font-size: 1.2em; } }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-title">Edit Candidate</div>
        <?php if (!empty($error_message)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($candidate['name']); ?>">
            </div>
            <div class="form-group">
                <label for="party">Party</label>
                <input type="text" id="party" name="party" required value="<?php echo htmlspecialchars($candidate['party']); ?>">
            </div>
            <div class="form-group">
                <label for="biography">Biography</label>
                <textarea id="biography" name="biography" required><?php echo htmlspecialchars($candidate['biography']); ?></textarea>
            </div>
            <button type="submit" class="submit-btn">Update Candidate</button>
        </form>
    </div>
</body>
</html> 
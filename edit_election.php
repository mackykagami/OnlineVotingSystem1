<?php
require_once 'config.php';
if (!Utils::isLoggedIn() || !Utils::isAdmin()) {
    Utils::redirect('login.php');
}
$database = new Database();
$db = $database->getConnection();
$error_message = '';
$election_id = $_GET['id'] ?? null;
if (!$election_id) {
    Utils::redirect('admin_dashboard.php');
}
// Fetch election data
$stmt = $db->prepare('SELECT * FROM elections WHERE id = ?');
$stmt->execute([$election_id]);
$election = $stmt->fetch();
if (!$election) {
    Utils::redirect('admin_dashboard.php');
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = Utils::sanitize($_POST['title'] ?? '');
    $description = Utils::sanitize($_POST['description'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $status = $_POST['status'] ?? 'upcoming';
    $stmt = $db->prepare('UPDATE elections SET title=?, description=?, start_date=?, end_date=?, status=?, updated_at=NOW() WHERE id=?');
    try {
        $stmt->execute([$title, $description, $start_date, $end_date, $status, $election_id]);
        Utils::redirect('admin_dashboard.php');
    } catch (PDOException $e) {
        $error_message = 'Failed to update election: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Election - Admin</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .form-container { background: white; padding: 40px 32px 32px 32px; border-radius: 18px; box-shadow: 0 8px 32px rgba(102, 126, 234, 0.15); width: 100%; max-width: 400px; position: relative; }
        .form-container::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 5px; background: linear-gradient(90deg, #667eea, #764ba2); border-radius: 18px 18px 0 0; }
        .form-title { text-align: center; color: #333; font-size: 1.5em; font-weight: 700; margin-bottom: 18px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; color: #333; font-weight: 500; margin-bottom: 7px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px 14px; border: 2px solid #e1e5e9; border-radius: 7px; font-size: 16px; transition: all 0.3s ease; }
        .form-group textarea { resize: vertical; min-height: 60px; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.13); }
        .submit-btn { width: 100%; padding: 13px; background: linear-gradient(135deg, #28a745 0%, #667eea 100%); color: white; border: none; border-radius: 7px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; margin-top: 8px; }
        .submit-btn:hover { background: linear-gradient(135deg, #667eea 0%, #28a745 100%); }
        .alert-error { background-color: #fee; border: 1px solid #fbb; color: #c33; padding: 12px 16px; border-radius: 7px; margin-bottom: 18px; font-size: 15px; }
        @media (max-width: 480px) { .form-container { padding: 28px 10px; margin: 10px; } .form-title { font-size: 1.2em; } }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-title">Edit Election</div>
        <?php if (!empty($error_message)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($election['title']); ?>">
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($election['description']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="datetime-local" id="start_date" name="start_date" required value="<?php echo date('Y-m-d\TH:i', strtotime($election['start_date'])); ?>">
            </div>
            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="datetime-local" id="end_date" name="end_date" required value="<?php echo date('Y-m-d\TH:i', strtotime($election['end_date'])); ?>">
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="upcoming" <?php if ($election['status'] == 'upcoming') echo 'selected'; ?>>Upcoming</option>
                    <option value="active" <?php if ($election['status'] == 'active') echo 'selected'; ?>>Active</option>
                    <option value="completed" <?php if ($election['status'] == 'completed') echo 'selected'; ?>>Completed</option>
                </select>
            </div>
            <button type="submit" class="submit-btn">Update Election</button>
        </form>
    </div>
</body>
</html> 
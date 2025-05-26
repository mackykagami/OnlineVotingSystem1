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
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #e6f3ff;
            margin: 0;
            padding: 15px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .form-container { 
            background: #f5f9ff; 
            padding: 30px;
            width: 100%;
            max-width: 500px;
            border-radius: 18px; 
            box-shadow: 0 0 20px rgba(0, 123, 255, 0.08);
            position: relative; 
        }
        .form-container::before { 
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
        .form-title { 
            text-align: center; 
            color: #007bff; 
            font-size: 1.4em; 
            font-weight: 600; 
            margin-bottom: 20px; 
        }
        .form-group { 
            margin-bottom: 16px; 
        }
        .form-group label { 
            display: block; 
            color: #007bff; 
            font-weight: 500; 
            margin-bottom: 6px; 
        }
        .form-group input, 
        .form-group textarea, 
        .form-group select { 
            width: 100%; 
            padding: 10px 12px; 
            border: 2px solid #cce4ff; 
            border-radius: 8px; 
            font-size: 15px;
            background: #f5f9ff;
            transition: all 0.3s ease; 
        }
        .form-group textarea { 
            resize: vertical; 
            min-height: 100px; 
        }
        .form-group input:focus, 
        .form-group textarea:focus, 
        .form-group select:focus { 
            outline: none; 
            border-color: #007bff; 
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
            background: #fff;
        }
        .submit-btn { 
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
            margin-top: 12px; 
        }
        .submit-btn:hover { 
            background: #0056b3;
            transform: translateY(-1px);
        }
        .alert-error { 
            background: #f0f7ff;
            border: 2px solid #007bff;
            color: #007bff;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 0.95em;
            line-height: 1.4;
        }
        .back-btn {
            display: block;
            text-align: center;
            color: #007bff;
            text-decoration: none;
            margin-top: 12px;
            font-weight: 500;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .back-btn:hover {
            background: #f0f7ff;
        }
        @media (max-width: 480px) { 
            .form-container { 
                padding: 25px 20px; 
            }
            .logo h1 {
                font-size: 1.8em;
            }
            .form-title {
                font-size: 1.2em;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="logo">
            <h1>🗳️ VoteSecure</h1>
            <p>Edit Election</p>
        </div>
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
        <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>
</body>
</html> 
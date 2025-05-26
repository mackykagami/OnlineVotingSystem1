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
    $position = intval($_POST['position'] ?? 0);
    $stmt = $db->prepare('UPDATE candidates SET name=?, party=?, biography=?, position=?, updated_at=NOW() WHERE id=?');
    try {
        $stmt->execute([$name, $party, $biography, $position, $candidate_id]);
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
            border-radius: 18px; 
            box-shadow: 0 0 20px rgba(0, 123, 255, 0.08); 
            width: 100%; 
            max-width: 500px; 
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
        .form-title { 
            text-align: center; 
            color: #007bff; 
            font-size: 1.4em; 
            font-weight: 700; 
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
        .form-group textarea { 
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
        .form-group textarea:focus { 
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
            display: inline-block;
            padding: 8px;
            color: #007bff;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .back-btn:hover {
            background: #f0f7ff;
        }
        @media (max-width: 480px) { 
            .form-container { 
                padding: 25px 20px; 
            } 
            .form-title { 
                font-size: 1.2em; 
            } 
        }
        .form-group select { 
            width: 100%; 
            padding: 10px 12px; 
            border: 2px solid #cce4ff; 
            border-radius: 8px; 
            font-size: 15px;
            background: #f5f9ff;
            transition: all 0.3s ease; 
        }
        .form-group select:focus { 
            outline: none; 
            border-color: #007bff; 
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
            background: #fff;
        }
        .form-group optgroup {
            font-weight: 600;
            color: #007bff;
            background: #f0f7ff;
        }
        .form-group option {
            font-weight: normal;
            color: #333;
            background: white;
            padding: 8px;
        }
        .form-group select option:hover,
        .form-group select option:focus,
        .form-group select option:active {
            background: #f0f7ff;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-title">🗳️ Edit Candidate</div>
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
            <div class="form-group">
                <label for="position">Position</label>
                <select id="position" name="position" required>
                    <optgroup label="Executive Positions">
                        <option value="1" <?php if ($candidate['position'] == 1) echo 'selected'; ?>>President</option>
                        <option value="2" <?php if ($candidate['position'] == 2) echo 'selected'; ?>>Vice President</option>
                        <option value="3" <?php if ($candidate['position'] == 3) echo 'selected'; ?>>Secretary</option>
                    </optgroup>
                    <optgroup label="Year Representatives">
                        <option value="7" <?php if ($candidate['position'] == 7) echo 'selected'; ?>>4th Year Representative</option>
                        <option value="6" <?php if ($candidate['position'] == 6) echo 'selected'; ?>>3rd Year Representative</option>
                        <option value="5" <?php if ($candidate['position'] == 5) echo 'selected'; ?>>2nd Year Representative</option>
                        <option value="4" <?php if ($candidate['position'] == 4) echo 'selected'; ?>>1st Year Representative</option>
                    </optgroup>
                </select>
            </div>
            <button type="submit" class="submit-btn">Update Candidate</button>
        </form>
    </div>
</body>
</html> 
<?php
require_once 'config.php';
if (!Utils::isLoggedIn() || !Utils::isAdmin()) {
    Utils::redirect('login.php');
}
$election_id = $_GET['election_id'] ?? null;
if (!$election_id) {
    Utils::redirect('admin_dashboard.php');
}
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = Utils::sanitize($_POST['name'] ?? '');
    $party = Utils::sanitize($_POST['party'] ?? '');
    $biography = Utils::sanitize($_POST['biography'] ?? '');
    $position = intval($_POST['position'] ?? 0);

    if (empty($name) || empty($party) || empty($biography) || empty($position)) {
        $error_message = 'All fields are required.';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        $stmt = $db->prepare('INSERT INTO candidates (election_id, name, party, biography, position, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
        try {
            $stmt->execute([$election_id, $name, $party, $biography, $position]);
            Utils::redirect('manage_candidates.php?election_id=' . $election_id);
        } catch (PDOException $e) {
            $error_message = 'Failed to add candidate: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Candidate - Admin</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #e6f3ff;
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 15px; 
            margin: 0;
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
            border: 2px solid #f8d7da; 
            border-radius: 8px; 
            font-size: 15px; 
            transition: all 0.3s ease; 
        }
        .form-group select:focus { 
            outline: none; 
            border-color: #dc3545; 
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1); 
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
        <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
        <div class="form-title">🗳️ Add New Candidate</div>
        <?php if (!empty($error_message)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required placeholder="Candidate name">
            </div>
            <div class="form-group">
                <label for="party">Party</label>
                <input type="text" id="party" name="party" required placeholder="Party name">
            </div>
            <div class="form-group">
                <label for="biography">Biography</label>
                <textarea id="biography" name="biography" required placeholder="Short biography"></textarea>
            </div>
            <div class="form-group">
                <label for="position">Position</label>
                <select id="position" name="position" required class="form-control">
                    <option value="">Select Position</option>
                    <optgroup label="Executive Positions">
                        <option value="1">President</option>
                        <option value="2">Vice President</option>
                        <option value="3">Secretary</option>
                    </optgroup>
                    <optgroup label="Year Representatives">
                        <option value="7">4th Year Representative</option>
                        <option value="6">3rd Year Representative</option>
                        <option value="5">2nd Year Representative</option>
                        <option value="4">1st Year Representative</option>
                    </optgroup>
                </select>
            </div>
            <button type="submit" class="submit-btn">Add Candidate</button>
        </form>
    </div>
</body>
</html> 
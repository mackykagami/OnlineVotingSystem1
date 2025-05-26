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
// Define positions
$positions = [
    1 => 'President',
    2 => 'Vice President',
    3 => 'Secretary',
    4 => '1st Year Representative',
    5 => '2nd Year Representative',
    6 => '3rd Year Representative',
    7 => '4th Year Representative'
];
// Get all candidates grouped by position
$stmt = $db->prepare('SELECT * FROM candidates WHERE election_id = ? ORDER BY position, name');
$stmt->execute([$election_id]);
$candidates = $stmt->fetchAll();
// Group candidates by position
$candidates_by_position = [];
foreach ($candidates as $candidate) {
    $candidates_by_position[$candidate['position']][] = $candidate;
}
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $selected_candidates = $_POST['candidates'] ?? [];
    $all_selected = true;
    
    // Check if a candidate is selected for each position that has candidates
    foreach ($candidates_by_position as $pos_id => $pos_candidates) {
        if (empty($selected_candidates[$pos_id])) {
            $all_selected = false;
            break;
        }
    }
    
    if (!$all_selected) {
        $error_message = 'Please select a candidate for every position.';
    } else {
        // Record votes for each position
        try {
            $db->beginTransaction();
            foreach ($selected_candidates as $position => $candidate_id) {
                try {
                    $vote_hash = hash('sha256', $user_id . $election_id . $candidate_id . time() . rand());
                    $stmt = $db->prepare('INSERT INTO votes (user_id, election_id, candidate_id, vote_hash, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$user_id, $election_id, $candidate_id, $vote_hash, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
                } catch (PDOException $e) {
                    // Check if it's a duplicate vote error
                    if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'unique_user_election') !== false) {
                        $db->rollBack();
                        $_SESSION['error_message'] = 'You have already voted in this election.';
                        Utils::redirect('dashboard.php');
                        exit;
                    }
                    // For other errors, throw the exception to be caught by outer try-catch
                    throw $e;
                }
            }
            $db->commit();
            $_SESSION['success_message'] = 'Your votes have been recorded successfully!';
            Utils::redirect('dashboard.php');
        } catch (Exception $e) {
            $db->rollBack();
            $error_message = 'An error occurred while recording your vote. Please try again.';
        }
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
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #e6f3ff;
            margin: 0;
            padding: 15px;
            min-height: 100vh;
        }
        .voting-container {
            max-width: 800px;
            margin: 0 auto;
            background: #f5f9ff;
            padding: 25px;
            border-radius: 18px;
            box-shadow: 0 0 20px rgba(0, 123, 255, 0.08);
            position: relative;
        }
        .voting-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: #007bff;
            border-radius: 18px 18px 0 0;
        }
        .position-section {
            margin-bottom: 25px;
            padding: 16px;
            background: #f5f9ff;
            border-radius: 12px;
            border-left: 5px solid #007bff;
        }
        .position-title {
            color: #007bff;
            font-size: 1.2em;
            margin-bottom: 12px;
            font-weight: 600;
        }
        .candidate-card {
            background: #f5f9ff;
            padding: 12px;
            margin-bottom: 12px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 123, 255, 0.05);
            display: flex;
            align-items: center;
        }
        .candidate-info {
            flex-grow: 1;
            margin-left: 12px;
        }
        .candidate-name {
            font-weight: 600;
            color: #007bff;
            margin-bottom: 4px;
            font-size: 1.1em;
        }
        .candidate-party {
            color: #666;
            font-size: 0.9em;
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
            margin-top: 16px;
        }
        .submit-btn:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }
        .error-message {
            background: #f0f7ff;
            border: 2px solid #007bff;
            color: #007bff;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 0.95em;
            line-height: 1.4;
        }
        .radio-input {
            accent-color: #007bff;
        }
        .page-title {
            text-align: center;
            color: #007bff;
            font-size: 1.8em;
            margin-bottom: 20px;
            font-weight: 700;
        }
        @media (max-width: 768px) {
            .voting-container {
                padding: 20px;
            }
            .position-section {
                padding: 14px;
            }
            .page-title {
                font-size: 1.6em;
            }
        }
        @media (max-width: 480px) {
            .voting-container {
                padding: 15px;
            }
            .position-section {
                padding: 12px;
            }
            .candidate-card {
                padding: 10px;
            }
            .page-title {
                font-size: 1.4em;
            }
        }
    </style>
</head>
<body>
    <div class="voting-container">
        <h1 class="page-title">🗳️ Cast Your Vote</h1>
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <form method="POST">
            <?php foreach ($positions as $pos_id => $pos_name): ?>
                <?php if (isset($candidates_by_position[$pos_id])): ?>
                    <div class="position-section">
                        <div class="position-title"><?php echo htmlspecialchars($pos_name); ?></div>
                        <?php foreach ($candidates_by_position[$pos_id] as $candidate): ?>
                            <div class="candidate-card">
                                <input type="radio" name="candidates[<?php echo $pos_id; ?>]" value="<?php echo $candidate['id']; ?>" class="radio-input" required>
                                <div class="candidate-info">
                                    <div class="candidate-name"><?php echo htmlspecialchars($candidate['name']); ?></div>
                                    <div class="candidate-party"><?php echo htmlspecialchars($candidate['party']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
            <button type="submit" class="submit-btn">Submit Vote</button>
        </form>
    </div>
</body>
</html> 
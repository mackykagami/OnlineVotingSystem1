<?php
require_once 'config.php';
$election_id = $_GET['election_id'] ?? null;
if (!Utils::isLoggedIn() || !$election_id) {
    Utils::redirect('dashboard.php');
}
$database = new Database();
$db = $database->getConnection();
// Fetch election info
$stmt = $db->prepare('SELECT * FROM elections WHERE id = ?');
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Candidates - Online Voting System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #e6f3ff;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        .candidates-container {
            max-width: 1200px;
            margin: 0 auto;
            background: #f5f9ff;
            padding: 30px;
            border-radius: 18px;
            box-shadow: 0 0 20px rgba(0, 123, 255, 0.08);
            position: relative;
        }
        .position-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f5f9ff;
            border-radius: 10px;
            border-left: 5px solid #007bff;
        }
        .position-title {
            color: #007bff;
            font-size: 1.5em;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .candidate-card {
            background: #f5f9ff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.05);
        }
        .candidate-name {
            font-size: 1.2em;
            font-weight: 600;
            color: #007bff;
            margin-bottom: 8px;
        }
        .candidate-party {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 12px;
        }
        .candidate-bio {
            color: #444;
            font-size: 0.95em;
            line-height: 1.5;
        }
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .back-btn:hover {
            background: #0056b3;
        }
        @media (max-width: 768px) {
            .candidates-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="content-wrapper">
        <div class="candidates-container">
            <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            <h1 style="text-align: center; color: #007bff; margin-bottom: 30px;">🗳️ Candidates List</h1>
            
            <?php foreach ($positions as $pos_id => $pos_name): ?>
                <?php if (isset($candidates_by_position[$pos_id])): ?>
                    <div class="position-section">
                        <div class="position-title"><?php echo htmlspecialchars($pos_name); ?></div>
                        <div class="candidates-grid">
                            <?php foreach ($candidates_by_position[$pos_id] as $candidate): ?>
                                <div class="candidate-card">
                                    <div class="candidate-name"><?php echo htmlspecialchars($candidate['name']); ?></div>
                                    <div class="candidate-party"><?php echo htmlspecialchars($candidate['party']); ?></div>
                                    <div class="candidate-bio"><?php echo nl2br(htmlspecialchars($candidate['biography'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html> 
<?php
require_once 'config.php';
require_once 'election.php';
$election_id = $_GET['election_id'] ?? null;
if (!Utils::isLoggedIn() || !$election_id) {
    Utils::redirect('dashboard.php');
}
$database = new Database();
$db = $database->getConnection();

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

// Get election details
$stmt = $db->prepare('SELECT * FROM elections WHERE id = ?');
$stmt->execute([$election_id]);
$election = $stmt->fetch();

if (!$election) {
    Utils::redirect('dashboard.php');
}

// Get results grouped by position
$stmt = $db->prepare('
    SELECT 
        c.id,
        c.name,
        c.party,
        c.position,
        COUNT(v.id) as vote_count,
        (SELECT COUNT(*) FROM votes WHERE election_id = ?) as total_votes
    FROM candidates c
    LEFT JOIN votes v ON c.id = v.candidate_id
    WHERE c.election_id = ?
    GROUP BY c.id
    ORDER BY c.position, vote_count DESC
');
$stmt->execute([$election_id, $election_id]);
$results = $stmt->fetchAll();

// Group results by position
$results_by_position = [];
foreach ($results as $result) {
    $results_by_position[$result['position']][] = $result;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results - Online Voting System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #e6f3ff;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        .results-container {
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
        .result-card {
            background: #f5f9ff;
            padding: 20px;
            margin-bottom: 15px;
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
        .vote-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
        }
        .vote-count {
            background: #007bff;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
        }
        .vote-percentage {
            color: #666;
            font-size: 0.9em;
        }
        .progress-bar {
            height: 10px;
            background: #e6f3ff;
            border-radius: 5px;
            margin-top: 10px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: #007bff;
            border-radius: 5px;
            transition: width 0.3s ease;
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
        .winner {
            background: #007bff;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            margin-left: 10px;
        }
        @media (max-width: 768px) {
            .results-container {
                padding: 15px;
            }
            .position-section {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="content-wrapper">
        <div class="results-container">
            <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
            <h1 style="text-align: center; color: #007bff; margin-bottom: 30px;">🗳️ Election Results</h1>
            <h2 style="text-align: center; color: #666; margin-bottom: 30px;"><?php echo htmlspecialchars($election['title']); ?></h2>
            
            <?php foreach ($positions as $pos_id => $pos_name): ?>
                <?php if (isset($results_by_position[$pos_id])): ?>
                    <div class="position-section">
                        <div class="position-title"><?php echo htmlspecialchars($pos_name); ?></div>
                        <?php 
                        $position_results = $results_by_position[$pos_id];
                        $max_votes = max(array_column($position_results, 'vote_count'));
                        foreach ($position_results as $result): 
                            $percentage = $result['total_votes'] > 0 ? 
                                round(($result['vote_count'] / $result['total_votes']) * 100, 1) : 0;
                        ?>
                            <div class="result-card">
                                <div class="candidate-name">
                                    <?php echo htmlspecialchars($result['name']); ?>
                                    <?php if ($result['vote_count'] == $max_votes && $result['vote_count'] > 0): ?>
                                        <span class="winner">Winner</span>
                                    <?php endif; ?>
                                </div>
                                <div class="candidate-party"><?php echo htmlspecialchars($result['party']); ?></div>
                                <div class="vote-info">
                                    <span class="vote-count"><?php echo $result['vote_count']; ?> votes</span>
                                    <span class="vote-percentage"><?php echo $percentage; ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html> 
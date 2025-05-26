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
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #e6f3ff;
            margin: 0;
            padding: 15px;
            min-height: 100vh;
        }
        .container { 
            background: #f5f9ff; 
            padding: 30px;
            width: 95%;
            max-width: 1400px;
            margin: 0 auto;
            border-radius: 18px; 
            box-shadow: 0 0 20px rgba(0, 123, 255, 0.08);
            position: relative; 
        }
        .container::before { 
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
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 6px;
        }
        .logo-image {
            width: 45px;
            height: 45px;
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
        .page-title {
            text-align: center;
            color: #007bff;
            font-size: 1.4em;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 16px;
            margin: 20px 0;
        }
        .candidate-card {
            background: #f5f9ff;
            padding: 16px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.1);
        }
        .candidate-name {
            color: #007bff;
            font-size: 1.1em;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .candidate-party {
            color: #666;
            font-size: 0.95em;
            margin-bottom: 8px;
        }
        .candidate-bio {
            color: #444;
            font-size: 0.9em;
            margin-bottom: 12px;
            line-height: 1.4;
        }
        .candidate-position {
            color: #007bff;
            font-size: 0.9em;
            font-weight: 500;
            margin-bottom: 12px;
            padding: 4px 8px;
            background: white;
            border-radius: 6px;
            display: inline-block;
        }
        .action-buttons {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }
        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 0.95em;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            flex: 1;
        }
        .btn-edit {
            background: #007bff;
            color: white;
        }
        .btn-edit:hover {
            background: #0056b3;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        .btn-delete:hover {
            background: #c82333;
        }
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .add-candidate-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .add-candidate-btn:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }
        .back-btn {
            display: inline-block;
            padding: 8px 12px;
            color: #007bff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: 16px;
        }
        .back-btn:hover {
            background: #f0f7ff;
        }
        .no-candidates {
            text-align: center;
            color: #666;
            margin: 20px 0;
        }
        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 0;
                width: auto;
            }
            .candidates-grid {
                grid-template-columns: 1fr;
            }
            .logo h1 {
                font-size: 1.8em;
            }
        }
        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }
            .action-buttons {
                flex-direction: column;
            }
            .btn {
                width: 100%;
            }
            .logo h1 {
                font-size: 1.6em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>🗳️ VoteSecure</h1>
            <p>Manage Candidates</p>
        </div>
        <h2 class="page-title"><?php echo htmlspecialchars($election['title']); ?></h2>
        <a href="add_candidate.php?election_id=<?php echo $election_id; ?>" class="add-candidate-btn">+ Add New Candidate</a>
        <div class="candidates-grid">
            <?php if (count($candidates) === 0): ?>
                <div class="no-candidates">No candidates found.</div>
            <?php else: ?>
                <?php foreach ($candidates as $candidate): ?>
                    <div class="candidate-card">
                        <div class="candidate-name"><?php echo htmlspecialchars($candidate['name']); ?></div>
                        <div class="candidate-party">Party: <?php echo htmlspecialchars($candidate['party']); ?></div>
                        <div class="candidate-position">Position: <?php 
                            $positions = [1=>'President',2=>'Vice President',3=>'Secretary',4=>'1st Year Representative',5=>'2nd Year Representative',6=>'3rd Year Representative',7=>'4th Year Representative'];
                            echo $positions[$candidate['position']] ?? 'Unknown';
                        ?></div>
                        <div class="action-buttons">
                            <a href="edit_candidate.php?id=<?php echo $candidate['id']; ?>&election_id=<?php echo $election_id; ?>" class="btn btn-edit">Edit</a>
                            <form action="delete_candidate.php" method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo $candidate['id']; ?>">
                                <input type="hidden" name="election_id" value="<?php echo $election_id; ?>">
                                <button type="submit" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this candidate?');">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <a href="admin_dashboard.php" class="back-btn">Back to Dashboard</a>
    </div>
</body>
</html> 
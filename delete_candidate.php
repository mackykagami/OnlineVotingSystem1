<?php
require_once 'config.php';
if (!Utils::isLoggedIn() || !Utils::isAdmin()) {
    Utils::redirect('login.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['election_id'])) {
    $candidate_id = $_POST['id'];
    $election_id = $_POST['election_id'];
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare('DELETE FROM candidates WHERE id = ?');
    $stmt->execute([$candidate_id]);
    Utils::redirect('manage_candidates.php?election_id=' . $election_id);
} else {
    Utils::redirect('admin_dashboard.php');
} 
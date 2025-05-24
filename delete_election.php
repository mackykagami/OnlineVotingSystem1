<?php
require_once 'config.php';
if (!Utils::isLoggedIn() || !Utils::isAdmin()) {
    Utils::redirect('login.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $election_id = $_POST['id'];
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare('DELETE FROM elections WHERE id = ?');
    $stmt->execute([$election_id]);
}
Utils::redirect('admin_dashboard.php'); 
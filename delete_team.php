<?php
session_start();
require_once('config.php');

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['delete_btn']) && isset($_POST['team_id'])) {
    $team_id = $_POST['team_id'];
    $user_email = $_SESSION['user'];

    // 1. Verify this team belongs to the logged-in user
    $check = $conn->prepare("SELECT id FROM team_information WHERE id = ? AND user_email = ?");
    $check->bind_param("is", $team_id, $user_email);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        // 2. Start Transaction
        $conn->begin_transaction();

        try {
            // 3. Delete Members first (if you don't have ON DELETE CASCADE)
            $del_members = $conn->prepare("DELETE FROM team_members WHERE team_id = ?");
            $del_members->bind_param("i", $team_id);
            $del_members->execute();

            // 4. Delete the Team
            $del_team = $conn->prepare("DELETE FROM team_information WHERE id = ?");
            $del_team->bind_param("i", $team_id);
            $del_team->execute();

            $conn->commit();
            $_SESSION['success'] = "Team deleted successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "Failed to delete team.";
        }
    }
}

header("Location: dashboard.php");
exit();
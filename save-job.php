<?php
session_start();
require_once 'database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['userId']) || ($_SESSION['userRole'] ?? 'user') !== 'user' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Please log in as a job seeker.']);
    exit;
}

$jobId = (int) ($_POST['job_id'] ?? 0);
if ($jobId < 1) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Invalid job.']);
    exit;
}

$userId = (int) $_SESSION['userId'];
$check = $conn->prepare('SELECT id FROM saved_jobs WHERE user_id = ? AND job_id = ?');
$check->bind_param('ii', $userId, $jobId);
$check->execute();

if ($check->get_result()->fetch_assoc()) {
    $remove = $conn->prepare('DELETE FROM saved_jobs WHERE user_id = ? AND job_id = ?');
    $remove->bind_param('ii', $userId, $jobId);
    $remove->execute();
    echo json_encode(['ok' => true, 'saved' => false, 'message' => 'Removed from saved jobs.']);
} else {
    $save = $conn->prepare('INSERT INTO saved_jobs (user_id, job_id) VALUES (?, ?)');
    $save->bind_param('ii', $userId, $jobId);
    $save->execute();
    echo json_encode(['ok' => true, 'saved' => true, 'message' => 'Job successfully saved.']);
}
?>

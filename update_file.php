<?php
session_start();

// Verify user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Session expired, please login again.']);
    exit();
}

// Database Connection
$host = 'localhost';
$user = 'root';
$pass = 'root';
$dbname = 'cloudbox';
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

$userid = $_SESSION['user_id'];

// Check if required parameters are provided
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['file_id']) || !isset($_POST['content'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
    exit();
}

$fileId = intval($_POST['file_id']);
$content = $_POST['content'];

// Verify user has permission to edit this file
$permissionCheck = $conn->prepare("SELECT f.id 
                                 FROM files f 
                                 LEFT JOIN shared_files sf ON f.id = sf.file_id AND sf.shared_with = ?
                                 WHERE f.id = ? AND (f.user_id = ? OR sf.permission = 'edit')");
$permissionCheck->bind_param("iii", $userid, $fileId, $userid);
$permissionCheck->execute();
$result = $permissionCheck->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You do not have permission to edit this file']);
    exit();
}

// Update file content
$updateQuery = $conn->prepare("UPDATE file_content SET content = ? WHERE file_id = ?");
$updateQuery->bind_param("si", $content, $fileId);

if ($updateQuery->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'File updated successfully']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error updating file: ' . $conn->error]);
}

$conn->close();
?>

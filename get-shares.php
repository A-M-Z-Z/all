<?php
session_start();

// Verify user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

// Database Connection
$host = 'localhost';
$user = 'root';
$pass = 'root';
$dbname = 'cloudbox;
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$userid = $_SESSION['user_id'];

// Check if file_id is provided
if (!isset($_GET['file_id']) || !is_numeric($_GET['file_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid file ID']);
    exit();
}

$fileId = intval($_GET['file_id']);

// Verify file belongs to user
$fileCheck = $conn->prepare("SELECT id FROM files WHERE id = ? AND user_id = ?");
$fileCheck->bind_param("ii", $fileId, $userid);
$fileCheck->execute();

if ($fileCheck->get_result()->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'File not found or access denied']);
    exit();
}

// Get share information
$shareQuery = $conn->prepare("SELECT sf.shared_with as user_id, sf.permission, sf.expiration_date, u.username 
                            FROM shared_files sf 
                            JOIN users u ON sf.shared_with = u.id 
                            WHERE sf.file_id = ?");
$shareQuery->bind_param("i", $fileId);
$shareQuery->execute();
$shareResult = $shareQuery->get_result();

$shares = [];
while ($share = $shareResult->fetch_assoc()) {
    // Format the date if it exists
    if ($share['expiration_date']) {
        $share['expiration_date'] = date('Y-m-d', strtotime($share['expiration_date']));
    }
    $shares[] = $share;
}

// Return the shares information
header('Content-Type: application/json');
echo json_encode(['success' => true, 'shares' => $shares]);
exit();

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

// Check if file_id is provided
if (!isset($_GET['file_id']) || !is_numeric($_GET['file_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid file ID']);
    exit();
}

$fileId = intval($_GET['file_id']);

// Get file information from database
$query = $conn->prepare("SELECT f.id, f.filename, f.file_type, f.file_size, f.user_id 
                        FROM files f 
                        WHERE f.id = ? AND (f.user_id = ? OR f.id IN (
                            SELECT file_id FROM shared_files WHERE shared_with = ?
                        ))");
$query->bind_param("iii", $fileId, $userid, $userid);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'File not found or you don\'t have permission to access it.']);
    exit();
}

$file = $result->fetch_assoc();

// Get file extension
$extension = strtolower(pathinfo($file['filename'], PATHINFO_EXTENSION));

// Return file information
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'id' => $file['id'],
    'filename' => $file['filename'],
    'file_type' => $file['file_type'],
    'file_size' => $file['file_size'],
    'extension' => $extension,
    'is_owner' => ($file['user_id'] == $userid)
]);

$conn->close();
?>

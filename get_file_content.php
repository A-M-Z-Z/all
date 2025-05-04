<?php
session_start();

// Verify user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Session expired, please login again.']);
    exit();
}

// Database Connection
$host = '91.216.107.164';
$user = 'amzz2427862';
$pass = '37qB5xqen4prX8@';
$dbname = 'amzz2427862';
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
$raw = isset($_GET['raw']) && $_GET['raw'] == 1;

// Get file information from database
$query = $conn->prepare("SELECT f.filename, f.file_type, fc.content 
                        FROM files f 
                        JOIN file_content fc ON f.id = fc.file_id 
                        WHERE f.id = ? AND (f.user_id = ? OR f.id IN (
                            SELECT file_id FROM shared_files WHERE shared_with = ?
                        ))");
$query->bind_param("iii", $fileId, $userid, $userid);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    if ($raw) {
        header('HTTP/1.1 404 Not Found');
        exit('File not found or access denied');
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'File not found or you don\'t have permission to access it.']);
        exit();
    }
}

$file = $result->fetch_assoc();

// If raw mode is requested, output the file content directly with appropriate headers
if ($raw) {
    // Set content type header
    header('Content-Type: ' . $file['file_type']);
    
    // Set content disposition header for download
    if (isset($_GET['download'])) {
        header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
    } else {
        header('Content-Disposition: inline; filename="' . $file['filename'] . '"');
    }
    
    // Output the file content
    echo $file['content'];
} else {
    // Return file content as JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'filename' => $file['filename'],
        'file_type' => $file['file_type'],
        'content' => base64_encode($file['content'])
    ]);
}

$conn->close();
?>
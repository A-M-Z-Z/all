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
$dbname = 'cloudbox';
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$userid = $_SESSION['user_id'];

// Check if necessary parameters are provided
if (!isset($_POST['file_id']) || !is_numeric($_POST['file_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid file ID']);
    exit();
}

$fileId = intval($_POST['file_id']);
$folderId = $_POST['folder_id'] === 'root' ? null : intval($_POST['folder_id']);

// Verify file belongs to user
$fileCheck = $conn->prepare("SELECT id, filename FROM files WHERE id = ? AND user_id = ?");
$fileCheck->bind_param("ii", $fileId, $userid);
$fileCheck->execute();
$fileResult = $fileCheck->get_result();

if ($fileResult->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'File not found or access denied']);
    exit();
}

$fileData = $fileResult->fetch_assoc();

// If moving to a folder, verify folder belongs to user
if ($folderId !== null) {
    $folderCheck = $conn->prepare("SELECT id, folder_name FROM folders WHERE id = ? AND user_id = ?");
    $folderCheck->bind_param("ii", $folderId, $userid);
    $folderCheck->execute();
    $folderResult = $folderCheck->get_result();
    
    if ($folderResult->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Folder not found or access denied']);
        exit();
    }
    
    $folderData = $folderResult->fetch_assoc();
}

// Check if a file with the same name already exists in the destination
$checkQuery = $conn->prepare("SELECT id FROM files WHERE user_id = ? AND filename = ? AND " . 
                          ($folderId ? "folder_id = ?" : "folder_id IS NULL"));

if ($folderId) {
    $checkQuery->bind_param("isi", $userid, $fileData['filename'], $folderId);
} else {
    $checkQuery->bind_param("is", $userid, $fileData['filename']);
}

$checkQuery->execute();
$checkResult = $checkQuery->get_result();

if ($checkResult->num_rows > 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'A file with the same name already exists in the destination folder']);
    exit();
}

// Move the file
$moveQuery = $conn->prepare("UPDATE files SET folder_id = ? WHERE id = ? AND user_id = ?");
$moveQuery->bind_param("iii", $folderId, $fileId, $userid);
$moveQuery->execute();

if ($moveQuery->affected_rows > 0) {
    $destinationName = $folderId ? $folderData['folder_name'] : 'Root';
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => "File '{$fileData['filename']}' moved to '{$destinationName}' successfully"
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error moving file: ' . $conn->error]);
}

<?php
require_once '../db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();
    
    try {
        // 🔑 NOTE: Key name is 'teacher_id'
        $teacher_id = $_POST['teacher_id'] ?? null;
        $current_pic_path = $_POST['current_pic_path'] ?? null;
        
        if (empty($teacher_id)) {
            throw new Exception("Teacher ID is missing.");
        }

        // 1. Delete the physical file from the server
        if (!empty($current_pic_path) && file_exists("../" . $current_pic_path)) {
             // The "../" path navigates up one level from the Action folder to the project root
             if (!unlink("../" . $current_pic_path)) {
                 throw new Exception("Failed to delete physical file. Check file permissions.");
             }
        }
        
        // 2. Update the TEACHERS table, setting profile_picture to NULL
        $sql_teacher = "UPDATE teachers SET profile_picture = NULL WHERE id = ?";
        $stmt_teacher = $conn->prepare($sql_teacher);
        $stmt_teacher->bind_param("i", $teacher_id);
        $stmt_teacher->execute();
        $stmt_teacher->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Profile picture removed successfully!']);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Removal failed: ' . $e->getMessage()]);
        exit;
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
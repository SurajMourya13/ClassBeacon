<?php
require_once '../db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();
    
    try {
        // Get the student ID and current picture path from the POST request
        $student_id = $_POST['student_id'] ?? null;
        $current_pic_path = $_POST['current_pic_path'] ?? null;
        
        if (empty($student_id)) {
            throw new Exception("Student ID is missing.");
        }

        // 1. Delete the physical file from the server
        if (!empty($current_pic_path) && file_exists("../" . $current_pic_path)) {
             // Use "../" to correctly point to the project root directory
             if (!unlink("../" . $current_pic_path)) {
                 // Throw error if file deletion fails (e.g., due to permissions)
                 throw new Exception("Failed to delete physical file. Check permissions.");
             }
        }
        
        // 2. Update the STUDENTS table, setting profile_picture to NULL
        $sql_student = "UPDATE students SET profile_picture = NULL WHERE id = ?";
        $stmt_student = $conn->prepare($sql_student);
        $stmt_student->bind_param("i", $student_id);
        $stmt_student->execute();
        $stmt_student->close();

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
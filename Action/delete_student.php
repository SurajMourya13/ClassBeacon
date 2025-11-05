<?php
// Correct path for file inside the Action/ folder
require_once '../db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['id'];

    if (empty($student_id)) {
        echo json_encode(['success' => false, 'message' => 'Student ID is required.']);
        exit;
    }

    // Start transaction to ensure both deletions succeed or fail together
    $conn->begin_transaction();

    try {
        // 1. Get the user_id associated with the student_id
        $sql_select = "SELECT user_id FROM students WHERE id = ?";
        $stmt_select = $conn->prepare($sql_select);
        $stmt_select->bind_param('i', $student_id);
        $stmt_select->execute();
        $result = $stmt_select->get_result();
        
        if ($user = $result->fetch_assoc()) {
            $user_id = $user['user_id'];
        } else {
            // If student record doesn't exist, stop and report failure
            throw new Exception("Student record not found in profile table.");
        }
        $stmt_select->close();


        // 2. Delete the record from the students profile table
        $sql_student = "DELETE FROM students WHERE id = ?";
        $stmt_student = $conn->prepare($sql_student);
        $stmt_student->bind_param('i', $student_id);
        $stmt_student->execute();
        $stmt_student->close();


        // 3. Delete the corresponding user login from the users table
        $sql_user = "DELETE FROM users WHERE id = ?";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param('i', $user_id);
        $stmt_user->execute();
        $stmt_user->close();


        // Commit the transaction if all deletions succeeded
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Student profile and login deleted successfully!']);

    } catch (Exception $e) {
        // Rollback the transaction on any failure
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Deletion failed: ' . $e->getMessage()]);
    }

    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
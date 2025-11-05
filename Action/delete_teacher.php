<?php
require_once '../db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = $_POST['id'];

    if (empty($teacher_id)) {
        echo json_encode(['success' => false, 'message' => 'Teacher ID is required.']);
        exit;
    }

    // Start transaction to ensure both deletions succeed or fail together
    $conn->begin_transaction();

    try {
        // 1. Get the user_id associated with the teacher_id
        $sql_select = "SELECT user_id FROM teachers WHERE id = ?";
        $stmt_select = $conn->prepare($sql_select);
        $stmt_select->bind_param('i', $teacher_id);
        $stmt_select->execute();
        $result = $stmt_select->get_result();
        
        if ($user = $result->fetch_assoc()) {
            $user_id = $user['user_id'];
        } else {
            // If the teacher profile record doesn't exist, we still commit the cleanup.
            throw new Exception("Teacher profile record not found.");
        }
        $stmt_select->close();


        // 2. Delete the record from the teachers profile table
        $sql_teacher = "DELETE FROM teachers WHERE id = ?";
        $stmt_teacher = $conn->prepare($sql_teacher);
        $stmt_teacher->bind_param('i', $teacher_id);
        $stmt_teacher->execute();
        $stmt_teacher->close();


        // 3. Delete the corresponding user login from the users table
        $sql_user = "DELETE FROM users WHERE id = ?";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param('i', $user_id);
        $stmt_user->execute();
        $stmt_user->close();


        // Commit the transaction if all deletions succeeded
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Teacher profile and login deleted successfully!']);

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
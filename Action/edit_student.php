<?php
require_once '../db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();
    
    try {
        // --- Get all updated data ---
        $id = $_POST['id'];
        $fullName = $_POST['full_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $gender = $_POST['gender'];
        $dob = $_POST['dob'];
        $startYear = $_POST['starting_year'];
        $nationality = $_POST['nationality'];
        $newCourseId = $_POST['course_id'] ?? null;

        // Fetch current user_id and email
        $stmt_fetch = $conn->prepare("SELECT user_id, email FROM students WHERE id = ?");
        $stmt_fetch->bind_param('i', $id);
        $stmt_fetch->execute();
        $current_data = $stmt_fetch->get_result()->fetch_assoc();
        $stmt_fetch->close();
        
        if (!$current_data) {
            throw new Exception("Student record not found.");
        }
        $user_id = $current_data['user_id'];
        $old_email = $current_data['email'];

        // --- Update USERS table if email changed ---
        if ($email !== $old_email) {
            $stmt_user = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt_user->bind_param('si', $email, $user_id);
            $stmt_user->execute();
            $stmt_user->close();
        }

        // --- Update STUDENTS table ---
        $sql_student = "UPDATE students SET full_name = ?, gender = ?, dob = ?, starting_year = ?, nationality = ?, email = ?, phone = ? WHERE id = ?";
        $stmt_student = $conn->prepare($sql_student);
        $stmt_student->bind_param("sssssssi", $fullName, $gender, $dob, $startYear, $nationality, $email, $phone, $id);
        $stmt_student->execute();
        $stmt_student->close();

        // --- Handle Course Enrollment Update ---
        $conn->query("DELETE FROM enrollments WHERE student_id = $id");
        if (!empty($newCourseId)) {
            $stmt_enroll = $conn->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
            $stmt_enroll->bind_param("ii", $id, $newCourseId);
            $stmt_enroll->execute();
            $stmt_enroll->close();
        }

        // --- Handle Profile Picture (if uploaded) ---
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            // (Your existing file upload logic is fine and can be placed here)
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Student profile updated successfully!']);

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
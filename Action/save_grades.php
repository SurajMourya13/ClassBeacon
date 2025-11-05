<?php
require_once '../db_connect.php';
header('Content-Type: text/html'); // Keeping it simple for the action response

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_id = $_POST['course_id'];
    $subject_name = trim($_POST['subject_name']);
    $grades_data = $_POST['grades'];

    if (empty($course_id) || empty($subject_name) || empty($grades_data)) {
        echo "Error: Course, subject, and grade data are required.";
        exit;
    }

    $conn->begin_transaction();

    try {
        // NOTE: This logic assumes the 'grades' table has columns: student_id, course_id, subject_name, grade.
        $sql = "INSERT INTO grades (student_id, course_id, subject_name, grade) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE grade = VALUES(grade)";
        $stmt = $conn->prepare($sql);

        // Bind types: i (student_id), i (course_id), s (subject_name), s (grade)
        $bind_types = "iiss";

        foreach ($grades_data as $student_id => $grade) {
            // Only save if a grade is actually selected
            if (!empty($grade)) {
                $stmt->bind_param($bind_types, $student_id, $course_id, $subject_name, $grade);
                $stmt->execute();
            }
        }
        
        $conn->commit();
        echo "Grades for **" . htmlspecialchars($subject_name) . "** saved successfully! <br><br> <a href='../admin_grades.php?course_id=" . htmlspecialchars($course_id) . "'>Go Back to Grades</a>";

    } catch (Exception $e) {
        $conn->rollback();
        echo "Error saving grades: " . $e->getMessage();
    } finally {
        if (isset($stmt) && $stmt) $stmt->close();
        if ($conn) $conn->close();
    }
} else {
    echo "Invalid request method.";
}
?>
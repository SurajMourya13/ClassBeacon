<?php
require_once '../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->begin_transaction();
    try {
        // First, clear all existing enrollments
        $conn->query("DELETE FROM enrollments");

        // Now, insert the new enrollments from the form
        if (!empty($_POST['enrollments'])) {
            $sql = "INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);

            foreach ($_POST['enrollments'] as $student_id => $courses) {
                foreach ($courses as $course_id) {
                    $stmt->bind_param("ii", $student_id, $course_id);
                    $stmt->execute();
                }
            }
            $stmt->close();
        }
        
        $conn->commit();
        echo "Enrollments updated successfully! <a href='enroll_students.php'>Go Back</a>";

    } catch (Exception $e) {
        $conn->rollback();
        echo "Error updating enrollments: " . $e->getMessage();
    }
    $conn->close();
}
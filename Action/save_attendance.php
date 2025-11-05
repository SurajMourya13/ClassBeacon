<?php
require_once '../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_id = $_POST['course_id'];
    $attendance_date = $_POST['attendance_date'];
    $attendance_data = $_POST['attendance'];

    $conn->begin_transaction();

    try {
        $sql = "INSERT INTO attendance (student_id, course_id, attendance_date, status) VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE status = VALUES(status)";
        $stmt = $conn->prepare($sql);

        foreach ($attendance_data as $student_id => $status) {
            $stmt->bind_param("iiss", $student_id, $course_id, $attendance_date, $status);
            $stmt->execute();
        }
        
        $conn->commit();
        echo "Attendance saved successfully! <a href='../admin_attendance.php'>Go Back</a>";

    } catch (Exception $e) {
        $conn->rollback();
        echo "Error saving attendance: " . $e->getMessage();
    }

    $stmt->close();
    $conn->close();
}
?>
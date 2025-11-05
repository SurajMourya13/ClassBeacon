<?php
require_once '../db_connect.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $name = trim($_POST['course_name']);
    $code = trim($_POST['code']);
    $teacherId = trim($_POST['teacher_id']);
    $isActive = trim($_POST['is_active']);

    if (empty($id) || empty($name) || empty($code)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Course ID, name, and code are required."]);
        exit;
    }

    $sql = "UPDATE courses SET course_name = ?, code = ?, teacher_id = ?, is_active = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssiii", $name, $code, $teacherId, $isActive, $id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Course updated successfully!"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error updating course: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>
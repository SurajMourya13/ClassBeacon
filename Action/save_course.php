<?php
require_once '../db_connect.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['course_name']);
    $code = trim($_POST['code']);
    $isActive = trim($_POST['is_active']);

    if (empty($name) || empty($code)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Course name and code are required."]);
        exit;
    }

    // UPDATED: The SQL query no longer includes `teacher_id`
    $sql = "INSERT INTO courses (course_name, code, is_active) VALUES (?, ?, ?)";
    $stmt = anull;
    
    // Use a try-catch block for better error handling
    try {
        $stmt = $conn->prepare($sql);
        // UPDATED: The bind_param now only includes the three relevant fields
        $stmt->bind_param("ssi", $name, $code, $isActive);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Course added successfully!"]);
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error adding course: " . $e->getMessage()]);
    } finally {
        if ($stmt) $stmt->close();
        $conn->close();
    }

} else {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>
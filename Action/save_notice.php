<?php
require_once '../db_connect.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if (empty($title) || empty($content)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Title and content are required."]);
        exit;
    }

    $sql = "INSERT INTO notices (title, content) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $title, $content);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Notice added successfully!"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error adding notice: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>
<?php
require_once '../db_connect.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if (empty($id) || empty($title) || empty($content)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Notice ID, title, and content are required."]);
        exit;
    }

    $sql = "UPDATE notices SET title = ?, content = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $title, $content, $id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Notice updated successfully!"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error updating notice: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>
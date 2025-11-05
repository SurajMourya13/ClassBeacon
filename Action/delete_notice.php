<?php
require_once '../db_connect.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];

    if (empty($id)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Notice ID is required."]);
        exit;
    }

    $sql = "DELETE FROM notices WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Notice deleted successfully!"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error deleting notice: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>
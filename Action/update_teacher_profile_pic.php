<?php
require_once '../db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();
    
    try {
        // 🔑 NOTE: The key name must match the hidden input field name in the form
        $teacher_id = $_POST['teacher_id'];
        $current_pic_path = $_POST['current_pic_path'];
        $profilePicPath = null;
        
        if (empty($teacher_id)) {
            throw new Exception("Teacher ID is missing.");
        }

        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            
            // --- File Upload Logic ---
            $fileName = basename($_FILES['profile_picture']['name']);
            $targetDir = "../uploads/profile_pics/"; 
            
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = uniqid('profile_') . '.' . $fileExtension;
            $targetFilePath = $targetDir . $newFileName;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFilePath)) {
                $profilePicPath = "uploads/profile_pics/" . $newFileName; 

                // --- Delete Old Picture if it exists ---
                if (!empty($current_pic_path) && file_exists("../" . $current_pic_path)) {
                     unlink("../" . $current_pic_path);
                }

            } else {
                throw new Exception("File upload failed or permission denied.");
            }
        } else {
            throw new Exception("No new file was uploaded.");
        }

        // 🔑 Update the TEACHERS table
        $sql_teacher = "UPDATE teachers SET profile_picture = ? WHERE id = ?";
        $stmt_teacher = $conn->prepare($sql_teacher);
        $stmt_teacher->bind_param("si", $profilePicPath, $teacher_id);
        $stmt_teacher->execute();
        $stmt_teacher->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Profile picture updated successfully!']);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        // Clean up the newly uploaded file if the DB update fails
        if (isset($targetFilePath) && file_exists($targetFilePath)) {
             unlink($targetFilePath);
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
        exit;
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
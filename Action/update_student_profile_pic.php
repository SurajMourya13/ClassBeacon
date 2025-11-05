<?php
require_once '../db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();
    
    try {
        $student_id = $_POST['student_id'];
        $current_pic_path = $_POST['current_pic_path'];
        $profilePicPath = null;
        
        if (empty($student_id)) {
            throw new Exception("Student ID is missing.");
        }

        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            
            // --- File Upload Logic ---
            $fileName = basename($_FILES['profile_picture']['name']);
            // Target directory is two levels up from Action/ folder, then into uploads/
            $targetDir = "../uploads/profile_pics/"; 
            
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            // Create a unique file name
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = uniqid('profile_') . '.' . $fileExtension;
            $targetFilePath = $targetDir . $newFileName;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFilePath)) {
                // Path to save in DB (relative to the project root)
                $profilePicPath = "uploads/profile_pics/" . $newFileName; 

                // --- Delete Old Picture if it exists ---
                if (!empty($current_pic_path) && file_exists("../" . $current_pic_path)) {
                     // Delete the old file from the server
                     unlink("../" . $current_pic_path);
                }

            } else {
                throw new Exception("File upload failed or permission denied.");
            }
        } else {
            throw new Exception("No new file was uploaded.");
        }

        // --- Update STUDENTS table ---
        $sql_student = "UPDATE students SET profile_picture = ? WHERE id = ?";
        $stmt_student = $conn->prepare($sql_student);
        $stmt_student->bind_param("si", $profilePicPath, $student_id);
        $stmt_student->execute();
        $stmt_student->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Profile picture updated successfully!']);

    } catch (Exception $e) {
        $conn->rollback();
        // Clean up the newly uploaded file if the DB update fails
        if (isset($targetFilePath) && file_exists($targetFilePath)) {
             unlink($targetFilePath);
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
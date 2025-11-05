<?php
require_once '../db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();
    
    try {
        // --- Get all updated data ---
        $id = $_POST['id'];
        $fullName = $_POST['full_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $gender = $_POST['gender'];
        $dob = $_POST['dob'];
        $startYear = $_POST['starting_year'];
        $nationality = $_POST['nationality'];

        // --- 1. Handle Profile Picture Update ---
        $profilePicPath = null;
        $fileUploaded = false;
        
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
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
                $fileUploaded = true;
            } else {
                throw new Exception("File upload failed.");
            }
        }
        
        // --- 2. Fetch current user_id and old email ---
        $sql_fetch = "SELECT user_id, email, profile_picture FROM teachers WHERE id = ?";
        $stmt_fetch = $conn->prepare($sql_fetch);
        $stmt_fetch->bind_param('i', $id);
        $stmt_fetch->execute();
        $current_data = $stmt_fetch->get_result()->fetch_assoc();
        $stmt_fetch->close();
        
        if (!$current_data) {
            throw new Exception("Teacher record not found.");
        }
        $user_id = $current_data['user_id'];
        $old_email = $current_data['email'];


        // --- 3. Update USERS table (Login Email) ---
        if ($email !== $old_email) {
            $sql_user = "UPDATE users SET email = ? WHERE id = ?";
            $stmt_user = $conn->prepare($sql_user);
            $stmt_user->bind_param('si', $email, $user_id);
            $stmt_user->execute();
            $stmt_user->close();
        }

        // --- 4. Update TEACHERS table (All fields) ---
        $update_fields = "full_name = ?, gender = ?, dob = ?, starting_year = ?, nationality = ?, email = ?, phone = ?";
        $bind_types = "sssssssi"; // 7 's' + 'i' for ID
        $bind_params = [
            $fullName, $gender, $dob, $startYear, $nationality, $email, $phone, $id
        ];

        // Add profile picture update if a new file was uploaded
        if ($fileUploaded) {
            $update_fields .= ", profile_picture = ?";
            $bind_types = "s" . $bind_types; // Add one 's' at the beginning for profile_picture
            array_unshift($bind_params, $profilePicPath); // Add path to the beginning of params array
            
            // Optional: Delete old profile picture file if it existed
            if (!empty($current_data['profile_picture']) && file_exists("../" . $current_data['profile_picture'])) {
                 unlink("../" . $current_data['profile_picture']);
            }
        }

        $sql_teacher = "UPDATE teachers SET $update_fields WHERE id = ?";
        $stmt_teacher = $conn->prepare($sql_teacher);

        // Bind the parameters dynamically
        $stmt_teacher->bind_param($bind_types, ...$bind_params);
        $stmt_teacher->execute();
        $stmt_teacher->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Teacher profile updated successfully!']);

    } catch (Exception $e) {
        $conn->rollback();
        // If file upload failed, try to clean up the partially uploaded file
        if (isset($targetFilePath) && file_exists($targetFilePath)) {
             unlink($targetFilePath);
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
<?php
// FIX 1: Corrected path for file inside the Action/ folder
require_once '../db_connect.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->begin_transaction();

    try {
        // --- User Data (for USERS table) ---
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $role = 'student';

        // --- Student Profile Data (for STUDENTS table) ---
        $fullName = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $gender = trim($_POST['gender']);
        $dob = trim($_POST['dob']);
        $startYear = trim($_POST['starting_year']);
        $nationality = trim($_POST['nationality']);
        $courseId = $_POST['course_id'] ?? null;
        
        // --- FILE UPLOAD LOGIC ---
        $profilePicPath = null;
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $fileName = basename($_FILES['profile_picture']['name']);
            $targetDir = "../uploads/profile_pics/"; // Target directory one level up
            
            // Ensure the uploads directory exists
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            // Unique file name logic
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = uniqid('profile_') . '.' . $fileExtension;
            $targetFilePath = $targetDir . $newFileName;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFilePath)) {
                $profilePicPath = "uploads/profile_pics/" . $newFileName; // Path to save in DB
            } else {
                throw new Exception("File upload failed.");
            }
        }
        
        // 1. Create the user login
        $sql_user = "INSERT INTO users (email, password, role) VALUES (?, ?, ?)";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param("sss", $email, $password, $role);
        $stmt_user->execute();
        
        $user_id = $conn->insert_id;

        // 2. Create the student profile (Includes ALL new fields)
        $sql_student = "INSERT INTO students (user_id, full_name, gender, dob, starting_year, nationality, profile_picture, email, phone, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        // NOTE: Order of binding is crucial. sss is wrong for binding user_id (int)
        // Bind parameters: i (user_id), s (full_name), s (gender), s (dob), s (startYear), s (nationality), s (profile_picture), s (email), s (phone)
        $stmt_student = $conn->prepare($sql_student);
        $stmt_student->bind_param("issssssss", 
            $user_id, 
            $fullName, 
            $gender, 
            $dob, 
            $startYear, 
            $nationality, 
            $profilePicPath,
            $email, 
            $phone
        );
        $stmt_student->execute();
        
        // Get the ID of the new student record (for enrollment)
        $student_id = $conn->insert_id;

        // 3. Initial Course Enrollment (If course_id is provided)
        if (!empty($courseId)) {
            $sql_enroll = "INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)";
            $stmt_enroll = $conn->prepare($sql_enroll);
            $stmt_enroll->bind_param("ii", $student_id, $courseId); // Use student_id here
            $stmt_enroll->execute();
            $stmt_enroll->close();
        }

        $conn->commit();
        echo json_encode(["success" => true, "message" => "Student created and enrolled successfully!"]);

    } catch (Exception $e) {
        $conn->rollback();
        // Clean up the user record if the student profile insert failed
        if (isset($user_id)) {
            $conn->query("DELETE FROM users WHERE id = $user_id");
        }
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database Transaction Error. (Error: " . $e->getMessage() . ")"]);

    } finally {
        if (isset($stmt_user) && $stmt_user) $stmt_user->close();
        if (isset($stmt_student) && $stmt_student) $stmt_student->close();
        if ($conn) $conn->close();
    }

} else {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>
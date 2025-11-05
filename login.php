<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if ($password == $user['password']) { // In a real app, use password_verify()
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            switch ($user['role']) {
                case 'admin':
                    header("location: admin_dashboard.php");
                    break;
                case 'teacher':
                    header("location: teacher_dashboard.php");
                    break;
                case 'student':
                    header("location: student_dashboard.php");
                    break;
            }
        } else {
            echo "Invalid password.";
        }
    } else {
        echo "No user found with that email.";
    }
    $stmt->close();
    $conn->close();
}
?>
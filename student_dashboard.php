<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("location: login.html");
    exit;
}

// Fetch student name
$user_id = $_SESSION['user_id'];
$sql_student = "SELECT full_name FROM students WHERE user_id = ?";
$stmt_student = $conn->prepare($sql_student);
$stmt_student->bind_param("i", $user_id);
$stmt_student->execute();
$student_result = $stmt_student->get_result();
$student = $student_result->fetch_assoc();
$stmt_student->close();

// Fetch latest notice
$sql_notice = "SELECT title, content FROM notices ORDER BY created_at DESC LIMIT 1";
$notice_result = $conn->query($sql_notice);
$notice = $notice_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="students.css">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="container">
          <aside class="sidebar">
             <div class="sidebar-logo"><i class="fa-solid fa-graduation-cap"></i><h1>ClassBeacon</h1></div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="student_dashboard.php" class="active"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                    <li><a href="student_profile.php"><i class="fa-solid fa-user"></i> My Profile</a></li>
                    <li><a href="my_attendance.php"><i class="fa-solid fa-calendar-check"></i> My Attendance</a></li>
                    <li><a href="my_grades.php"><i class="fa-solid fa-star"></i> My Grades</a></li> </ul>
            </nav>
        </aside>
        <main class="main-content">
            <header class="header">
                 <div class="user-info">
                    <div class="user-details">
                        <span class="user-role"><i class="fa-solid fa-user-graduate"></i></span>
                        <span class="user-name"><?php echo htmlspecialchars($student['full_name']); ?></span>
                    </div>
                    <a href="logout.php" class="logout-btn">Logout <i class="fa-solid fa-right-from-bracket"></i></a>
                </div>
            </header>
            <section class="content-body">
                <div class="welcome-header">
                    <h2>Welcome back, <?php echo htmlspecialchars($student['full_name']); ?>!</h2>
                     <p>Here's a summary of your dashboard.</p>
                </div>
                
                <div class="cards-container">
                    <?php if ($notice): ?>
                    <div class="card">
                        <div class="card-header">
                            <i class="fa-solid fa-bell"></i>
                            <h3>Latest Notice</h3>
                        </div>
                        <h4><?php echo htmlspecialchars($notice['title']); ?></h4>
                        <p><?php echo htmlspecialchars($notice['content']); ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="card">
                         <div class="card-header">
                            <i class="fa-solid fa-bolt"></i>
                            <h3>Quick Actions</h3>
                        </div>
                         <a href="my_attendance.php" class="quick-link">Check My Attendance</a>
                         <a href="student_profile.php" class="quick-link">View My Profile</a>
                    </div>
                </div>
            </section>
        </main>
    </div>
    <style>
        .welcome-header { margin-bottom: 2rem; }
        .cards-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; }
        .card { background-color: #fff; padding: 1.5rem; border-radius: 12px; box-shadow: var(--shadow); }
        .card-header { display: flex; align-items: center; margin-bottom: 1rem; color: var(--primary-color); }
        .card-header i { font-size: 1.2rem; margin-right: 0.75rem; }
        .card h3 { font-size: 1.1rem; font-weight: 600; }
        .card h4 { font-size: 1rem; font-weight: 600; margin-bottom: 0.5rem; }
        .quick-link { display: block; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; margin-top: 1rem; text-align: center; font-weight: 500; }
        .quick-link:hover { border-color: var(--primary-color); color: var(--primary-color); }
    </style>
</body>
</html>
<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("location: login.html");
    exit;
}
// Fetch teacher name
$user_id = $_SESSION['user_id'];
$sql_teacher = "SELECT full_name FROM teachers WHERE user_id = ?";
$stmt_teacher = $conn->prepare($sql_teacher);
$stmt_teacher->bind_param("i", $user_id);
$stmt_teacher->execute();
$teacher_result = $stmt_teacher->get_result();
$teacher = $teacher_result->fetch_assoc();
$stmt_teacher->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="teacher.css">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="container">
         <aside class="sidebar">
             <div class="sidebar-logo"><i class="fa-solid fa-graduation-cap"></i><h1>ClassBeacon</h1></div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="teacher_dashboard.php" class="active"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                    <li><a href="teacher_profile.php"><i class="fa-solid fa-user"></i> My Profile</a></li>
                    <li><a href="manage_attendance.php"><i class="fa-solid fa-calendar-check"></i> Manage Attendance</a></li>
                    <li><a href="manage_grades.php"><i class="fa-solid fa-star"></i> Manage Grades</a></li>
                </ul>
            </nav>
        </aside>
        <main class="main-content">
             <header class="header">
                 <div class="user-info">
                    <div class="user-details">
                        <span class="user-role"><i class="fa-solid fa-chalkboard-user"></i></span>
                        <span class="user-name"><?php echo htmlspecialchars($teacher['full_name']); ?></span>
                    </div>
                    <a href="logout.php" class="logout-btn">Logout <i class="fa-solid fa-right-from-bracket"></i></a>
                </div>
            </header>
            <section class="content-body">
                <div class="welcome-header">
                    <h2>Welcome back, <?php echo htmlspecialchars($teacher['full_name']); ?>!</h2>
                    <p>Here are your quick actions.</p>
                </div>
                <div class="cards-container">
                     <div class="card">
                         <div class="card-header">
                            <i class="fa-solid fa-bolt"></i>
                            <h3>Quick Actions</h3>
                        </div>
                         <a href="manage_attendance.php" class="quick-link">Manage Attendance</a>
                         <a href="manage_grades.php" class="quick-link">Manage Grades</a>
                    </div>
                     <div class="card">
                         <div class="card-header">
                            <i class="fa-solid fa-info-circle"></i>
                            <h3>Dashboard Info</h3>
                        </div>
                        <p>Use the sidebar to navigate to different sections. You can manage attendance and grades for students enrolled in your courses.</p>
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
        .card p { color: var(--text-light); }
        .quick-link { display: block; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; margin-top: 1rem; text-align: center; font-weight: 500; }
        .quick-link:hover { border-color: var(--primary-color); color: var(--primary-color); }
    </style>
</body>
</html>
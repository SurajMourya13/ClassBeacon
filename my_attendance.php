<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("location: login.html");
    exit;
}

$user_id = $_SESSION['user_id'];
// FIX: Get the current page name for dynamic highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// 1. Fetch student details (for header)
$sql_student = "SELECT id, full_name FROM students WHERE user_id = ?";
$stmt_student = $conn->prepare($sql_student);
$stmt_student->bind_param("i", $user_id);
$stmt_student->execute();
$student_result = $stmt_student->get_result();
$student = $student_result->fetch_assoc();
$student_id = $student['id'];
$stmt_student->close();

// 2. Fetch attendance data for all enrolled courses
$sql_attendance = "
    SELECT 
        c.course_name, 
        a.attendance_date, 
        a.status 
    FROM attendance a
    JOIN enrollments e ON a.student_id = e.student_id AND a.course_id = e.course_id
    JOIN courses c ON e.course_id = c.id
    WHERE e.student_id = ?
    ORDER BY c.course_name, a.attendance_date DESC";
    
$stmt_attendance = $conn->prepare($sql_attendance);
$stmt_attendance->bind_param("i", $student_id);
$stmt_attendance->execute();
$attendance_results = $stmt_attendance->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_attendance->close();

// Group attendance by course for easier display
$course_attendance = [];
foreach ($attendance_results as $record) {
    $course_attendance[$record['course_name']][] = $record;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Attendance</title>
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
                    <li><a href="student_dashboard.php" class="<?php echo ($current_page == 'student_dashboard.php') ? 'active' : ''; ?>"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                    <li><a href="student_profile.php" class="<?php echo ($current_page == 'student_profile.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user"></i> My Profile</a></li>
                    <li><a href="my_attendance.php" class="<?php echo ($current_page == 'my_attendance.php') ? 'active' : ''; ?>"><i class="fa-solid fa-calendar-check"></i> My Attendance</a></li>
                    <li><a href="my_grades.php" class="<?php echo ($current_page == 'my_grades.php') ? 'active' : ''; ?>"><i class="fa-solid fa-star"></i> My Grades</a></li> </ul>
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
                <h2>My Attendance History</h2>
                <?php if (empty($course_attendance)): ?>
                    <p>No attendance records found yet.</p>
                <?php else: ?>
                    <?php foreach ($course_attendance as $course_name => $records): ?>
                        <div class="attendance-section">
                            <h3 class="course-header"><?php echo htmlspecialchars($course_name); ?></h3>
                            <table class="attendance-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records as $record): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($record['attendance_date'])); ?></td>
                                            <td class="status-<?php echo htmlspecialchars($record['status']); ?>">
                                                <?php echo htmlspecialchars($record['status']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
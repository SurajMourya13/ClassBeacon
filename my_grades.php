<?php
session_start();
require_once 'db_connect.php';

// Redirect if not logged in as a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("location: login.html");
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. Fetch student details (for header)
$sql_student = "SELECT id, full_name FROM students WHERE user_id = ?";
$stmt_student = $conn->prepare($sql_student);
$stmt_student->bind_param("i", $user_id);
$stmt_student->execute();
$student_result = $stmt_student->get_result();
$student = $student_result->fetch_assoc();
$student_id = $student['id'];
$stmt_student->close();

// 2. Fetch grade data for all enrolled courses and subjects
// The grades table has student_id, course_id, subject_name, and grade
$sql_grades = "
    SELECT 
        c.course_name, 
        g.subject_name, 
        g.grade 
    FROM grades g
    JOIN courses c ON g.course_id = c.id
    WHERE g.student_id = ?
    ORDER BY c.course_name, g.subject_name";
    
$stmt_grades = $conn->prepare($sql_grades);
$stmt_grades->bind_param("i", $student_id);
$stmt_grades->execute();
$grades_results = $stmt_grades->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_grades->close();

// Group grades by course for easier display
$course_grades = [];
foreach ($grades_results as $record) {
    $course_grades[$record['course_name']][] = $record;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Grades</title>
    <link rel="stylesheet" href="students.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
         /* Reusing attendance styles for a consistent table look */
         .grades-section { margin-bottom: 2.5rem; }
         .course-header { font-size: 1.5rem; font-weight: 600; color: var(--primary-color); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--border-color); }
         .grades-table { width: 100%; border-collapse: collapse; background-color: #fff; border-radius: 8px; overflow: hidden; box-shadow: var(--shadow); }
         .grades-table th, .grades-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border-color); }
         .grades-table thead th { background-color: var(--light-primary-color); color: var(--primary-color); }
         .grades-table tbody tr:hover { background-color: #f6f6f6; }
         .status-Pass { color: var(--success-color); font-weight: 600; }
         .status-Fail { color: var(--danger-color); font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-logo"><i class="fa-solid fa-graduation-cap"></i><h1>ClassBeacon</h1></div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="student_dashboard.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                    <li><a href="student_profile.php"><i class="fa-solid fa-user"></i> My Profile</a></li>
                    <li><a href="my_attendance.php"><i class="fa-solid fa-calendar-check"></i> My Attendance</a></li>
                    <li><a href="my_grades.php" class="active"><i class="fa-solid fa-star"></i> My Grades</a></li>
                </ul>
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
                <h2>My Grades Report</h2>
                <?php if (empty($course_grades)): ?>
                    <p>No grade records found yet. Grades will appear here after they are submitted by your teacher/admin.</p>
                <?php else: ?>
                    <?php foreach ($course_grades as $course_name => $records): ?>
                        <div class="grades-section">
                            <h3 class="course-header"><?php echo htmlspecialchars($course_name); ?></h3>
                            <table class="grades-table">
                                <thead>
                                    <tr>
                                        <th>Subject Name</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records as $record): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($record['subject_name']); ?></td>
                                            <td class="status-<?php echo ($record['grade'] === 'F' ? 'Fail' : 'Pass'); ?>">
                                                <?php echo htmlspecialchars($record['grade']); ?>
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
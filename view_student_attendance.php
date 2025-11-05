<?php
session_start();
require_once 'db_connect.php';

// Redirect if not logged in as a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("location: login.html");
    exit;
}

$user_id = $_SESSION['user_id'];

// --- START: Fetch teacher name for header ---
$sql_teacher = "SELECT full_name FROM teachers WHERE user_id = ?";
$stmt_teacher = $conn->prepare($sql_teacher);
$stmt_teacher->bind_param("i", $user_id);
$stmt_teacher->execute();
$teacher_result = $stmt_teacher->get_result();
$teacher = $teacher_result->fetch_assoc();
$stmt_teacher->close();
// --- END: Fetch teacher name ---

// Get student and course IDs from the URL
$student_id = $_GET['student_id'] ?? null;
$course_id = $_GET['course_id'] ?? null;

if (!$student_id || !$course_id) {
    die("Student or Course ID is missing.");
}

// Fetch student's name
$stmt_student = $conn->prepare("SELECT full_name FROM students WHERE id = ?");
$stmt_student->bind_param("i", $student_id);
$stmt_student->execute();
$student = $stmt_student->get_result()->fetch_assoc();
$stmt_student->close();

// Fetch course name
$stmt_course = $conn->prepare("SELECT course_name FROM courses WHERE id = ?");
$stmt_course->bind_param("i", $course_id);
$stmt_course->execute();
$course = $stmt_course->get_result()->fetch_assoc();
$stmt_course->close();

// Fetch attendance records for this student in this course
$sql_attendance = "SELECT attendance_date, status FROM attendance WHERE student_id = ? AND course_id = ? ORDER BY attendance_date DESC";
$stmt_attendance = $conn->prepare($sql_attendance);
$stmt_attendance->bind_param("ii", $student_id, $course_id);
$stmt_attendance->execute();
$records = $stmt_attendance->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_attendance->close();
$conn->close();

// --- START: Calculate Attendance Percentage ---
$total_classes = count($records);
$total_present = 0;
foreach ($records as $record) {
    if ($record['status'] === 'Present') {
        $total_present++;
    }
}
$percentage = ($total_classes > 0) ? round(($total_present / $total_classes) * 100, 2) : 0;
// --- END: Calculate Attendance Percentage ---

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance for <?php echo htmlspecialchars($student['full_name']); ?></title>
    <link rel="stylesheet" href="teacher.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .summary-card {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            text-align: center;
        }
        .summary-item h4 {
            font-size: 0.9rem;
            color: var(--text-light);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        .summary-item p {
            font-size: 2rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        .attendance-table { width: 100%; border-collapse: collapse; background-color: #fff; border-radius: 8px; overflow: hidden; box-shadow: var(--shadow); }
        .attendance-table th, .attendance-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border-color); }
        .attendance-table thead th { background-color: var(--light-primary-color); color: var(--primary-color); }
        .status-Present { color: var(--success-color); font-weight: 600; }
        .status-Absent { color: var(--danger-color); font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-logo"><i class="fa-solid fa-graduation-cap"></i><h1>ClassBeacon</h1></div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="teacher_dashboard.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                    <li><a href="teacher_profile.php"><i class="fa-solid fa-user"></i> My Profile</a></li>
                    <li><a href="manage_attendance.php" class="active"><i class="fa-solid fa-calendar-check"></i> Manage Attendance</a></li>
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
                <h2>Attendance History</h2>
                <p style="margin-bottom: 1rem;">
                    <strong>Student:</strong> <?php echo htmlspecialchars($student['full_name']); ?><br>
                    <strong>Course:</strong> <?php echo htmlspecialchars($course['course_name']); ?>
                </p>

                <div class="summary-card">
                    <div class="summary-item">
                        <h4>Total Classes</h4>
                        <p><?php echo $total_classes; ?></p>
                    </div>
                    <div class="summary-item">
                        <h4>Classes Attended</h4>
                        <p><?php echo $total_present; ?></p>
                    </div>
                    <div class="summary-item">
                        <h4>Attendance</h4>
                        <p><?php echo $percentage; ?>%</p>
                    </div>
                </div>
                
                <?php if (empty($records)): ?>
                    <p>No attendance records found for this student in this course.</p>
                <?php else: ?>
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
                <?php endif; ?>
                <br>
                <a href="manage_attendance.php?course_id=<?php echo $course_id; ?>" class="add-button" style="text-decoration: none; display: inline-block; width: auto;">Back to Attendance</a>
            </section>
        </main>
    </div>
</body>
</html>
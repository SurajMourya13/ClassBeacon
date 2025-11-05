<?php
session_start();
require_once 'db_connect.php';

// Redirect if not logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("location: login.html");
    exit;
}

// Get all courses
$courses_result = $conn->query("SELECT id, course_name FROM courses ORDER BY course_name");
$courses = $courses_result->fetch_all(MYSQLI_ASSOC);

$students = [];
$attendance_date = date('Y-m-d'); // Default to today
if (isset($_GET['course_id'])) {
    $selected_course_id = $_GET['course_id'];
    if (isset($_GET['attendance_date'])) {
        $attendance_date = $_GET['attendance_date'];
    }

    // Get students enrolled in the selected course
    $sql_students = "SELECT s.id, s.full_name 
                     FROM students s 
                     JOIN enrollments e ON s.id = e.student_id 
                     WHERE e.course_id = ?";
    $stmt_students = $conn->prepare($sql_students);
    $stmt_students->bind_param("i", $selected_course_id);
    $stmt_students->execute();
    $students_result = $stmt_students->get_result();
    $students = $students_result->fetch_all(MYSQLI_ASSOC);
    $stmt_students->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Manage Attendance</title>
    <link rel="stylesheet" href="teacher.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .form-container {
            background-color: #fff; padding: 1.5rem; border-radius: 12px; box-shadow: var(--shadow);
        }
        .form-container table {
            width: 100%; border-collapse: collapse; margin-top: 1.5rem;
        }
        .form-container th, .form-container td {
            padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border-color);
        }
        .form-container thead th {
            background-color: var(--light-primary-color); color: var(--primary-color);
        }
        .form-container select, .form-container input[type="date"] {
            width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 1rem; margin-bottom: 1.5rem;
        }
        .submit-button {
            background-color: var(--primary-color); color: white; border: none;
            padding: 10px 20px; border-radius: 8px; font-size: 1rem; cursor: pointer;
            margin-top: 1rem; transition: background-color 0.2s;
        }
        .submit-button:hover { background-color: #5a5cc2; }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-logo"><i class="fa-solid fa-graduation-cap"></i><h1>ClassBeacon</h1></div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="admin_dashboard.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                    <li><a href="students.php"><i class="fa-solid fa-user-graduate"></i> Students</a></li>
                    <li><a href="teachers.php"><i class="fa-solid fa-chalkboard-user"></i> Teachers</a></li>
                    <li><a href="courses.php"><i class="fa-solid fa-book"></i> Courses</a></li>
                    <li><a href="admin_attendance.php" class="active"><i class="fa-solid fa-calendar-check"></i> Attendance</a></li>
                    <li><a href="admin_grades.php"><i class="fa-solid fa-star"></i> Grades</a></li>
                    <li><a href="notices.php"><i class="fa-solid fa-bell"></i> Notices</a></li>
                </ul>
            </nav>
        </aside>
        <main class="main-content">
            <header class="header">
                 <div class="user-info">
                    <div class="user-details">
                        <span class="user-role">Admin</span>
                        <span class="user-name"><i class="fa-solid fa-user-cog"></i></span>
                    </div>
                    <a href="logout.php" class="logout-btn">Logout <i class="fa-solid fa-right-from-bracket"></i></a>
                </div>
            </header>
            <section class="content-body">
                <h2>Manage Attendance</h2>
                 <div class="form-container">
                    <form method="get" action="">
                        <label for="course_id" style="font-weight: 500;">Select a Course</label>
                        <select name="course_id" id="course_id">
                            <option value="">-- Select Course --</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>" <?php echo (isset($_GET['course_id']) && $_GET['course_id'] == $course['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="attendance_date" style="font-weight: 500;">Select Date</label>
                        <input type="date" name="attendance_date" id="attendance_date" value="<?php echo $attendance_date; ?>">
                        <button type="submit" class="submit-button">Load Students</button>
                    </form>

                    <?php if (!empty($students)): ?>
                    <form id="attendanceForm" action="Action/save_attendance.php" method="post">
                        <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($_GET['course_id']); ?>">
                        <input type="hidden" name="attendance_date" value="<?php echo $attendance_date; ?>">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                    <td>
                                        <input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="Present" checked> Present
                                        <input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="Absent"> Absent
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="submit" class="submit-button">Save Attendance</button>
                    </form>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
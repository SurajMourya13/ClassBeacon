<?php
session_start();
require_once 'db_connect.php';

// Redirect if not logged in as a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("location: login.html");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch teacher name
$sql_teacher = "SELECT full_name FROM teachers WHERE user_id = ?";
$stmt_teacher = $conn->prepare($sql_teacher);
$stmt_teacher->bind_param("i", $user_id);
$stmt_teacher->execute();
$teacher_result = $stmt_teacher->get_result();
$teacher = $teacher_result->fetch_assoc();
$stmt_teacher->close();

// Get courses taught by this teacher
$sql_courses = "SELECT id, course_name FROM courses WHERE teacher_id = (SELECT id FROM teachers WHERE user_id = ?)";
$stmt_courses = $conn->prepare($sql_courses);
$stmt_courses->bind_param("i", $user_id);
$stmt_courses->execute();
$courses = $stmt_courses->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_courses->close();

$students = [];
$attendance_date = date('Y-m-d'); // Default to today
if (isset($_GET['course_id'])) {
    $selected_course_id = $_GET['course_id'];
    if (isset($_GET['attendance_date'])) {
        $attendance_date = $_GET['attendance_date'];
    }

    // Get students for the selected course
    $sql_students = "SELECT s.id, s.full_name FROM students s JOIN enrollments e ON s.id = e.student_id WHERE e.course_id = ?";
    $stmt_students = $conn->prepare($sql_students);
    $stmt_students->bind_param("i", $selected_course_id);
    $stmt_students->execute();
    $students = $stmt_students->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_students->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Attendance</title>
    <link rel="stylesheet" href="teacher.css">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Add the same styles as in admin_attendance.php */
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
        .action-link {
    display: inline-block;
    margin-left: 1rem;
    font-size: 0.9rem;
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
}
.action-link:hover {
    text-decoration: underline;
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
        <td>
            <?php echo htmlspecialchars($student['full_name']); ?>
            <a href="view_student_attendance.php?student_id=<?php echo $student['id']; ?>&course_id=<?php echo $selected_course_id; ?>" class="action-link">
                <i class="fa-solid fa-eye"></i> View History
            </a>
        </td>
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
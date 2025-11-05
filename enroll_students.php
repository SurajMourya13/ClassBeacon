<?php
require_once 'db_connect.php';

// Fetch all courses
$courses_result = $conn->query("SELECT id, course_name FROM courses WHERE is_active = 1 ORDER BY course_name");
$courses = $courses_result->fetch_all(MYSQLI_ASSOC);

// Fetch all students
$students_result = $conn->query("SELECT id, full_name FROM students ORDER BY full_name");
$students = $students_result->fetch_all(MYSQLI_ASSOC);

// Fetch existing enrollments to pre-check boxes
$enrollments_result = $conn->query("SELECT student_id, course_id FROM enrollments");
$enrollments = [];
while ($row = $enrollments_result->fetch_assoc()) {
    $enrollments[$row['student_id']][] = $row['course_id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enroll Students</title>
    <link rel="stylesheet" href="teacher.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .enrollment-container { background-color: #fff; padding: 1.5rem; border-radius: 12px; box-shadow: var(--shadow); }
        .enrollment-table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
        .enrollment-table th, .enrollment-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border-color); }
        .enrollment-table thead th { background-color: var(--light-primary-color); color: var(--primary-color); }
        .submit-button { background-color: var(--primary-color); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-size: 1rem; cursor: pointer; margin-top: 1.5rem; }
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
                    <li><a href="enroll_students.php" class="active"><i class="fa-solid fa-user-plus"></i> Enroll Students</a></li>
                    <li><a href="admin_attendance.php"><i class="fa-solid fa-calendar-check"></i> Attendance</a></li>
                    <li><a href="notices.php"><i class="fa-solid fa-bell"></i> Notices</a></li>
                </ul>
            </nav>
        </aside>
        <main class="main-content">
            <header class="header">
                 <div class="user-info">
                    <span class="user-role">Admin</span>
                    <a href="logout.php" class="logout-btn">Logout <i class="fa-solid fa-right-from-bracket"></i></a>
                </div>
            </header>
            <section class="content-body">
                <h2>Enroll Students in Courses</h2>
                <div class="enrollment-container">
                    <form action="save_enrollment.php" method="post">
                        <table class="enrollment-table">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <?php foreach ($courses as $course): ?>
                                        <th><?php echo htmlspecialchars($course['course_name']); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                        <?php foreach ($courses as $course): ?>
                                            <td>
                                                <input type="checkbox" name="enrollments[<?php echo $student['id']; ?>][]" value="<?php echo $course['id']; ?>"
                                                <?php 
                                                if (isset($enrollments[$student['id']]) && in_array($course['id'], $enrollments[$student['id']])) {
                                                    echo 'checked';
                                                }
                                                ?>>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="submit" class="submit-button">Save Enrollments</button>
                    </form>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
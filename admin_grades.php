<?php
session_start();
require_once 'db_connect.php';
require_once 'subjects.php'; // Include subject helper functions

// Redirect if not logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("location: login.html");
    exit;
}

$grade_options = ['O', 'A+', 'A', 'B+', 'B', 'C+', 'C', 'D', 'E', 'F'];

// --- UTILITY FUNCTIONS (Defined in subjects.php, but repeated here for context/safety if subjects.php fails) ---
function get_student_year($starting_year) {
    if (empty($starting_year)) return null;
    $current_year = (int)date('Y');
    $start_year = (int)$starting_year;
    $year_diff = $current_year - $start_year;

    if ($year_diff == 0) return 'FY';
    if ($year_diff == 1) return 'SY';
    if ($year_diff >= 2) return 'TY';
    return null;
}

// --- DATA FETCHING ---
$selected_course_id = $_GET['course_id'] ?? null;
$selected_subject = $_GET['subject'] ?? null;
$selected_year = $_GET['year'] ?? null; 

$courses = $conn->query("SELECT id, course_name FROM courses ORDER BY course_name")->fetch_all(MYSQLI_ASSOC);

$students = [];
$current_subjects = [];
$degree = null;
$current_year = (int)date('Y');

if ($selected_course_id) {
    // 1. Get Course Name to determine Degree
    $stmt_course_name = $conn->prepare("SELECT course_name FROM courses WHERE id = ?");
    $stmt_course_name->bind_param("i", $selected_course_id);
    $stmt_course_name->execute();
    $course_data = $stmt_course_name->get_result()->fetch_assoc();
    $course_name = $course_data['course_name'] ?? '';
    $stmt_course_name->close();

    $degree = extract_course_info($course_name)['degree']; // Use the external function

    if ($degree) {
        // 2. Fetch all subjects grouped by year from DB
        $grouped_subjects = fetch_all_subjects_by_degree($conn, $degree);
        
        // Populate current_subjects based on the selected year for the subject dropdown
        if ($selected_year && isset($grouped_subjects[$selected_year])) {
            $current_subjects = $grouped_subjects[$selected_year];
        }
    }

    if ($selected_subject) {
        // 3. Fetch students and their current grade for the selected subject
        $bind_types = "i";
        $bind_params = [$selected_course_id];
        
        $sql_students = "
            SELECT 
                s.id, 
                s.full_name, 
                s.starting_year, 
                g.grade 
            FROM students s 
            JOIN enrollments e ON s.id = e.student_id 
            LEFT JOIN grades g ON s.id = g.student_id AND e.course_id = g.course_id AND g.subject_name = ?
            WHERE e.course_id = ?";
        
        // Add subject to bind parameters
        array_unshift($bind_params, $selected_subject);
        $bind_types = "si";

        // Filter by calculated student year if selected
        if ($selected_year) {
            $target_year = $current_year;
            switch ($selected_year) {
                case 'SY':
                    $target_year = $current_year - 1;
                    break;
                case 'TY':
                    $target_year = $current_year - 2;
                    break;
            }
            $sql_students .= " AND s.starting_year = ?";
            $bind_params[] = (string)$target_year;
            $bind_types .= "s";
        }
        
        $stmt_students = $conn->prepare($sql_students);
        $stmt_students->bind_param($bind_types, ...$bind_params);
        $stmt_students->execute();
        $students = $stmt_students->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_students->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Manage Grades</title>
    <link rel="stylesheet" href="teacher.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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
        .form-container select, .form-container input[type="text"] {
            width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 1rem; margin-bottom: 1.5rem;
        }
        .submit-button {
            background-color: var(--primary-color); color: white; border: none;
            padding: 10px 20px; border-radius: 8px; font-size: 1rem; cursor: pointer;
            margin-top: 1rem; transition: background-color 0.2s;
        }
        .submit-button:hover { background-color: #5a5cc2; }
        .filter-group { display: flex; gap: 1.5rem; align-items: flex-end; margin-bottom: 1.5rem; }
        .filter-group > div { flex: 1; }
        .filter-group label { font-weight: 500; display: block; margin-bottom: 0.5rem; }
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
                    <li><a href="admin_attendance.php"><i class="fa-solid fa-calendar-check"></i> Attendance</a></li>
                    <li><a href="admin_grades.php" class="active"><i class="fa-solid fa-star"></i> Grades</a></li>
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
                <h2>Manage Grades</h2>
                 <div class="form-container">
                    <form method="get" action="">
                        <div class="filter-group">
                            <div>
                                <label for="course_id">Select a Course</label>
                                <select name="course_id" id="course_id" onchange="this.form.submit()">
                                    <option value="">-- Select Course --</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>" <?php echo ($selected_course_id == $course['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['course_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if ($degree): ?>
                            <div>
                                <label for="year">Select Year</label>
                                <select name="year" id="year" onchange="this.form.submit()">
                                    <option value="">-- All Years --</option>
                                    <?php foreach (['FY', 'SY', 'TY'] as $year_key): ?>
                                        <option value="<?php echo $year_key; ?>" <?php echo ($selected_year == $year_key) ? 'selected' : ''; ?>>
                                            <?php echo $year_key . ' - ' . $degree; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            <?php if ($selected_year): ?>
                            <div>
                                <label for="subject">Select Subject</label>
                                <select name="subject" id="subject" onchange="this.form.submit()">
                                    <option value="">-- Select Subject to Grade --</option>
                                    <?php foreach ($current_subjects as $subject): ?>
                                        <option value="<?php echo htmlspecialchars($subject); ?>" <?php echo ($selected_subject == $subject) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($subject); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>
                    </form>

                    <?php if ($selected_subject && !empty($students)): ?>
                    <h3 style="margin-top: 1rem; margin-bottom: 1rem; font-weight: 600;">Grading for: <?php echo htmlspecialchars($selected_subject); ?></h3>
                    <form method="post" action="Action/save_grades.php">
                        <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($selected_course_id); ?>">
                        <input type="hidden" name="subject_name" value="<?php echo htmlspecialchars($selected_subject); ?>">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student Name (Year: <?php echo htmlspecialchars($selected_year); ?>)</th>
                                    <th>Current Grade</th>
                                    <th>New Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): 
                                    $student_year = get_student_year($student['starting_year']);
                                    // Skip students not matching the year filter (though the SQL handles this, this is a safety check)
                                    if ($selected_year && $student_year !== $selected_year) continue; 
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['grade'] ?? 'N/A'); ?></td>
                                    <td>
                                        <select name="grades[<?php echo $student['id']; ?>]">
                                            <option value="">-- Select Grade --</option>
                                            <?php foreach ($grade_options as $option): ?>
                                                <option value="<?php echo htmlspecialchars($option); ?>" <?php echo (!empty($student['grade']) && $student['grade'] === $option) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($option); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="submit" class="submit-button">Save Grades for Subject</button>
                    </form>
                    <?php elseif ($selected_course_id && $selected_year && !$selected_subject): ?>
                        <p style="margin-top: 1.5rem;">Please select a subject to load students and add grades.</p>
                    <?php elseif ($selected_course_id && $degree && !$selected_year): ?>
                        <p style="margin-top: 1.5rem;">Please select an academic year (FY, SY, TY) to filter subjects and students.</p>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
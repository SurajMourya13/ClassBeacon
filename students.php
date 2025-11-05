<?php
// Include the database connection file
require_once 'db_connect.php';

// --- PHP LOGIC FOR FETCHING STUDENTS AND THEIR ENROLLMENT ---
$course_id_filter = $_GET['course_id'] ?? null;
$year_filter = $_GET['year_filter'] ?? null;
$current_year = (int)date('Y');

// Base query
$sql = "
    SELECT 
        s.*, 
        u.email, 
        e.course_id AS enrolled_course_id 
    FROM students s 
    JOIN users u ON s.user_id = u.id
    LEFT JOIN enrollments e ON s.id = e.student_id
";

$where_clauses = [];
$bind_types = "";
$bind_params = [];

// Apply filters if they exist
if ($course_id_filter) {
    $where_clauses[] = "e.course_id = ?";
    $bind_types .= "i";
    $bind_params[] = $course_id_filter;
}

if ($year_filter) {
    $year_to_check = $current_year;
    if ($year_filter === 'SY') {
        $year_to_check = $current_year - 1;
    } elseif ($year_filter === 'TY') {
        $year_to_check = $current_year - 2;
    }
    
    if ($year_filter === 'TY') {
        $where_clauses[] = "s.starting_year <= ?";
    } else {
        $where_clauses[] = "s.starting_year = ?";
    }
    $bind_types .= "s";
    $bind_params[] = (string)$year_to_check;
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

// Prepare and execute statement
if (empty($bind_params)) {
    $result = $conn->query($sql);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($bind_types, ...$bind_params);
    $stmt->execute();
    $result = $stmt->get_result();
}

$students = $result->fetch_all(MYSQLI_ASSOC);

// Fetch all courses for the dropdown menus
$courses_list = $conn->query("SELECT id, course_name FROM courses WHERE is_active = 1 ORDER BY course_name")->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassBeacon - Students</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="students.css">
</head>
<body>
    <div class="container">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-logo"><i class="fa-solid fa-graduation-cap"></i><h1>ClassBeacon</h1></div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="admin_dashboard.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                    <li><a href="students.php" class="active"><i class="fa-solid fa-user-graduate"></i> Students</a></li>
                    <li><a href="teachers.php"><i class="fa-solid fa-chalkboard-user"></i> Teachers</a></li>
                    <li><a href="courses.php"><i class="fa-solid fa-book"></i> Courses</a></li>
                    <li><a href="admin_attendance.php"><i class="fa-solid fa-calendar-check"></i> Attendance</a></li>
                    <li><a href="admin_grades.php"><i class="fa-solid fa-star"></i> Grades</a></li>
                    <li><a href="notices.php"><i class="fa-solid fa-bell"></i> Notices</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="menu-toggle" id="menu-toggle"><i class="fa-solid fa-bars"></i></div>
                <div class="user-info">
                    <div class="user-details"><span class="user-role">Admin</span><span class="user-name">System Administrator</span></div>
                    <button id="logoutBtn" class="logout-btn">Logout <i class="fa-solid fa-right-from-bracket"></i></button>
                </div>
            </header>

            <section class="content-body">
                <div class="section-header">
                    <h2>Students</h2>
                    <button class="add-button"><i class="fa-solid fa-plus"></i> Add Student</button>
                </div>
                
                <div class="card-list-container">
                    <?php if (empty($students)): ?>
                        <p>No students found.</p>
                    <?php else: ?>
                        <?php foreach ($students as $student): ?>
                            <div class="info-card">
                                <div class="info-header"><span class="info-id">ID: <?php echo htmlspecialchars($student['id']); ?></span></div>
                                <div class="info-content">
    <p class="info-name"><?php echo htmlspecialchars($student['full_name']); ?></p>
    <p class="info-detail"><i class="fas fa-envelope info-icon"></i> <?php echo htmlspecialchars($student['email']); ?></p>
    <p class="info-detail"><i class="fas fa-phone info-icon"></i> <?php echo htmlspecialchars($student['phone']); ?></p>
    <p class="info-detail"><i class="fas fa-calendar-alt info-icon"></i> Joined: <?php echo htmlspecialchars($student['starting_year']); ?></p>
</div>
                                <div class="info-actions">
                                    <span class="status-badge active">Active</span>
                                    <button class="edit-button" 
                                        data-id="<?php echo htmlspecialchars($student['id']); ?>"
                                        data-name="<?php echo htmlspecialchars($student['full_name']); ?>"
                                        data-email="<?php echo htmlspecialchars($student['email']); ?>"
                                        data-phone="<?php echo htmlspecialchars($student['phone']); ?>"
                                        data-gender="<?php echo htmlspecialchars($student['gender']); ?>"
                                        data-dob="<?php echo htmlspecialchars($student['dob']); ?>"
                                        data-year="<?php echo htmlspecialchars($student['starting_year']); ?>"
                                        data-nationality="<?php echo htmlspecialchars($student['nationality']); ?>"
                                        data-course_id="<?php echo htmlspecialchars($student['enrolled_course_id'] ?? ''); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="delete-button" data-id="<?php echo htmlspecialchars($student['id']); ?>"><i class="fas fa-trash-alt"></i></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <div id="addStudentModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h3>Add New Student</h3>
            <form id="addStudentForm" class="add-form" enctype="multipart/form-data">
                <div class="form-group"><label>Full Name</label><input type="text" name="full_name" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
                <div class="form-group"><label>Phone Number</label><input type="tel" name="phone" required></div>
                <div class="form-group"><label>Gender</label><select name="gender" required><option value="">-- Select --</option><option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option></select></div>
                <div class="form-group"><label>Date of Birth</label><input type="date" name="dob" required></div>
                <div class="form-group"><label>Starting Year</label><input type="number" name="starting_year" placeholder="e.g., <?php echo date('Y'); ?>" required></div>
                <div class="form-group"><label>Nationality</label><input type="text" name="nationality" required></div>
                <div class="form-group"><label>Initial Course Enrollment</label><select name="course_id"><option value="">-- Select Course --</option><?php foreach ($courses_list as $course): ?><option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['course_name']); ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Profile Picture</label><input type="file" name="profile_picture" accept="image/*"></div>
                <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
                <button type="submit" class="submit-button">Save Student</button>
            </form>
        </div>
    </div>

    <div id="editStudentModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h3>Edit Student</h3>
            <form id="editStudentForm" class="add-form" enctype="multipart/form-data">
                <input type="hidden" id="editId" name="id">
                <div class="form-group"><label>Full Name</label><input type="text" id="editFullName" name="full_name" required></div>
                <div class="form-group"><label>Email</label><input type="email" id="editEmail" name="email" required></div>
                <div class="form-group"><label>Phone Number</label><input type="tel" id="editPhone" name="phone" required></div>
                <div class="form-group"><label>Gender</label><select id="editGender" name="gender" required><option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option></select></div>
                <div class="form-group"><label>Date of Birth</label><input type="date" id="editDob" name="dob" required></div>
                <div class="form-group"><label>Starting Year</label><input type="number" id="editStartYear" name="starting_year" required></div>
                <div class="form-group"><label>Nationality</label><input type="text" id="editNationality" name="nationality" required></div>
                <div class="form-group"><label>Change Course Enrollment</label><select id="editCourseId" name="course_id"><option value="">-- Remove Enrollment --</option><?php foreach ($courses_list as $course): ?><option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['course_name']); ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Change Profile Picture</label><input type="file" name="profile_picture" accept="image/*"></div>
                <button type="submit" class="submit-button">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const addModal = document.getElementById('addStudentModal');
        const editModal = document.getElementById('editStudentModal');

        // Open modals
        document.querySelector('.add-button').addEventListener('click', () => addModal.style.display = 'block');
        document.querySelectorAll('.edit-button').forEach(button => {
            button.addEventListener('click', (e) => {
                const data = e.currentTarget.dataset;
                editModal.querySelector('#editId').value = data.id;
                editModal.querySelector('#editFullName').value = data.name;
                editModal.querySelector('#editEmail').value = data.email;
                editModal.querySelector('#editPhone').value = data.phone;
                editModal.querySelector('#editGender').value = data.gender;
                editModal.querySelector('#editDob').value = data.dob;
                editModal.querySelector('#editStartYear').value = data.year;
                editModal.querySelector('#editNationality').value = data.nationality;
                editModal.querySelector('#editCourseId').value = data.course_id;
                editModal.style.display = 'block';
            });
        });

        // Close modals
        document.querySelectorAll('.modal .close-button').forEach(button => {
            button.addEventListener('click', (e) => e.target.closest('.modal').style.display = 'none');
        });
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) e.target.style.display = 'none';
        });

        // Form submissions
        document.getElementById('addStudentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('Action/save_student.php', { method: 'POST', body: new FormData(this) })
            .then(res => res.json()).then(data => {
                alert(data.message); if (data.success) location.reload();
            });
        });

        document.getElementById('editStudentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('Action/edit_student.php', { method: 'POST', body: new FormData(this) })
            .then(res => res.json()).then(data => {
                alert(data.message); if (data.success) location.reload();
            });
        });
        
        // Delete button
        document.querySelectorAll('.delete-button').forEach(button => {
            button.addEventListener('click', (e) => {
                if (confirm('Are you sure you want to delete this student?')) {
                    const id = e.currentTarget.dataset.id;
                    fetch('Action/delete_student.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `id=${id}`
                    }).then(res => res.json()).then(data => {
                        alert(data.message); if(data.success) location.reload();
                    });
                }
            });
        });
        
        // Logout button
        document.getElementById('logoutBtn').addEventListener('click', () => {
            alert('You have been logged out.'); window.location.href = 'logout.php';
        });
    });
    </script>
</body>
</html>
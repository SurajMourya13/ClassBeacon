<?php
// Include the database connection file ONCE.
require_once 'db_connect.php';

// --- HELPER FUNCTIONS ---

// Helper function to extract ONLY the degree from a course name
function extract_degree($course_name) {
    // Sanitize the course name by removing dots before checking
    $sanitized_name = str_replace('.', '', $course_name);

    if (preg_match('/(BBA-CA|BCOM|BCS|BBA)/i', $sanitized_name, $matches)) {
        return strtoupper($matches[1]);
    }
    return null;
}

// Function to fetch subjects from the 'course_subjects' table
function fetch_subjects_from_db($conn, $degree, $year) {
    if (!$degree || !$year) {
        return [];
    }
    $sql = "SELECT subject_name FROM course_subjects WHERE degree = ? AND year = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $degree, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row['subject_name'];
    }
    $stmt->close();
    return $subjects;
}

// --- DATA FETCHING ---

// Fetch all courses
$result_courses = $conn->query("SELECT c.*, t.full_name AS teacher_name FROM courses c LEFT JOIN teachers t ON c.teacher_id = t.id");
$courses = $result_courses->fetch_all(MYSQLI_ASSOC);

// Fetch all teachers
$teachers_result = $conn->query("SELECT id, full_name FROM teachers ORDER BY full_name");
$teachers_list = $teachers_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassBeacon - Courses</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="courses.css">
</head>
<body>
    <div class="container">
        <aside class="sidebar" id="sidebar">
             <div class="sidebar-logo">
                <i class="fa-solid fa-graduation-cap"></i>
                <h1>ClassBeacon</h1>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="admin_dashboard.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                    <li><a href="students.php"><i class="fa-solid fa-user-graduate"></i> Students</a></li>
                    <li><a href="teachers.php"><i class="fa-solid fa-chalkboard-user"></i> Teachers</a></li>
                    <li><a href="courses.php" class="active"><i class="fa-solid fa-book"></i> Courses</a></li>
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
                    <div class="user-details"><span class="user-role">Admin</span><span class="user-name"><i class="fa-solid fa-user-cog"></i></span></div>
                    <button id="logoutBtn" class="logout-btn">Logout <i class="fa-solid fa-right-from-bracket"></i></button>
                </div>
            </header>

            <section class="content-body">
                <div class="section-header">
                    <h2>Courses</h2>
                    <button class="add-button" id="openAddModalBtn"><i class="fa-solid fa-plus"></i> Add Course</button>
                </div>
                
                <div class="card-list-container">
                    <?php if (empty($courses)): ?>
                        <p>No courses found.</p>
                    <?php else: ?>
                        <?php foreach ($courses as $course): 
                            $degree_key = extract_degree($course['course_name']);
                            $years = ['FY', 'SY', 'TY'];
                            $all_subjects = [];
                            foreach ($years as $year) {
                                $all_subjects[$year] = fetch_subjects_from_db($conn, $degree_key, $year);
                            }
                        ?>
                            <div class="info-card">
                                <div class="info-header">
                                    <span class="info-id">Code: <?php echo htmlspecialchars($course['code']); ?></span>
                                </div>
                                <div class="info-content">
                                    <p class="info-name"><?php echo htmlspecialchars($course['course_name']); ?></p>
                                    <p class="info-detail"><i class="fas fa-chalkboard-user info-icon"></i> Teacher: <?php echo htmlspecialchars($course['teacher_name'] ?? 'N/A'); ?></p>
                                    
                                    <div class="subjects-buttons-container">
                                        <?php foreach ($years as $year_key): ?>
                                            <?php if (!empty($all_subjects[$year_key])): ?>
                                                <button class="view-subjects-btn" 
                                                        data-course="<?php echo htmlspecialchars($course['course_name']); ?>"
                                                        data-year="<?php echo htmlspecialchars($year_key); ?>"
                                                        data-subjects='<?php echo json_encode($all_subjects[$year_key]); ?>'>
                                                    <i class="fas fa-list"></i> View <?php echo count($all_subjects[$year_key]); ?> Subjects (<?php echo $year_key; ?>)
                                                </button>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="info-actions">
                                    <span class="status-badge <?php echo ($course['is_active'] ? 'active' : 'inactive'); ?>">
                                        <?php echo ($course['is_active'] ? 'Active' : 'Inactive'); ?>
                                    </span>
                                    <div class="action-buttons-group">
                                         <a href="students.php?course_id=<?php echo htmlspecialchars($course['id']); ?>" class="action-button-text">
                                            <i class="fas fa-users"></i> View Students
                                        </a>
                                        <button class="edit-button" 
                                                data-id="<?php echo htmlspecialchars($course['id']); ?>" 
                                                data-name="<?php echo htmlspecialchars($course['course_name']); ?>" 
                                                data-code="<?php echo htmlspecialchars($course['code']); ?>"
                                                data-teacher_id="<?php echo htmlspecialchars($course['teacher_id']); ?>"
                                                data-is_active="<?php echo htmlspecialchars($course['is_active']); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="delete-button" data-id="<?php echo htmlspecialchars($course['id']); ?>">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <div id="addCourseModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h3>Add New Course</h3>
            <form id="addCourseForm" class="add-form">
                <div class="form-group"><label for="courseName">Course Name</label><input type="text" id="courseName" name="course_name" placeholder="e.g. BBA-CA" required></div>
                <div class="form-group"><label for="courseCode">Course Code</label><input type="text" id="courseCode" name="code" required></div>
                <div class="form-group"><label for="is_active_add">Status</label><select id="is_active_add" name="is_active"><option value="1">Active</option><option value="0">Inactive</option></select></div>
                <button type="submit" class="submit-button">Save Course</button>
            </form>
        </div>
    </div>

    <div id="editCourseModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h3>Edit Course</h3>
            <form id="editCourseForm" class="add-form">
                <input type="hidden" id="editId" name="id">
                <div class="form-group"><label for="editCourseName">Course Name</label><input type="text" id="editCourseName" name="course_name" required></div>
                <div class="form-group"><label for="editCourseCode">Course Code</label><input type="text" id="editCourseCode" name="code" required></div>
                <div class="form-group"><label for="editTeacherId">Assigned Teacher</label><select id="editTeacherId" name="teacher_id"><option value="">-- Select a Teacher --</option><?php foreach ($teachers_list as $teacher): ?><option value="<?php echo $teacher['id']; ?>"><?php echo htmlspecialchars($teacher['full_name']); ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label for="is_active_edit">Status</label><select id="is_active_edit" name="is_active"><option value="1">Active</option><option value="0">Inactive</option></select></div>
                <button type="submit" class="submit-button">Save Changes</button>
            </form>
        </div>
    </div>

    <div id="subjectsModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h3 id="subjectsModalHeader">Subjects</h3>
            <ul id="subjectsModalList" style="list-style-type: disc; margin-left: 20px; padding-top: 1rem;"></ul>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- Modal and Button Elements ---
        const addModal = document.getElementById('addCourseModal');
        const editModal = document.getElementById('editCourseModal');
        const subjectsModal = document.getElementById('subjectsModal');
        const openAddModalBtn = document.getElementById('openAddModalBtn');

        // --- Generic Modal Functions ---
        const openModal = (modal) => {
            if (modal) modal.style.display = 'block';
        };
        const closeModal = (modal) => {
            if (modal) modal.style.display = 'none';
        };

        // --- Open Modal Event Listeners ---
        if (openAddModalBtn) {
            openAddModalBtn.addEventListener('click', () => openModal(addModal));
        }

        document.querySelectorAll('.edit-button').forEach(button => {
            button.addEventListener('click', (e) => {
                const { id, name, code, teacher_id, is_active } = e.currentTarget.dataset;
                if (editModal) {
                    editModal.querySelector('#editId').value = id;
                    editModal.querySelector('#editCourseName').value = name;
                    editModal.querySelector('#editCourseCode').value = code;
                    editModal.querySelector('#editTeacherId').value = teacher_id || '';
                    editModal.querySelector('#is_active_edit').value = is_active;
                    openModal(editModal);
                }
            });
        });
        
        document.querySelectorAll('.view-subjects-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const { course, year, subjects } = e.currentTarget.dataset;
                const subjectArray = JSON.parse(subjects);
                if (subjectsModal) {
                    subjectsModal.querySelector('#subjectsModalHeader').textContent = `Subjects for ${course} (${year})`;
                    const list = subjectsModal.querySelector('#subjectsModalList');
                    list.innerHTML = '';
                    subjectArray.forEach(subject => {
                        const li = document.createElement('li');
                        li.textContent = subject;
                        list.appendChild(li);
                    });
                    openModal(subjectsModal);
                }
            });
        });

        // --- Close Modal Event Listeners ---
        document.querySelectorAll('.modal .close-button').forEach(button => {
            button.addEventListener('click', (e) => {
                const modal = e.target.closest('.modal');
                closeModal(modal);
            });
        });
        
        window.addEventListener('click', (event) => {
            if (event.target.classList.contains('modal')) {
                closeModal(event.target);
            }
        });

        // --- Form Submission & Other Actions ---
        const addForm = document.getElementById('addCourseForm');
        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                e.preventDefault();
                fetch('Action/save_course.php', { method: 'POST', body: new FormData(this) })
                    .then(res => res.json())
                    .then(data => {
                        alert(data.message);
                        if(data.success) location.reload();
                    });
            });
        }
        
        const editForm = document.getElementById('editCourseForm');
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();
                fetch('Action/edit_course.php', { method: 'POST', body: new FormData(this) })
                    .then(res => res.json())
                    .then(data => {
                        alert(data.message);
                        if(data.success) location.reload();
                    });
            });
        }
        
        document.querySelectorAll('.delete-button').forEach(button => {
            button.addEventListener('click', (e) => {
                if (confirm('Are you sure you want to delete this course?')) {
                    const id = e.currentTarget.dataset.id;
                    fetch('Action/delete_course.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `id=${id}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        alert(data.message);
                        if(data.success) location.reload();
                    });
                }
            });
        });

        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function() {
                alert('You have been logged out.');
                window.location.href = 'logout.php';
            });
        }
    });
</script>
</body>
</html>
<?php
// Close the single database connection at the very end.
$conn->close();
?>
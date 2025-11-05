<?php
// Include the database connection file (Correct path for the root directory)
require_once 'db_connect.php';

// Fetch all teachers and their corresponding email from the users table
$sql = "SELECT t.*, u.email 
        FROM teachers t 
        JOIN users u ON t.user_id = u.id";
$result = $conn->query($sql);

$teachers = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }
}

// Fetch all courses for the dropdown menu
$courses_result = $conn->query("SELECT id, course_name FROM courses WHERE is_active = 1 ORDER BY course_name");
$courses_list = [];
if ($courses_result && $courses_result->num_rows > 0) {
    while($row = $courses_result->fetch_assoc()) {
        $courses_list[] = $row;
    }
}

// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassBeacon - Teachers</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="teacher.css">
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
                    <li><a href="teachers.php" class="active"><i class="fa-solid fa-chalkboard-user"></i> Teachers</a></li>
                    <li><a href="courses.php"><i class="fa-solid fa-book"></i> Courses</a></li>
                     <li><a href="admin_attendance.php"><i class="fa-solid fa-calendar-check"></i> Attendance</a></li>
                    <li><a href="admin_grades.php"><i class="fa-solid fa-star"></i> Grades</a></li>
                    <li><a href="notices.php"><i class="fa-solid fa-bell"></i> Notices</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="menu-toggle" id="menu-toggle">
                    <i class="fa-solid fa-bars"></i>
                </div>
                <div class="user-info">
                    <div class="user-details">
                        <span class="user-role">Admin</span>
                        <span class="user-name"><i class="fa-solid fa-user-cog"></i></span>
                    </div>
                    <button id="logoutBtn" class="logout-btn">
    Logout
    <i class="fa-solid fa-right-from-bracket"></i>
</button>
                </div>
            </header>

            <section class="content-body">
                <div class="section-header">
                    <h2>Teachers</h2>
                    <button class="add-button"><i class="fa-solid fa-plus"></i> Add Teacher</button>
                </div>
                
                <div class="card-list-container">
                    <?php if (empty($teachers)): ?>
                        <p>No teachers found.</p>
                    <?php else: ?>
                        <?php foreach ($teachers as $teacher): ?>
                            <div class="info-card">
                                <div class="info-header">
                                    <span class="info-id">ID: <?php echo htmlspecialchars($teacher['id']); ?></span>
                                </div>
                                <div class="info-content">
                                    <p class="info-name"><?php echo htmlspecialchars($teacher['full_name']); ?></p>
                                    <p class="info-detail"><i class="fas fa-envelope info-icon"></i> <?php echo htmlspecialchars($teacher['email']); ?></p>
                                    <p class="info-detail"><i class="fas fa-phone info-icon"></i> <?php echo htmlspecialchars($teacher['phone']); ?></p>
                                    <p class="info-detail"><i class="fas fa-calendar-alt info-icon"></i> Joined: <?php echo date('d/m/Y', strtotime($teacher['created_at'])); ?></p>
                                </div>
                                <div class="info-actions">
                                    <span class="status-badge active">Active</span>
                                    <button class="edit-button" 
                                            data-id="<?php echo htmlspecialchars($teacher['id']); ?>" 
                                            data-name="<?php echo htmlspecialchars($teacher['full_name']); ?>" 
                                            data-email="<?php echo htmlspecialchars($teacher['email']); ?>" 
                                            data-phone="<?php echo htmlspecialchars($teacher['phone']); ?>"
                                            data-gender="<?php echo htmlspecialchars($teacher['gender']); ?>"
                                            data-dob="<?php echo htmlspecialchars($teacher['dob']); ?>"
                                            data-year="<?php echo htmlspecialchars($teacher['starting_year']); ?>"
                                            data-nationality="<?php echo htmlspecialchars($teacher['nationality']); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="delete-button" data-id="<?php echo htmlspecialchars($teacher['id']); ?>"><i class="fas fa-trash-alt"></i></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <div id="addTeacherModal" class="modal">
      <div class="modal-content">
        <span class="close-button">&times;</span>
        <h3>Add New Teacher</h3>
       <form id="addTeacherForm" class="add-form" enctype="multipart/form-data">
          <div class="form-group">
            <label for="fullName">Full Name</label>
            <input type="text" id="fullName" name="full_name" required>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
          </div>
          <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" required>
          </div>
          
          <div class="form-group">
            <label for="gender">Gender</label>
            <select id="gender" name="gender" required>
                <option value="">-- Select Gender --</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label for="dob">Date of Birth</label>
            <input type="date" id="dob" name="dob" required>
          </div>
          <div class="form-group">
            <label for="startYear">Starting Year</label>
            <input type="number" id="startYear" name="starting_year" min="2000" max="2099" placeholder="e.g., 2024" required>
          </div>
          <div class="form-group">
            <label for="nationality">Nationality</label>
            <input type="text" id="nationality" name="nationality" required>
          </div>
          <div class="form-group">
            <label for="course_id">Initial Course Assignment</label>
            <select id="course_id" name="course_id">
                <option value="">-- No Initial Course --</option>
                <?php foreach ($courses_list as $course): ?>
                    <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['course_name']); ?></option>
                <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="profilePic">Profile Picture</label>
            <input type="file" id="profilePic" name="profile_picture" accept="image/*">
          </div>
          
          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
          </div>
          
          <button type="submit" class="submit-button">Save Teacher</button>
        </form> </div>
    </div>

    <div id="editTeacherModal" class="modal">
      <div class="modal-content">
        <span class="close-button">&times;</span>
        <h3>Edit Teacher</h3>
        <form id="editTeacherForm" class="add-form" enctype="multipart/form-data">
          <input type="hidden" id="editId" name="id">
          <div class="form-group">
            <label for="editFullName">Full Name</label>
            <input type="text" id="editFullName" name="full_name" required>
          </div>
          <div class="form-group">
            <label for="editEmail">Email</label>
            <input type="email" id="editEmail" name="email" required>
          </div>
          <div class="form-group">
            <label for="editPhone">Phone Number</label>
            <input type="tel" id="editPhone" name="phone" required>
          </div>
          
          <div class="form-group">
            <label for="editGender">Gender</label>
            <select id="editGender" name="gender" required>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label for="editDob">Date of Birth</label>
            <input type="date" id="editDob" name="dob" required>
          </div>
          <div class="form-group">
            <label for="editStartYear">Starting Year</label>
            <input type="number" id="editStartYear" name="starting_year" min="2000" max="2099" required>
          </div>
          <div class="form-group">
            <label for="editNationality">Nationality</label>
            <input type="text" id="editNationality" name="nationality" required>
          </div>
          <div class="form-group">
            <label for="editProfilePic">Change Profile Picture</label>
            <input type="file" id="editProfilePic" name="profile_picture" accept="image/*">
            <small class="text-muted">Current picture will be replaced.</small>
          </div>

          <button type="submit" class="submit-button">Save Changes</button>
        </form>
      </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const menuToggle = document.getElementById('menu-toggle');
            const sidebar = document.getElementById('sidebar');
            if (menuToggle && sidebar) {
                menuToggle.addEventListener('click', () => sidebar.classList.toggle('active'));
                document.addEventListener('click', (event) => {
                    if (!sidebar.contains(event.target) && !menuToggle.contains(event.target) && sidebar.classList.contains('active')) {
                        sidebar.classList.remove('active');
                    }
                });
            }

            // Add Teacher Modal
            const addModal = document.getElementById('addTeacherModal');
            const openAddModalBtn = document.querySelector('.add-button');
            const closeAddModalBtn = addModal.querySelector('.close-button');
            const addForm = document.getElementById('addTeacherForm');

            openAddModalBtn.addEventListener('click', () => addModal.style.display = 'block');
            closeAddModalBtn.addEventListener('click', () => addModal.style.display = 'none');
            window.addEventListener('click', (event) => {
                if (event.target === addModal) addModal.style.display = 'none';
            });
            addForm.addEventListener('submit', (event) => {
                event.preventDefault();
                // Corrected path to Action folder for fetch
                fetch('Action/save_teacher.php', { method: 'POST', body: new FormData(addForm) })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message);
                        if (data.success) location.reload();
                    });
            });

            // Edit Teacher Modal
            const editModal = document.getElementById('editTeacherModal');
            const closeEditModalBtn = editModal.querySelector('.close-button');
            const editForm = document.getElementById('editTeacherForm');

            closeEditModalBtn.addEventListener('click', () => editModal.style.display = 'none');
            window.addEventListener('click', (event) => {
                if (event.target === editModal) editModal.style.display = 'none';
            });
            document.querySelectorAll('.edit-button').forEach(button => {
                button.addEventListener('click', (e) => {
                    const { id, name, email, phone, gender, dob, year, nationality } = e.currentTarget.dataset;
                    
                    // Populate existing fields
                    editModal.querySelector('#editId').value = id;
                    editModal.querySelector('#editFullName').value = name;
                    editModal.querySelector('#editEmail').value = email;
                    editModal.querySelector('#editPhone').value = phone;
                    
                    // Populate NEW fields
                    editModal.querySelector('#editGender').value = gender;
                    editModal.querySelector('#editDob').value = dob; // Correct format for input type="date"
                    editModal.querySelector('#editStartYear').value = year;
                    editModal.querySelector('#editNationality').value = nationality;

                    editModal.style.display = 'block';
                });
            });
            editForm.addEventListener('submit', (event) => {
                event.preventDefault();
                // Corrected path to Action folder for fetch
                fetch('Action/edit_teacher.php', { method: 'POST', body: new FormData(editForm) })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message);
                        if (data.success) location.reload();
                    });
            });

            // Delete Button
            document.querySelectorAll('.delete-button').forEach(button => {
                button.addEventListener('click', (e) => {
                    const id = e.currentTarget.dataset.id;
                    if (confirm('Are you sure you want to delete this teacher?')) {
                        // Corrected path to Action folder for fetch
                        fetch('Action/delete_teacher.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `id=${id}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            alert(data.message);
                            if (data.success) location.reload();
                        });
                    }
                });
            });
        });
        document.getElementById('logoutBtn').addEventListener('click', function() {
    // Redirects to logout.php
    alert('You have been logged out.');
    window.location.href = 'logout.php';
});
    </script>
</body>
</html>
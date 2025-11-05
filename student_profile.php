<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("location: login.html");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get the current page name for dynamic highlighting
$current_page = basename($_SERVER['PHP_SELF']); 

// Fetch student details including new fields
$sql_student = "SELECT s.*, u.email 
                FROM students s 
                JOIN users u ON s.user_id = u.id 
                WHERE u.id = ?";
$stmt_student = $conn->prepare($sql_student);
$stmt_student->bind_param("i", $user_id);
$stmt_student->execute();
$result = $stmt_student->get_result();
$student = $result->fetch_assoc();
$student_id = $student['id'];

// Fetch enrolled courses
$sql_courses = "SELECT c.course_name 
                FROM courses c 
                JOIN enrollments e ON c.id = e.course_id 
                WHERE e.student_id = ?";
$stmt_courses = $conn->prepare($sql_courses);
$stmt_courses->bind_param("i", $student_id);
$stmt_courses->execute();
$courses_result = $stmt_courses->get_result();
$courses = $courses_result->fetch_all(MYSQLI_ASSOC);

// --- START: Attendance Summary Fetch ---
$sql_attendance_summary = "
    SELECT 
        COUNT(a.student_id) as total_records,
        SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as total_present
    FROM attendance a
    JOIN enrollments e ON a.student_id = e.student_id AND a.course_id = e.course_id
    WHERE e.student_id = ?";
$stmt_summary = $conn->prepare($sql_attendance_summary);
$stmt_summary->bind_param("i", $student_id);
$stmt_summary->execute();
$summary_result = $stmt_summary->get_result();
$attendance_summary = $summary_result->fetch_assoc();
$stmt_summary->close();

// Calculate percentage
$total_records = $attendance_summary['total_records'];
$total_present = $attendance_summary['total_present'];
$attendance_percentage = ($total_records > 0) ? round(($total_present / $total_records) * 100, 2) : 0;
// --- END: Attendance Summary Fetch ---

$stmt_student->close();
$stmt_courses->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link rel="stylesheet" href="students.css"> 
    <link rel="stylesheet" href="profile.css"> 
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-logo">
                <i class="fa-solid fa-graduation-cap"></i><h1>ClassBeacon</h1>
            </div>
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
                <div class="profile-container">
                    <aside class="profile-sidebar">
                        <div class="profile-avatar">
                            <?php if (!empty($student['profile_picture'])): ?>
                                <img src="<?php echo htmlspecialchars($student['profile_picture']); ?>" alt="Profile Picture">
                            <?php else: ?>
                                <i class="fa-solid fa-user-graduate"></i>
                            <?php endif; ?>
                        </div>
                        <h2 class="profile-name"><?php echo htmlspecialchars($student['full_name']); ?></h2>
                        <p class="profile-role">Student</p>
                        
                        <button class="edit-pic-btn" id="changePictureButton">
    <i class="fa-solid fa-camera"></i> Change Picture
</button>

<?php if (!empty($student['profile_picture'])): ?>
<button class="delete-pic-btn" id="removePictureButton" 
        data-student-id="<?php echo htmlspecialchars($student_id); ?>" 
        data-pic-path="<?php echo htmlspecialchars($student['profile_picture']); ?>">
    <i class="fa-solid fa-trash-alt"></i> Remove Picture
</button>
<?php endif; ?>


<div id="updateProfilePicturePopup">
    <div class="popup-content">
        <span class="close-button">&times;</span> 

        <h3>Update Profile Picture</h3>
        <form id="uploadPicForm" action="Action/update_student_profile_pic.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>">
            <input type="hidden" name="current_pic_path" value="<?php echo htmlspecialchars($student['profile_picture'] ?? ''); ?>">
            
            <p>Select New Image (JPG, PNG)</p>
            <input type="file" id="profileImageInput" name="profile_picture" accept="image/jpeg, image/png" required>
            <button type="submit" id="uploadAndSaveButton">Upload & Save</button>
        </form>
    </div>
</div>
                    </aside>
                    <div class="profile-main">
                        <div class="profile-card">
                            <h3 class="profile-card-header">About Me</h3>
                            <div class="profile-details-list">
                                <p class="detail-item"><i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($student['email']); ?></p>
                                <p class="detail-item"><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($student['phone']); ?></p>
                                <p class="detail-item"><i class="fa-solid fa-venus-mars"></i> <?php echo htmlspecialchars($student['gender']); ?></p>
                                <p class="detail-item"><i class="fa-solid fa-cake-candles"></i> <?php echo date('d/m/Y', strtotime($student['dob'])); ?></p>
                                <p class="detail-item"><i class="fa-solid fa-calendar-check"></i> Starting Year: <?php echo htmlspecialchars($student['starting_year']); ?></p>
                                <p class="detail-item"><i class="fa-solid fa-flag"></i> <?php echo htmlspecialchars($student['nationality']); ?></p>
                            </div>
                        </div>
                        
                        <div class="profile-card">
                            <h3 class="profile-card-header">Attendance Summary</h3>
                            <div class="profile-details-list">
                                <p class="detail-item"><i class="fa-solid fa-calendar-day"></i> Total Classes Tracked: <?php echo htmlspecialchars($total_records); ?></p>
                                <p class="detail-item"><i class="fa-solid fa-check-circle" style="color: var(--success-color);"></i> Total Present: <?php echo htmlspecialchars($total_present); ?></p>
                                <p class="detail-item"><i class="fa-solid fa-percent" style="color: var(--primary-color);"></i> Attendance Percentage: **<?php echo htmlspecialchars($attendance_percentage); ?>%**</p>
                            </div>
                        </div>
                        <div class="profile-card">
                            <h3 class="profile-card-header">My Courses</h3>
                            <div class="course-list">
                                <?php if (empty($courses)): ?>
                                    <p>Not enrolled in any courses.</p>
                                <?php else: ?>
                                    <?php foreach ($courses as $course): ?>
                                        <div class="course-item"><?php echo htmlspecialchars($course['course_name']); ?></div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
    
    <script>
document.addEventListener('DOMContentLoaded', function() {
    const changePictureButton = document.getElementById('changePictureButton');
    const updateProfilePicturePopup = document.getElementById('updateProfilePicturePopup');
    const closeButton = updateProfilePicturePopup ? updateProfilePicturePopup.querySelector('.close-button') : null;
    const uploadPicForm = document.getElementById('uploadPicForm'); 
    const removePictureButton = document.getElementById('removePictureButton'); // 🔑 NEW: Button reference

    function showPopup() {
        if (updateProfilePicturePopup) {
            updateProfilePicturePopup.style.display = 'flex'; 
        }
    }

    function hidePopup() {
        if (updateProfilePicturePopup) {
            updateProfilePicturePopup.style.display = 'none';
        }
    }

    if (changePictureButton) {
        changePictureButton.addEventListener('click', showPopup);
    }

    if (closeButton) {
        closeButton.addEventListener('click', hidePopup);
    }

    // Hide pop-up if user clicks outside of the content box (on the grey overlay)
    if (updateProfilePicturePopup) {
        updateProfilePicturePopup.addEventListener('click', function(event) {
            if (event.target === updateProfilePicturePopup) {
                hidePopup();
            }
        });
    }

    // --- AJAX Form Submission Logic (Upload) ---
    if (uploadPicForm) {
        uploadPicForm.addEventListener('submit', function(e) {
            e.preventDefault(); 
            
            fetch('Action/update_student_profile_pic.php', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload(); 
                } else {
                    alert('Error updating picture: ' + data.message); 
                }
            })
            .catch(error => {
                alert('An unexpected network error occurred during upload.');
                console.error('Error:', error);
            });
        });
    }

    // --- 🔑 NEW: Profile Picture REMOVAL Logic ---
    if (removePictureButton) {
        removePictureButton.addEventListener('click', function() {
            if (confirm('Are you sure you want to permanently remove your profile picture?')) {
                const studentId = this.getAttribute('data-student-id');
                const picPath = this.getAttribute('data-pic-path');

                const formData = new FormData();
                formData.append('student_id', studentId);
                formData.append('current_pic_path', picPath);
                
                fetch('Action/delete_student_profile_pic.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // Refresh to show the default icon
                    } else {
                        alert('Error removing picture: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('An unexpected network error occurred during removal.');
                    console.error('Error:', error);
                });
            }
        });
    }
    
    // --- Logout Logic (Keep this) ---
    document.querySelector('.logout-btn').addEventListener('click', function(e) {
        e.preventDefault();
        alert('You have been logged out.');
        window.location.href = 'logout.php';
    });
});
</script>
</body>
</html>
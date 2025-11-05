<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("location: login.html");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get the current page name for dynamic highlighting
$current_page = basename($_SERVER['PHP_SELF']); 

// Fetch teacher details
// NOTE: Make sure to fetch profile_picture here (it is present in the previous steps)
$sql_teacher = "SELECT t.id, t.full_name, t.phone, t.gender, t.dob, t.starting_year, t.nationality, t.profile_picture, u.email 
                FROM teachers t 
                JOIN users u ON t.user_id = u.id 
                WHERE u.id = ?";
$stmt_teacher = $conn->prepare($sql_teacher);
$stmt_teacher->bind_param("i", $user_id);
$stmt_teacher->execute();
$result = $stmt_teacher->get_result();
$teacher = $result->fetch_assoc();
$teacher_id = $teacher['id'];

// Fetch assigned courses
$sql_courses = "SELECT course_name FROM courses WHERE teacher_id = ?";
$stmt_courses = $conn->prepare($sql_courses);
$stmt_courses->bind_param("i", $teacher_id);
$stmt_courses->execute();
$courses_result = $stmt_courses->get_result();
$courses = $courses_result->fetch_all(MYSQLI_ASSOC);

$stmt_teacher->close();
$stmt_courses->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="teacher.css"> 
    <link rel="stylesheet" href="profile.css">  
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
                    <li><a href="teacher_dashboard.php" class="<?php echo ($current_page == 'teacher_dashboard.php') ? 'active' : ''; ?>"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                    <li><a href="teacher_profile.php" class="<?php echo ($current_page == 'teacher_profile.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user"></i> My Profile</a></li>
                    <li><a href="manage_attendance.php"><i class="fa-solid fa-calendar-check"></i> Manage Attendance</a></li>
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
                <div class="profile-container">
                    <aside class="profile-sidebar">
                        <div class="profile-avatar">
                            <?php if (!empty($teacher['profile_picture'])): ?>
                                <img src="<?php echo htmlspecialchars($teacher['profile_picture']); ?>" alt="Profile Picture">
                            <?php else: ?>
                                <i class="fa-solid fa-chalkboard-user"></i>
                            <?php endif; ?>
                        </div>
                        <h2 class="profile-name"><?php echo htmlspecialchars($teacher['full_name']); ?></h2>
                        <p class="profile-role">Teacher</p>
                        
                        <button class="edit-pic-btn" id="changePictureButton">
                            <i class="fa-solid fa-camera"></i> Change Picture
                        </button>

                        <?php if (!empty($teacher['profile_picture'])): ?>
                        <button class="delete-pic-btn" id="removePictureButton" 
                                data-teacher-id="<?php echo htmlspecialchars($teacher_id); ?>" 
                                data-pic-path="<?php echo htmlspecialchars($teacher['profile_picture']); ?>">
                            <i class="fa-solid fa-trash-alt"></i> Remove Picture
                        </button>
                        <?php endif; ?>
                        
                        <div id="updateProfilePicturePopup">
                            <div class="popup-content">
                                <span class="close-button">&times;</span> 

                                <h3>Update Profile Picture</h3>
                                <form id="uploadPicForm" action="Action/update_teacher_profile_pic.php" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="teacher_id" value="<?php echo htmlspecialchars($teacher_id); ?>">
                                    <input type="hidden" name="current_pic_path" value="<?php echo htmlspecialchars($teacher['profile_picture'] ?? ''); ?>">
                                    
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
                                <p class="detail-item"><i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($teacher['email']); ?></p>
                                <p class="detail-item"><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($teacher['phone']); ?></p>
                                
                                <p class="detail-item"><i class="fa-solid fa-venus-mars"></i> Gender: <?php echo htmlspecialchars($teacher['gender'] ?? 'N/A'); ?></p>
                                <p class="detail-item"><i class="fa-solid fa-cake-candles"></i> DOB: <?php echo htmlspecialchars(date('d/m/Y', strtotime($teacher['dob']))); ?></p>
                                <p class="detail-item"><i class="fa-solid fa-calendar-check"></i> Joined: <?php echo htmlspecialchars($teacher['starting_year'] ?? 'N/A'); ?></p>
                                <p class="detail-item"><i class="fa-solid fa-flag"></i> Nationality: <?php echo htmlspecialchars($teacher['nationality'] ?? 'N/A'); ?></p>
                                </div>
                        </div>
                        <div class="profile-card">
                            <h3 class="profile-card-header">My Courses</h3>
                            <div class="course-list">
                                <?php if (empty($courses)): ?>
                                    <p>Not assigned to any courses.</p>
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
            
            fetch('Action/update_teacher_profile_pic.php', {
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
                // NOTE: Use 'data-teacher-id' here
                const teacherId = this.getAttribute('data-teacher-id');
                const picPath = this.getAttribute('data-pic-path');

                const formData = new FormData();
                formData.append('teacher_id', teacherId);
                formData.append('current_pic_path', picPath);
                
                fetch('Action/delete_teacher_profile_pic.php', { // 🔑 NEW ACTION FILE
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
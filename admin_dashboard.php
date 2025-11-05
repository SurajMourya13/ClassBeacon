<?php
// Include the database connection file
require_once 'db_connect.php';

// --- Fetch data for the stat cards ---

// 1. Get Total Students
$students_result = $conn->query("SELECT COUNT(id) AS total_students FROM students");
$total_students = $students_result->fetch_assoc()['total_students'];

// 2. Get Total Teachers
$teachers_result = $conn->query("SELECT COUNT(id) AS total_teachers FROM teachers");
$total_teachers = $teachers_result->fetch_assoc()['total_teachers'];

// 3. Get Active Courses
$courses_result = $conn->query("SELECT COUNT(id) AS active_courses FROM courses WHERE is_active = 1");
$active_courses = $courses_result->fetch_assoc()['active_courses'];

// 4. Get Recent Notices (e.g., total notices for simplicity as in the image)
$notices_result = $conn->query("SELECT COUNT(id) AS recent_notices FROM notices");
$recent_notices = $notices_result->fetch_assoc()['recent_notices'];

// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassBeacon - Admin Dashboard</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --primary-color: #6a6cce;
            --light-primary-color: #f1f1ff;
            --text-dark: #333;
            --text-light: #777;
            --bg-color: #f8f9fa;
            --sidebar-bg: #ffffff;
            --card-bg: #ffffff;
            --border-color: #e9ecef;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-color); color: var(--text-dark); line-height: 1.6; display: flex; }
        a { text-decoration: none; color: inherit; }
        ul { list-style-type: none; }
        .container { display: flex; width: 100%; min-height: 100vh; }

        /* Sidebar Navigation */
        .sidebar { width: 260px; background-color: var(--sidebar-bg); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 1.5rem; transition: transform 0.3s ease-in-out; }
        .sidebar-logo { display: flex; align-items: center; margin-bottom: 2.5rem; }
        .sidebar-logo i { font-size: 1.8rem; color: var(--primary-color); margin-right: 0.75rem; }
        .sidebar-logo h1 { font-size: 1.5rem; font-weight: 600; }
        .sidebar-nav ul li { margin-bottom: 0.5rem; }
        .sidebar-nav a { display: flex; align-items: center; padding: 0.8rem 1rem; border-radius: 8px; color: var(--text-light); font-weight: 500; transition: background-color 0.2s, color 0.2s; }
        .sidebar-nav a i { font-size: 1.1rem; margin-right: 1rem; width: 20px; text-align: center; }
        .sidebar-nav a:hover { background-color: var(--light-primary-color); color: var(--primary-color); }
        .sidebar-nav a.active { background-color: var(--primary-color); color: #fff; }
        
        /* Main Content */
        .main-content { flex-grow: 1; display: flex; flex-direction: column; }
        .header { display: flex; justify-content: flex-end; align-items: center; padding: 1.25rem 2.5rem; background-color: #fff; border-bottom: 1px solid var(--border-color); }
        .menu-toggle { display: none; font-size: 1.5rem; cursor: pointer; margin-right: auto; }
        .user-info { display: flex; align-items: center; }
        .user-details { text-align: right; margin-right: 1rem; }
        .user-details .user-role { font-size: 0.8rem; color: var(--text-light); }
        .user-details .user-name { font-weight: 600; }
        .logout-btn { display: flex; align-items: center; background: none; border: none; color: var(--text-light); font-size: 1rem; cursor: pointer; padding: 0.5rem; border-radius: 6px; transition: background-color 0.2s, color 0.2s; }
        .logout-btn:hover { color: var(--primary-color); background-color: var(--light-primary-color); }
        .logout-btn i { margin-left: 0.5rem; }
        
        /* Content Body */
        .content-body { padding: 2.5rem; flex-grow: 1; }
        .welcome-header h2 { font-size: 2rem; font-weight: 600; }
        .welcome-header p { color: var(--text-light); margin-bottom: 2rem; }
        
        /* Stat Cards */
        .stat-cards-container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background-color: var(--card-bg); padding: 1.5rem; border-radius: 12px; box-shadow: var(--shadow); }
        .stat-card .stat-title { font-size: 0.9rem; color: var(--text-light); margin-bottom: 0.5rem; }
        .stat-card .stat-value { font-size: 2.5rem; font-weight: 700; color: var(--text-dark); }
        
        /* Lower Cards Container */
        .cards-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2rem; }
        .card { background-color: var(--card-bg); border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow); }
        .card-header { display: flex; align-items: center; margin-bottom: 1.5rem; }
        .card-header i { font-size: 1.2rem; color: var(--primary-color); margin-right: 0.75rem; }
        .card-header h3 { font-size: 1.1rem; font-weight: 600; }
        
        .quick-actions-list a { display: flex; align-items: center; padding: 1rem; border: 1px solid var(--border-color); border-radius: 8px; font-weight: 500; transition: border-color 0.2s, color 0.2s; }
        .quick-actions-list a:not(:last-child) { margin-bottom: 1rem; }
        .quick-actions-list a:hover { border-color: var(--primary-color); color: var(--primary-color); }
        .quick-actions-list a i { margin-right: 1rem; width: 20px; color: var(--text-light); transition: color 0.2s;}
        .quick-actions-list a:hover i { color: var(--primary-color); }

        .activity-item { display: flex; align-items: flex-start; }
        .activity-item:not(:last-child) { margin-bottom: 1.5rem; }
        .activity-dot { width: 10px; height: 10px; border-radius: 50%; background-color: #28a745; margin-right: 1rem; margin-top: 6px; flex-shrink: 0; }
        .activity-content .activity-title { font-weight: 500; }
        .activity-content .activity-subtitle { font-size: 0.9rem; color: var(--text-light); }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .stat-cards-container { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 992px) {
            .sidebar { position: fixed; left: 0; top: 0; height: 100%; z-index: 1000; transform: translateX(-100%); box-shadow: 0 0 15px rgba(0,0,0,0.1); }
            .sidebar.active { transform: translateX(0); }
            .menu-toggle { display: block; }
            .header, .content-body { padding: 1.5rem; }
        }
        @media (max-width: 768px) {
             .stat-cards-container { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) {
            .user-details { display: none; }
            .welcome-header h2 { font-size: 1.5rem; }
        }
    </style>
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
                    <li><a href="#" class="active"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                    <li><a href="students.php"><i class="fa-solid fa-user-graduate"></i> Students</a></li>
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
                 <div class="menu-toggle" id="menu-toggle">
                    <i class="fa-solid fa-bars"></i>
                </div>
                <div class="user-info">
                    <div class="user-details">
                        <span class="user-role">Admin</span>
                        <i class="fa-solid fa-user-cog"></i>
                    </div>
                    <button id="logoutBtn" class="logout-btn">
                    Logout
                    <i class="fa-solid fa-right-from-bracket"></i>
                    </button>
                </div>
            </header>

            <section class="content-body">
                <div class="welcome-header">
                    <h2>Welcome back, System Administrator!</h2>
                    <p>Here's what's happening in your admin dashboard today.</p>
                </div>

                <div class="stat-cards-container">
                    <div class="stat-card">
                        <p class="stat-title">Total Students</p>
                        <p class="stat-value"><?php echo $total_students; ?></p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-title">Total Teachers</p>
                        <p class="stat-value"><?php echo $total_teachers; ?></p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-title">Active Courses</p>
                        <p class="stat-value"><?php echo $active_courses; ?></p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-title">Recent Notices</p>
                        <p class="stat-value"><?php echo $recent_notices; ?></p>
                    </div>
                </div>

                <div class="cards-container">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa-solid fa-bolt"></i>
                            <h3>Quick Actions</h3>
                        </div>
                        <div class="quick-actions-list">
                            <a href="students.php">
                                <i class="fa-solid fa-plus"></i>
                                <span>Add New Student</span>
                            </a>
                            <a href="courses.php">
                                <i class="fa-solid fa-plus"></i>
                                <span>Create Course</span>
                            </a>
                             <a href="notices.php">
                                <i class="fa-solid fa-paper-plane"></i>
                                <span>Send Notice</span>
                            </a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <h3>Recent Activity</h3>
                        </div>
                        <div class="recent-activity-list">
                            <div class="activity-item">
                                <span class="activity-dot"></span>
                                <div class="activity-content">
                                    <p class="activity-title">System initialized</p>
                                    <p class="activity-subtitle">Welcome to ClassBeacon!</p>
                                </div>
                            </div>
                            <div class="activity-item">
                                <span class="activity-dot"></span>
                                <div class="activity-content">
                                    <p class="activity-title">Dashboard ready</p>
                                    <p class="activity-subtitle">All features are available</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        // JavaScript for mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menu-toggle');
            const sidebar = document.getElementById('sidebar');

            if (menuToggle && sidebar) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
                document.addEventListener('click', function(event) {
                    const isClickInsideSidebar = sidebar.contains(event.target);
                    const isClickOnMenuToggle = menuToggle.contains(event.target);
                    if (!isClickInsideSidebar && !isClickOnMenuToggle && sidebar.classList.contains('active')) {
                        sidebar.classList.remove('active');
                    }
                });
            }
        });
        document.getElementById('logoutBtn').addEventListener('click', function() {
    // Here, you would clear any session data or cookies if you had them.
    // For this demo, we'll just redirect to the login page.
    alert('You have been logged out.');
    window.location.href = 'login.html';
});
    </script>

</body>
</html>
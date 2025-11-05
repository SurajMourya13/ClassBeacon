<?php
// Include the database connection file
require_once 'db_connect.php';

// Fetch all notices from the database
$sql = "SELECT * FROM notices ORDER BY created_at DESC";
$result = $conn->query($sql);

$notices = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $notices[] = $row;
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
    <title>ClassBeacon - Notices</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="notice.css">
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
                    <li><a href="courses.php"><i class="fa-solid fa-book"></i> Courses</a></li>
                    <li><a href="admin_attendance.php"><i class="fa-solid fa-calendar-check"></i> Attendance</a></li>
                    <li><a href="admin_grades.php"><i class="fa-solid fa-star"></i> Grades</a></li>
                    <li><a href="notices.php" class="active"><i class="fa-solid fa-bell"></i> Notices</a></li>
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
                    <h2>Notices</h2>
                    <button class="add-button" id="openAddModalBtn"><i class="fa-solid fa-plus"></i> Add Notice</button>
                </div>
                
                <div class="card-list-container">
                    <?php if (empty($notices)): ?>
                        <p>No notices found.</p>
                    <?php else: ?>
                        <?php foreach ($notices as $notice): ?>
                            <div class="info-card">
                                <div class="info-header">
                                    <span class="info-id">ID: <?php echo htmlspecialchars($notice['id']); ?></span>
                                </div>
                                <div class="info-content">
                                    <p class="info-name"><?php echo htmlspecialchars($notice['title']); ?></p>
                                    <p class="info-detail"><?php echo htmlspecialchars(substr($notice['content'], 0, 100)) . (strlen($notice['content']) > 100 ? '...' : ''); ?></p>
                                    <p class="info-detail"><i class="fas fa-calendar-alt info-icon"></i> Created: <?php echo date('d/m/Y', strtotime($notice['created_at'])); ?></p>
                                </div>
                                <div class="info-actions">
                                    <button class="edit-button" 
                                            data-id="<?php echo htmlspecialchars($notice['id']); ?>" 
                                            data-title="<?php echo htmlspecialchars($notice['title']); ?>" 
                                            data-content="<?php echo htmlspecialchars($notice['content']); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="delete-button" data-id="<?php echo htmlspecialchars($notice['id']); ?>">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <div id="addNoticeModal" class="modal">
      <div class="modal-content">
        <span class="close-button">&times;</span>
        <h3>Add New Notice</h3>
        <form id="addNoticeForm" class="add-form">
          <div class="form-group">
            <label for="noticeTitle">Title</label>
            <input type="text" id="noticeTitle" name="title" required>
          </div>
          <div class="form-group">
            <label for="noticeContent">Content</label>
            <textarea id="noticeContent" name="content" rows="5" required></textarea>
          </div>
          <button type="submit" class="submit-button">Save Notice</button>
        </form>
      </div>
    </div>

    <div id="editNoticeModal" class="modal">
      <div class="modal-content">
        <span class="close-button">&times;</span>
        <h3>Edit Notice</h3>
        <form id="editNoticeForm" class="add-form">
          <input type="hidden" id="editId" name="id">
          <div class="form-group">
            <label for="editNoticeTitle">Title</label>
            <input type="text" id="editNoticeTitle" name="title" required>
          </div>
          <div class="form-group">
            <label for="editNoticeContent">Content</label>
            <textarea id="editNoticeContent" name="content" rows="5" required></textarea>
          </div>
          <button type="submit" class="submit-button">Save Changes</button>
        </form>
      </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle & Logout button
            const menuToggle = document.getElementById('menu-toggle');
            const sidebar = document.getElementById('sidebar');
            const logoutBtn = document.getElementById('logoutBtn');
            if (menuToggle && sidebar) {
                menuToggle.addEventListener('click', () => sidebar.classList.toggle('active'));
                document.addEventListener('click', (event) => {
                    if (!sidebar.contains(event.target) && !menuToggle.contains(event.target) && sidebar.classList.contains('active')) {
                        sidebar.classList.remove('active');
                    }
                });
            }
            if (logoutBtn) {
                logoutBtn.addEventListener('click', () => {
                    alert('You have been logged out.');
                    window.location.href = 'login.html';
                });
            }

            // Add Notice Modal
            const addModal = document.getElementById('addNoticeModal');
            const openAddModalBtn = document.getElementById('openAddModalBtn');
            const closeAddModalBtn = addModal.querySelector('.close-button');
            const addForm = document.getElementById('addNoticeForm');

            // This is the core logic to open the modal.
            openAddModalBtn.addEventListener('click', () => addModal.style.display = 'block');
            
            closeAddModalBtn.addEventListener('click', () => {
                addModal.style.display = 'none';
                addForm.reset();
            });
            window.addEventListener('click', (event) => {
                if (event.target === addModal) {
                    addModal.style.display = 'none';
                    addForm.reset();
                }
            });
            addForm.addEventListener('submit', (event) => {
                event.preventDefault();
                // Submits form data to Action/save_notice.php
                fetch('Action/save_notice.php', { method: 'POST', body: new FormData(addForm) })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message);
                        if (data.success) location.reload();
                    });
            });

            // Edit Notice Modal
            const editModal = document.getElementById('editNoticeModal');
            const closeEditModalBtn = editModal.querySelector('.close-button');
            const editForm = document.getElementById('editNoticeForm');

            closeEditModalBtn.addEventListener('click', () => {
                editModal.style.display = 'none';
                editForm.reset();
            });
            window.addEventListener('click', (event) => {
                if (event.target === editModal) {
                    editModal.style.display = 'none';
                    editForm.reset();
                }
            });
            document.querySelectorAll('.edit-button').forEach(button => {
                button.addEventListener('click', (e) => {
                    const { id, title, content } = e.currentTarget.dataset;
                    editModal.querySelector('#editId').value = id;
                    editModal.querySelector('#editNoticeTitle').value = title;
                    editModal.querySelector('#editNoticeContent').value = content;
                    editModal.style.display = 'block';
                });
            });
            editForm.addEventListener('submit', (event) => {
                event.preventDefault();
                // Submits form data to Action/edit_notice.php
                fetch('Action/edit_notice.php', { method: 'POST', body: new FormData(editForm) })
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
                    if (confirm('Are you sure you want to delete this notice?')) {
                        // Submits ID to Action/delete_notice.php
                        fetch('Action/delete_notice.php', {
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
    </script>
</body>
</html>
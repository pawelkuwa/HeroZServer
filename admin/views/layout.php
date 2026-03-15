<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hero Zero Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="assets/admin.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-mask text-warning"></i>
                <span class="sidebar-title">HeroZero Admin</span>
            </div>
            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'dashboard') ? 'active' : '' ?>" href="index.php?page=dashboard">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'users') ? 'active' : '' ?>" href="index.php?page=users">
                            <i class="fas fa-users"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'characters') ? 'active' : '' ?>" href="index.php?page=characters">
                            <i class="fas fa-user-ninja"></i>
                            <span>Characters</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'guilds') ? 'active' : '' ?>" href="index.php?page=guilds">
                            <i class="fas fa-shield-halved"></i>
                            <span>Guilds</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'items') ? 'active' : '' ?>" href="index.php?page=items">
                            <i class="fas fa-gem"></i>
                            <span>Items</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'messages') ? 'active' : '' ?>" href="index.php?page=messages">
                            <i class="fas fa-envelope"></i>
                            <span>Messages</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'email') ? 'active' : '' ?>" href="index.php?page=email">
                            <i class="fas fa-paper-plane"></i>
                            <span>Email</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'vouchers') ? 'active' : '' ?>" href="index.php?page=vouchers">
                            <i class="fas fa-ticket-alt"></i>
                            <span>Vouchers</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'config') ? 'active' : '' ?>" href="index.php?page=config">
                            <i class="fas fa-cogs"></i>
                            <span>Config</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <div class="main-content" id="main-content">
            <nav class="topbar">
                <button class="btn btn-sm btn-outline-secondary d-md-none" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="topbar-right">
                    <span class="admin-user">
                        <i class="fas fa-user-shield"></i>
                        <?= e($_SESSION['admin_username'] ?? 'Admin') ?>
                    </span>
                    <a href="index.php?action=logout" class="btn btn-sm btn-outline-danger ms-3">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </nav>

            <div class="content-wrapper">
                <?php include $viewFile; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/admin.js"></script>
</body>
</html>

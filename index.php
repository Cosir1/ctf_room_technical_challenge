<?php
session_start();
require_once 'config/database.php';
require_once 'auth.php';

$role = get_user_role();
$display_name = get_user_display_name();

$stmt = $mysqli->prepare("SELECT * FROM events WHERE status = 'active' ORDER BY start_date DESC");
$stmt->execute();
$active_events = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CTF Room Challenge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-trophy-fill me-2"></i>CTF Room Challenge
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (is_judge()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="judges.php">Judge Portal</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if (is_admin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php">Admin Panel</a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="scoreboard.php">Scoreboard</a>
                    </li>
                    
                    <?php if (is_logged_in()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-body p-5">
                        <?php if (is_logged_in()): ?>
                            <h1 class="h3 mb-4">Welcome, <?php echo htmlspecialchars($display_name); ?>!</h1>
                        <?php else: ?>
                            <h1 class="h3 mb-4">Welcome to CTF Room Challenge</h1>
                        <?php endif; ?>

                        <?php if (isset($_GET['error']) && $_GET['error'] === 'unauthorized'): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>You are not authorized to access that page.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <div class="text-center">
                            <i class="bi bi-trophy-fill text-primary" style="font-size: 5rem;"></i>
                            <p class="mt-3">Join our CTF challenges and test your skills!</p>
                            
                            <?php if (!is_logged_in()): ?>
                                <div class="mt-4">
                                    <a href="login.php" class="btn btn-primary me-2">
                                        <i class="bi bi-box-arrow-in-right me-2"></i>Login
                                    </a>
                                    <a href="register.php" class="btn btn-outline-primary">
                                        <i class="bi bi-person-plus-fill me-2"></i>Register
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($active_events->num_rows > 0): ?>
        <div class="row mt-5">
            <div class="col-12">
                <h2 class="h3 mb-4">Active Events</h2>
                <div class="row g-4">
                    <?php while ($event = $active_events->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h3 class="card-title h5"><?php echo htmlspecialchars($event['name']); ?></h3>
                                <p class="card-text text-muted"><?php echo htmlspecialchars($event['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar-event me-1"></i>
                                        <?php echo date('M d, Y', strtotime($event['start_date'])); ?>
                                    </small>
                                    <a href="event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>CTF Room Challenge</h5>
                    <p class="text-muted">A platform for competitive technical challenges</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> CTF Room Challenge. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
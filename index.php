<?php
session_start();
require_once 'config/database.php';

// Fetch active events
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
                    <li class="nav-item">
                        <a class="nav-link" href="scoreboard.php">
                            <i class="bi bi-bar-chart-fill me-1"></i>Scoreboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="judges.php">
                            <i class="bi bi-person-badge me-1"></i>Judge Portal
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php">
                            <i class="bi bi-gear-fill me-1"></i>Admin Panel
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="display-4 text-center mb-4">Welcome to CTF Room Challenge</h1>
                <p class="lead text-center text-muted">A platform for competitive technical challenges and scoring</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 shadow-sm hover-shadow">
                    <div class="card-body text-center">
                        <i class="bi bi-bar-chart-fill display-4 text-primary mb-3"></i>
                        <h3 class="card-title h4">Live Scoreboard</h3>
                        <p class="card-text text-muted">Track real-time progress and rankings of all participants.</p>
                        <a href="scoreboard.php" class="btn btn-primary">
                            <i class="bi bi-arrow-right-circle me-1"></i>View Scoreboard
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 shadow-sm hover-shadow">
                    <div class="card-body text-center">
                        <i class="bi bi-person-badge display-4 text-success mb-3"></i>
                        <h3 class="card-title h4">Judge Portal</h3>
                        <p class="card-text text-muted">Access the judge portal to evaluate and score participants.</p>
                        <a href="judges.php" class="btn btn-success">
                            <i class="bi bi-arrow-right-circle me-1"></i>Enter Judge Portal
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 shadow-sm hover-shadow">
                    <div class="card-body text-center">
                        <i class="bi bi-gear-fill display-4 text-info mb-3"></i>
                        <h3 class="card-title h4">Admin Panel</h3>
                        <p class="card-text text-muted">Manage events, judges, and system settings.</p>
                        <a href="admin.php" class="btn btn-info text-white">
                            <i class="bi bi-arrow-right-circle me-1"></i>Access Admin Panel
                        </a>
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
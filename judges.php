<?php
session_start();
require_once 'config/database.php';

// Get the current judge ID (you might want to implement proper authentication)
$judge_id = isset($_GET['judge_id']) ? (int)$_GET['judge_id'] : null;

if (!$judge_id) {
    // Redirect to a judge selection page or show an error
    die("Please select a judge first.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_id = $_POST['event_id'];
    $user_id = $_POST['user_id'];
    $points = $_POST['points'];
    $comments = $_POST['comments'];
    
    // Verify that the judge is assigned to this event
    $check_stmt = $mysqli->prepare("SELECT 1 FROM event_judges WHERE event_id = ? AND judge_id = ?");
    $check_stmt->bind_param("ii", $event_id, $judge_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        $stmt = $mysqli->prepare("INSERT INTO scores (event_id, user_id, judge_id, points, comments) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $event_id, $user_id, $judge_id, $points, $comments);
        $stmt->execute();
        $stmt->close();
        
        // Redirect to prevent form resubmission
        header("Location: judges.php?judge_id=" . $judge_id . "&success=1");
        exit();
    } else {
        $error = "You are not authorized to score this event.";
    }
    $check_stmt->close();
}

// Fetch events this judge is assigned to
$events = $mysqli->prepare("
    SELECT e.* 
    FROM events e
    INNER JOIN event_judges ej ON e.id = ej.event_id
    WHERE ej.judge_id = ? AND e.status = 'active'
    ORDER BY e.start_date DESC
");
$events->bind_param("i", $judge_id);
$events->execute();
$events = $events->get_result();

// Fetch users
$users = $mysqli->query("SELECT * FROM users ORDER BY display_name");

// Get judge information
$judge_stmt = $mysqli->prepare("SELECT * FROM judges WHERE id = ?");
$judge_stmt->bind_param("i", $judge_id);
$judge_stmt->execute();
$judge = $judge_stmt->get_result()->fetch_assoc();
$judge_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Judge Portal - CTF Room Challenge</title>
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
                        <a class="nav-link active" href="judges.php">
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
        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>Score has been successfully recorded!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-12">
                <h1 class="display-4 text-center mb-4">Judge Portal</h1>
                <p class="lead text-center text-muted">Welcome, <?php echo htmlspecialchars($judge['display_name']); ?></p>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h4 mb-4">Submit Score</h2>
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-4">
                                <label for="event_id" class="form-label">Select Event</label>
                                <select name="event_id" id="event_id" class="form-select" required>
                                    <option value="">Choose an event...</option>
                                    <?php while ($event = $events->fetch_assoc()): ?>
                                        <option value="<?php echo $event['id']; ?>">
                                            <?php echo htmlspecialchars($event['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="invalid-feedback">Please select an event.</div>
                            </div>

                            <div class="mb-4">
                                <label for="user_id" class="form-label">Select Participant</label>
                                <select name="user_id" id="user_id" class="form-select" required>
                                    <option value="">Choose a participant...</option>
                                    <?php while ($user = $users->fetch_assoc()): ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo htmlspecialchars($user['display_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="invalid-feedback">Please select a participant.</div>
                            </div>

                            <div class="mb-4">
                                <label for="points" class="form-label">Points (1-100)</label>
                                <input type="number" name="points" id="points" class="form-control" min="1" max="100" required>
                                <div class="invalid-feedback">Please enter a valid score between 1 and 100.</div>
                            </div>

                            <div class="mb-4">
                                <label for="comments" class="form-label">Comments</label>
                                <textarea name="comments" id="comments" class="form-control" rows="3" placeholder="Add any comments or feedback..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Submit Score
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
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
    <script>
    // Form validation
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()
    </script>
</body>
</html>

<?php
session_start();
require_once 'config/database.php';
require_once 'auth.php';

require_judge();
$judge_id = $_SESSION['judge_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_id = $_POST['event_id'];
    $user_id = $_POST['user_id'];
    $points = $_POST['points'];
    $comments = $_POST['comments'];
    $check_stmt = $mysqli->prepare("SELECT 1 FROM event_judges WHERE event_id = ? AND judge_id = ?");
    $check_stmt->bind_param("ii", $event_id, $judge_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        $stmt = $mysqli->prepare("INSERT INTO scores (event_id, user_id, judge_id, points, comments) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $event_id, $user_id, $judge_id, $points, $comments);
        $stmt->execute();
        $stmt->close();
        header("Location: judges.php?success=1&event_id=" . $event_id);
        exit();
    } else {
        $error = "You are not authorized to score this event.";
    }
    $check_stmt->close();
}

$selected_event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : null;

$events = $mysqli->prepare("
    SELECT e.*, 
           CASE WHEN ej.judge_id IS NOT NULL THEN 1 ELSE 0 END as is_assigned
    FROM events e
    LEFT JOIN event_judges ej ON e.id = ej.event_id AND ej.judge_id = ?
    WHERE e.status = 'active'
    ORDER BY e.start_date DESC
");
$events->bind_param("i", $judge_id);
$events->execute();
$events = $events->get_result();

$users = $mysqli->query("SELECT * FROM users WHERE role = 'user' ORDER BY display_name");

$judge_stmt = $mysqli->prepare("
    SELECT j.*, u.display_name 
    FROM judges j 
    JOIN users u ON j.user_id = u.id 
    WHERE j.id = ?
");
$judge_stmt->bind_param("i", $judge_id);
$judge_stmt->execute();
$judge = $judge_stmt->get_result()->fetch_assoc();

$selected_event = null;
if ($selected_event_id) {
    $event_stmt = $mysqli->prepare("
        SELECT e.*, 
               COUNT(DISTINCT s.user_id) as total_participants,
               AVG(s.points) as average_score,
               CASE WHEN ej.judge_id IS NOT NULL THEN 1 ELSE 0 END as is_assigned
        FROM events e
        LEFT JOIN scores s ON e.id = s.event_id
        LEFT JOIN event_judges ej ON e.id = ej.event_id AND ej.judge_id = ?
        WHERE e.id = ?
        GROUP BY e.id
    ");
    $event_stmt->bind_param("ii", $judge_id, $selected_event_id);
    $event_stmt->execute();
    $selected_event = $event_stmt->get_result()->fetch_assoc();
}

$existing_scores = [];
if ($selected_event_id) {
    $scores_stmt = $mysqli->prepare("
        SELECT s.*, u.display_name as participant_name
        FROM scores s
        JOIN users u ON s.user_id = u.id
        WHERE s.event_id = ? AND s.judge_id = ?
        ORDER BY s.user_id, s.points DESC
    ");
    $scores_stmt->bind_param("ii", $selected_event_id, $judge_id);
    $scores_stmt->execute();
    $existing_scores = $scores_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
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
                <i class="bi bi-check-circle-fill me-2"></i>Score has been successfully submitted!
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

        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h4 mb-4">Available Events</h2>
                        <div class="row g-4">
                            <?php 
                            $events->data_seek(0);
                            while ($event = $events->fetch_assoc()): 
                                $is_selected = $selected_event_id == $event['id'];
                            ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 <?php echo $is_selected ? 'border-primary' : ''; ?>">
                                    <div class="card-body">
                                        <h3 class="h5 card-title"><?php echo htmlspecialchars($event['name']); ?></h3>
                                        <p class="card-text text-muted"><?php echo htmlspecialchars($event['description']); ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="bi bi-calendar-event me-1"></i>
                                                <?php echo date('M d, Y', strtotime($event['start_date'])); ?>
                                            </small>
                                            <?php if ($event['is_assigned']): ?>
                                                <span class="badge bg-success">Assigned</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Not Assigned</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mt-3">
                                            <a href="?event_id=<?php echo $event['id']; ?>" 
                                               class="btn btn-sm <?php echo $is_selected ? 'btn-primary' : 'btn-outline-primary'; ?> w-100">
                                                <?php echo $is_selected ? 'Currently Selected' : 'Select Event'; ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($selected_event): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h4 mb-4">Event Details: <?php echo htmlspecialchars($selected_event['name']); ?></h2>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Description:</strong> <?php echo htmlspecialchars($selected_event['description']); ?></p>
                                <p class="mb-2">
                                    <strong>Date:</strong> 
                                    <?php echo date('M d, Y', strtotime($selected_event['start_date'])); ?> - 
                                    <?php echo date('M d, Y', strtotime($selected_event['end_date'])); ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Total Participants:</strong> <?php echo $selected_event['total_participants']; ?></p>
                                <p class="mb-2"><strong>Average Score:</strong> <?php echo round($selected_event['average_score'], 1); ?></p>
                                <p class="mb-2">
                                    <strong>Status:</strong> 
                                    <?php if ($selected_event['is_assigned']): ?>
                                        <span class="badge bg-success">Assigned to You</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Not Assigned to You</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($selected_event['is_assigned']): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h4 mb-4">Existing Scores</h2>
                        <?php if (empty($existing_scores)): ?>
                            <p class="text-muted">No scores have been submitted for this event yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Participant</th>
                                            <th>Points</th>
                                            <th>Comments</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($existing_scores as $score): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($score['participant_name']); ?></td>
                                            <td><?php echo $score['points']; ?></td>
                                            <td><?php echo htmlspecialchars($score['comments']); ?></td>
                                            <td><?php echo date('M d, Y H:i', strtotime($score['created_at'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($selected_event['is_assigned']): ?>
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
                                    <?php 
                                    $events->data_seek(0);
                                    while ($event = $events->fetch_assoc()): 
                                        if ($event['is_assigned']):
                                    ?>
                                        <option value="<?php echo $event['id']; ?>" 
                                                <?php echo $selected_event_id == $event['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($event['name']); ?>
                                        </option>
                                    <?php 
                                        endif;
                                    endwhile; 
                                    ?>
                                </select>
                                <div class="invalid-feedback">Please select an event.</div>
                            </div>

                            <div class="mb-4">
                                <label for="user_id" class="form-label">Select Participant</label>
                                <select name="user_id" id="user_id" class="form-select" required>
                                    <option value="">Choose a participant...</option>
                                    <?php 
                                    $users->data_seek(0);
                                    while ($user = $users->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo htmlspecialchars($user['display_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="invalid-feedback">Please select a participant.</div>
                            </div>

                            <div class="mb-4">
                                <label for="points" class="form-label">Points (1-100)</label>
                                <input type="number" class="form-control" id="points" name="points" 
                                       min="1" max="100" required>
                                <div class="invalid-feedback">Please enter points between 1 and 100.</div>
                            </div>

                            <div class="mb-4">
                                <label for="comments" class="form-label">Comments</label>
                                <textarea class="form-control" id="comments" name="comments" rows="3"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>Submit Score
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            You are not assigned to this event. Please contact an administrator to be assigned.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
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

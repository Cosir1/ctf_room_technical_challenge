<?php
session_start();
require_once 'config/database.php';

// Get the selected event ID from query parameter, default to active events
$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : null;

// Fetch events for the dropdown
$events = $mysqli->query("SELECT * FROM events WHERE status IN ('active', 'completed') ORDER BY start_date DESC");

// Prepare the base query for scores
$query = "
    SELECT 
        u.display_name as participant_name,
        e.name as event_name,
        COUNT(DISTINCT s.judge_id) as total_judges,
        AVG(s.points) as average_score,
        GROUP_CONCAT(DISTINCT j.display_name SEPARATOR ', ') as judges
    FROM users u
    CROSS JOIN events e
    LEFT JOIN scores s ON u.id = s.user_id AND e.id = s.event_id
    LEFT JOIN judges j ON s.judge_id = j.id
    WHERE e.status IN ('active', 'completed')
";

// Add event filter if specified
if ($event_id) {
    $query .= " AND e.id = " . $event_id;
}

$query .= "
    GROUP BY u.id, e.id
    ORDER BY e.start_date DESC, average_score DESC
";

$scores = $mysqli->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scoreboard - CTF Room Challenge</title>
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
                        <a class="nav-link active" href="scoreboard.php">
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
                <h1 class="display-4 text-center mb-4">Live Scoreboard</h1>
                <p class="lead text-center text-muted">Track real-time progress and rankings of all participants</p>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-2">
                            <select name="event_id" class="form-select" onchange="this.form.submit()">
                                <option value="">All Events</option>
                                <?php while ($event = $events->fetch_assoc()): ?>
                                    <option value="<?php echo $event['id']; ?>" <?php echo $event_id == $event['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($event['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Rank</th>
                                        <th>Participant</th>
                                        <th>Event</th>
                                        <th>Average Score</th>
                                        <th>Judges</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $rank = 1;
                                    $current_event = '';
                                    while ($score = $scores->fetch_assoc()): 
                                        if ($current_event != $score['event_name']) {
                                            $current_event = $score['event_name'];
                                            $rank = 1;
                                        }
                                    ?>
                                    <tr>
                                        <td>
                                            <?php if ($score['average_score'] !== null): ?>
                                                <span class="badge bg-primary rounded-pill"><?php echo $rank++; ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($score['participant_name']); ?></td>
                                        <td><?php echo htmlspecialchars($score['event_name']); ?></td>
                                        <td>
                                            <?php if ($score['average_score'] !== null): ?>
                                                <span class="fw-bold"><?php echo number_format($score['average_score'], 1); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">Not scored</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($score['judges']): ?>
                                                <span class="text-muted"><?php echo htmlspecialchars($score['judges']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">No judges assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($score['total_judges'] > 0): ?>
                                                <span class="badge bg-success">Scored</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
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
</body>
</html>

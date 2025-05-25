<?php
session_start();
require_once 'config/database.php';

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : null;
$events = $mysqli->query("SELECT * FROM events WHERE status IN ('active', 'completed') ORDER BY start_date DESC");

$query = "
    SELECT 
        u.display_name as participant_name,
        e.name as event_name,
        e.status as event_status,
        COUNT(DISTINCT s.judge_id) as total_judges,
        AVG(s.points) as average_score,
        GROUP_CONCAT(DISTINCT ju.display_name SEPARATOR ', ') as judges
    FROM users u
    CROSS JOIN events e
    LEFT JOIN scores s ON u.id = s.user_id AND e.id = s.event_id
    LEFT JOIN judges j ON s.judge_id = j.id
    LEFT JOIN users ju ON j.user_id = ju.id
    WHERE e.status IN ('active', 'completed')
";

if ($event_id) {
    $query .= " AND e.id = " . $event_id;
}

$query .= "
    GROUP BY u.id, e.id
    ORDER BY e.start_date DESC, average_score DESC
";

$scores = $mysqli->query($query);

$scores_query = "
    SELECT 
        u.id,
        u.display_name,
        COALESCE(SUM(s.points), 0) as total_points,
        COUNT(DISTINCT s.event_id) as events_participated
    FROM users u
    LEFT JOIN scores s ON u.id = s.user_id
    WHERE u.role = 'user'
    GROUP BY u.id, u.display_name
    ORDER BY total_points DESC
";
$scores = $mysqli->query($scores_query);

// Get the highest score for highlighting
$highest_score = 0;
if ($scores->num_rows > 0) {
    $first_row = $scores->fetch_assoc();
    $highest_score = $first_row['total_points'];
    $scores->data_seek(0); // Reset pointer
}
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
            <div class="col-md-8 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Event</label>
                                <select name="event_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Events</option>
                                    <?php while ($event = $events->fetch_assoc()): ?>
                                        <option value="<?php echo $event['id']; ?>" <?php echo $event_id == $event['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($event['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo isset($_GET['status']) && $_GET['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="completed" <?php echo isset($_GET['status']) && $_GET['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sort By</label>
                                <select name="sort" class="form-select" onchange="this.form.submit()">
                                    <option value="score" <?php echo (!isset($_GET['sort']) || $_GET['sort'] === 'score') ? 'selected' : ''; ?>>Score</option>
                                    <option value="name" <?php echo isset($_GET['sort']) && $_GET['sort'] === 'name' ? 'selected' : ''; ?>>Name</option>
                                    <option value="event" <?php echo isset($_GET['sort']) && $_GET['sort'] === 'event' ? 'selected' : ''; ?>>Event</option>
                                </select>
                            </div>
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
                                        <th>Total Points</th>
                                        <th>Events Participated</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $rank = 1;
                                    while ($score = $scores->fetch_assoc()): 
                                        $highlight_class = '';
                                        if ($score['total_points'] == $highest_score) {
                                            $highlight_class = 'table-success';
                                        } elseif ($score['total_points'] > 0) {
                                            $highlight_class = 'table-light';
                                        }
                                    ?>
                                    <tr class="<?php echo $highlight_class; ?>">
                                        <td><?php echo $rank++; ?></td>
                                        <td><?php echo htmlspecialchars($score['display_name']); ?></td>
                                        <td>
                                            <strong><?php echo $score['total_points']; ?></strong>
                                            <?php if ($score['total_points'] == $highest_score): ?>
                                                <i class="bi bi-trophy-fill text-warning ms-2"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $score['events_participated']; ?></td>
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
    <script>
    // Auto-refresh scoreboard every 30 seconds
    function refreshScoreboard() {
        location.reload();
    }
    setInterval(refreshScoreboard, 30000);
    </script>
</body>
</html>

<?php
session_start();
require_once 'config/database.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_event':
                $name = $_POST['name'];
                $description = $_POST['description'];
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                $status = $_POST['status'];
                
                $stmt = $mysqli->prepare("INSERT INTO events (name, description, start_date, end_date, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $name, $description, $start_date, $end_date, $status);
                $stmt->execute();
                $event_id = $stmt->insert_id;
                $stmt->close();
                
                // Assign selected judges to the event
                if (isset($_POST['judges']) && is_array($_POST['judges'])) {
                    $stmt = $mysqli->prepare("INSERT INTO event_judges (event_id, judge_id) VALUES (?, ?)");
                    foreach ($_POST['judges'] as $judge_id) {
                        $stmt->bind_param("ii", $event_id, $judge_id);
                        $stmt->execute();
                    }
                    $stmt->close();
                }
                
                header("Location: admin.php?success=event_created");
                exit();
                break;

            case 'assign_judges':
                $event_id = $_POST['event_id'];
                
                // First remove all existing judge assignments for this event
                $stmt = $mysqli->prepare("DELETE FROM event_judges WHERE event_id = ?");
                $stmt->bind_param("i", $event_id);
                $stmt->execute();
                $stmt->close();
                
                // Then add the new assignments
                if (isset($_POST['judges']) && is_array($_POST['judges'])) {
                    $stmt = $mysqli->prepare("INSERT INTO event_judges (event_id, judge_id) VALUES (?, ?)");
                    foreach ($_POST['judges'] as $judge_id) {
                        $stmt->bind_param("ii", $event_id, $judge_id);
                        $stmt->execute();
                    }
                    $stmt->close();
                }
                
                header("Location: admin.php?success=judges_assigned");
                exit();
                break;

            case 'create_judge':
                $email = $_POST['email'];
                $password = $_POST['password'];
                $display_name = $_POST['display_name'];
                $is_admin = isset($_POST['is_admin']) ? 1 : 0;
                
                // Check if email already exists
                $stmt = $mysqli->prepare("SELECT 1 FROM judges WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    header("Location: admin.php?error=email_exists");
                    exit();
                }
                $stmt->close();
                
                // Hash password and insert new judge
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare("INSERT INTO judges (email, password, display_name, is_admin) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $email, $hashed_password, $display_name, $is_admin);
                $stmt->execute();
                $stmt->close();
                
                header("Location: admin.php?success=judge_created");
                exit();
                break;
        }
    }
}

// Fetch all events
$events = $mysqli->query("SELECT * FROM events ORDER BY start_date DESC");

// Fetch all judges
$judges = $mysqli->query("SELECT * FROM judges ORDER BY display_name");

// Fetch all users
$users = $mysqli->query("SELECT * FROM users ORDER BY display_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - CTF Room Challenge</title>
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
                        <a class="nav-link active" href="admin.php">
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
                <?php if ($_GET['success'] == 'event_created'): ?>
                    <i class="bi bi-check-circle-fill me-2"></i>Event has been successfully created!
                <?php elseif ($_GET['success'] == 'judges_assigned'): ?>
                    <i class="bi bi-check-circle-fill me-2"></i>Judges have been successfully assigned!
                <?php elseif ($_GET['success'] == 'judge_created'): ?>
                    <i class="bi bi-check-circle-fill me-2"></i>Judge account has been successfully created!
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-12">
                <h1 class="display-4 text-center mb-4">Admin Panel</h1>
                <p class="lead text-center text-muted">Manage events, judges, and system settings</p>
            </div>
        </div>

        <div class="row g-4">
            <!-- Create Judge Account Section -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h4 mb-4">Create Judge Account</h2>
                        <form method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="create_judge">
                            
                            <div class="mb-3">
                                <label for="judge_email" class="form-label">Email address</label>
                                <input type="email" class="form-control" id="judge_email" name="email" required>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>

                            <div class="mb-3">
                                <label for="judge_password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="judge_password" name="password" required minlength="6">
                                <div class="invalid-feedback">Password must be at least 6 characters long.</div>
                            </div>

                            <div class="mb-3">
                                <label for="judge_display_name" class="form-label">Display Name</label>
                                <input type="text" class="form-control" id="judge_display_name" name="display_name" required>
                                <div class="invalid-feedback">Please enter a display name.</div>
                            </div>

                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_admin" name="is_admin">
                                    <label class="form-check-label" for="is_admin">
                                        Grant admin privileges
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-person-plus me-1"></i>Create Judge Account
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Create Event Section -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h4 mb-4">Create New Event</h2>
                        <form method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="create_event">
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Event Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <div class="invalid-feedback">Please enter an event name.</div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                                <div class="invalid-feedback">Please enter an event description.</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="datetime-local" class="form-control" id="start_date" name="start_date" required>
                                    <div class="invalid-feedback">Please select a start date.</div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="datetime-local" class="form-control" id="end_date" name="end_date" required>
                                    <div class="invalid-feedback">Please select an end date.</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="pending">Pending</option>
                                    <option value="active">Active</option>
                                    <option value="completed">Completed</option>
                                </select>
                                <div class="invalid-feedback">Please select a status.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Assign Judges</label>
                                <div class="border rounded p-3">
                                    <?php while ($judge = $judges->fetch_assoc()): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="judges[]" value="<?php echo $judge['id']; ?>" id="judge_<?php echo $judge['id']; ?>">
                                            <label class="form-check-label" for="judge_<?php echo $judge['id']; ?>">
                                                <?php echo htmlspecialchars($judge['display_name']); ?>
                                            </label>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-1"></i>Create Event
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Manage Events Section -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h4 mb-4">Manage Events</h2>
                        <div class="list-group">
                            <?php 
                            $events->data_seek(0); // Reset the pointer
                            while ($event = $events->fetch_assoc()): 
                            ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-1"><?php echo htmlspecialchars($event['name']); ?></h5>
                                            <p class="mb-1 text-muted small">
                                                <i class="bi bi-calendar-event me-1"></i>
                                                <?php echo date('M d, Y', strtotime($event['start_date'])); ?> - 
                                                <?php echo date('M d, Y', strtotime($event['end_date'])); ?>
                                            </p>
                                            <span class="badge bg-<?php 
                                                echo $event['status'] == 'active' ? 'success' : 
                                                    ($event['status'] == 'completed' ? 'secondary' : 'warning'); 
                                            ?>">
                                                <?php echo ucfirst($event['status']); ?>
                                            </span>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#assignJudgesModal<?php echo $event['id']; ?>">
                                            <i class="bi bi-person-plus me-1"></i>Assign Judges
                                        </button>
                                    </div>
                                </div>

                                <!-- Assign Judges Modal -->
                                <div class="modal fade" id="assignJudgesModal<?php echo $event['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Assign Judges - <?php echo htmlspecialchars($event['name']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="assign_judges">
                                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                    
                                                    <?php 
                                                    // Fetch currently assigned judges
                                                    $assigned_judges = $mysqli->query("
                                                        SELECT judge_id 
                                                        FROM event_judges 
                                                        WHERE event_id = " . $event['id']
                                                    );
                                                    $assigned_judge_ids = [];
                                                    while ($row = $assigned_judges->fetch_assoc()) {
                                                        $assigned_judge_ids[] = $row['judge_id'];
                                                    }
                                                    
                                                    // Reset judges pointer
                                                    $judges->data_seek(0);
                                                    while ($judge = $judges->fetch_assoc()): 
                                                    ?>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" 
                                                                   name="judges[]" 
                                                                   value="<?php echo $judge['id']; ?>" 
                                                                   id="event_<?php echo $event['id']; ?>_judge_<?php echo $judge['id']; ?>"
                                                                   <?php echo in_array($judge['id'], $assigned_judge_ids) ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="event_<?php echo $event['id']; ?>_judge_<?php echo $judge['id']; ?>">
                                                                <?php echo htmlspecialchars($judge['display_name']); ?>
                                                            </label>
                                                        </div>
                                                    <?php endwhile; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
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

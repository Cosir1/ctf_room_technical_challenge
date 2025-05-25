<?php 
require_once 'config/database.php';

function create_user($username, $email, $password, $display_name, $role = 'user') {
    global $mysqli;
    
    $stmt = $mysqli->prepare("SELECT 1 FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo "User $username already exists.<br>";
        return false;
    }
    $stmt->close();
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare("INSERT INTO users (username, email, password, display_name, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $email, $hashed_password, $display_name, $role);
    $result = $stmt->execute();
    $user_id = $stmt->insert_id;
    $stmt->close();
    
    if ($result) {
        echo "Created user account for $username with role: $role.<br>";
        return $user_id;
    }
    return false;
}

function create_judge($user_id) {
    global $mysqli;
    
    if (!$user_id) {
        echo "Invalid user ID provided for judge creation.<br>";
        return false;
    }
    
    $stmt = $mysqli->prepare("SELECT username, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "User with ID $user_id does not exist.<br>";
        $stmt->close();
        return false;
    }
    
    $user = $result->fetch_assoc();
    if ($user['role'] !== 'judge') {
        echo "User {$user['username']} does not have judge role.<br>";
        $stmt->close();
        return false;
    }
    $stmt->close();
    
    $stmt = $mysqli->prepare("SELECT 1 FROM judges WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo "Judge record for {$user['username']} already exists.<br>";
        $stmt->close();
        return false;
    }
    $stmt->close();
    
    $stmt = $mysqli->prepare("INSERT INTO judges (user_id) VALUES (?)");
    $stmt->bind_param("i", $user_id);
    $result = $stmt->execute();
    $judge_id = $stmt->insert_id;
    $stmt->close();
    
    if ($result) {
        echo "Created judge record for {$user['username']}.<br>";
        return $judge_id;
    }
    return false;
}

function create_event($name, $description, $start_date, $end_date, $status = 'active') {
    global $mysqli;
    
    $stmt = $mysqli->prepare("INSERT INTO events (name, description, start_date, end_date, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $description, $start_date, $end_date, $status);
    $result = $stmt->execute();
    $event_id = $stmt->insert_id;
    $stmt->close();
    
    if ($result) {
        echo "Created event: $name<br>";
        return $event_id;
    }
    return false;
}

function assign_judge_to_event($judge_id, $event_id) {
    global $mysqli;
    
    if (!$judge_id || !$event_id) {
        echo "Invalid judge ID or event ID provided for assignment.<br>";
        return false;
    }
    
    // Verify judge exists
    $stmt = $mysqli->prepare("SELECT 1 FROM judges WHERE id = ?");
    $stmt->bind_param("i", $judge_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        echo "Judge ID $judge_id does not exist.<br>";
        $stmt->close();
        return false;
    }
    $stmt->close();
    
    // Verify event exists
    $stmt = $mysqli->prepare("SELECT 1 FROM events WHERE id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        echo "Event ID $event_id does not exist.<br>";
        $stmt->close();
        return false;
    }
    $stmt->close();
    
    // Check if assignment already exists
    $stmt = $mysqli->prepare("SELECT 1 FROM event_judges WHERE judge_id = ? AND event_id = ?");
    $stmt->bind_param("ii", $judge_id, $event_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo "Judge ID $judge_id is already assigned to event ID $event_id.<br>";
        $stmt->close();
        return true;
    }
    $stmt->close();
    
    $stmt = $mysqli->prepare("INSERT INTO event_judges (judge_id, event_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $judge_id, $event_id);
    $result = $stmt->execute();
    $stmt->close();
    
    if ($result) {
        echo "Assigned judge ID $judge_id to event ID $event_id<br>";
        return true;
    }
    return false;
}

function create_score($event_id, $user_id, $judge_id, $points, $comments = '') {
    global $mysqli;
    
    if (!$event_id || !$user_id || !$judge_id) {
        echo "Invalid event ID, user ID, or judge ID provided for score creation.<br>";
        return false;
    }
    
    $stmt = $mysqli->prepare("INSERT INTO scores (event_id, user_id, judge_id, points, comments) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiss", $event_id, $user_id, $judge_id, $points, $comments);
    $result = $stmt->execute();
    $stmt->close();
    
    if ($result) {
        echo "Created score: $points points for user ID $user_id in event ID $event_id<br>";
        return true;
    }
    return false;
}

echo "<h2>Creating Test Data</h2>";

// Create admin account
$admin_user_id = create_user(
    'admin',
    'admin@test.com',
    'admin123',
    'Test Admin',
    'admin'
);

// Create judge accounts
$judge_user_id = create_user(
    'judge',
    'judge@test.com',
    'judge123',
    'Test Judge',
    'judge'
);

$judge2_user_id = create_user(
    'judge2',
    'judge2@test.com',
    'judge123',
    'Second Test Judge',
    'judge'
);

// Create judge records
$judge_id = create_judge($judge_user_id);
$judge2_id = create_judge($judge2_user_id);

if (!$judge_id || !$judge2_id) {
    echo "<p style='color: red;'>Failed to create judge records. Cannot proceed with event assignments.</p>";
    exit;
}

// Create participant accounts
$participant1_id = create_user(
    'participant1',
    'participant1@test.com',
    'participant123',
    'First Test Participant',
    'user'
);

$participant2_id = create_user(
    'participant2',
    'participant2@test.com',
    'participant123',
    'Second Test Participant',
    'user'
);

$participant3_id = create_user(
    'participant3',
    'participant3@test.com',
    'participant123',
    'Third Test Participant',
    'user'
);

// Create events
$event1_id = create_event(
    'Web Security Challenge',
    'Test your web security skills in this CTF challenge',
    date('Y-m-d H:i:s', strtotime('-2 days')),
    date('Y-m-d H:i:s', strtotime('+5 days')),
    'active'
);

$event2_id = create_event(
    'Network Forensics',
    'Analyze network traffic and solve security puzzles',
    date('Y-m-d H:i:s', strtotime('-1 day')),
    date('Y-m-d H:i:s', strtotime('+6 days')),
    'active'
);

$event3_id = create_event(
    'Cryptography Challenge',
    'Decrypt messages and solve cryptographic puzzles',
    date('Y-m-d H:i:s', strtotime('+1 day')),
    date('Y-m-d H:i:s', strtotime('+8 days')),
    'pending'
);

if (!$event1_id || !$event2_id || !$event3_id) {
    echo "<p style='color: red;'>Failed to create events. Cannot proceed with judge assignments.</p>";
    exit;
}

// Assign judges to events
$assignments = [
    [$judge_id, $event1_id],
    [$judge_id, $event2_id],
    [$judge2_id, $event1_id],
    [$judge2_id, $event3_id]
];

foreach ($assignments as $assignment) {
    assign_judge_to_event($assignment[0], $assignment[1]);
}

// Create sample scores
$scores = [
    [$event1_id, $participant1_id, $judge_id, 85, 'Good understanding of web security concepts'],
    [$event1_id, $participant1_id, $judge2_id, 90, 'Excellent problem-solving skills'],
    [$event1_id, $participant2_id, $judge_id, 75, 'Needs improvement in some areas'],
    [$event1_id, $participant2_id, $judge2_id, 80, 'Showed good progress'],
    [$event1_id, $participant3_id, $judge_id, 95, 'Outstanding performance'],
    [$event1_id, $participant3_id, $judge2_id, 92, 'Very impressive work'],
    [$event2_id, $participant1_id, $judge_id, 88, 'Strong network analysis skills'],
    [$event2_id, $participant2_id, $judge_id, 82, 'Good understanding of protocols'],
    [$event2_id, $participant3_id, $judge_id, 90, 'Excellent technical knowledge']
];

foreach ($scores as $score) {
    create_score($score[0], $score[1], $score[2], $score[3], $score[4]);
}

echo "<h3>Test Account Credentials:</h3>";
echo "<strong>Admin Account:</strong><br>";
echo "Username: admin@test.com<br>";
echo "Password: admin123<br>";
echo "Role: admin<br><br>";

echo "<strong>Judge Accounts:</strong><br>";
echo "Username: judge@test.com<br>";
echo "Password: judge123<br>";
echo "Role: judge<br><br>";

echo "Username: judge2@test.com<br>";
echo "Password: judge123<br>";
echo "Role: judge<br><br>";

echo "<strong>Participant Accounts:</strong><br>";
echo "Username: participant1@test.com<br>";
echo "Password: participant123<br>";
echo "Role: user<br><br>";

echo "Username: participant2@test.com<br>";
echo "Password: participant123<br>";
echo "Role: user<br><br>";

echo "Username: participant3@test.com<br>";
echo "Password: participant123<br>";
echo "Role: user<br><br>";

echo "<h3>Created Events:</h3>";
echo "1. Web Security Challenge (Active)<br>";
echo "2. Network Forensics (Active)<br>";
echo "3. Cryptography Challenge (Pending)<br><br>";

echo "<p>All test data has been created successfully!</p>";
?>
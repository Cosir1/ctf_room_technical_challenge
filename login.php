<?php
require_once 'auth.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    if (login($username, $password, $role)) {
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}

if (isset($_GET['message']) && $_GET['message'] === 'logged_out') {
    $success = "You have been successfully logged out.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CTF Room Challenge</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-4 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-trophy-fill text-primary" style="font-size: 3rem;"></i>
                            <h1 class="h3 mt-3">Welcome Back</h1>
                            <p class="text-muted">Sign in to your account</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i><?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                                <div class="invalid-feedback">Please enter your username.</div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="invalid-feedback">Please enter your password.</div>
                            </div>

                            <div class="mb-4">
                                <label for="role" class="form-label">Login as</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="user">Participant</option>
                                    <option value="judge">Judge</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                            </button>

                          
                        </form>

                        <div class="mt-4">
                            <h6 class="text-muted mb-3">Demo Accounts:</h6>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <p class="mb-1"><strong>Admin:</strong> admin / admin123</p>
                                    <p class="mb-1"><strong>Judge:</strong> judge / judge123</p>
                                    <p class="mb-0"><strong>Participant:</strong> participant / participant123</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
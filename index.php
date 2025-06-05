<?php
session_start();
include 'includes/config.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on user role
    if ($_SESSION['role'] == 'librarian') {
        header('Location: librarian/dashboard.php');
    } else {
        header('Location: student/dashboard.php');
    }
    exit();
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password";
    } else {
        // Prepare SQL statement
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Redirect based on role
                if ($user['role'] == 'librarian') {
                    header('Location: librarian/dashboard.php');
                } else {
                    header('Location: student/dashboard.php');
                }
                exit();
            } else {
                $error = "Invalid password";
            }
        } else {
            $error = "User not found";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - Login</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .login-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #0d47a1 0%, #1565c0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        .login-header {
            background: #0d47a1;
            color: white;
            padding: 30px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
            transform: rotate(45deg);
        }

        .login-header h1 {
            margin: 0;
            font-size: 2em;
            font-weight: 600;
            position: relative;
        }

        .login-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 1em;
            position: relative;
        }

        .login-form {
            padding: 40px 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: #0d47a1;
            box-shadow: 0 0 0 3px rgba(13, 71, 161, 0.1);
        }

        .btn-primary {
            background: #0d47a1;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            width: 100%;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #1565c0;
            transform: translateY(-2px);
        }

        .login-footer {
            text-align: center;
            padding: 20px;
            color: #666;
            border-top: 1px solid #eee;
        }

        .register-link {
            display: block;
            margin-top: 15px;
            color: #0d47a1;
            text-decoration: none;
            font-weight: 500;
        }

        .register-link:hover {
            text-decoration: underline;
        }

        .forgot-password-link {
            display: block;
            margin-top: 10px;
            color: #666;
            text-decoration: none;
            font-size: 0.9em;
        }

        .forgot-password-link:hover {
            color: #0d47a1;
            text-decoration: underline;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            animation: fadeIn 0.3s ease-out;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-book-reader"></i> Library Management System</h1>
            <p>Access your library dashboard</p>
        </div>
        
        <div class="login-form">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>

                <a href="forgot_password.php" class="forgot-password-link">
                    <i class="fas fa-key"></i> Forgot your password?
                </a>

                <a href="register.php" class="register-link">
                    <i class="fas fa-user-plus"></i> Don't have an account? Register here
                </a>
            </form>
            
            <div class="login-footer">
                <p>&copy; 2025 Library Management System</p>
            </div>
        </div>
    </div>
</body>
</html>
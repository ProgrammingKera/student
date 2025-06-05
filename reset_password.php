<?php
session_start();
include 'includes/config.php';

$message = '';
$messageType = '';
$validToken = false;
$token = isset($_GET['token']) ? $_GET['token'] : '';

// Verify token
if (!empty($token)) {
    $stmt = $conn->prepare("
        SELECT pr.*, u.email 
        FROM password_resets pr
        JOIN users u ON pr.user_id = u.id
        WHERE pr.token = ? AND pr.expires_at > NOW()
        LIMIT 1
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $reset = $result->fetch_assoc();
        $validToken = true;
    } else {
        $message = "Invalid or expired reset token.";
        $messageType = "danger";
    }
}

// Process password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $validToken) {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
        $messageType = "danger";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
        $messageType = "danger";
    } else {
        // Update password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $reset['user_id']);
        
        if ($stmt->execute()) {
            // Delete used token
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            
            $message = "Password has been reset successfully. You can now login with your new password.";
            $messageType = "success";
            
            // Redirect to login page after 3 seconds
            header("refresh:3;url=index.php");
        } else {
            $message = "Error resetting password. Please try again.";
            $messageType = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Library Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .reset-password-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #0d47a1 0%, #1565c0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .reset-password-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        .reset-password-header {
            background: #0d47a1;
            color: white;
            padding: 30px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .reset-password-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
            transform: rotate(45deg);
        }

        .reset-password-header h1 {
            margin: 0;
            font-size: 2em;
            font-weight: 600;
            position: relative;
        }

        .reset-password-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 1em;
            position: relative;
        }

        .reset-password-form {
            padding: 40px 30px;
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
    </style>
</head>
<body class="reset-password-page">
    <div class="reset-password-container">
        <div class="reset-password-header">
            <h1><i class="fas fa-key"></i> Reset Password</h1>
            <p>Enter your new password</p>
        </div>
        
        <div class="reset-password-form">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($validToken): ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock"></i> New Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                        <small class="text-muted">Password must be at least 6 characters long</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password"><i class="fas fa-lock"></i> Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Reset Password
                    </button>
                </form>
            <?php else: ?>
                <div class="text-center">
                    <a href="forgot_password.php" class="btn btn-primary">
                        <i class="fas fa-redo"></i> Request New Reset Link
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-link">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>
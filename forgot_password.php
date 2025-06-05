<?php
session_start();
include 'includes/config.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store reset token
        $stmt = $conn->prepare("
            INSERT INTO password_resets (user_id, token, expires_at)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iss", $user['id'], $token, $expires);
        
        if ($stmt->execute()) {
            // In a real application, you would send an email here
            // For this demo, we'll just show the reset link
            $resetLink = "http://{$_SERVER['HTTP_HOST']}/reset_password.php?token=" . $token;
            
            $message = "Password reset instructions have been sent to your email address. For demo purposes, here's your reset link: <a href='$resetLink'>Reset Password</a>";
            $messageType = "success";
        } else {
            $message = "Error generating reset token. Please try again.";
            $messageType = "danger";
        }
    } else {
        $message = "No account found with this email address.";
        $messageType = "danger";
    }
}

// Create password_resets table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_token (token)
)";
$conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Library Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .forgot-password-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #0d47a1 0%, #1565c0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .forgot-password-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        .forgot-password-header {
            background: #0d47a1;
            color: white;
            padding: 30px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .forgot-password-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
            transform: rotate(45deg);
        }

        .forgot-password-header h1 {
            margin: 0;
            font-size: 2em;
            font-weight: 600;
            position: relative;
        }

        .forgot-password-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 1em;
            position: relative;
        }

        .forgot-password-form {
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
<body class="forgot-password-page">
    <div class="forgot-password-container">
        <div class="forgot-password-header">
            <h1><i class="fas fa-key"></i> Forgot Password</h1>
            <p>Enter your email to reset your password</p>
        </div>
        
        <div class="forgot-password-form">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Reset Instructions
                </button>
                
                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-link">
                        <i class="fas fa-arrow-left"></i> Back to Login
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
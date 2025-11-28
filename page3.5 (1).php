<?php
ob_start();

include 'page3.1.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: page1.php");
    exit();
}

// Check if user came from forgot password page
if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['reset_username'])) {
    ob_end_clean();
    header("Location: page3.4.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dob = trim($_POST['dob'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    if (!empty($dob) && !empty($password) && !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            $error = "Passwords do not match";
        } else if (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long";
        } else {
            // Verify DOB
            $user_id = $_SESSION['reset_user_id'];
            $stmt = $conn->prepare("SELECT dob FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if ($user['dob'] === $dob) {
                    // Update password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update_stmt->bind_param("si", $hashed_password, $user_id);
                    
                    if ($update_stmt->execute()) {
                        $success = "Password updated successfully! Redirecting to login...";
                        // Clear reset session
                        unset($_SESSION['reset_user_id']);
                        unset($_SESSION['reset_username']);
                        unset($_SESSION['reset_dob']);
                        $update_stmt->close();
                        
                        // Redirect after 2 seconds
                        ob_end_clean();
                        header("refresh:2;url=page3.2.php");
                        exit();
                    } else {
                        $error = "Error updating password. Please try again.";
                    }
                    $update_stmt->close();
                } else {
                    $error = "Incorrect date of birth. Please try again.";
                }
            } else {
                $error = "User not found";
            }
            $stmt->close();
        }
    } else {
        $error = "Please fill in all fields";
    }
}

ob_end_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitAI - Reset Password</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .container {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            padding: 30px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo h1 {
            color: #1a2a6c;
            font-size: 28px;
            font-weight: 700;
        }
        
        .logo span {
            color: #fdbb2d;
        }
        
        .form-group {
            margin-bottom: 20px;
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
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            border-color: #1a2a6c;
            outline: none;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(to right, #1a2a6c, #b21f1f);
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .links {
            text-align: center;
            margin-top: 20px;
        }
        
        .links a {
            color: #1a2a6c;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .links a:hover {
            color: #b21f1f;
            text-decoration: underline;
        }
        
        .error {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #c62828;
        }
        
        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #2e7d32;
        }
        
        .info {
            background-color: #e3f2fd;
            color: #1565c0;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            border-left: 4px solid #1565c0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>Fit<span>AI</span></h1>
            <p>Reset your password</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (empty($success)): ?>
            <div class="info">
                Resetting password for: <?php echo htmlspecialchars($_SESSION['reset_username'] ?? ''); ?>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="dob">Date of Birth (for verification)</label>
                    <input type="date" id="dob" name="dob" required>
                </div>
                
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>
                
                <button type="submit" class="btn">Reset Password</button>
                
                <div class="links">
                    <a href="page3.2.php">Back to Login</a>
                </div>
            </form>
        <?php else: ?>
            <div class="links">
                <a href="page3.2.php">Go to Login</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const dob = document.getElementById('dob').value;
            const password = document.getElementById('password').value;
            const confirm_password = document.getElementById('confirm_password').value;
            
            if (!dob || !password || !confirm_password) {
                alert('Please fill in all fields');
                e.preventDefault();
                return;
            }
            
            if (password.length < 6) {
                alert('Password must be at least 6 characters long');
                e.preventDefault();
                return;
            }
            
            if (password !== confirm_password) {
                alert('Passwords do not match');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>
<?php
// Start session and include database connection
include 'page3.1.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: page1.php");
    exit();
}

$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username']);
    
    if (!empty($username)) {
        // Check if username exists (using email column)
        $stmt = $conn->prepare("SELECT id, dob FROM users WHERE email = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // Store reset information in session
            $_SESSION['reset_user_id'] = $user['id'];
            $_SESSION['reset_username'] = $username;
            $_SESSION['reset_dob'] = $user['dob'];
            
            // Redirect to next page
            header("Location: page3.5.php");
            exit();
        } else {
            $error = "Username not found. Please check your username and try again.";
        }
        $stmt->close();
    } else {
        $error = "Please enter your username";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitAI - Forgot Password</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #1a2a6c, #4dfafaff, #5770b6ff);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .container {
            background-color: rgba(171, 241, 243, 0.9);
            border-radius: 10px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            padding: 30px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #1a2a6c;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: #666;
            font-size: 16px;
        }
        
        .logo span {
            color: #fdbb2d;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 15px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
            background-color: #f9f9f9;
        }
        
        .form-group input:focus {
            border-color: #1a2a6c;
            background-color: white;
            outline: none;
            box-shadow: 0 0 0 3px rgba(26, 42, 108, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(to right, #1a2a6c, #b21f1f);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .links {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e1e1e1;
        }
        
        .links a {
            color: #1a2a6c;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .links a:hover {
            color: #b21f1f;
            text-decoration: underline;
        }
        
        .error {
            background-color: #ffebee;
            color: #c62828;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #c62828;
            animation: shake 0.5s;
        }
        
        .instructions {
            background-color: #e3f2fd;
            color: #1565c0;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            border-left: 4px solid #1565c0;
        }
        
        .debug-info {
            background-color: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 12px;
            border-left: 4px solid #ffc107;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>Fit<span>AI</span></h1>
            <p>Reset your password</p>
        </div>
        
        <div class="instructions">
            Enter your username to reset your password
        </div>

        <!-- Debug Information -->
        <div class="debug-info">
            <strong>Debug Info:</strong><br>
            Form Submitted: <?php echo ($_SERVER['REQUEST_METHOD'] === 'POST') ? 'Yes' : 'No'; ?><br>
            Username Entered: <?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : 'None'; ?><br>
            PHP Session ID: <?php echo session_id(); ?>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- SIMPLE FORM WITHOUT JAVASCRIPT INTERFERENCE -->
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required placeholder="Enter your username">
            </div>
            
            <button type="submit" class="btn" id="submitBtn">Continue</button>
            
            <div class="links">
                <a href="page3.2.php">Back to Login</a>
            </div>
        </form>
    </div>

    <!-- SIMPLE JAVASCRIPT WITHOUT PREVENTING DEFAULT -->
    <script>
        // Simple loading indicator without preventing form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = 'Checking...';
            btn.disabled = true;
            
            // Let the form submit normally - don't prevent default
            // The form will submit to the server as a POST request
        });
    </script>
</body>
</html>
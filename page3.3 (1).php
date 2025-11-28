<?php
include 'page3.1.php';

// Redirect if already logged in (session is already started in page3.1.php)
if (isset($_SESSION['user_id'])) {
    header("Location: page1.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $dob = $_POST['dob'];
    
    // Validate all fields are filled
    if (!empty($username) && !empty($password) && !empty($confirm_password) && !empty($dob)) {
        
        // Validate password match
        if ($password !== $confirm_password) {
            $error = "Passwords do not match";
        } 
        // Validate password length
        elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long";
        }
        // Validate username length
        elseif (strlen($username) < 3) {
            $error = "Username must be at least 3 characters long";
        }
        // Validate date of birth (must be at least 13 years old)
        elseif (strtotime($dob) > strtotime('-13 years')) {
            $error = "You must be at least 13 years old to register";
        }
        else {
            try {
                // Check if username already exists (using email column)
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error = "Username already taken. Please choose a different one.";
                } else {
                    // Hash password and insert new user (without first_login column)
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (email, password, dob, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->bind_param("sss", $username, $hashed_password, $dob);
                    
                    if ($stmt->execute()) {
                        // Get the newly created user ID
                        $user_id = $stmt->insert_id;
                        
                        // Create default workout schedule for the new user
                        createDefaultWorkoutSchedule($conn, $user_id);
                        
                        $success = "Account created successfully! You can now login.";
                        // Clear form fields
                        $username = $dob = '';
                    } else {
                        $error = "Error creating account. Please try again.";
                    }
                }
                $stmt->close();
            } catch (Exception $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    } else {
        $error = "Please fill in all fields";
    }
}

// Function to create default workout schedule for new users
function createDefaultWorkoutSchedule($conn, $user_id) {
    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    
    foreach ($days as $day) {
        // Insert workout day
        $stmt = $conn->prepare("INSERT INTO workouts (user_id, day, is_break) VALUES (?, ?, ?)");
        $is_break = ($day === 'Sunday') ? 1 : 0; // Make Sunday a break day by default
        $stmt->bind_param("iss", $user_id, $day, $is_break);
        $stmt->execute();
        $workout_id = $stmt->insert_id;
        $stmt->close();
        
        // If it's not a break day, add some default exercises
        if (!$is_break) {
            $default_exercises = [
                ['Push-ups', 3, 15, 3, 1],
                ['Squats', 3, 12, 4, 1],
                ['Plank', 3, 1, 60, 1] // 1 rep for time-based exercises
            ];
            
            foreach ($default_exercises as $exercise) {
                $stmt = $conn->prepare("INSERT INTO exercises (workout_id, name, sets, reps, rest_between_reps_sec, rest_between_sets_min) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isiiii", $workout_id, $exercise[0], $exercise[1], $exercise[2], $exercise[3], $exercise[4]);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitAI - Sign Up</title>
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
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
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
        
        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #2e7d32;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .age-requirement {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }
            
            .logo h1 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>Fit<span>AI</span></h1>
            <p>Create your account</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required placeholder="Enter your username">
                <div class="password-requirements">Must be at least 3 characters</div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
                <div class="password-requirements">Must be at least 6 characters</div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
            </div>
            
            <div class="form-group">
                <label for="dob">Date of Birth</label>
                <input type="date" id="dob" name="dob" value="<?php echo isset($_POST['dob']) ? htmlspecialchars($_POST['dob']) : ''; ?>" required max="<?php echo date('Y-m-d', strtotime('-13 years')); ?>">
                <div class="age-requirement">You must be at least 13 years old</div>
            </div>
            
            <button type="submit" class="btn">Create Account</button>
            
            <div class="links">
                <a href="page3.2.php">Already have an account? Login here</a>
            </div>
        </form>
    </div>

    <script>
        // Simple client-side validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const username = document.getElementById('username').value;
            const dob = document.getElementById('dob').value;
            
            if (username.length < 3) {
                alert('Username must be at least 3 characters long');
                e.preventDefault();
                return;
            }
            
            if (password.length < 6) {
                alert('Password must be at least 6 characters long');
                e.preventDefault();
                return;
            }
            
            if (password !== confirmPassword) {
                alert('Passwords do not match');
                e.preventDefault();
                return;
            }
            
            // Age validation (13 years old)
            const dobDate = new Date(dob);
            const today = new Date();
            const age = today.getFullYear() - dobDate.getFullYear();
            const monthDiff = today.getMonth() - dobDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dobDate.getDate())) {
                age--;
            }
            
            if (age < 13) {
                alert('You must be at least 13 years old to register');
                e.preventDefault();
                return;
            }
        });

        // Set max date for date of birth (13 years ago)
        document.getElementById('dob').max = new Date(new Date().setFullYear(new Date().getFullYear() - 13)).toISOString().split('T')[0];
    </script>
</body>
</html>
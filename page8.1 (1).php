<?php
session_start();
require 'page3.1.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: page3.2.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$request_sent = false;
$selected_day = $_GET['day'] ?? 'Sunday'; // Default to Sunday

// Get user info - include created_at field
$stmt = $conn->prepare("SELECT email, dob, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle workout request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_email'])) {
    $request_input = trim($_POST['request_email']);
    
    if (!empty($request_input)) {
        // Check if user is trying to request from themselves
        if ($request_input === $user['email']) {
            $error = "You cannot request a workout from yourself.";
        } else {
            // Check if email/username exists in database
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $request_input);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $target_user = $result->fetch_assoc();
                $target_id = $target_user['id'];
                
                // Check if request already exists (any status)
                $stmt = $conn->prepare("SELECT id, status FROM workout_requests WHERE requester_id = ? AND target_id = ?");
                $stmt->bind_param("ii", $user_id, $target_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    // Create new request
                    $stmt = $conn->prepare("INSERT INTO workout_requests (requester_id, target_id, status, created_at) VALUES (?, ?, 'pending', NOW())");
                    $stmt->bind_param("ii", $user_id, $target_id);
                    
                    if ($stmt->execute()) {
                        $success = "Workout request sent to " . htmlspecialchars($request_input);
                        $request_sent = true;
                    } else {
                        $error = "Error sending request. Please try again.";
                    }
                } else {
                    $request_data = $result->fetch_assoc();
                    if ($request_data['status'] === 'pending') {
                        $error = "You have already sent a pending request to this user.";
                    } elseif ($request_data['status'] === 'approved') {
                        $error = "Your previous request to this user was already approved. You can send a new request if needed.";
                    }
                }
            } else {
                $error = "No user found with that username/email address.";
            }
        }
    } else {
        $error = "Please enter a username or email address.";
    }
}

// Handle request approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_request'])) {
    $request_id = intval($_POST['request_id']);
    
    // Get request details and verify it's valid
    $stmt = $conn->prepare("SELECT requester_id, target_id, status FROM workout_requests WHERE id = ? AND target_id = ?");
    $stmt->bind_param("ii", $request_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $request = $result->fetch_assoc();
        
        if ($request['status'] === 'pending') {
            // Check if the target user (current user) has any workouts to share
            $stmt = $conn->prepare("SELECT COUNT(*) as workout_count FROM workouts WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $workout_data = $result->fetch_assoc();
            
            if ($workout_data['workout_count'] > 0) {
                // Copy workouts from target to requester
                if (copyWorkouts($request['requester_id'], $user_id)) {
                    // Update request status to approved
                    $stmt = $conn->prepare("UPDATE workout_requests SET status = 'approved' WHERE id = ?");
                    $stmt->bind_param("i", $request_id);
                    
                    if ($stmt->execute()) {
                        $success = "Workout plan shared successfully!";
                    } else {
                        $error = "Error updating request status.";
                    }
                } else {
                    $error = "Error copying workout plan. Please try again.";
                }
            } else {
                $error = "You don't have any workout plans to share.";
            }
        } else {
            $error = "This request has already been processed.";
        }
    } else {
        $error = "Invalid request or you don't have permission to approve this request.";
    }
}

// Handle request deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_request'])) {
    $request_id = intval($_POST['request_id']);
    
    // Verify the user has permission to delete this request
    $stmt = $conn->prepare("SELECT id FROM workout_requests WHERE id = ? AND (requester_id = ? OR target_id = ?)");
    $stmt->bind_param("iii", $request_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        // Delete the request
        $stmt = $conn->prepare("DELETE FROM workout_requests WHERE id = ?");
        $stmt->bind_param("i", $request_id);
        
        if ($stmt->execute()) {
            $success = "Request removed successfully.";
        } else {
            $error = "Error removing request. Please try again.";
        }
    } else {
        $error = "Invalid request or you don't have permission to delete this request.";
    }
}

// Function to copy workouts from one user to another
function copyWorkouts($to_user_id, $from_user_id) {
    global $conn;
    
    try {
        // Start transaction
        $conn->autocommit(FALSE);
        
        // Delete existing workouts for the target user
        $stmt = $conn->prepare("SELECT id FROM workouts WHERE user_id = ?");
        $stmt->bind_param("i", $to_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $workout_ids = [];
        while ($workout = $result->fetch_assoc()) {
            $workout_ids[] = $workout['id'];
        }
        
        // Delete exercises for these workouts
        if (!empty($workout_ids)) {
            $placeholders = implode(',', array_fill(0, count($workout_ids), '?'));
            $stmt = $conn->prepare("DELETE FROM exercises WHERE workout_id IN ($placeholders)");
            $stmt->bind_param(str_repeat('i', count($workout_ids)), ...$workout_ids);
            $stmt->execute();
        }
        
        // Delete workouts
        $stmt = $conn->prepare("DELETE FROM workouts WHERE user_id = ?");
        $stmt->bind_param("i", $to_user_id);
        $stmt->execute();
        
        // Copy workouts from source user
        $stmt = $conn->prepare("SELECT id, day, is_break FROM workouts WHERE user_id = ? ORDER BY FIELD(day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')");
        $stmt->bind_param("i", $from_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($workout = $result->fetch_assoc()) {
            $stmt2 = $conn->prepare("INSERT INTO workouts (user_id, day, is_break) VALUES (?, ?, ?)");
            $stmt2->bind_param("isi", $to_user_id, $workout['day'], $workout['is_break']);
            $stmt2->execute();
            $new_workout_id = $conn->insert_id;
            
            // Copy exercises if not a break day
            if (!$workout['is_break']) {
                $stmt3 = $conn->prepare("SELECT name, sets, reps, rest_between_reps_sec, rest_between_sets_min FROM exercises WHERE workout_id = ?");
                $stmt3->bind_param("i", $workout['id']);
                $stmt3->execute();
                $exercises = $stmt3->get_result();
                
                while ($exercise = $exercises->fetch_assoc()) {
                    $stmt4 = $conn->prepare("INSERT INTO exercises (workout_id, name, sets, reps, rest_between_reps_sec, rest_between_sets_min) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt4->bind_param("isiiii", $new_workout_id, $exercise['name'], $exercise['sets'], $exercise['reps'], $exercise['rest_between_reps_sec'], $exercise['rest_between_sets_min']);
                    $stmt4->execute();
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        $conn->autocommit(TRUE);
        return true;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $conn->autocommit(TRUE);
        return false;
    }
}

// Get workout requests - Improved queries with error handling
$received_requests = [];
$sent_requests = [];

// Get requests where current user is the target (received requests)
$stmt = $conn->prepare("SELECT wr.id, u.email, wr.status, wr.created_at 
                       FROM workout_requests wr 
                       JOIN users u ON wr.requester_id = u.id 
                       WHERE wr.target_id = ? 
                       ORDER BY wr.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $received_requests[] = $row;
}

// Get requests where current user is the requester (sent requests)  
$stmt = $conn->prepare("SELECT wr.id, u.email, wr.status, wr.created_at 
                       FROM workout_requests wr 
                       JOIN users u ON wr.target_id = u.id 
                       WHERE wr.requester_id = ? 
                       ORDER BY wr.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $sent_requests[] = $row;
}

// Get days of the week
$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Function to get workout data for a specific day
function getWorkoutData($user_id, $day) {
    global $conn;
    
    $data = ['day' => $day, 'is_break' => false, 'exercises' => []];
    
    // Check if it's a rest day
    $stmt = $conn->prepare("SELECT id, is_break FROM workouts WHERE user_id = ? AND day = ?");
    $stmt->bind_param("is", $user_id, $day);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $data['is_break'] = (bool)$row['is_break'];
        $workout_id = $row['id'];
        
        // If not a rest day, get exercises
        if (!$data['is_break']) {
            $stmt = $conn->prepare("SELECT name, sets, reps, rest_between_reps_sec, rest_between_sets_min FROM exercises WHERE workout_id = ? ORDER BY id");
            $stmt->bind_param("i", $workout_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($exercise = $result->fetch_assoc()) {
                $data['exercises'][] = $exercise;
            }
        }
    }
    
    return $data;
}

// Get workout data for the selected day
$selected_workout = getWorkoutData($user_id, $selected_day);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitAI - Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .day-link {
            transition: all 0.3s ease;
        }
        .day-link:hover, .day-link.active {
            background-color: #4F46E5;
            color: white;
        }
        .exercise-item {
            border-left: 4px solid #4F46E5;
        }
        .request-item {
            transition: all 0.2s ease;
        }
        .request-item:hover {
            background-color: #f9fafb;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">
                <a href="page1.php" class="text-blue-600 hover:text-blue-800 mr-4">
                    <i class="fas fa-arrow-left"></i>
                </a>
                Your Profile
            </h1>
            <a href="logout.php" class="text-red-600 hover:text-red-800">
                <i class="fas fa-sign-out-alt mr-1"></i> Logout
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-exclamation-triangle mr-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column - Profile Info & Days -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="text-center">
                        <h2 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($user['email']); ?></h2>
                        <?php if (!empty($user['created_at'])): ?>
                            <p class="text-gray-600">Member since: <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Days List -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Your Workout Days</h3>
                    <div class="space-y-2">
                        <?php foreach ($days as $day): ?>
                            <a href="?day=<?php echo urlencode($day); ?>" 
                               class="block day-link p-3 rounded-md <?php echo $day === $selected_day ? 'active bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>">
                                <i class="fas fa-calendar-day mr-2"></i> <?php echo $day; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Workout Request Section -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Request Workout Plan</h3>
                    <form method="POST" onsubmit="return validateRequestForm();">
                        <div class="mb-4">
                            <label for="request_email" class="block text-sm font-medium text-gray-700 mb-1">Enter username</label>
                            <input type="text" id="request_email" name="request_email" class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="username or user@example.com" required>
                        </div>
                        <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2 rounded-md transition duration-200">
                            <i class="fas fa-paper-plane mr-2"></i> Send Request
                        </button>
                    </form>

                    <!-- Received Requests -->
                    <?php if (!empty($received_requests)): ?>
                        <div class="mt-6">
                            <h4 class="text-md font-medium text-gray-800 mb-2">
                                <i class="fas fa-inbox mr-1"></i> Requests Received (<?php echo count($received_requests); ?>)
                            </h4>
                            <div class="space-y-2">
                                <?php foreach ($received_requests as $request): ?>
                                    <div class="request-item flex justify-between items-center p-3 bg-gray-50 rounded border">
                                        <div class="flex-1">
                                            <span class="text-sm font-medium block"><?php echo htmlspecialchars($request['email']); ?></span>
                                            <span class="text-xs text-gray-500">Wants your workout • <?php echo date('M j, Y', strtotime($request['created_at'])); ?></span>
                                            <span class="text-xs px-2 py-1 rounded-full <?php echo $request['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'; ?>">
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                        </div>
                                        <div class="flex gap-1 ml-2">
                                            <?php if ($request['status'] == 'pending'): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to share your workout plan?');">
                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                    <button type="submit" name="approve_request" class="text-green-600 hover:text-green-800 text-sm px-2 py-1 rounded hover:bg-green-50" title="Approve Request">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this request?');">
                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                <button type="submit" name="delete_request" class="text-red-600 hover:text-red-800 text-sm px-2 py-1 rounded hover:bg-red-50" title="Remove Request">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Sent Requests -->
                    <?php if (!empty($sent_requests)): ?>
                        <div class="mt-6">
                            <h4 class="text-md font-medium text-gray-800 mb-2">
                                <i class="fas fa-paper-plane mr-1"></i> Requests Sent (<?php echo count($sent_requests); ?>)
                            </h4>
                            <div class="space-y-2">
                                <?php foreach ($sent_requests as $request): ?>
                                    <div class="request-item flex justify-between items-center p-3 bg-gray-50 rounded border">
                                        <div class="flex-1">
                                            <span class="text-sm font-medium block">To: <?php echo htmlspecialchars($request['email']); ?></span>
                                            <span class="text-xs text-gray-500">Requested their workout • <?php echo date('M j, Y', strtotime($request['created_at'])); ?></span>
                                            <span class="text-xs px-2 py-1 rounded-full <?php echo $request['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'; ?>">
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                        </div>
                                        <div class="ml-2">
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this request?');">
                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                <button type="submit" name="delete_request" class="text-red-600 hover:text-red-800 text-sm px-2 py-1 rounded hover:bg-red-50" title="Remove Request">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column - Exercises for Selected Day -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-6">
                        <i class="fas fa-calendar-day mr-2"></i> <?php echo $selected_day; ?> Workout
                    </h2>
                    
                    <?php if ($selected_workout['is_break']): ?>
                        <div class="bg-yellow-100 border border-yellow-200 text-yellow-700 px-4 py-3 rounded">
                            <i class="fas fa-umbrella-beach mr-2"></i> Rest Day - No exercises planned
                        </div>
                    <?php elseif (empty($selected_workout['exercises'])): ?>
                        <div class="bg-blue-100 border border-blue-200 text-blue-700 px-4 py-3 rounded">
                            <i class="fas fa-info-circle mr-2"></i> No exercises planned for this day
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($selected_workout['exercises'] as $index => $exercise): ?>
                                <div class="exercise-item bg-white p-4 rounded shadow-sm border">
                                    <div class="flex justify-between items-start mb-3">
                                        <h3 class="font-semibold text-gray-800 text-lg"><?php echo htmlspecialchars($exercise['name']); ?></h3>
                                        <span class="text-sm text-gray-500 bg-gray-100 px-2 py-1 rounded">#<?php echo $index + 1; ?></span>
                                    </div>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                        <div class="bg-gray-50 p-3 rounded text-center">
                                            <span class="text-gray-600 block text-xs">Sets</span>
                                            <span class="font-bold text-xl text-blue-600"><?php echo $exercise['sets']; ?></span>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded text-center">
                                            <span class="text-gray-600 block text-xs">Reps</span>
                                            <span class="font-bold text-xl text-green-600"><?php echo $exercise['reps']; ?></span>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded text-center">
                                            <span class="text-gray-600 block text-xs">Rep Rest</span>
                                            <span class="font-bold text-xl text-orange-600"><?php echo $exercise['rest_between_reps_sec']; ?>s</span>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded text-center">
                                            <span class="text-gray-600 block text-xs">Set Rest</span>
                                            <span class="font-bold text-xl text-purple-600"><?php echo $exercise['rest_between_sets_min']; ?>m</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    function validateRequestForm() {
        const input = document.getElementById('request_email').value.trim();
        const currentUserEmail = '<?php echo addslashes($user['email']); ?>';
        
        if (input === currentUserEmail) {
            alert('You cannot request a workout from yourself!');
            return false;
        }
        
        return true;
    }
    </script>
</body>
</html>
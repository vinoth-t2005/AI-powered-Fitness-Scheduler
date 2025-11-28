<?php
session_start();
require 'page3.1.php'; // Your database connection file

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Function to get workout data for a specific day
function getWorkoutData($user_id, $day) {
    global $conn;
    
    $data = ['day' => $day, 'is_break' => false, 'exercises' => []];
    
    // Check if it's a rest day
    $stmt = $conn->prepare("SELECT is_break FROM workouts WHERE user_id = ? AND day = ?");
    $stmt->bind_param("is", $user_id, $day);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $data['is_break'] = (bool)$row['is_break'];
        
        // If not a rest day, get exercises
        if (!$data['is_break']) {
            $stmt = $conn->prepare("SELECT id FROM workouts WHERE user_id = ? AND day = ?");
            $stmt->bind_param("is", $user_id, $day);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($workout = $result->fetch_assoc()) {
                $workout_id = $workout['id'];
                
                $stmt = $conn->prepare("SELECT name, sets, reps, rest_between_reps_sec, rest_between_sets_min FROM exercises WHERE workout_id = ?");
                $stmt->bind_param("i", $workout_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($exercise = $result->fetch_assoc()) {
                    $data['exercises'][] = $exercise;
                }
            }
        }
    }
    
    return $data;
}

// Function to delete a workout day
function deleteWorkout($user_id, $day) {
    global $conn;
    
    // First get workout ID to delete exercises
    $stmt = $conn->prepare("SELECT id FROM workouts WHERE user_id = ? AND day = ?");
    $stmt->bind_param("is", $user_id, $day);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($workout = $result->fetch_assoc()) {
        $workout_id = $workout['id'];
        
        // Delete exercises
        $stmt = $conn->prepare("DELETE FROM exercises WHERE workout_id = ?");
        $stmt->bind_param("i", $workout_id);
        $stmt->execute();
        
        // Delete workout
        $stmt = $conn->prepare("DELETE FROM workouts WHERE id = ?");
        $stmt->bind_param("i", $workout_id);
        $stmt->execute();
    }
}

// Function to create a rest day
function createRestDay($user_id, $day) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO workouts (user_id, day, is_break) VALUES (?, ?, 1)");
    $stmt->bind_param("is", $user_id, $day);
    $stmt->execute();
}

// Function to create a workout day
function createWorkoutDay($user_id, $day) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO workouts (user_id, day, is_break) VALUES (?, ?, 0)");
    $stmt->bind_param("is", $user_id, $day);
    $stmt->execute();
    
    return $conn->insert_id;
}

// Function to create an exercise
function createExercise($workout_id, $exercise) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO exercises (workout_id, name, sets, reps, rest_between_reps_sec, rest_between_sets_min) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isiiii", $workout_id, $exercise['name'], $exercise['sets'], $exercise['reps'], 
                     $exercise['rest_between_reps_sec'], $exercise['rest_between_sets_min']);
    $stmt->execute();
}

$user_id = $_SESSION['user_id'];
$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Handle copy-paste actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['copy_day'])) {
        // Store the copied day data in session
        $day_to_copy = $_POST['copy_day'];
        $_SESSION['copied_workout'] = getWorkoutData($user_id, $day_to_copy);
        $_SESSION['message'] = "Workout for $day_to_copy has been copied!";
    } elseif (isset($_POST['paste_day'])) {
        $day_to_paste = $_POST['paste_day'];
        
        // Check if we have copied data
        if (isset($_SESSION['copied_workout'])) {
            $copied_data = $_SESSION['copied_workout'];
            
            // First delete existing workout for this day
            deleteWorkout($user_id, $day_to_paste);
            
            // Create new workout with copied data
            if ($copied_data['is_break']) {
                // It's a rest day
                createRestDay($user_id, $day_to_paste);
            } else {
                // It's a workout day with exercises
                $workout_id = createWorkoutDay($user_id, $day_to_paste);
                foreach ($copied_data['exercises'] as $exercise) {
                    createExercise($workout_id, $exercise);
                }
            }
            
            // Show success message
            $_SESSION['message'] = "Workout for $day_to_paste has been updated with copied data!";
        } else {
            $_SESSION['message'] = "No workout data copied to paste!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout Planner | Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .btn-small {
            padding: 0.35rem 0.75rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= $_SESSION['message'] ?>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Workout Planner</h1>
            <a href="logout.php" class="text-red-600 hover:text-red-800">
                <i class="fas fa-sign-out-alt mr-1"></i> Logout
            </a>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <?php foreach ($days as $day): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden transition-transform hover:scale-105">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4"><?= $day ?></h2>
                        <div class="flex justify-between items-center">
                            <div class="flex flex-col space-y-2">
                                <!-- Copy button (top left) -->
                                <form method="post" class="inline">
                                    <button type="submit" name="copy_day" value="<?= $day ?>" 
                                       class="btn-small bg-yellow-500 hover:bg-yellow-600 text-white rounded-md transition-colors w-full">
                                        <i class="fas fa-copy mr-1"></i> Copy
                                    </button>
                                </form>
                                
                                <!-- Paste button (bottom left) -->
                                <form method="post" class="inline">
                                    <button type="submit" name="paste_day" value="<?= $day ?>" 
                                       class="btn-small bg-indigo-500 hover:bg-indigo-600 text-white rounded-md transition-colors w-full">
                                        <i class="fas fa-paste mr-1"></i> Paste
                                    </button>
                                </form>
                            </div>
                            
                            <div class="flex space-x-2">
                                <!-- Edit button -->
                                <a href="page4.2.php?day=<?= urlencode($day) ?>" 
                                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                                    <i class="fas fa-edit mr-1"></i> Edit
                                </a>
                                
                                <!-- View button -->
                                <a href="page4.6.php?day=<?= urlencode($day) ?>" 
                                   class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition-colors">
                                    <i class="fas fa-eye mr-1"></i> View
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-8 text-center space-x-4">
            <a href="page1.php" 
               class="inline-block bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg shadow-lg transition-colors">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="page4.3.php" 
               class="inline-block bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg shadow-lg transition-colors">
                <i class="fas fa-calendar-alt mr-1"></i> Weekly Summary
            </a>
        </div>
    </div>
</body>
</html>
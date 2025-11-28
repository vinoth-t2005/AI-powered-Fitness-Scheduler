<?php
session_start();
require 'page3.1.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$day = $_GET['day'] ?? '';
$error = '';
$success = '';

// Validate day
$valid_days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
if (!in_array($day, $valid_days)) {
    header("Location: dashboard.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_exercise'])) {
        // Check if day is set as break day before allowing exercise addition
        $stmt = $conn->prepare("SELECT is_break FROM workouts WHERE user_id = ? AND day = ?");
        $stmt->bind_param("is", $user_id, $day);
        $stmt->execute();
        $result = $stmt->get_result();
        $is_break = false;
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $is_break = (bool)$row['is_break'];
        }
        
        if ($is_break) {
            $error = "Cannot add exercises to a break day. Please remove break day status first.";
        } else {
            $name = trim($_POST['exercise_name']);
            $sets = (int)$_POST['sets'];
            $reps = (int)$_POST['reps'];
            $rest_reps = (int)$_POST['rest_between_reps'];
            $rest_sets = (int)$_POST['rest_between_sets'];

            // Validate inputs
            if (empty($name) || $sets < 1 || $reps < 1 || $rest_reps < 0 || $rest_sets < 0) {
                $error = "Please fill all fields with valid values";
            } else {
                // Check if workout exists for this day
                $stmt = $conn->prepare("SELECT id FROM workouts WHERE user_id = ? AND day = ?");
                $stmt->bind_param("is", $user_id, $day);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    // Create workout if it doesn't exist
                    $stmt = $conn->prepare("INSERT INTO workouts (user_id, day, is_break) VALUES (?, ?, FALSE)");
                    $stmt->bind_param("is", $user_id, $day);
                    $stmt->execute();
                    $workout_id = $conn->insert_id;
                } else {
                    $row = $result->fetch_assoc();
                    $workout_id = $row['id'];
                }

                // Add exercise
                $stmt = $conn->prepare("INSERT INTO exercises (workout_id, name, sets, reps, rest_between_reps_sec, rest_between_sets_min) 
                                       VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isiiii", $workout_id, $name, $sets, $reps, $rest_reps, $rest_sets);
                
                if ($stmt->execute()) {
                    $success = "Exercise added successfully!";
                } else {
                    $error = "Error adding exercise: " . $conn->error;
                }
            }
        }
    } elseif (isset($_POST['set_break_day'])) {
        // Begin transaction to ensure data consistency
        $conn->begin_transaction();
        
        try {
            // First, check if workout exists for this day
            $stmt = $conn->prepare("SELECT id FROM workouts WHERE user_id = ? AND day = ?");
            $stmt->bind_param("is", $user_id, $day);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $workout_id = $row['id'];
                
                // Delete all exercises for this workout
                $stmt = $conn->prepare("DELETE FROM exercises WHERE workout_id = ?");
                $stmt->bind_param("i", $workout_id);
                $stmt->execute();
                
                // Set as break day
                $stmt = $conn->prepare("UPDATE workouts SET is_break = TRUE WHERE id = ?");
                $stmt->bind_param("i", $workout_id);
                $stmt->execute();
            } else {
                // Create new workout as break day
                $stmt = $conn->prepare("INSERT INTO workouts (user_id, day, is_break) VALUES (?, ?, TRUE)");
                $stmt->bind_param("is", $user_id, $day);
                $stmt->execute();
            }
            
            $conn->commit();
            $success = "Day set as break day successfully! All exercises have been removed.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error setting break day: " . $e->getMessage();
        }
    } elseif (isset($_POST['remove_break_day'])) {
        // Remove break day status
        $stmt = $conn->prepare("UPDATE workouts SET is_break = FALSE WHERE user_id = ? AND day = ?");
        $stmt->bind_param("is", $user_id, $day);
        
        if ($stmt->execute()) {
            $success = "Break day status removed successfully! You can now add exercises.";
        } else {
            $error = "Error removing break day status: " . $conn->error;
        }
    }
}

// Get workout data for this day
$workout = [
    'is_break' => false,
    'exercises' => []
];

$stmt = $conn->prepare("SELECT w.is_break, e.id, e.name, e.sets, e.reps, e.rest_between_reps_sec, e.rest_between_sets_min 
                       FROM workouts w 
                       LEFT JOIN exercises e ON e.workout_id = w.id 
                       WHERE w.user_id = ? AND w.day = ?
                       ORDER BY e.id");
$stmt->bind_param("is", $user_id, $day);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['is_break'] !== null) {
            $workout['is_break'] = (bool)$row['is_break'];
        }
        if ($row['name'] !== null) {
            $workout['exercises'][] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'sets' => $row['sets'],
                'reps' => $row['reps'],
                'rest_between_reps_sec' => $row['rest_between_reps_sec'],
                'rest_between_sets_min' => $row['rest_between_sets_min']
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout Planner | <?= htmlspecialchars($day) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">
                <a href="page4.1.php" class="text-blue-600 hover:text-blue-800 mr-4">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <?= htmlspecialchars($day) ?> Workout
            </h1>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-exclamation-triangle mr-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <?php if ($workout['is_break']): ?>
                <div class="text-center py-8">
                    <i class="fas fa-umbrella-beach text-5xl text-yellow-500 mb-4"></i>
                    <h3 class="text-2xl font-semibold text-gray-800 mb-2">Rest Day</h3>
                    <p class="text-gray-600 mb-4">This day is set as a recovery day with no exercises.</p>
                    <p class="text-sm text-gray-500 mb-6">All previous exercises have been removed from this day.</p>
                    <form method="POST">
                        <button type="submit" name="remove_break_day" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-colors">
                            <i class="fas fa-play mr-2"></i>Remove Break Day & Enable Exercises
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Add New Exercise</h2>
                <form method="POST" class="mb-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="exercise_name" class="block text-gray-700 mb-2">Exercise Name</label>
                            <input type="text" id="exercise_name" name="exercise_name" 
                                   class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div>
                            <label for="sets" class="block text-gray-700 mb-2">Sets</label>
                            <input type="number" id="sets" name="sets" min="1" 
                                   class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div>
                            <label for="reps" class="block text-gray-700 mb-2">Reps per Set</label>
                            <input type="number" id="reps" name="reps" min="1" 
                                   class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div>
                            <label for="rest_between_reps" class="block text-gray-700 mb-2">Rest Between Reps (sec)</label>
                            <input type="number" id="rest_between_reps" name="rest_between_reps" min="0" 
                                   class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div>
                            <label for="rest_between_sets" class="block text-gray-700 mb-2">Rest Between Sets (min)</label>
                            <input type="number" id="rest_between_sets" name="rest_between_sets" min="0" 
                                   class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" name="add_exercise" 
                                class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md transition-colors">
                            <i class="fas fa-plus mr-2"></i> Add Exercise
                        </button>
                    </div>
                </form>

                <div class="mt-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Current Exercises</h2>
                    
                    <?php if (empty($workout['exercises'])): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-dumbbell text-4xl mb-4"></i>
                            <p>No exercises added yet for this day.</p>
                            <p class="text-sm mt-2">Add your first exercise above or set this day as a break day.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($workout['exercises'] as $exercise): ?>
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="font-semibold text-lg text-gray-800"><?= htmlspecialchars($exercise['name']) ?></h3>
                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2">
                                                <div>
                                                    <span class="text-gray-600">Sets:</span>
                                                    <span class="font-medium"><?= $exercise['sets'] ?></span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-600">Reps:</span>
                                                    <span class="font-medium"><?= $exercise['reps'] ?></span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-600">Rep Rest:</span>
                                                    <span class="font-medium"><?= $exercise['rest_between_reps_sec'] ?> sec</span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-600">Set Rest:</span>
                                                    <span class="font-medium"><?= $exercise['rest_between_sets_min'] ?> min</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex space-x-2">
                                            <a href="page4.4.php?id=<?= $exercise['id'] ?>" 
                                               class="text-blue-600 hover:text-blue-800 p-2 rounded-md hover:bg-blue-50 transition-colors"
                                               title="Edit Exercise">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="page4.5.php?id=<?= $exercise['id'] ?>" 
                                               class="text-red-600 hover:text-red-800 p-2 rounded-md hover:bg-red-50 transition-colors" 
                                               title="Delete Exercise"
                                               onclick="return confirm('Are you sure you want to delete this exercise?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-200">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-yellow-600 mt-1 mr-3"></i>
                            <div>
                                <h4 class="font-medium text-yellow-800">Set as Break Day</h4>
                                <p class="text-sm text-yellow-700 mt-1">
                                    Warning: Setting this day as a break day will permanently remove all current exercises. 
                                    This action cannot be undone.
                                </p>
                            </div>
                        </div>
                    </div>
                    <form method="POST" onsubmit="return confirm('Are you sure? This will remove all exercises from this day and cannot be undone.')">
                        <button type="submit" name="set_break_day" 
                                class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded-md transition-colors">
                            <i class="fas fa-umbrella-beach mr-2"></i> Set as Break Day & Remove All Exercises
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
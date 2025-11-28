<?php
session_start();
require 'page3.1.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: page3.2.php");
    exit();
}

$exercise_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get exercise data with better error handling
$stmt = $conn->prepare("SELECT e.*, w.day 
                       FROM exercises e
                       JOIN workouts w ON e.workout_id = w.id
                       WHERE e.id = ? AND w.user_id = ?");
$stmt->bind_param("ii", $exercise_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: page4.1.php");
    exit();
}

$exercise = $result->fetch_assoc();
$day = $exercise['day'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['exercise_name']);
    $sets = (int)$_POST['sets'];
    $reps = (int)$_POST['reps'];
    $rest_reps = (int)$_POST['rest_between_reps'];
    $rest_sets = (int)$_POST['rest_between_sets'];

    if (empty($name)) {
        $error = "Exercise name is required";
    } elseif ($sets < 1 || $sets > 100) {
        $error = "Sets must be between 1 and 100";
    } elseif ($reps < 1 || $reps > 1000) {
        $error = "Reps must be between 1 and 1000";
    } elseif ($rest_reps < 0 || $rest_reps > 3600) {
        $error = "Rest between reps must be between 0 and 3600 seconds";
    } elseif ($rest_sets < 0 || $rest_sets > 60) {
        $error = "Rest between sets must be between 0 and 60 minutes";
    } else {
        $stmt = $conn->prepare("UPDATE exercises SET name = ?, sets = ?, reps = ?, 
                               rest_between_reps_sec = ?, rest_between_sets_min = ? 
                               WHERE id = ? AND workout_id IN (SELECT id FROM workouts WHERE user_id = ?)");
        $stmt->bind_param("siiiiii", $name, $sets, $reps, $rest_reps, $rest_sets, $exercise_id, $user_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $success = "Exercise updated successfully!";
                // Update the exercise array for display
                $exercise['name'] = $name;
                $exercise['sets'] = $sets;
                $exercise['reps'] = $reps;
                $exercise['rest_between_reps_sec'] = $rest_reps;
                $exercise['rest_between_sets_min'] = $rest_sets;
            } else {
                $error = "No changes were made or exercise not found";
            }
        } else {
            $error = "Error updating exercise: " . $conn->error;
        }
    }
}

// Determine the correct back URL
$back_url = isset($_GET['from']) ? $_GET['from'] : "page4.2.php?day=" . urlencode($day);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout Planner | Edit Exercise</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .form-group {
            transition: all 0.2s ease;
        }
        .form-group:focus-within {
            transform: translateY(-1px);
        }
        .input-field {
            transition: all 0.2s ease;
        }
        .input-field:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">
                <a href="<?= htmlspecialchars($back_url) ?>" class="text-blue-600 hover:text-blue-800 mr-4 transition-colors">
                    <i class="fas fa-arrow-left"></i>
                </a>
                Edit Exercise
            </h1>
            <div class="text-sm text-gray-600">
                <i class="fas fa-calendar-day mr-1"></i>
                Editing: <span class="font-semibold"><?= htmlspecialchars($day) ?></span>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 shadow-md">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 shadow-md">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="mb-6 p-4 bg-blue-50 rounded-lg border-l-4 border-blue-400">
                <div class="flex items-center">
                    <i class="fas fa-dumbbell text-blue-600 mr-3"></i>
                    <div>
                        <h3 class="font-semibold text-blue-800">Currently Editing</h3>
                        <p class="text-blue-600 text-sm"><?= htmlspecialchars($exercise['name']) ?> - <?= htmlspecialchars($day) ?></p>
                    </div>
                </div>
            </div>

            <form method="POST" onsubmit="return validateForm();">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="md:col-span-2 form-group">
                        <label for="exercise_name" class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-tag mr-1"></i> Exercise Name
                        </label>
                        <input type="text" id="exercise_name" name="exercise_name" 
                               value="<?= htmlspecialchars($exercise['name']) ?>" 
                               class="input-field w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                               placeholder="Enter exercise name" required>
                        <p class="text-xs text-gray-500 mt-1">Enter a descriptive name for this exercise</p>
                    </div>

                    <div class="form-group">
                        <label for="sets" class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-layer-group mr-1"></i> Sets
                        </label>
                        <input type="number" id="sets" name="sets" min="1" max="100" 
                               value="<?= $exercise['sets'] ?>" 
                               class="input-field w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        <p class="text-xs text-gray-500 mt-1">Number of sets (1-100)</p>
                    </div>

                    <div class="form-group">
                        <label for="reps" class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-repeat mr-1"></i> Reps per Set
                        </label>
                        <input type="number" id="reps" name="reps" min="1" max="1000" 
                               value="<?= $exercise['reps'] ?>" 
                               class="input-field w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        <p class="text-xs text-gray-500 mt-1">Repetitions per set (1-1000)</p>
                    </div>

                    <div class="form-group">
                        <label for="rest_between_reps" class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-clock mr-1"></i> Rest Between Reps
                        </label>
                        <div class="flex">
                            <input type="number" id="rest_between_reps" name="rest_between_reps" 
                                   min="0" max="3600" value="<?= $exercise['rest_between_reps_sec'] ?>" 
                                   class="input-field flex-1 px-4 py-3 border rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                            <span class="bg-gray-100 border border-l-0 rounded-r-lg px-3 py-3 text-gray-600 text-sm">seconds</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Rest time between repetitions (0-3600 seconds)</p>
                    </div>

                    <div class="form-group">
                        <label for="rest_between_sets" class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-pause mr-1"></i> Rest Between Sets
                        </label>
                        <div class="flex">
                            <input type="number" id="rest_between_sets" name="rest_between_sets" 
                                   min="0" max="60" value="<?= $exercise['rest_between_sets_min'] ?>" 
                                   class="input-field flex-1 px-4 py-3 border rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                            <span class="bg-gray-100 border border-l-0 rounded-r-lg px-3 py-3 text-gray-600 text-sm">minutes</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Rest time between sets (0-60 minutes)</p>
                    </div>
                </div>

                <!-- Exercise Preview -->
                <div class="mb-8 p-4 bg-gray-50 rounded-lg border">
                    <h4 class="font-semibold text-gray-700 mb-2">
                        <i class="fas fa-eye mr-1"></i> Exercise Preview
                    </h4>
                    <div id="exercise-preview" class="text-sm text-gray-600">
                        <span id="preview-name"><?= htmlspecialchars($exercise['name']) ?></span> - 
                        <span id="preview-sets"><?= $exercise['sets'] ?></span> sets × 
                        <span id="preview-reps"><?= $exercise['reps'] ?></span> reps, 
                        <span id="preview-rest-reps"><?= $exercise['rest_between_reps_sec'] ?></span>s between reps, 
                        <span id="preview-rest-sets"><?= $exercise['rest_between_sets_min'] ?></span>m between sets
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-4">
                    <a href="<?= htmlspecialchars($back_url) ?>" 
                       class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-3 rounded-lg transition-colors text-center">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </a>
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition-colors">
                        <i class="fas fa-save mr-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- Quick Tips -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h4 class="font-semibold text-blue-800 mb-2">
                <i class="fas fa-lightbulb mr-1"></i> Quick Tips
            </h4>
            <ul class="text-sm text-blue-700 space-y-1">
                <li>• Use descriptive exercise names for easy identification</li>
                <li>• Rest between reps is typically 5-30 seconds</li>
                <li>• Rest between sets is usually 1-5 minutes depending on intensity</li>
                <li>• Higher sets/reps generally mean lower weight and more endurance focus</li>
            </ul>
        </div>
    </div>

    <script>
    function validateForm() {
        const name = document.getElementById('exercise_name').value.trim();
        const sets = parseInt(document.getElementById('sets').value);
        const reps = parseInt(document.getElementById('reps').value);
        const restReps = parseInt(document.getElementById('rest_between_reps').value);
        const restSets = parseInt(document.getElementById('rest_between_sets').value);

        if (!name) {
            alert('Please enter an exercise name.');
            return false;
        }

        if (sets < 1 || sets > 100) {
            alert('Sets must be between 1 and 100.');
            return false;
        }

        if (reps < 1 || reps > 1000) {
            alert('Reps must be between 1 and 1000.');
            return false;
        }

        if (restReps < 0 || restReps > 3600) {
            alert('Rest between reps must be between 0 and 3600 seconds.');
            return false;
        }

        if (restSets < 0 || restSets > 60) {
            alert('Rest between sets must be between 0 and 60 minutes.');
            return false;
        }

        return true;
    }

    // Update preview in real-time
    function updatePreview() {
        document.getElementById('preview-name').textContent = document.getElementById('exercise_name').value || 'Exercise Name';
        document.getElementById('preview-sets').textContent = document.getElementById('sets').value || '0';
        document.getElementById('preview-reps').textContent = document.getElementById('reps').value || '0';
        document.getElementById('preview-rest-reps').textContent = document.getElementById('rest_between_reps').value || '0';
        document.getElementById('preview-rest-sets').textContent = document.getElementById('rest_between_sets').value || '0';
    }

    // Add event listeners for real-time preview updates
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = ['exercise_name', 'sets', 'reps', 'rest_between_reps', 'rest_between_sets'];
        inputs.forEach(function(inputId) {
            document.getElementById(inputId).addEventListener('input', updatePreview);
        });
    });
    </script>
</body>
</html>
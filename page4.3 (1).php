<?php
session_start();
require 'page3.1.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: page3.2.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Get workout data for all days
$weekly_workouts = [];

foreach ($days as $day) {
    $weekly_workouts[$day] = [
        'is_break' => false,
        'exercises' => []
    ];

    $stmt = $conn->prepare("SELECT w.is_break, e.name, e.sets, e.reps 
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
                $weekly_workouts[$day]['is_break'] = (bool)$row['is_break'];
            }
            if ($row['name'] !== null) {
                $weekly_workouts[$day]['exercises'][] = [
                    'name' => $row['name'],
                    'sets' => $row['sets'],
                    'reps' => $row['reps']
                ];
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
    <title>Workout Planner | Weekly Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .day-card {
            transition: all 0.3s ease;
        }
        .day-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">
                <a href="page4.1.php" class="text-blue-600 hover:text-blue-800 mr-4 transition-colors">
                    <i class="fas fa-arrow-left"></i>
                </a>
                Weekly Workout Summary
            </h1>
        </div>

        <!-- Desktop Grid View -->
        <div class="hidden lg:block">
            <div class="grid grid-cols-7 gap-4 mb-8">
                <?php foreach ($days as $day): ?>
                    <div class="day-card bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="bg-blue-600 text-white text-center py-3">
                            <h2 class="font-semibold text-sm"><?= $day ?></h2>
                        </div>
                        
                        <div class="p-4 h-64 overflow-y-auto">
                            <?php if ($weekly_workouts[$day]['is_break']): ?>
                                <div class="text-center py-8 bg-yellow-50 rounded-lg h-full flex flex-col justify-center">
                                    <i class="fas fa-umbrella-beach text-yellow-500 text-2xl mb-2"></i>
                                    <p class="text-sm text-gray-600">Rest Day</p>
                                </div>
                            <?php elseif (empty($weekly_workouts[$day]['exercises'])): ?>
                                <div class="text-center py-8 bg-gray-50 rounded-lg h-full flex flex-col justify-center">
                                    <i class="fas fa-dumbbell text-gray-400 text-2xl mb-2"></i>
                                    <p class="text-sm text-gray-600">No exercises</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-2">
                                    <?php foreach ($weekly_workouts[$day]['exercises'] as $exercise): ?>
                                        <div class="bg-blue-50 rounded p-2 border-l-4 border-blue-400">
                                            <h3 class="font-medium text-xs text-blue-800 truncate" title="<?= htmlspecialchars($exercise['name']) ?>">
                                                <?= htmlspecialchars($exercise['name']) ?>
                                            </h3>
                                            <p class="text-xs text-gray-600 mt-1">
                                                <?= $exercise['sets'] ?> sets × <?= $exercise['reps'] ?> reps
                                            </p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Mobile List View -->
        <div class="lg:hidden">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="divide-y divide-gray-200">
                    <?php foreach ($days as $day): ?>
                        <div class="p-4">
                            <h2 class="font-semibold text-lg text-center mb-4 text-gray-800 bg-blue-50 py-2 rounded"><?= $day ?></h2>
                            
                            <?php if ($weekly_workouts[$day]['is_break']): ?>
                                <div class="text-center py-4 bg-yellow-50 rounded-lg">
                                    <i class="fas fa-umbrella-beach text-yellow-500 text-2xl mb-2"></i>
                                    <p class="text-sm text-gray-600">Rest Day</p>
                                </div>
                            <?php elseif (empty($weekly_workouts[$day]['exercises'])): ?>
                                <div class="text-center py-4 bg-gray-50 rounded-lg">
                                    <i class="fas fa-dumbbell text-gray-400 text-2xl mb-2"></i>
                                    <p class="text-sm text-gray-600">No exercises</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($weekly_workouts[$day]['exercises'] as $index => $exercise): ?>
                                        <div class="bg-blue-50 rounded p-3 border-l-4 border-blue-400">
                                            <div class="flex justify-between items-start">
                                                <h3 class="font-medium text-sm text-blue-800 flex-1 mr-2">
                                                    <?= htmlspecialchars($exercise['name']) ?>
                                                </h3>
                                                <span class="text-xs text-gray-500 bg-white px-2 py-1 rounded">#<?= $index + 1 ?></span>
                                            </div>
                                            <p class="text-xs text-gray-600 mt-2">
                                                <i class="fas fa-repeat mr-1"></i> <?= $exercise['sets'] ?> sets × <?= $exercise['reps'] ?> reps
                                            </p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Summary Statistics -->
        <?php
        $total_exercises = 0;
        $total_sets = 0;
        $rest_days = 0;
        $workout_days = 0;
        
        foreach ($weekly_workouts as $day_data) {
            if ($day_data['is_break']) {
                $rest_days++;
            } elseif (!empty($day_data['exercises'])) {
                $workout_days++;
                $total_exercises += count($day_data['exercises']);
                foreach ($day_data['exercises'] as $exercise) {
                    $total_sets += $exercise['sets'];
                }
            }
        }
        ?>

        <div class="mt-8 bg-white rounded-lg shadow-md p-6">
            <h3 class="text-xl font-semibold text-gray-800 mb-4 text-center">Weekly Summary</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600"><?= $workout_days ?></div>
                    <div class="text-sm text-gray-600">Workout Days</div>
                </div>
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-yellow-600"><?= $rest_days ?></div>
                    <div class="text-sm text-gray-600">Rest Days</div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-green-600"><?= $total_exercises ?></div>
                    <div class="text-sm text-gray-600">Total Exercises</div>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-purple-600"><?= $total_sets ?></div>
                    <div class="text-sm text-gray-600">Total Sets</div>
                </div>
            </div>
        </div>

        <div class="mt-8 text-center space-y-4">
            <a href="page4.1.php" 
               class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg shadow-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
            </a>
            <br>
        
        </div>
    </div>
</body>
</html>
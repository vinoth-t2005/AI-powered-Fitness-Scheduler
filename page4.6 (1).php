
<?php
session_start();
require 'page3.1.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$day = $_GET['day'] ?? '';
$valid_days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

if (!in_array($day, $valid_days)) {
    header("Location: page4.1.php");
    exit();
}

// Get workout data for this day
$workout = [
    'is_break' => false,
    'exercises' => []
];

$stmt = $conn->prepare("SELECT w.is_break, e.name, e.sets, e.reps, e.rest_between_reps_sec, e.rest_between_sets_min 
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
    <title>Workout Planner | View <?= htmlspecialchars($day) ?></title>
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

        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <?php if ($workout['is_break']): ?>
                <div class="text-center py-8">
                    <i class="fas fa-umbrella-beach text-5xl text-yellow-500 mb-4"></i>
                    <h3 class="text-2xl font-semibold text-gray-800 mb-2">Rest Day</h3>
                    <p class="text-gray-600">This day is set as a recovery day with no exercises.</p>
                </div>
            <?php elseif (empty($workout['exercises'])): ?>
                <div class="text-center py-8">
                    <i class="fas fa-dumbbell text-5xl text-gray-400 mb-4"></i>
                    <h3 class="text-2xl font-semibold text-gray-800 mb-2">No Exercises</h3>
                    <p class="text-gray-600">No exercises have been added for this day.</p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($workout['exercises'] as $exercise): ?>
                        <div class="border border-gray-200 rounded-lg p-6">
                            <h3 class="text-xl font-semibold text-gray-800 mb-4">
                                <?= htmlspecialchars($exercise['name']) ?>
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <h4 class="text-sm font-medium text-blue-800 mb-1">Sets</h4>
                                    <p class="text-2xl font-bold text-blue-600"><?= $exercise['sets'] ?></p>
                                </div>
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <h4 class="text-sm font-medium text-green-800 mb-1">Reps</h4>
                                    <p class="text-2xl font-bold text-green-600"><?= $exercise['reps'] ?></p>
                                </div>
                                <div class="bg-purple-50 p-4 rounded-lg">
                                    <h4 class="text-sm font-medium text-purple-800 mb-1">Rep Rest</h4>
                                    <p class="text-2xl font-bold text-purple-600"><?= $exercise['rest_between_reps_sec'] ?>s</p>
                                </div>
                                <div class="bg-yellow-50 p-4 rounded-lg">
                                    <h4 class="text-sm font-medium text-yellow-800 mb-1">Set Rest</h4>
                                    <p class="text-2xl font-bold text-yellow-600"><?= $exercise['rest_between_sets_min'] ?>m</p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="text-center">
            <a href="page4.2.php?day=<?= urlencode($day) ?>" 
               class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg shadow-lg transition-colors">
                <i class="fas fa-edit mr-2"></i> Edit Workout
            </a>
        </div>
    </div>
</body>
</html>
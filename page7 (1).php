<?php
session_start();
require 'page3.1.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout Analysis | FitAI</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .day-card {
            transition: all 0.3s ease;
        }
        .day-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8 max-w-3xl">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Workout Analysis</h1>
            <a href="page5.1.php" class="text-blue-600 hover:text-blue-800 font-medium">
                <i class="fas fa-arrow-left mr-1"></i> Back to Workouts
            </a>
        </div>

        <div class="space-y-4 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Daily Muscle Analysis</h2>
                <p class="text-gray-600">Click on any day to see detailed muscle group distribution and exercise analysis.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
            <?php foreach ($days as $day): 
                $stmt = $conn->prepare("SELECT COUNT(e.id) as exercise_count FROM workouts w 
                                      LEFT JOIN exercises e ON e.workout_id = w.id 
                                      WHERE w.user_id = ? AND w.day = ? AND w.is_break = 0");
                $stmt->bind_param("is", $user_id, $day);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $exercise_count = $row['exercise_count'] ?? 0;
            ?>
                <a href="page7.1.php?day=<?= urlencode($day) ?>" class="day-card bg-white rounded-lg shadow-md p-6 border border-gray-200 hover:border-blue-300">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-800"><?= $day ?></h3>
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                            <?= $exercise_count ?> exercises
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Weekly Analysis</h2>
            <p class="text-gray-600 mb-4">Get a complete overview of your weekly workout distribution with muscle group analysis and personalized suggestions.</p>
            <a href="page7.2.php" class="inline-block bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg shadow-lg transition-colors">
                View Weekly Analysis <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
        <div class="mt-8 text-center">
            <a href="page1.php" 
               class="inline-block bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg shadow-lg transition-colors">
                <i class="fas fa-home"></i> Home
            </a>
            
        </div>
    </div>
</body>
</html>
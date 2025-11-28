<?php
session_start();
require 'page3.1.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

date_default_timezone_set('Asia/Kolkata'); 

$user_id = $_SESSION['user_id'];
$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$current_day = date('l'); 
$current_time = time(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout Execution | Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .day-card {
            transition: all 0.3s ease;
        }
        .day-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .disabled-day {
            opacity: 0.6;
            pointer-events: none;
        }
        .current-day {
            border-left: 4px solid #3b82f6;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8 max-w-3xl">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Workout Execution - <?= date('F j, Y') ?></h1>
            <a href="page4.1.php" class="text-blue-600 hover:text-blue-800 font-medium">
                <i class="fas fa-arrow-left mr-1"></i> Back to Planner
            </a>
        </div>

        <div class="space-y-4">
            <?php foreach ($days as $day): 
                
                $stmt = $conn->prepare("SELECT id, is_break FROM workouts WHERE user_id = ? AND day = ?");
                $stmt->bind_param("is", $user_id, $day);
                $stmt->execute();
                $result = $stmt->get_result();
                $has_workout = $result->num_rows > 0;
                $is_break = $has_workout ? $result->fetch_assoc()['is_break'] : false;
                
                
                $is_current_day = ($day === $current_day);
            ?>
                <div class="day-card bg-white rounded-lg shadow-md overflow-hidden border border-gray-200 <?= $is_current_day ? 'current-day' : '' ?> <?= !$is_current_day ? 'disabled-day' : '' ?>">
                    <div class="p-6">
                        <div class="flex justify-between items-center">
                            <h2 class="text-xl font-semibold text-gray-800"><?= $day ?>
                                <?php if ($is_current_day): ?>
                                    <span class="ml-2 text-sm bg-blue-100 text-blue-800 px-2 py-1 rounded-full">Today</span>
                                <?php endif; ?>
                            </h2>
                            <?php if ($has_workout): ?>
                                <?php if ($is_break): ?>
                                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">
                                        Rest Day
                                    </span>
                                <?php else: ?>
                                    <?php if ($is_current_day): ?>
                                        <a href="page5.2.php?day=<?= urlencode($day) ?>" 
                                           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                                            <i class="fas fa-play mr-2"></i> Start Workout
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400">Available on <?= $day ?> only</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-gray-500">No workout planned</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-8 text-center">
            <a href="page1.php" 
               class="inline-block bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg shadow-lg transition-colors">
                <i class="fas fa-calendar-alt mr-2"></i> Home
            </a>
        </div>
    </div>

    <script>
        const now = new Date();
        const midnight = new Date();
        midnight.setHours(24, 0, 0, 0);
        const msUntilMidnight = midnight - now;
        
        setTimeout(() => {
            location.reload();
        }, msUntilMidnight);
    </script>
</body>
</html>
<?php
session_start();
require 'page3.1.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle workout completion recording
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_completion') {
    $exercise_id = $_POST['exercise_id'] ?? null;
    $completed_at = date('Y-m-d H:i:s');
    
    if ($exercise_id) {
        // Insert completion record - matching your actual database structure
        $insert_stmt = $conn->prepare("
            INSERT INTO workout_completions (user_id, exercise_id, completed_at)
            VALUES (?, ?, ?)
        ");
        $insert_stmt->bind_param("iis", $user_id, $exercise_id, $completed_at);
        
        if ($insert_stmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Workout completion recorded successfully']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to record completion']);
        }
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid exercise ID']);
        exit();
    }
}

// Continue with calendar display code...
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Adjust month/year if needed
if ($month < 1) {
    $month = 12;
    $year--;
} elseif ($month > 12) {
    $month = 1;
    $year++;
}

// Get first day of month
$first_day = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = date('t', $first_day);
$month_name = date('F', $first_day);
$day_of_week = date('w', $first_day); // 0=Sunday, 6=Saturday

// Get all workout days for this user
$workout_days = [];
$stmt = $conn->prepare("SELECT day, is_break FROM workouts WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $workout_days[$row['day']] = $row['is_break'];
}

// Get workout completions for this month - SIMPLIFIED for your database structure
$completions = [];
$start_date = date('Y-m-01', $first_day);
$end_date = date('Y-m-t', $first_day);

// Get completed exercises count per day
$stmt = $conn->prepare("
    SELECT DATE(completed_at) as completion_date, COUNT(*) as completed_count
    FROM workout_completions 
    WHERE user_id = ? 
    AND completed_at BETWEEN ? AND ?
    GROUP BY DATE(completed_at)
");
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $day_number = date('j', strtotime($row['completion_date']));
    $completions[$day_number] = $row['completed_count'];
}

// Get total planned exercises for each day of week
$total_exercises_by_day = [];
$stmt = $conn->prepare("SELECT w.day, COUNT(e.id) as total_exercises
                        FROM workouts w
                        LEFT JOIN exercises e ON e.workout_id = w.id
                        WHERE w.user_id = ? AND w.is_break = 0
                        GROUP BY w.day");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $total_exercises_by_day[$row['day']] = $row['total_exercises'];
}

// Debug info
error_log("User ID: " . $user_id);
error_log("Completions found: " . print_r($completions, true));
error_log("Workout days: " . print_r($workout_days, true));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout Calendar | FitAI</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .calendar-day {
            height: 100px;
            transition: all 0.2s ease;
        }
        .calendar-day:hover {
            transform: scale(1.03);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .break-day {
            background-color: #FEF9C3;
        }
        .completed-day {
            background-color: #ECFDF5;
            border-left: 4px solid #10B981;
        }
        .missed-day {
            background-color: #FEE2E2;
        }
        .planned-day {
            background-color: #EFF6FF;
        }
        .day-number {
            font-size: 0.8rem;
            font-weight: bold;
        }
        .completion-indicator {
            height: 6px;
            border-radius: 3px;
            margin-top: 2px;
            background-color: #10B981;
        }
        .clickable-day {
            cursor: pointer;
        }
        .clickable-day:hover {
            background-color: #f3f4f6;
        }
        .completion-badge {
            font-size: 0.7rem;
            padding: 2px 4px;
            border-radius: 4px;
            background-color: #10B981;
            color: white;
        }
        .today-highlight {
            border: 2px solid #3B82F6 !important;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Workout Calendar</h1>
            <div class="flex items-center space-x-4">
                <a href="page6.php?month=<?= $month-1 ?>&year=<?= $year ?>" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <h2 class="text-xl font-semibold"><?= $month_name ?> <?= $year ?></h2>
                <a href="page6.php?month=<?= $month+1 ?>&year=<?= $year ?>" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>

        <!-- Status Info -->
        <div class="mb-4 p-3 bg-blue-100 rounded-lg text-sm">
            <strong>Calendar Status:</strong> 
            Showing <?= $month_name ?> <?= $year ?> | 
            Completed days: <span id="completed-count"><?= count($completions) ?></span> |
            <a href="javascript:location.reload()" class="text-blue-600 underline">Refresh</a>
        </div>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="grid grid-cols-7 gap-px bg-gray-200">
                <!-- Day headers -->
                <?php 
                $day_names = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                foreach ($day_names as $day_name_header): ?>
                    <div class="py-2 text-center font-medium bg-gray-100">
                        <?= $day_name_header ?>
                    </div>
                <?php endforeach; ?>

                <!-- Blank days before the first of the month -->
                <?php for ($i = 0; $i < $day_of_week; $i++): ?>
                    <div class="calendar-day bg-white"></div>
                <?php endfor; ?>

                <!-- Calendar days -->
                <?php for ($day = 1; $day <= $days_in_month; $day++): 
                    $current_date = "$year-$month-$day";
                    $day_of_week_current = date('w', strtotime($current_date));
                    $full_day_name = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][$day_of_week_current];
                    
                    // Determine day type
                    $is_break = isset($workout_days[$full_day_name]) ? $workout_days[$full_day_name] : false;
                    $has_completion = isset($completions[$day]);
                    $is_past = strtotime($current_date) < strtotime(date('Y-m-d'));
                    $is_today = $current_date == date('Y-m-d');
                    $is_future = strtotime($current_date) > strtotime(date('Y-m-d'));
                    
                    // Get completion info
                    $completion_count = $has_completion ? $completions[$day] : 0;
                    $total_exercises = isset($total_exercises_by_day[$full_day_name]) ? $total_exercises_by_day[$full_day_name] : 0;
                    
                    // Determine day status
                    $day_status = 'empty';
                    $day_class = 'bg-white';
                    $day_content = '';
                    $clickable = false;
                    $workout_link = '#';
                    
                    if (!$is_break && isset($workout_days[$full_day_name])) {
                        $clickable = true;
                        $workout_link = "page5.2.php?day=" . urlencode($full_day_name);
                        
                        if ($has_completion) {
                            $day_status = 'completed';
                            $day_class = 'completed-day';
                            $completion_percent = $total_exercises > 0 ? min(100, ($completion_count / $total_exercises) * 100) : 100;
                            $day_content = '
                                <div class="text-xs text-green-700 mt-1 font-medium">
                                    ' . round($completion_percent) . '% complete
                                </div>
                                <div class="completion-indicator" style="width: ' . $completion_percent . '%"></div>
                                <div class="text-xs text-gray-600 mt-1">
                                    ' . $completion_count . ' of ' . $total_exercises . ' exercises
                                </div>
                            ';
                        } elseif ($is_past) {
                            $day_status = 'missed';
                            $day_class = 'missed-day';
                            $day_content = '<div class="text-xs text-red-700 mt-1 font-medium">Missed workout</div>';
                        } else {
                            $day_status = 'planned';
                            $day_class = 'planned-day';
                            $day_content = '<div class="text-xs text-blue-700 mt-1 font-medium">' . $total_exercises . ' exercises planned</div>';
                        }
                    } elseif ($is_break) {
                        $day_status = 'break';
                        $day_class = 'break-day';
                        $day_content = '<div class="text-xs text-yellow-700 mt-1 font-medium">Rest day</div>';
                    }
                    
                    // Today highlight
                    if ($is_today) {
                        $day_class .= ' today-highlight';
                    }
                ?>
                    <?php if ($clickable): ?>
                        <a href="<?= $workout_link ?>" class="block">
                    <?php endif; ?>
                        <div class="calendar-day p-2 <?= $day_class ?> <?= $clickable ? 'clickable-day' : '' ?>">
                            <div class="day-number flex justify-between items-start">
                                <span class="<?= $is_today ? 'text-blue-600 font-bold' : '' ?>"><?= $day ?></span>
                                <?php if ($day_status === 'completed'): ?>
                                    <span class="completion-badge" title="Workout completed">✓</span>
                                <?php elseif ($day_status === 'missed'): ?>
                                    <span class="bg-red-500 text-white text-xs px-1 rounded" title="Missed workout">✗</span>
                                <?php elseif ($day_status === 'planned'): ?>
                                    <span class="bg-blue-500 text-white text-xs px-1 rounded" title="Planned workout">●</span>
                                <?php elseif ($day_status === 'break'): ?>
                                    <span class="bg-yellow-500 text-white text-xs px-1 rounded" title="Rest day">ⓡ</span>
                                <?php endif; ?>
                            </div>
                            <?= $day_content ?>
                        </div>
                    <?php if ($clickable): ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <!-- Blank days after the last of the month -->
                <?php 
                $total_cells = $day_of_week + $days_in_month;
                $remaining_cells = 7 - ($total_cells % 7);
                if ($remaining_cells < 7) {
                    for ($i = 0; $i < $remaining_cells; $i++): ?>
                        <div class="calendar-day bg-white"></div>
                    <?php endfor;
                }
                ?>
            </div>
        </div>

        <!-- Navigation -->
        <div class="mt-8 flex justify-between items-center">
            <a href="page5.1.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-dumbbell mr-2"></i> Back to Workouts
            </a>
            <a href="page1.php" class="text-blue-600 hover:text-blue-800 font-medium">
                <i class="fas fa-home mr-1"></i> Home
            </a>
        </div>

        <!-- Legend -->
        <div class="mt-8 bg-white p-4 rounded-lg shadow-sm">
            <h3 class="font-medium mb-2">Calendar Legend</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="flex items-center">
                    <div class="w-4 h-4 mr-2 break-day rounded-sm border border-yellow-300"></div>
                    <span>Rest Day</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 mr-2 completed-day rounded-sm border border-green-300"></div>
                    <span>Completed Workout</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 mr-2 planned-day rounded-sm border border-blue-300"></div>
                    <span>Planned Workout</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 mr-2 missed-day rounded-sm border border-red-300"></div>
                    <span>Missed Workout</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Update completed count dynamically
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Calendar loaded - Completed days: <?= count($completions) ?>');
            <?php foreach($completions as $day => $count): ?>
                console.log('Day <?= $day ?>: <?= $count ?> completions');
            <?php endforeach; ?>
        });
    </script>
</body>
</html>
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
    header("Location: page7.php");
    exit();
}

// Google Gemini AI API configuration
$api_key = "AIzaSyC0p48BBVI91z2J1aImUPQvbbfUET4K_Fw";
$api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent";

// Cache to avoid repeated API calls for same exercise
$exercise_cache = [];

// FIXED: Enhanced and more accurate exercise analysis function (same as page7.2.php)
function analyzeExerciseWithAPI($exercise_name, $api_key, $api_url, &$exercise_cache) {
    // Check cache first
    $cache_key = md5(strtolower(trim($exercise_name)));
    if (isset($exercise_cache[$cache_key])) {
        return $exercise_cache[$cache_key];
    }
    
    // Clean and validate exercise name
    $exercise_name = trim($exercise_name);
    if (empty($exercise_name)) {
        $result = ['muscle_groups' => ['Core'], 'primary_muscle' => 'Core'];
        $exercise_cache[$cache_key] = $result;
        return $result;
    }
    
    // First try the improved fallback for better accuracy
    $fallback_result = analyzeExerciseFallback($exercise_name);
    
    $url = $api_url . "?key=" . $api_key;
    
    $prompt = "You are a certified fitness expert and biomechanics specialist. Analyze the exercise '$exercise_name' with extreme precision.

STRICT RULES:
- Use ONLY these 6 muscle categories: Chest, Back, Legs, Arms, Shoulders, Core
- Primary muscle = the MAIN muscle that does most of the work (80%+ of effort)
- Secondary muscles = significant supporting muscles (20%+ of effort), maximum 2
- Be extremely accurate based on exercise biomechanics and anatomy

CRITICAL EXAMPLES FOR ACCURACY:
- \"Incline Dumbbell Press\" = Primary: Chest, Secondary: Shoulders, Arms
- \"Decline Dumbbell Press\" = Primary: Chest, Secondary: Arms
- \"Dumbbell Press\" = Primary: Chest, Secondary: Shoulders, Arms
- \"Chest Press\" = Primary: Chest, Secondary: Arms
- \"Push-ups\" = Primary: Chest, Secondary: Arms, Shoulders
- \"Pull-ups\" = Primary: Back, Secondary: Arms
- \"Lat Pulldown\" = Primary: Back, Secondary: Arms
- \"Squats\" = Primary: Legs, Secondary: Core
- \"Deadlifts\" = Primary: Back, Secondary: Legs, Core
- \"Bench Press\" = Primary: Chest, Secondary: Arms, Shoulders
- \"Bicep Curls\" = Primary: Arms, Secondary: None
- \"Tricep Extensions\" = Primary: Arms, Secondary: None
- \"Shoulder Press\" = Primary: Shoulders, Secondary: Arms
- \"Lateral Raises\" = Primary: Shoulders, Secondary: None
- \"Plank\" = Primary: Core, Secondary: None
- \"Rows\" = Primary: Back, Secondary: Arms
- \"Leg Press\" = Primary: Legs, Secondary: None
- \"Calf Raises\" = Primary: Legs, Secondary: None

BIOMECHANICAL FOCUS:
- Chest exercises: Any pressing motion away from body, flies
- Back exercises: Any pulling motion toward body, rows, pull-ups
- Shoulders: Overhead presses, raises (lateral, front, rear)
- Arms: Bicep curls, tricep extensions, arm isolation
- Legs: Squats, lunges, leg presses, calf work
- Core: Planks, crunches, twists, stabilization

Return ONLY this exact JSON format:
{\"muscle_groups\": [\"Primary\", \"Secondary1\", \"Secondary2\"], \"primary_muscle\": \"Primary\"}

If any doubt exists, prioritize biomechanical accuracy. No explanations.";

    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.1,
            'maxOutputTokens' => 200,
            'topP' => 0.7,
            'topK' => 5
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($http_code == 200 && !empty($response)) {
        $result = json_decode($response, true);
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            $text = trim($result['candidates'][0]['content']['parts'][0]['text']);
            
            // Clean up the response to extract JSON
            $text = preg_replace('/```json\s*/', '', $text);
            $text = preg_replace('/```\s*/', '', $text);
            $text = preg_replace('/^\s*json\s*/i', '', $text);
            
            // Extract JSON from response
            if (preg_match('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/', $text, $matches)) {
                $analysis = json_decode($matches[0], true);
                if ($analysis && isset($analysis['muscle_groups']) && isset($analysis['primary_muscle'])) {
                    // Validate muscle names
                    $valid_muscles = ['Chest', 'Back', 'Legs', 'Arms', 'Shoulders', 'Core'];
                    
                    // Validate primary muscle
                    if (!in_array($analysis['primary_muscle'], $valid_muscles)) {
                        $exercise_cache[$cache_key] = $fallback_result;
                        return $fallback_result; // Use fallback if invalid
                    }
                    
                    // Validate and filter muscle groups
                    $filtered_muscles = [];
                    foreach ($analysis['muscle_groups'] as $muscle) {
                        if (in_array($muscle, $valid_muscles)) {
                            $filtered_muscles[] = $muscle;
                        }
                    }
                    
                    if (empty($filtered_muscles)) {
                        $exercise_cache[$cache_key] = $fallback_result;
                        return $fallback_result; // Use fallback if no valid muscles
                    }
                    
                    $analysis['muscle_groups'] = $filtered_muscles;
                    
                    // Validate that primary muscle is in the muscle groups
                    if (!in_array($analysis['primary_muscle'], $analysis['muscle_groups'])) {
                        $analysis['muscle_groups'][] = $analysis['primary_muscle'];
                    }
                    
                    // Convert to format compatible with page7.1.php
                    $converted_result = [
                        'primary_muscles' => [$analysis['primary_muscle']],
                        'secondary_muscles' => array_diff($analysis['muscle_groups'], [$analysis['primary_muscle']])
                    ];
                    
                    $exercise_cache[$cache_key] = $converted_result;
                    return $converted_result;
                }
            }
        }
    }
    
    // Convert fallback result to page7.1.php format
    $converted_fallback = [
        'primary_muscles' => [$fallback_result['primary_muscle']],
        'secondary_muscles' => array_diff($fallback_result['muscle_groups'], [$fallback_result['primary_muscle']])
    ];
    
    $exercise_cache[$cache_key] = $converted_fallback;
    return $converted_fallback;
}

// FIXED: Significantly improved fallback function with comprehensive exercise database (same as page7.2.php)
function analyzeExerciseFallback($exercise_name) {
    if (empty($exercise_name) || is_null($exercise_name)) {
        return ['muscle_groups' => ['Core'], 'primary_muscle' => 'Core'];
    }
    
    $name_lower = strtolower(trim($exercise_name));
    
    // CHEST EXERCISES - Comprehensive patterns
    if (preg_match('/(chest|pec|bench.*press|incline.*press|decline.*press|dumbbell.*press|barbell.*press|fly|flye|pec.*deck|chest.*press|push.*up|pushup)/i', $name_lower)) {
        // Incline variations
        if (preg_match('/(incline)/i', $name_lower)) {
            return ['muscle_groups' => ['Chest', 'Shoulders', 'Arms'], 'primary_muscle' => 'Chest'];
        }
        // Decline variations
        if (preg_match('/(decline)/i', $name_lower)) {
            return ['muscle_groups' => ['Chest', 'Arms'], 'primary_muscle' => 'Chest'];
        }
        // Fly variations
        if (preg_match('/(fly|flye)/i', $name_lower)) {
            return ['muscle_groups' => ['Chest'], 'primary_muscle' => 'Chest'];
        }
        // Push-ups
        if (preg_match('/(push.*up|pushup)/i', $name_lower)) {
            return ['muscle_groups' => ['Chest', 'Arms', 'Shoulders'], 'primary_muscle' => 'Chest'];
        }
        // General chest exercises
        return ['muscle_groups' => ['Chest', 'Arms', 'Shoulders'], 'primary_muscle' => 'Chest'];
    }
    
    // BACK EXERCISES - Comprehensive patterns
    elseif (preg_match('/(back|lat|pull.*up|pullup|pull.*down|pulldown|row|deadlift|hyperextension|back.*extension|reverse.*fly|chin.*up)/i', $name_lower)) {
        // Deadlift variations
        if (preg_match('/(deadlift|dead.*lift)/i', $name_lower)) {
            return ['muscle_groups' => ['Back', 'Legs', 'Core'], 'primary_muscle' => 'Back'];
        }
        // Pull-ups and chin-ups
        if (preg_match('/(pull.*up|pullup|chin.*up)/i', $name_lower)) {
            return ['muscle_groups' => ['Back', 'Arms'], 'primary_muscle' => 'Back'];
        }
        // Lat pulldowns
        if (preg_match('/(lat.*pulldown|pulldown)/i', $name_lower)) {
            return ['muscle_groups' => ['Back', 'Arms'], 'primary_muscle' => 'Back'];
        }
        // Rows
        if (preg_match('/(row)/i', $name_lower)) {
            return ['muscle_groups' => ['Back', 'Arms'], 'primary_muscle' => 'Back'];
        }
        // General back exercises
        return ['muscle_groups' => ['Back', 'Arms'], 'primary_muscle' => 'Back'];
    }
    
    // SHOULDER EXERCISES - Comprehensive patterns
    elseif (preg_match('/(shoulder|deltoid|delt|lateral.*raise|front.*raise|rear.*raise|overhead.*press|military.*press|shoulder.*press|upright.*row|shrug|arnold.*press)/i', $name_lower)) {
        // Shrugs (traps)
        if (preg_match('/(shrug)/i', $name_lower)) {
            return ['muscle_groups' => ['Shoulders'], 'primary_muscle' => 'Shoulders'];
        }
        // Raises (isolation)
        if (preg_match('/(lateral.*raise|front.*raise|rear.*raise)/i', $name_lower)) {
            return ['muscle_groups' => ['Shoulders'], 'primary_muscle' => 'Shoulders'];
        }
        // Shoulder presses
        if (preg_match('/(shoulder.*press|overhead.*press|military.*press|arnold.*press)/i', $name_lower)) {
            return ['muscle_groups' => ['Shoulders', 'Arms'], 'primary_muscle' => 'Shoulders'];
        }
        // Upright rows
        if (preg_match('/(upright.*row)/i', $name_lower)) {
            return ['muscle_groups' => ['Shoulders', 'Arms'], 'primary_muscle' => 'Shoulders'];
        }
        // General shoulder exercises
        return ['muscle_groups' => ['Shoulders', 'Arms'], 'primary_muscle' => 'Shoulders'];
    }
    
    // LEG EXERCISES - Comprehensive patterns
    elseif (preg_match('/(leg|squat|lunge|quad|hamstring|glute|calf|thigh|leg.*press|leg.*extension|leg.*curl|hip.*thrust|bulgarian|pistol.*squat)/i', $name_lower)) {
        // Calf exercises
        if (preg_match('/(calf.*raise|calf)/i', $name_lower)) {
            return ['muscle_groups' => ['Legs'], 'primary_muscle' => 'Legs'];
        }
        // Squats
        if (preg_match('/(squat)/i', $name_lower)) {
            return ['muscle_groups' => ['Legs', 'Core'], 'primary_muscle' => 'Legs'];
        }
        // Lunges
        if (preg_match('/(lunge)/i', $name_lower)) {
            return ['muscle_groups' => ['Legs', 'Core'], 'primary_muscle' => 'Legs'];
        }
        // Leg isolation exercises
        if (preg_match('/(leg.*extension|leg.*curl|leg.*press)/i', $name_lower)) {
            return ['muscle_groups' => ['Legs'], 'primary_muscle' => 'Legs'];
        }
        // Hip thrusts and glute exercises
        if (preg_match('/(hip.*thrust|glute)/i', $name_lower)) {
            return ['muscle_groups' => ['Legs'], 'primary_muscle' => 'Legs'];
        }
        // General leg exercises
        return ['muscle_groups' => ['Legs', 'Core'], 'primary_muscle' => 'Legs'];
    }
    
    // ARM EXERCISES - Comprehensive patterns
    elseif (preg_match('/(arm|bicep|tricep|curl|extension|dip|hammer.*curl|preacher.*curl|skull.*crusher|kickback|pushdown|close.*grip)/i', $name_lower)) {
        // Bicep exercises
        if (preg_match('/(bicep|curl)/i', $name_lower)) {
            return ['muscle_groups' => ['Arms'], 'primary_muscle' => 'Arms'];
        }
        // Tricep exercises
        if (preg_match('/(tricep|extension|dip|skull.*crusher|kickback|pushdown|close.*grip)/i', $name_lower)) {
            return ['muscle_groups' => ['Arms'], 'primary_muscle' => 'Arms'];
        }
        // General arm exercises
        return ['muscle_groups' => ['Arms'], 'primary_muscle' => 'Arms'];
    }
    
    // CORE EXERCISES - Comprehensive patterns
    elseif (preg_match('/(core|abs|abdominal|plank|crunch|sit.*up|situp|twist|mountain.*climber|leg.*raise|russian.*twist|bicycle|flutter.*kick|v.*up|hollow.*body|wood.*chopper)/i', $name_lower)) {
        return ['muscle_groups' => ['Core'], 'primary_muscle' => 'Core'];
    }
    
    // Enhanced pattern matching for compound movements
    elseif (preg_match('/(clean|snatch|thruster|burpee)/i', $name_lower)) {
        return ['muscle_groups' => ['Legs', 'Back', 'Shoulders'], 'primary_muscle' => 'Legs'];
    }
    
    // Default classification based on common exercise patterns
    else {
        // Try to infer from common exercise naming patterns
        if (preg_match('/(press)/i', $name_lower) && !preg_match('/(leg)/i', $name_lower)) {
            return ['muscle_groups' => ['Chest', 'Arms'], 'primary_muscle' => 'Chest'];
        }
        if (preg_match('/(pull)/i', $name_lower)) {
            return ['muscle_groups' => ['Back', 'Arms'], 'primary_muscle' => 'Back'];
        }
        if (preg_match('/(raise)/i', $name_lower)) {
            return ['muscle_groups' => ['Shoulders'], 'primary_muscle' => 'Shoulders'];
        }
        
        // Default to Core for truly unknown exercises
        return ['muscle_groups' => ['Core'], 'primary_muscle' => 'Core'];
    }
}

// Get exercises for this day
$exercises = [];
$stmt = $conn->prepare("SELECT e.name FROM workouts w 
                       LEFT JOIN exercises e ON e.workout_id = w.id 
                       WHERE w.user_id = ? AND w.day = ? AND w.is_break = 0");
$stmt->bind_param("is", $user_id, $day);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if (!empty($row['name'])) {
        $exercises[] = $row['name'];
    }
}

// Initialize muscle group tracking
$muscle_groups = [
    'Chest' => 0,
    'Back' => 0,
    'Legs' => 0,
    'Arms' => 0,
    'Shoulders' => 0,
    'Core' => 0
];

$muscle_exercises = [
    'Chest' => [],
    'Back' => [],
    'Legs' => [],
    'Arms' => [],
    'Shoulders' => [],
    'Core' => []
];

// Analyze each exercise using the FIXED AI function
foreach ($exercises as $exercise) {
    $analysis = analyzeExerciseWithAPI($exercise, $api_key, $api_url, $exercise_cache);
    
    // Process primary muscles
    if (!empty($analysis['primary_muscles'])) {
        foreach ($analysis['primary_muscles'] as $muscle) {
            if (isset($muscle_groups[$muscle])) {
                $muscle_groups[$muscle]++;
                $muscle_exercises[$muscle][] = $exercise . " (Primary)";
            }
        }
    }
    
    // Process secondary muscles (with less weight)
    if (!empty($analysis['secondary_muscles'])) {
        foreach ($analysis['secondary_muscles'] as $muscle) {
            if (isset($muscle_groups[$muscle])) {
                $muscle_groups[$muscle] += 0.5; // Secondary muscles count as 0.5
                $muscle_exercises[$muscle][] = $exercise . " (Secondary)";
            }
        }
    }
    
    // Small delay to avoid API rate limits
    usleep(200000); // 0.2 second delay
}

// Filter out muscle groups with 0 exercises and remove duplicates
$filtered_muscle_groups = [];
$filtered_muscle_exercises = [];
foreach ($muscle_groups as $muscle => $count) {
    if ($count > 0) {
        $filtered_muscle_groups[$muscle] = round($count, 1);
        $filtered_muscle_exercises[$muscle] = array_unique($muscle_exercises[$muscle]);
    }
}

// Prepare data for chart
$chart_labels = array_keys($filtered_muscle_groups);
$chart_data = array_values($filtered_muscle_groups);
$chart_colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($day) ?> Analysis | FitAI</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Page Title Header -->
        <div class="bg-gradient-to-r from-green-600 to-blue-600 text-white p-4 rounded-lg mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">FITAI</h1>
                </div>
                <div class="text-right">
                    <div class="bg-green-500 px-3 py-1 rounded-full text-sm">
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold text-gray-800">
                <a href="page7.php" class="text-blue-600 hover:text-blue-800 mr-4">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <?= htmlspecialchars($day) ?> Workout Analysis
            </h2>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-xl font-semibold mb-4 flex items-center">
                <i class="fas fa-dumbbell mr-2 text-blue-600"></i>
                Exercises Analyzed
            </h3>
            <?php if (!empty($exercises)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <?php foreach ($exercises as $exercise): ?>
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span class="text-gray-700"><?= htmlspecialchars($exercise) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-gray-600">
                    <i class="fas fa-calendar-times text-4xl mb-4"></i>
                    <p>No exercises planned for this day.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($filtered_muscle_groups)): ?>
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h3 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-chart-pie mr-2 text-blue-600"></i>
                    Fixed AI-Analyzed Muscle Group Distribution
                </h3>
                <div class="w-full md:w-2/3 mx-auto mb-6">
                    <canvas id="muscleChart"></canvas>
                </div>
                
                <!-- Exercise breakdown by muscle group -->
                <div class="mt-6">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800 flex items-center">
                        <i class="fas fa-list-ul mr-2 text-green-600"></i>
                        Exercises by Muscle Group (Fixed Accurate Analysis)
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($filtered_muscle_exercises as $muscle => $muscle_exercise_list): ?>
                            <?php if (!empty($muscle_exercise_list)): ?>
                                <div class="bg-gradient-to-r from-gray-50 to-gray-100 p-4 rounded-lg border-l-4" 
                                     style="border-left-color: <?= $chart_colors[array_search($muscle, $chart_labels)] ?? '#ccc' ?>">
                                    <h5 class="font-medium text-gray-800 mb-2 flex items-center">
                                        <span class="w-4 h-4 rounded-full mr-2" 
                                              style="background-color: <?= $chart_colors[array_search($muscle, $chart_labels)] ?? '#ccc' ?>"></span>
                                        <?= htmlspecialchars($muscle) ?> 
                                        <span class="ml-2 bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">
                                            <?= $filtered_muscle_groups[$muscle] ?> points
                                        </span>
                                    </h5>
                                    <ul class="space-y-1">
                                        <?php foreach ($muscle_exercise_list as $exercise): ?>
                                            <li class="text-gray-600 text-sm flex items-center">
                                                <i class="fas fa-arrow-right text-xs mr-2 text-gray-400"></i>
                                                <?= htmlspecialchars($exercise) ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Fixed Analysis Info -->
                <div class="mt-6 bg-green-50 p-4 rounded-lg">
                    <div class="flex items-start">
                        <i class="fas fa-check-circle text-green-600 mt-1 mr-3"></i>
                        <div>
                            <h5 class="font-medium text-green-800 mb-1">Fixed Analysis System Details</h5>
                            <p class="text-green-700 text-sm">
                                <strong>FIXED:</strong> This analysis now uses the same enhanced logic as page7.2.php with improved pattern matching 
                                for accurate exercise classification, comprehensive exercise database, improved AI prompts with specific examples, 
                                and intelligent fallback systems. Arm exercises are now correctly classified (not as Core).
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <a href="page7.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg shadow-lg transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Back to Analysis Dashboard
        </a>

        <?php if (!empty($chart_labels)): ?>
        <script>
            // Pie chart for muscle groups
            const ctx = document.getElementById('muscleChart').getContext('2d');
            const muscleChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: <?= json_encode($chart_labels) ?>,
                    datasets: [{
                        data: <?= json_encode($chart_data) ?>,
                        backgroundColor: <?= json_encode(array_slice($chart_colors, 0, count($chart_labels))) ?>,
                        borderWidth: 3,
                        borderColor: '#fff',
                        hoverBorderWidth: 4,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} points (${percentage}%)`;
                                }
                            }
                        }
                    },
                    animation: {
                        animateRotate: true,
                        duration: 1000
                    }
                }
            });
        </script>
        <?php endif; ?>
    </div>
</body>
</html>
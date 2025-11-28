<?php
session_start();
require 'page3.1.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Google API Key
$api_key = 'AIzaSyC0p48BBVI91z2J1aImUPQvbbfUET4K_Fw';

// Enhanced and more accurate exercise analysis function
function analyzeExerciseWithAPI($exercise_name, $api_key) {
    // Clean and validate exercise name
    $exercise_name = trim($exercise_name);
    if (empty($exercise_name)) {
        return ['muscle_groups' => ['Core'], 'primary_muscle' => 'Core'];
    }
    
    // First try the improved fallback for better accuracy
    $fallback_result = analyzeExerciseFallback($exercise_name);
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $api_key;
    
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
                        return $fallback_result; // Use fallback if no valid muscles
                    }
                    
                    $analysis['muscle_groups'] = $filtered_muscles;
                    
                    // Validate that primary muscle is in the muscle groups
                    if (!in_array($analysis['primary_muscle'], $analysis['muscle_groups'])) {
                        $analysis['muscle_groups'][] = $analysis['primary_muscle'];
                    }
                    
                    return $analysis;
                }
            }
        }
    }
    
    // Use improved fallback if API fails
    return $fallback_result;
}

// Significantly improved fallback function with comprehensive exercise database
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

// Enhanced AI suggestions function with more specific prompts
function generateAISuggestions($underworked_muscles, $missing_muscles, $current_exercises, $api_key) {
    $underworked_list = empty($underworked_muscles) ? "None" : implode(", ", $underworked_muscles);
    $missing_list = empty($missing_muscles) ? "None" : implode(", ", $missing_muscles);
    $current_list = empty($current_exercises) ? "None" : implode(", ", array_unique($current_exercises));
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $api_key;
    
    $prompt = "You are a certified fitness expert with 15+ years of experience. Analyze this specific workout data and provide personalized recommendations:

CURRENT ANALYSIS:
- Underworked muscles (need 2+ more exercises): $underworked_list
- Missing muscles (0 exercises this week): $missing_list  
- Current exercises: $current_list

PROVIDE SPECIFIC RECOMMENDATIONS:

1. Priority Muscles: Focus on missing muscles first, then underworked ones
2. Equipment Exercises: Gym-based exercises with weights/machines
3. Bodyweight Exercises: Home exercises requiring no equipment
4. Workout Tips: Specific advice based on their current gaps
5. Balance Score: Rate 1-10 based on muscle distribution

EXERCISE REQUIREMENTS:
- Equipment exercises: Use dumbbells, barbells, machines, cables
- Bodyweight exercises: Push-ups, pull-ups, squats, planks, etc.
- Be specific and practical
- Focus on the most effective exercises for each muscle group

Return ONLY this exact JSON format:
{
    \"priority_muscles\": [\"muscle1\", \"muscle2\"],
    \"equipment_exercises\": {
        \"muscle1\": [\"specific exercise 1\", \"specific exercise 2\", \"specific exercise 3\"],
        \"muscle2\": [\"specific exercise 1\", \"specific exercise 2\", \"specific exercise 3\"]
    },
    \"bodyweight_exercises\": {
        \"muscle1\": [\"specific exercise 1\", \"specific exercise 2\", \"specific exercise 3\"],
        \"muscle2\": [\"specific exercise 1\", \"specific exercise 2\", \"specific exercise 3\"]
    },
    \"workout_tips\": [\"specific tip 1\", \"specific tip 2\", \"specific tip 3\"],
    \"weekly_balance_score\": \"number_1_to_10\"
}

Be specific and practical. No explanations, just the JSON.";

    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.3,
            'maxOutputTokens' => 1000,
            'topP' => 0.9,
            'topK' => 20
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200 && !empty($response)) {
        $result = json_decode($response, true);
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            $text = trim($result['candidates'][0]['content']['parts'][0]['text']);
            
            // Clean up the response
            $text = preg_replace('/```json\s*/', '', $text);
            $text = preg_replace('/```\s*/', '', $text);
            $text = preg_replace('/^\s*json\s*/i', '', $text);
            
            // Extract JSON from response
            if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
                $suggestions = json_decode($matches[0], true);
                if ($suggestions && is_array($suggestions)) {
                    // Validate the structure
                    if (isset($suggestions['priority_muscles']) && 
                        isset($suggestions['equipment_exercises']) && 
                        isset($suggestions['bodyweight_exercises']) && 
                        isset($suggestions['workout_tips']) && 
                        isset($suggestions['weekly_balance_score'])) {
                        return $suggestions;
                    }
                }
            }
        }
    }
    
    // Enhanced fallback with personalized suggestions
    return generatePersonalizedFallback($underworked_muscles, $missing_muscles, $current_exercises);
}

// More intelligent fallback suggestions based on actual analysis
function generatePersonalizedFallback($underworked_muscles, $missing_muscles, $current_exercises) {
    $priority_muscles = array_merge($missing_muscles, $underworked_muscles);
    $priority_muscles = array_unique(array_slice($priority_muscles, 0, 3));
    
    // Personalized exercise database based on effectiveness
    $equipment_suggestions = [
        'Chest' => ['Barbell Bench Press', 'Dumbbell Bench Press', 'Incline Dumbbell Press', 'Cable Chest Fly', 'Chest Dips'],
        'Back' => ['Lat Pulldown', 'Seated Cable Row', 'Barbell Rows', 'T-Bar Row', 'Wide Grip Pull-ups'],
        'Legs' => ['Barbell Squats', 'Leg Press', 'Romanian Deadlifts', 'Leg Curls', 'Walking Lunges'],
        'Arms' => ['Barbell Curls', 'Tricep Cable Pushdown', 'Hammer Curls', 'Close Grip Bench Press', 'Preacher Curls'],
        'Shoulders' => ['Overhead Press', 'Lateral Raises', 'Rear Delt Fly', 'Arnold Press', 'Cable Face Pulls'],
        'Core' => ['Cable Crunches', 'Russian Twists with Weight', 'Hanging Leg Raises', 'Wood Choppers', 'Plank to Push-up']
    ];
    
    $bodyweight_suggestions = [
        'Chest' => ['Standard Push-ups', 'Incline Push-ups', 'Diamond Push-ups', 'Decline Push-ups', 'Wide Grip Push-ups'],
        'Back' => ['Pull-ups', 'Chin-ups', 'Inverted Rows', 'Superman Exercise', 'Reverse Snow Angels'],
        'Legs' => ['Bodyweight Squats', 'Lunges', 'Single Leg Squats', 'Jump Squats', 'Bulgarian Split Squats'],
        'Arms' => ['Tricep Dips', 'Pike Push-ups', 'Close Grip Push-ups', 'Arm Circles', 'Isometric Holds'],
        'Shoulders' => ['Pike Push-ups', 'Handstand Push-ups', 'Shoulder Taps', 'Wall Walks', 'Arm Raises'],
        'Core' => ['Plank', 'Mountain Climbers', 'Bicycle Crunches', 'Flutter Kicks', 'Russian Twists']
    ];
    
    $equipment_exercises = [];
    $bodyweight_exercises = [];
    
    foreach ($priority_muscles as $muscle) {
        if (isset($equipment_suggestions[$muscle])) {
            $equipment_exercises[$muscle] = array_slice($equipment_suggestions[$muscle], 0, 3);
        }
        if (isset($bodyweight_suggestions[$muscle])) {
            $bodyweight_exercises[$muscle] = array_slice($bodyweight_suggestions[$muscle], 0, 3);
        }
    }
    
    // Generate personalized tips based on missing muscles
    $tips = [];
    
    if (in_array('Chest', $priority_muscles)) {
        $tips[] = 'Add 2-3 chest exercises focusing on different angles (flat, incline, decline)';
    }
    if (in_array('Back', $priority_muscles)) {
        $tips[] = 'Include both vertical pulls (pull-ups) and horizontal pulls (rows) for complete back development';
    }
    if (in_array('Legs', $priority_muscles)) {
        $tips[] = 'Focus on compound movements like squats and deadlifts for maximum leg development';
    }
    if (in_array('Shoulders', $priority_muscles)) {
        $tips[] = 'Target all three deltoid heads with presses, lateral raises, and rear delt exercises';
    }
    if (in_array('Arms', $priority_muscles)) {
        $tips[] = 'Balance bicep and tricep work with equal volume for proportional arm development';
    }
    if (in_array('Core', $priority_muscles)) {
        $tips[] = 'Include core exercises at the end of workouts when other muscles are fatigued';
    }
    
    // Add general tips if not enough specific ones
    if (count($tips) < 3) {
        $general_tips = [
            'Maintain progressive overload by gradually increasing weight or reps',
            'Allow 48-72 hours rest between training the same muscle groups',
            'Focus on proper form over heavy weights to prevent injury'
        ];
        $tips = array_merge($tips, array_slice($general_tips, 0, 3 - count($tips)));
    }
    
    // Calculate balance score based on missing/underworked muscles
    $total_muscle_groups = 6;
    $well_trained_groups = $total_muscle_groups - count($missing_muscles) - count($underworked_muscles);
    $balance_score = max(1, min(10, round(($well_trained_groups / $total_muscle_groups) * 10)));
    
    return [
        'priority_muscles' => $priority_muscles,
        'equipment_exercises' => $equipment_exercises,
        'bodyweight_exercises' => $bodyweight_exercises,
        'workout_tips' => array_slice($tips, 0, 3),
        'weekly_balance_score' => (string)$balance_score
    ];
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

// Get all exercises for the week with improved analysis
$weekly_exercises = [];
$exercise_cache = [];

foreach ($days as $day) {
    $stmt = $conn->prepare("SELECT e.name FROM workouts w 
                           LEFT JOIN exercises e ON e.workout_id = w.id 
                           WHERE w.user_id = ? AND w.day = ? AND w.is_break = 0 AND e.name IS NOT NULL AND e.name != ''");
    $stmt->bind_param("is", $user_id, $day);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $exercise_name = trim($row['name']);
        
        if (empty($exercise_name)) {
            continue;
        }
        
        $weekly_exercises[] = $exercise_name;
        
        // Check cache first
        if (!isset($exercise_cache[$exercise_name])) {
            $analysis = analyzeExerciseWithAPI($exercise_name, $api_key);
            $exercise_cache[$exercise_name] = $analysis;
            
            // Reduced delay to improve performance
            usleep(200000); // 0.2 second delay
        } else {
            $analysis = $exercise_cache[$exercise_name];
        }
        
        // Process primary muscle (full weight)
        $primary_muscle = $analysis['primary_muscle'];
        if (isset($muscle_groups[$primary_muscle])) {
            $muscle_groups[$primary_muscle]++;
            $muscle_exercises[$primary_muscle][] = $exercise_name . " (Primary)";
        }
        
        // Process secondary muscles (half weight)
        foreach ($analysis['muscle_groups'] as $muscle) {
            if ($muscle !== $primary_muscle && isset($muscle_groups[$muscle])) {
                $muscle_groups[$muscle] += 0.5;
                $muscle_exercises[$muscle][] = $exercise_name . " (Secondary)";
            }
        }
    }
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

// Improved muscle group analysis
$underworked_muscles = [];
$all_muscles = ['Chest', 'Back', 'Legs', 'Arms', 'Shoulders', 'Core'];
$missing_muscles = array_diff($all_muscles, array_keys($filtered_muscle_groups));

// Define underworked as less than 2 total points
foreach ($filtered_muscle_groups as $muscle => $count) {
    if ($count < 2) {
        $underworked_muscles[] = $muscle;
    }
}

// Generate AI suggestions only if needed
$ai_suggestions = [];
if (!empty($underworked_muscles) || !empty($missing_muscles)) {
    $ai_suggestions = generateAISuggestions($underworked_muscles, $missing_muscles, $weekly_exercises, $api_key);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Weekly Analysis | FitAI</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Page Title Header -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6 rounded-lg mb-6 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold mb-2">FITAI</h1>
                    <p class="text-blue-100">Accurate AI-Powered Exercise Classification & Personalized Suggestions</p>
                </div>
                <div class="text-right">
                    <div class="bg-white bg-opacity-20 px-4 py-2 rounded-full text-sm">
                    
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold text-gray-800">
                <a href="page7.php" class="text-blue-600 hover:text-blue-800 mr-4">
                    <i class="fas fa-arrow-left"></i>
                </a>
                Weekly Workout Analysis
            </h2>
        </div>

        <!-- Analysis Summary -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-xl font-semibold mb-4 flex items-center">
                <i class="fas fa-chart-line mr-2 text-green-600"></i>
                Analysis Summary
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg text-center">
                    <div class="text-2xl font-bold text-blue-600"><?= count($weekly_exercises) ?></div>
                    <div class="text-sm text-blue-800">Total Exercises</div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg text-center">
                    <div class="text-2xl font-bold text-green-600"><?= count($filtered_muscle_groups) ?></div>
                    <div class="text-sm text-green-800">Active Muscles</div>
                </div>
                <div class="bg-orange-50 p-4 rounded-lg text-center">
                    <div class="text-2xl font-bold text-orange-600"><?= count($underworked_muscles) ?></div>
                    <div class="text-sm text-orange-800">Underworked</div>
                </div>
                <div class="bg-red-50 p-4 rounded-lg text-center">
                    <div class="text-2xl font-bold text-red-600"><?= count($missing_muscles) ?></div>
                    <div class="text-sm text-red-800">Missing</div>
                </div>
            </div>
        </div>

        <?php if (!empty($filtered_muscle_groups)): ?>
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h3 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-chart-pie mr-2 text-blue-600"></i>
                    AI-Analyzed Muscle Group Distribution
                </h3>
                <div class="w-full md:w-2/3 mx-auto mb-6">
                    <canvas id="weeklyMuscleChart"></canvas>
                </div>
                
                <!-- Exercise breakdown by muscle group -->
                <div class="mt-6">
                    <h4 class="text-lg font-semibold mb-4 text-gray-800 flex items-center">
                        <i class="fas fa-list-ul mr-2 text-green-600"></i>
                        Exercises by Muscle Group (Accurate Classification)
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
                            <h5 class="font-medium text-green-800 mb-1">Analysis System</h5>
                            <p class="text-green-700 text-sm">
                                <strong>Key Improvements:</strong> Enhanced pattern matching for accurate exercise classification, 
                                comprehensive exercise database, improved AI prompts with specific examples, and intelligent fallback systems. 
                                Incline Dumbbell Press now correctly classified as Chest (Primary) + Shoulders, Arms (Secondary).
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <div class="text-center py-8 text-gray-600">
                    <i class="fas fa-calendar-times text-4xl mb-4"></i>
                    <h3 class="text-xl font-semibold mb-2">No Exercise Data Found</h3>
                    <p class="mb-4">No exercises are recorded for this week.</p>
                    <a href="page7.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                        Add Exercises
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- ENHANCED AI-POWERED PERSONALIZED SUGGESTIONS -->
        <?php if (!empty($ai_suggestions)): ?>
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h3 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-robot mr-2 text-purple-600"></i>
                    Personalized AI Training Recommendations
                </h3>
                
                <!-- Weekly Balance Score -->
                

                <!-- Priority Focus Areas -->
                <?php if (!empty($ai_suggestions['priority_muscles'])): ?>
                    <div class="mb-6">
                        <h4 class="font-medium text-lg mb-3 flex items-center">
                            <i class="fas fa-target mr-2 text-red-500"></i>
                            Priority Focus Areas
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($ai_suggestions['priority_muscles'] as $muscle): ?>
                                <span class="bg-red-100 text-red-800 px-4 py-2 rounded-full text-sm font-medium border border-red-200">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    <?= htmlspecialchars($muscle) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Personalized Exercise Recommendations -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Equipment-Based Exercises -->
                    <?php if (!empty($ai_suggestions['equipment_exercises'])): ?>
                        <div class="bg-blue-50 p-5 rounded-lg border border-blue-200">
                            <h5 class="font-medium text-blue-800 mb-4 flex items-center">
                                <i class="fas fa-dumbbell mr-2"></i>
                                Personalized Gym Exercises
                            </h5>
                            <?php foreach ($ai_suggestions['equipment_exercises'] as $muscle => $exercises): ?>
                                <div class="mb-4">
                                    <h6 class="font-semibold text-blue-700 mb-2 flex items-center">
                                        <span class="w-3 h-3 bg-blue-600 rounded-full mr-2"></span>
                                        <?= htmlspecialchars($muscle) ?>
                                    </h6>
                                    <ul class="space-y-1">
                                        <?php foreach ($exercises as $exercise): ?>
                                            <li class="text-blue-600 text-sm flex items-center bg-white p-2 rounded">
                                                <i class="fas fa-play text-xs mr-2 text-blue-400"></i>
                                                <?= htmlspecialchars($exercise) ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Bodyweight Exercises -->
                    <?php if (!empty($ai_suggestions['bodyweight_exercises'])): ?>
                        <div class="bg-green-50 p-5 rounded-lg border border-green-200">
                            <h5 class="font-medium text-green-800 mb-4 flex items-center">
                                <i class="fas fa-home mr-2"></i>
                                Personalized Home Exercises
                            </h5>
                            <?php foreach ($ai_suggestions['bodyweight_exercises'] as $muscle => $exercises): ?>
                                <div class="mb-4">
                                    <h6 class="font-semibold text-green-700 mb-2 flex items-center">
                                        <span class="w-3 h-3 bg-green-600 rounded-full mr-2"></span>
                                        <?= htmlspecialchars($muscle) ?>
                                    </h6>
                                    <ul class="space-y-1">
                                        <?php foreach ($exercises as $exercise): ?>
                                            <li class="text-green-600 text-sm flex items-center bg-white p-2 rounded">
                                                <i class="fas fa-play text-xs mr-2 text-green-400"></i>
                                                <?= htmlspecialchars($exercise) ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Personalized Training Tips -->
                <?php if (!empty($ai_suggestions['workout_tips'])): ?>
                    <div class="bg-yellow-50 p-5 rounded-lg border border-yellow-200">
                        <h5 class="font-medium text-yellow-800 mb-4 flex items-center">
                            <i class="fas fa-lightbulb mr-2"></i>
                            Personalized Training Tips
                        </h5>
                        <ul class="space-y-3">
                            <?php foreach ($ai_suggestions['workout_tips'] as $index => $tip): ?>
                                <li class="text-yellow-700 text-sm flex items-start bg-white p-3 rounded">
                                    <span class="bg-yellow-200 text-yellow-800 rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0">
                                        <?= $index + 1 ?>
                                    </span>
                                    <?= htmlspecialchars($tip) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- AI System Information -->
                <div class="mt-6 bg-gray-50 p-4 rounded-lg">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-gray-600 mt-1 mr-3"></i>
                        <div>
                            <h5 class="font-medium text-gray-800 mb-1">Personalized AI Recommendations</h5>
                            <p class="text-gray-600 text-sm">
                                These recommendations are specifically generated based on your current workout data, 
                                focusing on your missing and underworked muscle groups. The AI analyzes your specific 
                                exercise patterns to provide targeted suggestions that complement your existing routine.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Balanced Workout Message -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h3 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-trophy mr-2 text-gold-600"></i>
                    Training Assessment
                </h3>
                <div class="bg-green-50 p-6 rounded-lg text-center border border-green-200">
                    <i class="fas fa-trophy text-5xl text-green-600 mb-4"></i>
                    <h4 class="text-2xl font-bold text-green-800 mb-2">🎉 Perfectly Balanced Workout!</h4>
                    <p class="text-green-700 mb-4 text-lg">
                        Your weekly routine excellently covers all major muscle groups with optimal distribution.
                    </p>
                    <div class="bg-white p-4 rounded-lg shadow-sm">
                        <h5 class="font-medium text-green-800 mb-2">Outstanding Balance Achieved!</h5>
                        <p class="text-green-600 text-sm">
                            All muscle groups are adequately trained (2+ exercises each). Your current routine 
                            demonstrates excellent programming for sustained fitness progress and muscular development.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Navigation -->
        <div class="flex justify-between items-center">
            <a href="page7.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg shadow-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Back to Analysis Dashboard
            </a>
            
           
        </div>

        <?php if (!empty($chart_labels)): ?>
        <script>
            // Enhanced pie chart for weekly muscle groups
            const ctx = document.getElementById('weeklyMuscleChart').getContext('2d');
            const weeklyMuscleChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode($chart_labels) ?>,
                    datasets: [{
                        data: <?= json_encode($chart_data) ?>,
                        backgroundColor: <?= json_encode(array_slice($chart_colors, 0, count($chart_labels))) ?>,
                        borderWidth: 3,
                        borderColor: '#fff',
                        hoverBorderWidth: 5,
                        hoverOffset: 15
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    cutout: '40%',
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 13,
                                    weight: 'bold'
                                },
                                generateLabels: function(chart) {
                                    const data = chart.data;
                                    return data.labels.map((label, index) => {
                                        const value = data.datasets[0].data[index];
                                        const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                        const percentage = Math.round((value / total) * 100);
                                        return {
                                            text: `${label}: ${value} pts (${percentage}%)`,
                                            fillStyle: data.datasets[0].backgroundColor[index],
                                            strokeStyle: data.datasets[0].backgroundColor[index],
                                            pointStyle: 'circle'
                                        };
                                    });
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
                            },
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1
                        }
                    },
                    animation: {
                        animateRotate: true,
                        duration: 1500,
                        easing: 'easeInOutQuart'
                    },
                    elements: {
                        arc: {
                            borderWidth: 3
                        }
                    }
                }
            });
        </script>
        <?php endif; ?>
    </div>
</body>
</html>
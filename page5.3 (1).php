<?php
session_start();
require 'page3.1.php';

// Set proper headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Not logged in',
        'error_code' => 'AUTH_ERROR'
    ]);
    exit();
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method',
        'error_code' => 'METHOD_ERROR'
    ]);
    exit();
}

// Get and validate JSON input
$json = file_get_contents('php://input');
if (empty($json)) {
    echo json_encode([
        'success' => false, 
        'message' => 'No data received',
        'error_code' => 'DATA_ERROR'
    ]);
    exit();
}

$data = json_decode($json, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid JSON: ' . json_last_error_msg(),
        'error_code' => 'JSON_ERROR'
    ]);
    exit();
}

// Validate required fields
$required_fields = ['user_id', 'exercise_id', 'sets_completed', 'reps_completed', 'duration', 'day'];
foreach ($required_fields as $field) {
    if (!isset($data[$field])) {
        echo json_encode([
            'success' => false, 
            'message' => "Missing required field: $field",
            'error_code' => 'VALIDATION_ERROR'
        ]);
        exit();
    }
}

// Verify the user making the request matches the logged in user
if ((int)$data['user_id'] !== (int)$_SESSION['user_id']) {
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized: User ID mismatch',
        'error_code' => 'AUTH_ERROR'
    ]);
    exit();
}

// Validate data types and ranges
$user_id = (int)$data['user_id'];
$exercise_id = (int)$data['exercise_id'];
$sets_completed = (int)$data['sets_completed'];
$reps_completed = (int)$data['reps_completed'];
$duration = (int)$data['duration'];
$day = trim($data['day']);

// Validate ranges
if ($exercise_id <= 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid exercise ID',
        'error_code' => 'VALIDATION_ERROR'
    ]);
    exit();
}

if ($sets_completed < 0 || $sets_completed > 1000) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid sets completed value',
        'error_code' => 'VALIDATION_ERROR'
    ]);
    exit();
}

if ($reps_completed < 0 || $reps_completed > 100000) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid reps completed value',
        'error_code' => 'VALIDATION_ERROR'
    ]);
    exit();
}

if ($duration < 0 || $duration > 86400) { // Max 24 hours
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid duration value',
        'error_code' => 'VALIDATION_ERROR'
    ]);
    exit();
}

$valid_days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
if (!in_array($day, $valid_days)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid day value',
        'error_code' => 'VALIDATION_ERROR'
    ]);
    exit();
}

try {
    // Verify the exercise belongs to the user
    $stmt = $conn->prepare("SELECT e.id FROM exercises e 
                           JOIN workouts w ON e.workout_id = w.id 
                           WHERE e.id = ? AND w.user_id = ?");
    $stmt->bind_param("ii", $exercise_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Exercise not found or access denied',
            'error_code' => 'ACCESS_ERROR'
        ]);
        exit();
    }

    // Check if completion record already exists for today
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT id FROM workout_completions 
                           WHERE user_id = ? AND exercise_id = ? AND day = ? AND DATE(completed_at) = ?");
    $stmt->bind_param("iiss", $user_id, $exercise_id, $day, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE workout_completions 
                               SET sets_completed = ?, reps_completed = ?, duration = ?, completed_at = NOW()
                               WHERE user_id = ? AND exercise_id = ? AND day = ? AND DATE(completed_at) = ?");
        $stmt->bind_param("iiiisss", $sets_completed, $reps_completed, $duration, $user_id, $exercise_id, $day, $today);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'Workout completion updated successfully',
                'action' => 'updated'
            ]);
        } else {
            throw new Exception('Failed to update workout completion: ' . $conn->error);
        }
    } else {
        // Insert new completion record
        $stmt = $conn->prepare("INSERT INTO workout_completions 
                              (user_id, exercise_id, sets_completed, reps_completed, duration, day, completed_at) 
                              VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiiiss", $user_id, $exercise_id, $sets_completed, $reps_completed, $duration, $day);
        
        if ($stmt->execute()) {
            $completion_id = $conn->insert_id;
            echo json_encode([
                'success' => true, 
                'message' => 'Workout completion recorded successfully',
                'completion_id' => $completion_id,
                'action' => 'created'
            ]);
        } else {
            throw new Exception('Failed to insert workout completion: ' . $conn->error);
        }
    }

} catch (mysqli_sql_exception $e) {
    error_log("Database error in workout completion: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred',
        'error_code' => 'DB_ERROR'
    ]);
} catch (Exception $e) {
    error_log("General error in workout completion: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred: ' . $e->getMessage(),
        'error_code' => 'GENERAL_ERROR'
    ]);
}
?>
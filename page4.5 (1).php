//page4.5
<?php
session_start();
require 'page3.1.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$exercise_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Verify the exercise belongs to the user
$stmt = $conn->prepare("SELECT w.day FROM exercises e
                       JOIN workouts w ON e.workout_id = w.id
                       WHERE e.id = ? AND w.user_id = ?");
$stmt->bind_param("ii", $exercise_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $day = $row['day'];
    
    // Delete the exercise
    $stmt = $conn->prepare("DELETE FROM exercises WHERE id = ?");
    $stmt->bind_param("i", $exercise_id);
    $stmt->execute();
    
    // Check if this was the last exercise for this workout
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM exercises WHERE workout_id = 
                          (SELECT id FROM workouts WHERE user_id = ? AND day = ?)");
    $stmt->bind_param("is", $user_id, $day);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    if ($count === 0) {
        // Delete the workout if no exercises left
        $stmt = $conn->prepare("DELETE FROM workouts WHERE user_id = ? AND day = ? AND is_break = FALSE");
        $stmt->bind_param("is", $user_id, $day);
        $stmt->execute();
    }
}

header("Location: page4.2.php?day=" . urlencode($day));
exit();
?>
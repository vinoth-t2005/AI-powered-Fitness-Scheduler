<?php
session_start();
require 'page3.1.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: page3.2.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$day = $_GET['day'] ?? '';
$valid_days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

if (!in_array($day, $valid_days)) {
    header("Location: page5.1.php");
    exit();
}

// Get workout data for the current user
$workout = [
    'is_break' => false,
    'exercises' => []
];

$stmt = $conn->prepare("SELECT w.is_break, e.id, e.name, e.sets, e.reps, e.rest_between_reps_sec, e.rest_between_sets_min 
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
                'id' => $row['id'],
                'name' => $row['name'],
                'sets' => $row['sets'],
                'reps' => $row['reps'],
                'rest_between_reps_sec' => $row['rest_between_reps_sec'],
                'rest_between_sets_min' => $row['rest_between_sets_min']
            ];
        }
    }
}

if ($workout['is_break']) {
    header("Location: page5.1.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout Execution | <?= htmlspecialchars($day) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .exercise-card {
            transition: all 0.3s ease;
        }
        .exercise-card.active {
            border-left: 4px solid #3b82f6;
            background-color: #f8fafc;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }
        .exercise-card.completed {
            border-left: 4px solid #10b981;
            background-color: #f0fdf4;
        }
        .progress-bar {
            height: 8px;
            background-color: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #1d4ed8);
            transition: width 0.3s ease;
        }
        .btn-pause {
            background-color: #f59e0b;
        }
        .btn-pause:hover {
            background-color: #d97706;
        }
        .pulse {
            animation: pulse 1s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .countdown-warning {
            color: #ef4444;
            font-weight: bold;
        }
        .countdown-final {
            color: #dc2626;
            font-weight: bold;
            animation: pulse 0.5s infinite;
        }
        .rep-timer {
            font-size: 2rem;
            font-weight: bold;
            color: #3b82f6;
        }
        .rep-timer.warning {
            color: #f59e0b;
        }
        .rep-timer.danger {
            color: #ef4444;
            animation: pulse 0.5s infinite;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">
                <a href="page5.1.php" class="text-blue-600 hover:text-blue-800 mr-4 transition-colors">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <?= htmlspecialchars($day) ?> Workout
            </h1>
            <div class="flex items-center space-x-4">
                <div id="timer-display" class="text-2xl font-mono bg-gray-800 text-white px-4 py-2 rounded-lg shadow">
                    00:00
                </div>
                <button id="pause-btn" class="btn-pause text-white px-4 py-2 rounded-lg hidden transition-colors">
                    <i class="fas fa-pause mr-2"></i> Pause
                </button>
                <button id="sound-toggle" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded-lg transition-colors" title="Toggle Sound">
                    <i class="fas fa-volume-up"></i>
                </button>
            </div>
        </div>

        <!-- Workout Progress -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex justify-between items-center">
                <h3 class="font-semibold text-gray-700">Workout Progress</h3>
                <div class="text-sm text-gray-600">
                    <span id="completed-exercises">0</span> / <?= count($workout['exercises']) ?> exercises completed
                </div>
            </div>
            <div class="progress-bar mt-2">
                <div id="overall-progress" class="progress-fill" style="width: 0%"></div>
            </div>
        </div>

        <div class="space-y-6">
            <?php if (empty($workout['exercises'])): ?>
                <div class="bg-white rounded-lg shadow-md p-8 text-center">
                    <i class="fas fa-dumbbell text-6xl text-gray-400 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">No Exercises Planned</h3>
                    <p class="text-gray-600 mb-4">This day doesn't have any exercises scheduled.</p>
                    <a href="page4.2.php?day=<?= urlencode($day) ?>" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition-colors">
                        <i class="fas fa-edit mr-2"></i> Edit Workout
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($workout['exercises'] as $index => $exercise): ?>
                    <div id="exercise-<?= $exercise['id'] ?>" class="exercise-card bg-white rounded-lg shadow-md p-6 border border-gray-200">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex-1">
                                <div class="flex items-center mb-2">
                                    <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-1 rounded mr-2">
                                        Exercise <?= $index + 1 ?>
                                    </span>
                                    <h2 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($exercise['name']) ?></h2>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-gray-600">
                                    <div class="bg-gray-50 p-2 rounded">
                                        <span class="block text-xs">Sets</span>
                                        <span class="font-bold text-lg text-blue-600"><?= $exercise['sets'] ?></span>
                                    </div>
                                    <div class="bg-gray-50 p-2 rounded">
                                        <span class="block text-xs">Reps</span>
                                        <span class="font-bold text-lg text-green-600"><?= $exercise['reps'] ?></span>
                                    </div>
                                    <div class="bg-gray-50 p-2 rounded">
                                        <span class="block text-xs">Rep Rest</span>
                                        <span class="font-bold text-lg text-orange-600"><?= $exercise['rest_between_reps_sec'] ?>s</span>
                                    </div>
                                    <div class="bg-gray-50 p-2 rounded">
                                        <span class="block text-xs">Set Rest</span>
                                        <span class="font-bold text-lg text-purple-600"><?= $exercise['rest_between_sets_min'] ?>m</span>
                                    </div>
                                </div>
                            </div>
                            <button onclick="startExercise(<?= $exercise['id'] ?>, <?= $exercise['sets'] ?>, <?= $exercise['reps'] ?>, <?= $exercise['rest_between_reps_sec'] ?>, <?= $exercise['rest_between_sets_min'] ?>)" 
                                    class="start-exercise-btn bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition-colors ml-4">
                                <i class="fas fa-play mr-2"></i> Start
                            </button>
                        </div>
                        
                        <div class="progress-container hidden mt-6">
                            <div class="flex justify-between text-sm text-gray-600 mb-2">
                                <span>Set <span class="current-set font-bold text-blue-600">1</span> of <?= $exercise['sets'] ?></span>
                                <span>Rep <span class="current-rep font-bold text-green-600">1</span> of <?= $exercise['reps'] ?></span>
                            </div>
                            <div class="progress-bar mb-3">
                                <div class="progress-fill" style="width: 0%"></div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="status-message text-gray-700 font-medium">Ready to start</span>
                                <span class="countdown-timer font-mono text-lg"></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Workout Summary -->
                <div class="bg-white rounded-lg shadow-md p-6 mt-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-chart-line mr-2"></i> Today's Summary
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600"><?= count($workout['exercises']) ?></div>
                            <div class="text-sm text-gray-600">Total Exercises</div>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">
                                <?= array_sum(array_column($workout['exercises'], 'sets')) ?>
                            </div>
                            <div class="text-sm text-gray-600">Total Sets</div>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-purple-600">
                                <?= array_sum(array_map(function($ex) { return $ex['sets'] * $ex['reps']; }, $workout['exercises'])) ?>
                            </div>
                            <div class="text-sm text-gray-600">Total Reps</div>
                        </div>
                        <div class="bg-yellow-50 p-4 rounded-lg">
                            <div id="completion-percentage" class="text-2xl font-bold text-yellow-600">0%</div>
                            <div class="text-sm text-gray-600">Completed</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Navigation -->
        <div class="mt-8 flex justify-between items-center">
            <a href="page5.1.php" class="text-blue-600 hover:text-blue-800 font-medium">
                <i class="fas fa-arrow-left mr-1"></i> Back to Workouts
            </a>
            <div class="flex space-x-4">
                <a href="page6.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-calendar mr-2"></i> View Calendar
                </a>
                <a href="page1.php" class="text-blue-600 hover:text-blue-800 font-medium">
                    <i class="fas fa-home mr-1"></i> Home
                </a>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let currentExercise = null;
        let currentSet = 1;
        let currentRep = 1;
        let totalSets = 0;
        let totalReps = 0;
        let repRestTime = 0;
        let setRestTime = 0;
        let timerInterval = null;
        let repTimerInterval = null;
        let secondsRemaining = 0;
        let workoutStartTime = null;
        let overallTimerInterval = null;
        let isPaused = false;
        let pausedTime = 0;
        let exerciseTimeout = null;
        let workoutDuration = 0;
        let completedExercises = 0;
        let soundEnabled = true;
        let audioContext = null;
        let repDurationSeconds = 3; // Default rep duration

        // DOM elements
        const timerDisplay = document.getElementById('timer-display');
        const pauseBtn = document.getElementById('pause-btn');
        const soundToggle = document.getElementById('sound-toggle');
        const overallProgress = document.getElementById('overall-progress');
        const completedExercisesSpan = document.getElementById('completed-exercises');
        const completionPercentage = document.getElementById('completion-percentage');

        // Audio using Web Audio API for better browser support
        async function initAudioContext() {
            try {
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
                
                // Create audio context on user interaction
                document.body.addEventListener('click', async () => {
                    if (audioContext.state === 'suspended') {
                        await audioContext.resume();
                    }
                }, { once: true });
            } catch (e) {
                console.log("Web Audio API not supported, using fallback");
                audioContext = null;
            }
        }

        // Generate beep sounds programmatically
        function playBeep(frequency = 800, duration = 200, type = 'sine') {
            if (!soundEnabled) return;
            
            try {
                if (audioContext) {
                    const oscillator = audioContext.createOscillator();
                    const gainNode = audioContext.createGain();
                    
                    oscillator.connect(gainNode);
                    gainNode.connect(audioContext.destination);
                    
                    oscillator.frequency.setValueAtTime(frequency, audioContext.currentTime);
                    oscillator.type = type;
                    
                    gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + duration / 1000);
                    
                    oscillator.start(audioContext.currentTime);
                    oscillator.stop(audioContext.currentTime + duration / 1000);
                } else {
                    // Fallback: Create a simple beep using data URL
                    const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmMcBjiCv+u3dCYGKXnJ8N+PQQsUWr3n6KpYFAlD0M=');
                    audio.volume = 0.3;
                    audio.play().catch(e => console.log("Fallback audio failed:", e));
                }
            } catch (e) {
                console.log("Audio playback failed:", e);
            }
        }

        function playStartBeep() {
            playBeep(1000, 300, 'square');
        }

        function playRepBeep() {
            playBeep(600, 150, 'sine');
        }

        function playCountdownBeep() {
            playBeep(400, 100, 'triangle');
        }

        function playLongBeep() {
            playBeep(1200, 3000, 'square');
        }

        function playSetWarningBeep() {
            playBeep(800, 3000, 'sawtooth');
        }

        function playCompleteBeep() {
            if (soundEnabled) {
                setTimeout(() => playBeep(523, 200), 0);
                setTimeout(() => playBeep(659, 200), 250);
                setTimeout(() => playBeep(784, 400), 500);
            }
        }

        // Toggle sound function
        function toggleSound() {
            soundEnabled = !soundEnabled;
            const icon = soundToggle.querySelector('i');
            if (soundEnabled) {
                icon.className = 'fas fa-volume-up';
                soundToggle.title = 'Sound On - Click to Mute';
                playRepBeep();
            } else {
                icon.className = 'fas fa-volume-mute';
                soundToggle.title = 'Sound Off - Click to Enable';
            }
        }

        // Start overall timer
        function startOverallTimer() {
            workoutStartTime = new Date();
            pausedTime = 0;
            updateOverallTimer();
            overallTimerInterval = setInterval(updateOverallTimer, 1000);
        }

        function updateOverallTimer() {
            if (isPaused) return;
            
            const now = new Date();
            const diff = Math.floor((now - workoutStartTime) / 1000) - pausedTime;
            workoutDuration = diff;
            const hours = Math.floor(diff / 3600);
            const minutes = Math.floor((diff % 3600) / 60);
            const seconds = diff % 60;
            
            if (hours > 0) {
                timerDisplay.textContent = ${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')};
            } else {
                timerDisplay.textContent = ${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')};
            }
        }

        // Enhanced pause/resume with better timer management
        function togglePause() {
            isPaused = !isPaused;
            
            if (isPaused) {
                if (timerInterval) {
                    clearInterval(timerInterval);
                    timerInterval = null;
                }
                if (repTimerInterval) {
                    clearInterval(repTimerInterval);
                    repTimerInterval = null;
                }
                if (overallTimerInterval) {
                    clearInterval(overallTimerInterval);
                }
                if (exerciseTimeout) {
                    clearTimeout(exerciseTimeout);
                }
                
                pauseBtn.innerHTML = '<i class="fas fa-play mr-2"></i> Resume';
                pauseBtn.classList.remove('btn-pause');
                pauseBtn.classList.add('bg-green-600', 'hover:bg-green-700');
                
                window.pauseStartTime = new Date();
                
                if (currentExercise) {
                    const exerciseCard = document.getElementById(exercise-${currentExercise});
                    const statusMessage = exerciseCard.querySelector('.status-message');
                    statusMessage.classList.add('text-yellow-600');
                    statusMessage.textContent = statusMessage.textContent + ' (PAUSED)';
                }
            } else {
                if (window.pauseStartTime) {
                    pausedTime += Math.floor((new Date() - window.pauseStartTime) / 1000);
                    window.pauseStartTime = null;
                }
                
                overallTimerInterval = setInterval(updateOverallTimer, 1000);
                
                pauseBtn.innerHTML = '<i class="fas fa-pause mr-2"></i> Pause';
                pauseBtn.classList.add('btn-pause');
                pauseBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
                
                if (currentExercise && secondsRemaining > 0) {
                    const exerciseCard = document.getElementById(exercise-${currentExercise});
                    const statusMessage = exerciseCard.querySelector('.status-message');
                    
                    statusMessage.classList.remove('text-yellow-600');
                    statusMessage.textContent = statusMessage.textContent.replace(' (PAUSED)', '');
                    
                    if (statusMessage.textContent.includes('Rest')) {
                        startRestPeriod(exerciseCard, secondsRemaining, statusMessage.textContent);
                    } else if (statusMessage.textContent.includes('Performing')) {
                        startRepCountdown(exerciseCard, repDurationSeconds);
                    }
                }
            }
        }

        // Exercise control functions
        function startExercise(exerciseId, sets, reps, repRest, setRest) {
            if (currentExercise) {
                resetExercise(currentExercise);
            }

            currentExercise = exerciseId;
            currentSet = 1;
            currentRep = 1;
            totalSets = sets;
            totalReps = reps;
            repRestTime = repRest;
            setRestTime = setRest * 60;

            const exerciseCard = document.getElementById(exercise-${exerciseId});
            exerciseCard.classList.add('active');
            
            const startBtn = exerciseCard.querySelector('.start-exercise-btn');
            startBtn.disabled = true;
            startBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> In Progress';
            startBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
            startBtn.classList.add('bg-blue-600');

            const progressContainer = exerciseCard.querySelector('.progress-container');
            progressContainer.classList.remove('hidden');

            pauseBtn.classList.remove('hidden');

            askPreparationTime(exerciseCard);
        }

        function askPreparationTime(exerciseCard) {
            let prepTime;
            do {
                prepTime = prompt('How many seconds do you need to prepare before starting this exercise?', '10');
                if (prepTime === null) {
                    resetExercise(currentExercise);
                    return;
                }
                prepTime = parseInt(prepTime);
            } while (isNaN(prepTime) || prepTime < 0 || prepTime > 300);

            if (prepTime === 0) {
                startRepCountdown(exerciseCard, repDurationSeconds);
            } else {
                startPreparationCountdown(exerciseCard, prepTime);
            }
        }

        function startPreparationCountdown(exerciseCard, prepSeconds) {
            const statusMessage = exerciseCard.querySelector('.status-message');
            const countdownTimer = exerciseCard.querySelector('.countdown-timer');

            statusMessage.textContent = 'Prepare for exercise...';
            statusMessage.classList.add('pulse');

            let secondsRemaining = prepSeconds;
            updateCountdownDisplay(countdownTimer, secondsRemaining);

            const prepInterval = setInterval(() => {
                if (isPaused) return;
                
                secondsRemaining--;
                updateCountdownDisplay(countdownTimer, secondsRemaining);

                if (secondsRemaining <= 3 && secondsRemaining > 0) {
                    playCountdownBeep();
                    countdownTimer.classList.add(secondsRemaining === 1 ? 'countdown-final' : 'countdown-warning');
                }

                if (secondsRemaining <= 0) {
                    clearInterval(prepInterval);
                    statusMessage.classList.remove('pulse');
                    countdownTimer.classList.remove('countdown-warning', 'countdown-final');
                    
                    playStartBeep();
                    setTimeout(() => {
                        if (!isPaused) {
                            startRepCountdown(exerciseCard, repDurationSeconds);
                        }
                    }, 500);
                }
            }, 1000);
        }

        function startRepCountdown(exerciseCard, duration = repDurationSeconds) {
            const statusMessage = exerciseCard.querySelector('.status-message');
            const countdownTimer = exerciseCard.querySelector('.countdown-timer');
            const progressFill = exerciseCard.querySelector('.progress-fill');

            if (repTimerInterval) {
                clearInterval(repTimerInterval);
                repTimerInterval = null;
            }
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }

            statusMessage.textContent = Performing Set ${currentSet}, Rep ${currentRep};
            statusMessage.classList.remove('text-green-600', 'font-bold');
            statusMessage.classList.add('pulse');
            
            const setProgress = ((currentSet - 1) / totalSets) * 100;
            const repProgress = ((currentRep - 1) / totalReps) * (100 / totalSets);
            const totalProgress = setProgress + repProgress;
            progressFill.style.width = ${totalProgress}%;

            let repSecondsRemaining = duration;
            countdownTimer.textContent = repSecondsRemaining;
            countdownTimer.classList.add('rep-timer');
            countdownTimer.classList.remove('countdown-warning', 'countdown-final', 'warning', 'danger');
            
            if (!isPaused) {
                repTimerInterval = setInterval(() => {
                    repSecondsRemaining--;
                    countdownTimer.textContent = repSecondsRemaining > 0 ? repSecondsRemaining : 'Complete!';
                    
                    if (repSecondsRemaining <= 1) {
                        countdownTimer.classList.add('danger');
                        countdownTimer.classList.remove('warning');
                    } else if (repSecondsRemaining <= 2) {
                        countdownTimer.classList.add('warning');
                        countdownTimer.classList.remove('danger');
                    } else {
                        countdownTimer.classList.remove('warning', 'danger');
                    }
                    
                    if (repSecondsRemaining <= 3 && repSecondsRemaining > 0) {
                        playCountdownBeep();
                    }
                    
                    if (repSecondsRemaining <= 0) {
                        clearInterval(repTimerInterval);
                        repTimerInterval = null;
                        statusMessage.classList.remove('pulse');
                        completeRep(exerciseCard);
                    }
                }, 1000);
            }
        }

        function completeRep(exerciseCard) {
            const statusMessage = exerciseCard.querySelector('.status-message');
            const countdownTimer = exerciseCard.querySelector('.countdown-timer');
            const progressFill = exerciseCard.querySelector('.progress-fill');
            
            if (repTimerInterval) {
                clearInterval(repTimerInterval);
                repTimerInterval = null;
            }
            
            playRepBeep();
            
            const setProgress = ((currentSet - 1) / totalSets) * 100;
            const repProgress = (currentRep / totalReps) * (100 / totalSets);
            const totalProgress = setProgress + repProgress;
            progressFill.style.width = ${totalProgress}%;
            
            statusMessage.textContent = Rep ${currentRep} completed!;
            statusMessage.classList.add('text-green-600', 'font-bold');
            countdownTimer.textContent = '✓';
            countdownTimer.classList.remove('rep-timer', 'warning', 'danger');
            
            setTimeout(() => {
                if (currentRep < totalReps) {
                    currentRep++;
                    exerciseCard.querySelector('.current-rep').textContent = currentRep;
                    
                    if (repRestTime > 0) {
                        startRestPeriod(exerciseCard, repRestTime, Rest between reps (${currentRep-1}/${totalReps}));
                    } else {
                        statusMessage.classList.remove('text-green-600', 'font-bold');
                        startRepCountdown(exerciseCard, repDurationSeconds);
                    }
                } else {
                    if (currentSet < totalSets) {
                        currentSet++;
                        currentRep = 1;
                        exerciseCard.querySelector('.current-set').textContent = currentSet;
                        exerciseCard.querySelector('.current-rep').textContent = currentRep;
                        
                        if (setRestTime > 0) {
                            startSetRestPeriod(exerciseCard, setRestTime);
                        } else {
                            statusMessage.classList.remove('text-green-600', 'font-bold');
                            playSetPreparationBeeps(() => {
                                startRepCountdown(exerciseCard, repDurationSeconds);
                            });
                        }
                    } else {
                        exerciseComplete(exerciseCard);
                    }
                }
            }, 300);
        }

        function startRestPeriod(exerciseCard, duration, restType) {
            const statusMessage = exerciseCard.querySelector('.status-message');
            const countdownTimer = exerciseCard.querySelector('.countdown-timer');

            statusMessage.textContent = restType;
            statusMessage.classList.remove('text-green-600', 'font-bold', 'pulse');
            
            secondsRemaining = duration;
            updateCountdownDisplay(countdownTimer, secondsRemaining);
            
            if (timerInterval) clearInterval(timerInterval);
            if (repTimerInterval) clearInterval(repTimerInterval);
            
            if (!isPaused) {
                timerInterval = setInterval(() => {
                    secondsRemaining--;
                    updateCountdownDisplay(countdownTimer, secondsRemaining);
                    
                    if (secondsRemaining <= 3 && secondsRemaining > 0) {
                        playCountdownBeep();
                        countdownTimer.classList.add(secondsRemaining === 1 ? 'countdown-final' : 'countdown-warning');
                    }
                    
                    if (secondsRemaining <= 0) {
                        clearInterval(timerInterval);
                        timerInterval = null;
                        countdownTimer.classList.remove('countdown-warning', 'countdown-final');
                        
                        playStartBeep();
                        setTimeout(() => {
                            if (!isPaused) {
                                startRepCountdown(exerciseCard, repDurationSeconds);
                            }
                        }, 500);
                    }
                }, 1000);
            }
        }

        function startSetRestPeriod(exerciseCard, duration) {
            const statusMessage = exerciseCard.querySelector('.status-message');
            const countdownTimer = exerciseCard.querySelector('.countdown-timer');

            statusMessage.textContent = 'Rest between sets';
            statusMessage.classList.remove('text-green-600', 'font-bold', 'pulse');
            secondsRemaining = duration;
            
            updateCountdownDisplay(countdownTimer, secondsRemaining);
            
            startSetRestCountdown(exerciseCard, secondsRemaining);
        }

        function startSetRestCountdown(exerciseCard, duration) {
            const statusMessage = exerciseCard.querySelector('.status-message');
            const countdownTimer = exerciseCard.querySelector('.countdown-timer');
            
            if (timerInterval) clearInterval(timerInterval);
            if (repTimerInterval) clearInterval(repTimerInterval);
            
            secondsRemaining = duration;
            updateCountdownDisplay(countdownTimer, secondsRemaining);
            
            const firstWarningTime = Math.max(10, secondsRemaining - 9);
            const secondWarningTime = Math.max(6, secondsRemaining - 5);
            const finalWarningTime = 3;

            if (!isPaused) {
                timerInterval = setInterval(() => {
                    secondsRemaining--;
                    updateCountdownDisplay(countdownTimer, secondsRemaining);
                    
                    if (duration >= 60) {
                        if (secondsRemaining === 10) {
                            playLongBeep();
                            statusMessage.textContent = 'Rest between sets - Get ready!';
                            statusMessage.classList.add('countdown-warning');
                        } else if (secondsRemaining === 6) {
                            playSetWarningBeep();
                        } else if (secondsRemaining === 3) {
                            playSetWarningBeep();
                        } else if (secondsRemaining === 1) {
                            playLongBeep();
                        }
                    } else if (duration >= 30) {
                        if (secondsRemaining === firstWarningTime) {
                            playLongBeep();
                            statusMessage.textContent = 'Rest between sets - Get ready!';
                            statusMessage.classList.add('countdown-warning');
                        } else if (secondsRemaining === secondWarningTime) {
                            playSetWarningBeep();
                        } else if (secondsRemaining === finalWarningTime) {
                            playSetWarningBeep();
                        } else if (secondsRemaining === 1) {
                            playLongBeep();
                        }
                    } else {
                        if (secondsRemaining <= 3 && secondsRemaining > 0) {
                            playCountdownBeep();
                            countdownTimer.classList.add(secondsRemaining === 1 ? 'countdown-final' : 'countdown-warning');
                        }
                    }
                    
                    if (secondsRemaining <= 0) {
                        clearInterval(timerInterval);
                        timerInterval = null;
                        statusMessage.classList.remove('countdown-warning');
                        countdownTimer.classList.remove('countdown-warning', 'countdown-final');
                        
                        playSetPreparationBeeps(() => {
                            startRepCountdown(exerciseCard, repDurationSeconds);
                        });
                    }
                }, 1000);
            }
        }

        function playSetPreparationBeeps(callback) {
            const statusMessage = document.getElementById(exercise-${currentExercise}).querySelector('.status-message');
            const countdownTimer = document.getElementById(exercise-${currentExercise}).querySelector('.countdown-timer');
            
            statusMessage.textContent = 'New set starting...';
            statusMessage.classList.add('pulse');
            countdownTimer.textContent = 'GET READY!';
            countdownTimer.classList.add('countdown-warning');
            
            playLongBeep();
            
            setTimeout(() => {
                playLongBeep();
                
                setTimeout(() => {
                    statusMessage.classList.remove('pulse');
                    countdownTimer.classList.remove('countdown-warning');
                    playStartBeep();
                    
                    setTimeout(() => {
                        if (!isPaused && callback) {
                            callback();
                        }
                    }, 500);
                }, 3000);
            }, 3000);
        }

        function updateCountdownDisplay(element, seconds) {
            const mins = Math.floor(seconds / 60).toString().padStart(2, '0');
            const secs = (seconds % 60).toString().padStart(2, '0');
            element.textContent = ${mins}:${secs};
        }

        function exerciseComplete(exerciseCard) {
            const statusMessage = exerciseCard.querySelector('.status-message');
            const countdownTimer = exerciseCard.querySelector('.countdown-timer');
            const progressFill = exerciseCard.querySelector('.progress-fill');
            const startBtn = exerciseCard.querySelector('.start-exercise-btn');

            if (repTimerInterval) {
                clearInterval(repTimerInterval);
                repTimerInterval = null;
            }
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }

            statusMessage.textContent = All ${totalSets} sets completed!;
            statusMessage.classList.remove('pulse');
            statusMessage.classList.add('text-green-600', 'font-bold');
            countdownTimer.textContent = '🎉';
            countdownTimer.classList.remove('rep-timer', 'warning', 'danger', 'countdown-warning', 'countdown-final');
            
            progressFill.style.width = '100%';
            progressFill.style.background = 'linear-gradient(90deg, #10b981, #059669)';
            
            if (startBtn) {
                startBtn.disabled = false;
                startBtn.innerHTML = '<i class="fas fa-check mr-2"></i> Completed';
                startBtn.classList.remove('bg-blue-600');
                startBtn.classList.add('bg-green-600');
                startBtn.onclick = null;
            }
            
            exerciseCard.classList.remove('active');
            exerciseCard.classList.add('completed');

            playCompleteBeep();
            
            completedExercises++;
            updateOverallProgress();

            if (currentExercise === parseInt(exerciseCard.id.split('-')[1])) {
                pauseBtn.classList.add('hidden');
                currentExercise = null;
            }
            
            recordExerciseCompletion(
                parseInt(exerciseCard.id.split('-')[1]), 
                totalSets, 
                totalSets * totalReps, 
                workoutDuration
            );
            
            currentSet = 1;
            currentRep = 1;
            secondsRemaining = 0;
        }

        function updateOverallProgress() {
            const totalExercises = <?= count($workout['exercises']) ?>;
            const percentage = totalExercises > 0 ? Math.round((completedExercises / totalExercises) * 100) : 0;
            
            completedExercisesSpan.textContent = completedExercises;
            completionPercentage.textContent = ${percentage}%;
            overallProgress.style.width = ${percentage}%;
            
            if (percentage === 100) {
                setTimeout(() => {
                    alert('🎉 Congratulations! You\'ve completed your workout for ' + '<?= $day ?>');
                    playCompleteBeep();
                }, 500);
            }
        }
function recordExerciseCompletion(exerciseId, setsCompleted, repsCompleted, duration) {
    // Create form data
    const formData = new FormData();
    formData.append('user_id', <?= $user_id ?>);
    formData.append('exercise_id', exerciseId);
    formData.append('sets_completed', setsCompleted);
    formData.append('reps_completed', repsCompleted);
    formData.append('duration', duration);
    formData.append('day', '<?= $day ?>');
    formData.append('action', 'record_completion');

    fetch('page6.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Failed to record exercise completion:', data.message);
        } else {
            console.log('Exercise completion recorded successfully');
        }
    })
    .catch(error => {
        console.error('Error recording exercise completion:', error);
        // Fallback: Try to record completion anyway
        console.log('Exercise marked as completed locally');
    });
}
        function resetExercise(exerciseId) {
            const exerciseCard = document.getElementById(exercise-${exerciseId});
            if (!exerciseCard) return;

            exerciseCard.classList.remove('active');
            
            const startBtn = exerciseCard.querySelector('.start-exercise-btn');
            if (startBtn && !exerciseCard.classList.contains('completed')) {
                startBtn.disabled = false;
                startBtn.innerHTML = '<i class="fas fa-play mr-2"></i> Start';
                startBtn.classList.remove('bg-blue-600');
                startBtn.classList.add('bg-green-600', 'hover:bg-green-700');
            }

            const progressContainer = exerciseCard.querySelector('.progress-container');
            if (progressContainer) {
                progressContainer.classList.add('hidden');
            }

            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }
            
            if (repTimerInterval) {
                clearInterval(repTimerInterval);
                repTimerInterval = null;
            }
            
            if (exerciseTimeout) {
                clearTimeout(exerciseTimeout);
                exerciseTimeout = null;
            }
        }

        // Event listeners
        pauseBtn.addEventListener('click', togglePause);
        soundToggle.addEventListener('click', toggleSound);

        // Initialize on page load
        window.addEventListener('load', async () => {
            await initAudioContext();
            startOverallTimer();
            
            <?php if (!empty($workout['exercises'])): ?>
                setTimeout(() => {
                    if (confirm('Ready to start your <?= $day ?> workout?\n\nNew Features:\n- You\'ll be asked for preparation time before each exercise starts\n- Special warning beeps for set transitions\n- Each rep auto-completes after ' + repDurationSeconds + ' seconds\n- Use SPACE to pause/resume, S for sound toggle')) {
                        document.querySelector('.start-exercise-btn')?.scrollIntoView({ behavior: 'smooth' });
                    }
                }, 1000);
            <?php endif; ?>
        });

        // Clean up on page unload
        window.addEventListener('beforeunload', () => {
            if (overallTimerInterval) clearInterval(overallTimerInterval);
            if (timerInterval) clearInterval(timerInterval);
            if (repTimerInterval) clearInterval(repTimerInterval);
            if (exerciseTimeout) clearTimeout(exerciseTimeout);
            if (audioContext) audioContext.close();
        });

        // Prevent accidental page reload during workout
        window.addEventListener('beforeunload', (e) => {
            if (currentExercise) {
                e.preventDefault();
                e.returnValue = 'You have an active workout. Are you sure you want to leave?';
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.code === 'Space') {
                e.preventDefault();
                if (!pauseBtn.classList.contains('hidden')) {
                    togglePause();
                }
            } else if (e.code === 'KeyS') {
                toggleSound();
            }
        });
    </script>
</body>
</html>
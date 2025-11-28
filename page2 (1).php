<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Muscle Groups | FitAI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: #333;
        }

        /* Header Styles */
        header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            display: flex;
            align-items: center;
        }

        .logo span {
            color: #3498db;
        }

        .back-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #2980b9;
        }

        /* Main Content Styles */
        .main-content {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        h1 {
            text-align: center;
            margin-bottom: 2rem;
            color: #2c3e50;
        }

        .image-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            gap: 2rem;
        }

        .muscle-image {
            flex: 1;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            background: #e8f4f8;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 400px;
        }

        .muscle-image img {
            width: 100%;
            height: auto;
            display: block;
        }

        .placeholder-text {
            text-align: center;
            color: #999;
            font-size: 1rem;
        }

        .muscle-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
            margin-top: 2rem;
        }

        .muscle-number {
            background: white;
            border: 2px solid #3498db;
            color: #3498db;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0 auto;
        }

        .muscle-number:hover {
            background: #3498db;
            color: white;
        }

        .muscle-info {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
            display: none;
        }

        .muscle-info h2 {
            color: #2c3e50;
            margin-bottom: 1rem;
            border-bottom: 2px solid #3498db;
            padding-bottom: 0.5rem;
        }

        .muscle-info p {
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .active {
            background: #3498db;
            color: white;
        }

        @media (max-width: 768px) {
            .image-container {
                flex-direction: column;
            }
            
            .muscle-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 480px) {
            .muscle-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-dumbbell" style="margin-right: 10px;"></i>
                Fit<span>AI</span>
            </div>
            <button class="back-btn" onclick="window.history.back()">
                <i class="fas fa-arrow-left"></i> Back
            </button>
        </div>
    </header>

    <div class="main-content">
        <h1>Muscle Groups</h1>
        
        <div class="image-container">
            <div class="muscle-image">
                <!-- Fixed image path -->
                <img src="images/anterior.png" alt="Anterior Muscles" onerror="handleImageError(this)">
            </div>
            <div class="muscle-image">
                <!-- Fixed image path -->
                <img src="images/Posterior.png" alt="Posterior Muscles" onerror="handleImageError(this)">
            </div>
        </div>

        <div class="muscle-grid" id="muscle-grid">
            <!-- Muscle numbers will be generated here -->
        </div>

        <div id="muscle-info" class="muscle-info">
            <!-- Content will be dynamically inserted here -->
        </div>
    </div>

    <script>
        const muscleData = {
            1: {
                name: "Shoulders (Deltoids)",
                description: "The deltoids are responsible for arm rotation and lifting. Strong shoulders improve posture, enhance upper body strength, and prevent injuries. Training them helps with pushing movements and provides that coveted V-taper look. Well-developed delts contribute to better performance in sports like swimming and basketball while reducing risk of rotator cuff injuries."
            },
            2: {
                name: "Biceps",
                description: "Biceps are the most visible arm muscles, responsible for elbow flexion and forearm supination. Training biceps improves pulling strength, enhances arm aesthetics, and supports daily activities like lifting objects. Strong biceps protect elbow joints and improve grip strength. They're crucial for exercises like chin-ups and rows, and contribute to overall arm balance with triceps."
            },
            3: {
                name: "Flexors",
                description: "Wrist flexors control hand and finger movements. Training them improves grip strength crucial for deadlifts and pull-ups. Strong flexors prevent wrist injuries and enhance performance in racquet sports, climbing, and typing. They're essential for any activity requiring hand endurance and contribute to forearm muscular development."
            },
            4: {
                name: "Extensors",
                description: "Wrist extensors work opposite flexors to open the hand. Strengthening them balances forearm development, prevents tennis elbow, and improves throwing motions. They're vital for rehabilitation after wrist injuries and enhance performance in sports requiring wrist stability like golf or boxing. Strong extensors improve overall grip endurance."
            },
            5: {
                name: "Supinator",
                description: "The supinator muscle rotates the forearm to palm-up position. Training it improves screwdriver motions, throwing accuracy, and pulling exercises. Strong supination prevents elbow injuries and enhances performance in tennis backhands or baseball pitching. It's crucial for balanced forearm development and functional strength."
            },
            6: {
                name: "Pronator",
                description: "Pronators rotate the forearm to palm-down position. Strengthening them improves hammering motions, typing endurance, and pushing exercises. Balanced pronator/supinator strength prevents elbow overuse injuries. They're essential for baseball pitching follow-through and contribute to overall forearm symmetry."
            },
            7: {
                name: "Brachioradialis",
                description: "This prominent forearm muscle flexes the elbow. Training it enhances grip strength for deadlifts and pull-ups while improving forearm aesthetics. Strong brachioradialis helps in hammer curls and neutral grip exercises. It's crucial for arm wrestling power and protects against elbow tendonitis."
            },
            8: {
                name: "Upper Chest (Clavicular Head)",
                description: "The upper pectorals give the chest a full, developed look. Training them improves pushing strength at angles above parallel and enhances bench press performance. Strong upper chest contributes to better posture and shoulder health. It's crucial for athletes performing overhead movements and creates that sought-after armor-like chest appearance."
            },
            9: {
                name: "Lower Chest (Sternal Head)",
                description: "The lower pectorals provide pressing power and chest definition. Training them enhances bench press strength and contributes to the chest 'sweep'. Strong lower pecs improve performance in dips and push-ups while supporting shoulder joint health. They're essential for the classic bodybuilding chest development."
            },
            10: {
                name: "Middle Chest",
                description: "The middle pectorals provide overall chest mass and thickness. Training them improves horizontal pushing strength and enhances the chest's center development. Strong middle pecs are crucial for bench press performance and create that unified chest look. They contribute to better posture and upper body power for contact sports."
            },
            11: {
                name: "Upper Abs",
                description: "The upper rectus abdominis is responsible for trunk flexion. Training them improves core stability and creates visible 'six-pack' definition. Strong upper abs enhance athletic performance in jumping and throwing while preventing lower back pain. They're crucial for maintaining proper posture and spinal health."
            },
            12: {
                name: "Lower Abs",
                description: "The lower rectus abdominis controls pelvic tilt. Training them improves core stability for squats and deadlifts while creating balanced abdominal development. Strong lower abs prevent anterior pelvic tilt and lower back pain. They're essential for leg raise movements and contribute to that complete 'eight-pack' look."
            },
            13: {
                name: "Obliques",
                description: "The obliques enable torso rotation and lateral flexion. Training them improves rotational power for sports like golf and baseball while creating the coveted 'V-taper'. Strong obliques protect against spine injuries and enhance core stability. They're crucial for any athletic movement involving twisting and contribute to waist definition."
            },
            14: {
                name: "Transverse Abdominals",
                description: "The deepest core muscle acts as a natural weight belt. Training it improves posture, enhances lifting stability, and flattens the stomach. Strong transverse abs prevent lower back injuries and are crucial for proper breathing mechanics. They're the foundation for all core training and athletic performance."
            },
            15: {
                name: "Quadriceps",
                description: "The quadriceps are powerful knee extensors crucial for running and jumping. Training them improves athletic performance, enhances leg aesthetics, and boosts metabolism. Strong quads protect knee joints and improve squat strength. They're essential for explosive movements in sports and contribute to balanced lower body development with hamstrings."
            },
            16: {
                name: "Latissimus Dorsi (Lats)",
                description: "The largest back muscles responsible for pulling movements and shoulder adduction. Well-developed lats create the coveted V-taper physique. Strong lats improve posture, enhance pulling strength for exercises like pull-ups and rows, and support shoulder health. They're crucial for swimming, climbing, and throwing sports while helping prevent shoulder injuries."
            },
            17: {
                name: "Trapezius (Traps)",
                description: "The trapezius muscles control shoulder blade movement and neck support. Training them improves posture and reduces neck strain. Strong traps enhance shoulder stability for heavy lifts and contribute to that powerful upper body look. They're essential for Olympic lifts, deadlifts, and any activity requiring shoulder shrug movements. Well-developed traps help prevent shoulder impingement."
            },
            18: {
                name: "Deltoids (All Heads)",
                description: "The shoulder muscles comprising anterior, lateral, and posterior heads. Complete deltoid development creates rounded, athletic shoulders. Strong delts improve all pressing and lifting motions while protecting rotator cuffs. They're crucial for throwing, swimming, and overhead movements. Balanced deltoid training prevents shoulder imbalances and enhances upper body aesthetics."
            },
            19: {
                name: "Triceps Brachii",
                description: "The arm's extensor muscles making up 2/3 of upper arm mass. Developed triceps create arm thickness and improve pushing strength. Strong triceps enhance bench press performance, punching power, and throwing velocity. They're crucial for elbow joint stability and counterbalance biceps development. Triceps endurance benefits daily pushing activities and sports performance."
            },
            20: {
                name: "Gluteus Maximus",
                description: "The body's largest muscle responsible for hip extension and power generation. Strong glutes improve sprinting, jumping, and lifting performance while preventing lower back pain. Well-developed glutes enhance posture and create balanced lower body aesthetics. They're crucial for squats, deadlifts, and athletic movements. Glute strength reduces risk of hamstring and knee injuries."
            },
            21: {
                name: "Hamstrings",
                description: "The posterior thigh muscles controlling knee flexion and hip extension. Balanced hamstring development prevents ACL injuries and improves running speed. Strong hamstrings enhance squat depth, jumping ability, and athletic performance. They're crucial for deceleration movements and work synergistically with quads. Flexible, powerful hamstrings reduce lower back strain during bending motions."
            },
            22: {
                name: "Gastrocnemius & Soleus (Calves)",
                description: "The calf muscles responsible for ankle plantar flexion. Strong calves improve jumping height, running efficiency, and balance. Developed calves enhance lower leg aesthetics and support proper walking mechanics. They're crucial for sports requiring explosive lower body movements and help prevent shin splints. Calf endurance benefits activities like hiking and dancing while supporting knee and ankle joints."
            }
        };

        function generateMuscleNumbers() {
            const grid = document.getElementById('muscle-grid');
            for (let i = 1; i <= 22; i++) {
                const div = document.createElement('div');
                div.className = 'muscle-number';
                div.textContent = i;
                div.onclick = () => showMuscleInfo(i, div);
                grid.appendChild(div);
            }
        }

        function showMuscleInfo(number, element) {
            const infoContainer = document.getElementById('muscle-info');
            const muscle = muscleData[number];
            
            // Update active number styling
            document.querySelectorAll('.muscle-number').forEach(el => {
                el.classList.remove('active');
            });
            element.classList.add('active');
            
            // Display muscle info
            infoContainer.innerHTML = `
                <h2>${muscle.name}</h2>
                <p>${muscle.description}</p>
            `;
            infoContainer.style.display = 'block';
        }

        // Handle image loading errors
        function handleImageError(img) {
            console.error('Failed to load image:', img.src);
            img.style.display = 'none';
            const placeholder = document.createElement('div');
            placeholder.className = 'placeholder-text';
            placeholder.innerHTML = `
                <p><strong>Image Not Found</strong></p>
                <p style="margin-top: 1rem; font-size: 0.9rem;">Please check the image path: ${img.src}</p>
            `;
            img.parentNode.appendChild(placeholder);
        }

        // Initialize on page load
        window.onload = function() {
            generateMuscleNumbers();
            const firstNumber = document.querySelector('.muscle-number');
            showMuscleInfo(1, firstNumber);
        };
    </script>
</body>
</html>
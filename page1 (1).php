<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Fitness Scheduler</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background-color: #f8f9fa;
            color: #333;
        }

        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 2rem 1rem;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            height: 100vh;
            position: fixed;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            padding-left: 0.5rem;
            color: #fff;
            display: flex;
            align-items: center;
        }

        .logo span {
            color: #3498db;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-item a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #ecf0f1;
            text-decoration: none;
            font-weight: 500;
        }

        .nav-item i {
            margin-right: 10px;
            font-size: 1.1rem;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 3rem;
        }

        .search-container {
            display: flex;
            justify-content: center;
            margin-top: 5rem;
            flex-direction: column;
            align-items: center;
        }

        .search-bar {
            width: 600px;
            display: flex;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border-radius: 50px;
            overflow: hidden;
        }

        .search-input {
            flex: 1;
            padding: 1.2rem 1.5rem;
            border: none;
            font-size: 1rem;
            outline: none;
        }

        .search-button {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 0 2rem;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .search-button:hover {
            background: linear-gradient(135deg, #2980b9, #3498db);
        }

        .results-container {
            width: 600px;
            margin-top: 2rem;
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .exercise-result {
            margin-bottom: 1.5rem;
        }

        .exercise-result h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .exercise-instruction {
            line-height: 1.8;
            margin-bottom: 1rem;
            white-space: pre-line;
        }

        .exercise-section {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .exercise-section:last-child {
            border-bottom: none;
        }

        .exercise-section h4 {
            color: #3498db;
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }

        .exercise-section ul, .exercise-section ol {
            padding-left: 1.5rem;
            margin: 0.5rem 0;
        }

        .exercise-section li {
            margin-bottom: 0.3rem;
            line-height: 1.6;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-left: 10px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .error-message {
            color: #e74c3c;
            background-color: #fde8e8;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
            }
            .search-bar,
            .results-container {
                width: 100%;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <i class="fas fa-dumbbell" style="margin-right: 10px;"></i>
            Fitness<span>AI</span>
        </div>
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="page8.1.php">
                    <i class="fa-solid fa-user"></i>
                    Profile
                </a>
            </li>
            <li class="nav-item">
                <a href="page2.php">
                    <i class="fas fa-male"></i>
                    View Muscle Groups
                </a>
            </li>
            <li class="nav-item">
                <a href="page4.1.php">
                    <i class="fas fa-cogs"></i>
                    Customize Plan
                </a>
            </li>
            <li class="nav-item">
                <a href="page5.1.php">
                    <i class="fas fa-clipboard-list"></i>
                    My Plan
                </a>
            </li>
            <li class="nav-item">
                <a href="page6.php">
                    <i class="fas fa-calendar-alt"></i>
                    Calendar
                </a>
            </li>
            <li class="nav-item">
                <a href="page7.php">
                    <i class="fas fa-chart-line"></i>
                    Plan Analyser
                </a>
            </li>
            <li class="nav-item">
                <a href="page3.6.php">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="search-container">
            <div class="search-bar">
                <input type="text" id="exercise-search" class="search-input" placeholder="Search any workout (e.g. 'pull ups', 'bodyweight legs', 'dumbbell chest')">
                <button id="search-btn" class="search-button">GO</button>
            </div>
            <div id="results-container" class="results-container" style="display: none;">
                <!-- Results will appear here -->
            </div>
        </div>
    </div>

    <script>
        // Gemini API Configuration
        const GEMINI_API_KEY = "AIzaSyBTYTAJJ1C_T6h0E6dqkdzVP7nSAZY36mk";
        const GEMINI_API_URL = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=${GEMINI_API_KEY}`;

        const searchInput = document.getElementById('exercise-search');
        const searchBtn = document.getElementById('search-btn');
        const resultsContainer = document.getElementById('results-container');

        searchBtn.addEventListener('click', searchExercise);
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') searchExercise();
        });

        async function searchExercise() {
            const query = searchInput.value.trim();
            if (!query) {
                showError("Please enter a workout to search");
                return;
            }

            searchBtn.innerHTML = 'Searching <span class="loading"></span>';
            searchBtn.disabled = true;
            resultsContainer.style.display = "none";

            try {
                const response = await fetch(GEMINI_API_URL, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        contents: [{
                            parts: [{
                                text: `Act as a professional fitness trainer. Provide detailed instructions for: ${query}
                                
                                Please format your response WITHOUT any markdown symbols like #, *, -, etc.
                                Use plain text with clear section headings.
                                
                                Required sections:
                                1. Exercise Name and Description
                                2. Targeted Muscle Groups
                                3. Step-by-Step Execution
                                4. Common Mistakes to Avoid
                                5. Recommended Sets and Reps
                                6. Equipment Needed
                                7. Variations for Different Levels
                                
                                Keep it concise but comprehensive. Use bullet points and numbered lists but without markdown symbols.`
                            }]
                        }],
                        generationConfig: {
                            temperature: 0.7,
                            maxOutputTokens: 2000,
                            topK: 40,
                            topP: 0.95
                        },
                        safetySettings: [
                            {
                                category: "HARM_CATEGORY_HARASSMENT",
                                threshold: "BLOCK_MEDIUM_AND_ABOVE"
                            },
                            {
                                category: "HARM_CATEGORY_HATE_SPEECH",
                                threshold: "BLOCK_MEDIUM_AND_ABOVE"
                            },
                            {
                                category: "HARM_CATEGORY_SEXUALLY_EXPLICIT",
                                threshold: "BLOCK_MEDIUM_AND_ABOVE"
                            },
                            {
                                category: "HARM_CATEGORY_DANGEROUS_CONTENT",
                                threshold: "BLOCK_MEDIUM_AND_ABOVE"
                            }
                        ]
                    })
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    let errorMessage = "Unknown error occurred";
                    
                    if (errorData.error) {
                        errorMessage = errorData.error.message || errorData.error.details || errorMessage;
                        
                        if (errorMessage.includes("API_KEY_INVALID")) {
                            errorMessage = "Invalid API key. Please check your Gemini API key.";
                        } else if (errorMessage.includes("QUOTA_EXCEEDED")) {
                            errorMessage = "API quota exceeded. Please try again later or check your billing.";
                        } else if (errorMessage.includes("not found")) {
                            errorMessage = "Model not found. Please verify the API endpoint.";
                        }
                    }
                    
                    throw new Error(errorMessage);
                }

                const data = await response.json();
                
                if (!data.candidates || !data.candidates[0]?.content?.parts?.[0]?.text) {
                    throw new Error("Invalid API response structure. Please try again.");
                }

                const answer = data.candidates[0].content.parts[0].text;
                
                displayResults({
                    name: query.charAt(0).toUpperCase() + query.slice(1),
                    content: answer
                });
            } catch (error) {
                console.error("API Error:", error);
                showError(`Failed to get workout information: ${error.message}`);
            } finally {
                searchBtn.innerHTML = 'GO';
                searchBtn.disabled = false;
            }
        }

        function displayResults(result) {
            resultsContainer.style.display = "block";
            
            // Clean the content by removing markdown symbols
            const cleanContent = cleanMarkdown(result.content);
            
            // Parse and format the content into sections
            const formattedContent = formatContentIntoSections(cleanContent);
            
            resultsContainer.innerHTML = `
                <div class="exercise-result">
                    <h3>${result.name}</h3>
                    ${formattedContent}
                </div>
            `;
        }

        function cleanMarkdown(text) {
            return text
                // Remove markdown headers (###, ##, #)
                .replace(/^#{1,3}\s+/gm, '')
                // Remove markdown bold/italic (**text**, *text*)
                .replace(/\*\*(.*?)\*\*/g, '$1')
                .replace(/\*(.*?)\*/g, '$1')
                // Remove markdown bullet points but keep the content
                .replace(/^[-*]\s+/gm, '• ')
                // Clean up numbered lists
                .replace(/^(\d+)\.\s+/gm, '$1. ')
                // Remove extra whitespace
                .replace(/\n\s*\n/g, '\n\n')
                .trim();
        }

        function formatContentIntoSections(content) {
            // Split content by common section indicators
            const sections = content.split(/\n(?=\d+\.\s+|\b(?:Exercise Name|Targeted Muscle Groups|Step-by-Step Execution|Common Mistakes|Recommended Sets and Reps|Equipment Needed|Variations)\b)/i);
            
            let html = '';
            
            sections.forEach(section => {
                if (section.trim()) {
                    const lines = section.split('\n').filter(line => line.trim());
                    if (lines.length > 0) {
                        const firstLine = lines[0].trim();
                        const isSectionHeader = /^\d+\.\s+/.test(firstLine) || 
                            /\b(?:Exercise Name|Targeted Muscle Groups|Step-by-Step Execution|Common Mistakes|Recommended Sets and Reps|Equipment Needed|Variations)\b/i.test(firstLine);
                        
                        if (isSectionHeader) {
                            // This is a section header
                            const sectionTitle = firstLine.replace(/^\d+\.\s+/, '');
                            const sectionContent = lines.slice(1).join('\n');
                            
                            html += `
                                <div class="exercise-section">
                                    <h4>${sectionTitle}</h4>
                                    <div class="exercise-instruction">${formatSectionContent(sectionContent)}</div>
                                </div>
                            `;
                        } else {
                            // This is regular content
                            html += `
                                <div class="exercise-section">
                                    <div class="exercise-instruction">${formatSectionContent(section)}</div>
                                </div>
                            `;
                        }
                    }
                }
            });
            
            return html || `<div class="exercise-instruction">${formatSectionContent(content)}</div>`;
        }

        function formatSectionContent(content) {
            if (!content) return '';
            
            // Convert bullet points to HTML list
            let formatted = content
                .split('\n')
                .map(line => {
                    line = line.trim();
                    if (line.startsWith('• ') || line.match(/^[*-]\s+/)) {
                        return `<li>${line.substring(2)}</li>`;
                    } else if (line.match(/^\d+\.\s/)) {
                        return `<li>${line.replace(/^\d+\.\s/, '')}</li>`;
                    } else if (line) {
                        return `<p>${line}</p>`;
                    }
                    return '';
                })
                .join('');
            
            // Wrap consecutive list items in ul
            formatted = formatted.replace(/(<li>.*?<\/li>)+/gs, match => {
                return `<ul>${match}</ul>`;
            });
            
            return formatted || `<p>${content}</p>`;
        }

        function showError(message) {
            resultsContainer.style.display = "block";
            resultsContainer.innerHTML = `
                <div class="error-message">
                    <h3>Error</h3>
                    <p>${message}</p>
                    <p>Try these example searches:</p>
                    <ul>
                        <li>"Proper pull up technique"</li>
                        <li>"Dumbbell shoulder exercises"</li>
                        <li>"Beginner core workout"</li>
                        <li>"Bodyweight leg exercises"</li>
                        <li>"Chest exercises for beginners"</li>
                    </ul>
                </div>
            `;
        }
    </script>
</body>
</html>
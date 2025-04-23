<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$error = '';
$pdo = null;

try {
    require_once 'db_connect.php';
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    $error = "Unable to connect to the database. Please try again later.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_screening'])) {
    $title = trim($_POST['title'] ?? '');
    $type = $_POST['type'] ?? '';
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');

    // Server-side validation
    if (empty($title) || empty($type) || empty($date) || empty($time)) {
        $error = "All fields are required.";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $error = "Invalid date format. Use YYYY-MM-DD.";
    } elseif (!preg_match('/^\d{1,2}:\d{2} (AM|PM)$/', $time)) {
        $error = "Invalid time format. Use HH:MM AM/PM.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO screenings (title, type, date, time) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $type, $date, $time]);
            $success = "Screening added successfully.";
        } catch (PDOException $e) {
            error_log("Insert screening failed: " . $e->getMessage());
            $error = "Failed to add screening: " . $e->getMessage();
        }
    }
}

// Fetch screening details
$screening_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$screening = null;

if (!$error && $pdo && $screening_id > 0) {
    try {
        // Check if screenings table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'screenings'");
        if ($stmt->rowCount() === 0) {
            error_log("Screenings table does not exist");
            $error = "Screenings table not found in database.";
        } else {
            // Fetch specific screening
            $stmt = $pdo->prepare("SELECT title, type, date, time FROM screenings WHERE id = ?");
            $stmt->execute([$screening_id]);
            $screening = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Screening query result for ID $screening_id: " . print_r($screening, true));

            if (!$screening) {
                $error = "Screening not found.";
            }
        }
    } catch (PDOException $e) {
        error_log("Screening query failed: " . $e->getMessage());
        $error = "Failed to load screening details: " . $e->getMessage();
    }
}

// Fetch all screenings for display
$screenings = [];
if (!$error && $pdo) {
    try {
        $stmt = $pdo->query("SELECT id, title, type, date, time FROM screenings ORDER BY date, time");
        $screenings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("All screenings: " . print_r($screenings, true));
    } catch (PDOException $e) {
        error_log("Fetch all screenings failed: " . $e->getMessage());
        $error = "Failed to load screenings list: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FilmForge - <?php echo $screening ? htmlspecialchars($screening['title']) : 'Screening Details'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease-out, transform 0.6s ease-out;
        }
        .animate-on-scroll.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .btn-animated {
            transition: transform 0.2s ease;
        }
        .btn-animated:hover {
            animation: pulse 0.5s;
        }
        .text-shadow {
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }
        .modal {
            transition: opacity 0.3s ease-in-out;
        }
        .modal.hidden {
            opacity: 0;
            pointer-events: none;
        }
        /* Dark mode overrides */
        .dark .bg-gray-100 { background-color: #111827; }
        .dark .bg-white { background-color: #1f2937; }
        .dark .text-gray-900 { color: #e5e7eb; }
        .dark .text-gray-700 { color: #d1d5db; }
        .dark .text-gray-600 { color: #9ca3af; }
        .dark .text-gray-500 { color: #d1d5db; }
        .dark .text-gray-400 { color: #f3f4f6; }
        .dark .text-indigo-600 { color: #818cf8; }
        .dark .text-red-600 { color: #f87171; }
        .dark .bg-gray-800 { background-color: #1f2937; }
        .dark .bg-gray-300 { background-color: #4b5563; }
        .dark .border-gray-300 { border-color: #4b5563; }
        /* Mobile menu */
        .mobile-menu {
            transition: transform 0.3s ease-in-out;
        }
        .mobile-menu.hidden {
            transform: translateX(100%);
        }
    </style>
</head>
<body class="font-inter bg-gray-100 dark:bg-gray-900 transition-colors duration-300 pt-16 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-gray-900 dark:bg-gray-950 shadow-lg animate__animated animate__fadeInDown fixed top-0 left-0 right-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-2xl font-bold text-indigo-400 dark:text-indigo-300">FilmForge</h1>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="index.php#home" class="nav-link text-gray-400 dark:text-gray-300 hover:text-gray-200 dark:hover:text-gray-100 inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium">Home</a>
                        <a href="index.php#films" class="nav-link text-gray-400 dark:text-gray-300 hover:text-gray-200 dark:hover:text-gray-100 inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium">Films</a>
                        <a href="index.php#schedule" class="nav-link border-indigo-400 dark:border-indigo-300 text-gray-200 dark:text-gray-100 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Screenings</a>
                        <a href="index.php#filmmakers" class="nav-link text-gray-400 dark:text-gray-300 hover:text-gray-200 dark:hover:text-gray-100 inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium">Filmmakers</a>
                    </div>
                </div>
                <div class="flex items-center">
                    <button id="mobile-menu-btn" class="sm:hidden text-gray-400 dark:text-gray-300 hover:text-gray-200 dark:hover:text-gray-100 px-2 py-2 rounded-md text-sm font-medium">☰</button>
                    <div class="hidden sm:ml-6 sm:flex sm:items-center">
                        <button id="theme-toggle" class="text-gray-400 hover:text-gray-200 dark:text-gray-300 dark:hover:text-gray-100 px-2 py-2 rounded-md text-sm font-medium" title="Toggle Dark/Light Mode">
                            <span id="theme-icon">☀️</span>
                        </button>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <button id="profile-btn" class="text-gray-400 dark:text-gray-300 hover:text-gray-200 dark:hover:text-gray-100 px-4 py-2 rounded-md text-sm font-medium mr-4">Profile</button>
                            <button onclick="window.location.href='logout.php'" class="bg-red-600 dark:bg-red-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700 dark:hover:bg-red-600 btn-animated">Sign Out</button>
                        <?php else: ?>
                            <button onclick="window.location.href='register.php'" class="bg-indigo-600 dark:bg-indigo-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700 dark:hover:bg-indigo-600 mr-4 btn-animated">Sign Up</button>
                            <button onclick="window.location.href='login.php'" class="text-gray-400 dark:text-gray-300 hover:text-gray-200 dark:hover:text-gray-100 px-4 py-2 rounded-md text-sm font-medium mr-4">Sign In</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Mobile Menu -->
            <div id="mobile-menu" class="mobile-menu hidden sm:hidden fixed top-16 right-0 w-64 bg-gray-900 dark:bg-gray-950 shadow-lg h-[calc(100vh-4rem)]">
                <div class="flex flex-col space-y-4 p-4">
                    <a href="index.php#home" class="text-gray-200 dark:text-gray-100 hover:text-indigo-400 dark:hover:text-indigo-300 text-sm font-medium">Home</a>
                    <a href="index.php#films" class="text-gray-200 dark:text-gray-100 hover:text-indigo-400 dark:hover:text-indigo-300 text-sm font-medium">Films</a>
                    <a href="index.php#schedule" class="text-gray-200 dark:text-gray-100 hover:text-indigo-400 dark:hover:text-indigo-300 text-sm font-medium">Screenings</a>
                    <a href="index.php#filmmakers" class="text-gray-200 dark:text-gray-100 hover:text-indigo-400 dark:hover:text-indigo-300 text-sm font-medium">Filmmakers</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="index.php#profile" class="text-gray-200 dark:text-gray-100 hover:text-indigo-400 dark:hover:text-indigo-300 text-sm font-medium">Profile</a>
                        <a href="logout.php" class="text-red-600 dark:text-red-500 hover:text-red-700 dark:hover:text-red-600 text-sm font-medium">Sign Out</a>
                    <?php else: ?>
                        <a href="register.php" class="text-indigo-600 dark:text-indigo-500 hover:text-indigo-700 dark:hover:text-indigo-600 text-sm font-medium">Sign Up</a>
                        <a href="login.php" class="text-gray-200 dark:text-gray-100 hover:text-indigo-400 dark:hover:text-indigo-300 text-sm font-medium">Sign In</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Screening Details Section -->
    <section class="py-16 animate-on-scroll">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Add Screening Button -->
            <div class="mb-6">
                <button id="add-screening-btn" class="bg-indigo-600 dark:bg-indigo-500 text-white px-6 py-3 rounded-md text-sm font-medium hover:bg-indigo-700 dark:hover:bg-indigo-600 btn-animated">Add Screening</button>
            </div>
            <!-- Upcoming Screenings -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8 mb-8 animate__animated animate__fadeInUp">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 text-shadow mb-6">Upcoming Screenings</h2>
                <?php if ($error): ?>
                    <div class="mb-4 text-red-600 dark:text-red-400 text-center"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if (isset($success)): ?>
                    <div class="mb-4 text-green-600 dark:text-green-400 text-center"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if (empty($screenings)): ?>
                    <p class="text-gray-600 dark:text-gray-300 text-center">No screenings available.</p>
                <?php else: ?>
                    <div class="grid gap-4">
                        <?php foreach ($screenings as $s): ?>
                            <div class="border-b border-gray-200 dark:border-gray-600 pb-4">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($s['title']); ?></h3>
                                <p class="text-gray-600 dark:text-gray-300"><?php echo htmlspecialchars($s['type']); ?></p>
                                <p class="text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($s['date']); ?> | <?php echo htmlspecialchars($s['time']); ?></p>
                                <a href="screening-details.php?id=<?php echo $s['id']; ?>" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">View Details</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Specific Screening Details (if ID provided) -->
            <?php if ($screening): ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8 animate__animated animate__fadeInUp">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 text-shadow"><?php echo htmlspecialchars($screening['title']); ?></h2>
                        <a href="index.php#schedule" class="text-gray-500 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 text-2xl">×</a>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 mb-2"><?php echo htmlspecialchars($screening['type']); ?></p>
                    <p class="text-gray-500 dark:text-gray-400 mb-4"><?php echo htmlspecialchars($screening['date']); ?> | <?php echo htmlspecialchars($screening['time']); ?></p>
                    <button class="bg-indigo-600 dark:bg-indigo-500 text-white px-6 py-3 rounded-md text-sm font-medium hover:bg-indigo-700 dark:hover:bg-indigo-600 btn-animated">Join Screening</button>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Add Screening Modal -->
    <div id="add-screening-modal" class="modal hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8 max-w-md w-full">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 text-shadow">Add New Screening</h2>
                <button id="close-modal-btn" class="text-gray-500 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 text-2xl">×</button>
            </div>
            <form id="add-screening-form" action="screening-details.php" method="POST" class="space-y-6">
                <input type="hidden" name="add_screening" value="1">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                    <input type="text" id="title" name="title" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200" placeholder="Enter screening title">
                </div>
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Screening Type</label>
                    <select id="type" name="type" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200">
                        <option value="">Select type</option>
                        <option value="Online Screening">Online Screening</option>
                        <option value="Virtual Premiere">Virtual Premiere</option>
                        <option value="Live Q&A with Director">Live Q&A with Director</option>
                    </select>
                </div>
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                    <input type="date" id="date" name="date" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200">
                </div>
                <div>
                    <label for="time" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Time</label>
                    <input type="text" id="time" name="time" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200" placeholder="e.g., 7:00 PM">
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" id="cancel-modal-btn" class="bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-400 dark:hover:bg-gray-500 btn-animated">Cancel</button>
                    <button type="submit" class="bg-indigo-600 dark:bg-indigo-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700 dark:hover:bg-indigo-600 btn-animated">Add Screening</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 dark:bg-gray-950 text-white py-12 animate-on-scroll mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-lg font-semibold text-gray-200 dark:text-gray-100">FilmForge</h3>
                    <p class="mt-4 text-gray-400 dark:text-gray-300">Empowering independent filmmakers to share their stories with the world.</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-200 dark:text-gray-100">Quick Links</h3>
                    <ul class="mt-4 space-y-2">
                        <li><a href="index.php#films" class="text-gray-400 dark:text-gray-300 hover:text-gray-200 dark:hover:text-gray-100">Films</a></li>
                        <li><a href="index.php#schedule" class="text-gray-400 dark:text-gray-300 hover:text-gray-200 dark:hover:text-gray-100">Screenings</a></li>
                        <li><a href="index.php#filmmakers" class="text-gray-400 dark:text-gray-300 hover:text-gray-200 dark:hover:text-gray-100">Filmmakers</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-200 dark:text-gray-100">Contact</h3>
                    <p class="mt-4 text-gray-400 dark:text-gray-300">Email: support@filmforge.com</p>
                    <p class="mt-2 text-gray-400 dark:text-gray-300">Phone: (123) 456-7890</p>
                </div>
            </div>
            <div class="mt-8 border-t border-gray-700 dark:border-gray-600 pt-8 text-center">
                <p class="text-gray-400 dark:text-gray-300">© 2025 FilmForge. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Debug: Log when script runs
        console.log('screening-details.php script loaded');

        // Theme Toggle Logic
        const htmlElement = document.documentElement;
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');

        // Initialize theme
        const savedTheme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        htmlElement.classList.add(savedTheme);
        themeIcon.textContent = savedTheme === 'dark' ? '☀️' : '🌙';
        console.log('Initial theme:', savedTheme);

        // Toggle theme
        themeToggle.addEventListener('click', () => {
            const currentTheme = htmlElement.classList.contains('dark') ? 'dark' : 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            htmlElement.classList.remove(currentTheme);
            htmlElement.classList.add(newTheme);
            localStorage.setItem('theme', newTheme);
            themeIcon.textContent = newTheme === 'dark' ? '☀️' : '🌙';
            console.log('Theme toggled to:', newTheme);
        });

        // Mobile Menu Toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            console.log('Mobile menu toggled');
        });

        // Scroll Animation Trigger
        document.addEventListener('DOMContentLoaded', () => {
            const elements = document.querySelectorAll('.animate-on-scroll');
            console.log('Animate-on-scroll elements:', elements.length);
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('visible');
                            observer.unobserve(entry.target);
                            console.log('Element visible:', entry.target);
                        }
                    });
                }, { threshold: 0.1 });
                elements.forEach(element => observer.observe(element));
            } else {
                elements.forEach(element => {
                    element.classList.add('visible');
                    console.log('Fallback: Made element visible:', element);
                });
            }
        });

        // Smooth Scrolling for Nav Links
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = link.getAttribute('href').split('#')[1];
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({ behavior: 'smooth' });
                } else {
                    window.location.href = link.getAttribute('href');
                }
                console.log('Smooth scroll to:', targetId);
            });
        });

        // Modal Toggle
        const addScreeningBtn = document.getElementById('add-screening-btn');
        const addScreeningModal = document.getElementById('add-screening-modal');
        const closeModalBtn = document.getElementById('close-modal-btn');
        const cancelModalBtn = document.getElementById('cancel-modal-btn');

        addScreeningBtn.addEventListener('click', () => {
            addScreeningModal.classList.remove('hidden');
            console.log('Add screening modal opened');
        });

        closeModalBtn.addEventListener('click', () => {
            addScreeningModal.classList.add('hidden');
            console.log('Add screening modal closed');
        });

        cancelModalBtn.addEventListener('click', () => {
            addScreeningModal.classList.add('hidden');
            console.log('Add screening modal cancelled');
        });

        // Client-side Form Validation
        document.getElementById('add-screening-form').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const type = document.getElementById('type').value;
            const date = document.getElementById('date').value;
            const time = document.getElementById('time').value.trim();
            const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
            const timeRegex = /^\d{1,2}:\d{2} (AM|PM)$/;

            if (!title) {
                e.preventDefault();
                alert('Title is required.');
            } else if (!type) {
                e.preventDefault();
                alert('Screening type is required.');
            } else if (!date || !dateRegex.test(date)) {
                e.preventDefault();
                alert('Valid date (YYYY-MM-DD) is required.');
            } else if (!time || !timeRegex.test(time)) {
                e.preventDefault();
                alert('Valid time (e.g., 7:00 PM) is required.');
            }
        });
    </script>
</body>
</html>
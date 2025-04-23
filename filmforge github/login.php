<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

try {
    require_once 'db_connect.php';
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    $error = "Unable to connect to the database. Please try again later.";
}

$error = $error ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, name, email, password, bio, location, profile_photo FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['bio'] = $user['bio'];
                $_SESSION['location'] = $user['location'];
                $_SESSION['profile_photo'] = !empty($user['profile_photo']) && file_exists($user['profile_photo']) ? $user['profile_photo'] : 'Uploads/default_profile.jpg';
                header("Location: index.php#home");
                exit;
            } else {
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            error_log("Login query failed: " . $e->getMessage());
            $error = "Login failed: Database error.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FilmForge - Login</title>
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
                        <a href="index.php#home" class="nav-link border-indigo-400 dark:border-indigo-300 text-gray-200 dark:text-gray-100 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Home</a>
                        <a href="index.php#films" class="nav-link text-gray-400 dark:text-gray-300 hover:text-gray-200 dark:hover:text-gray-100 inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium">Films</a>
                        <a href="index.php#schedule" class="nav-link text-gray-400 dark:text-gray-300 hover:text-gray-200 dark:hover:text-gray-100 inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium">Screenings</a>
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

    <!-- Login Form -->
    <section class="py-16 animate-on-scroll">
        <div class="max-w-md w-full mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-md p-8 animate__animated animate__fadeInUp">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 text-center mb-6 text-shadow">Sign In</h2>
            <?php if (!empty($error)): ?>
                <div class="mb-4 text-red-600 dark:text-red-400 text-center"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form action="login.php" method="POST" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                    <input type="email" id="email" name="email" required class="mt-1 h-9 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200" placeholder="Enter your email">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required class="mt-1 block h-9 w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200" placeholder="Enter your password">
                        <button type="button" id="toggle-password" class="absolute right-2 top-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">👁️</button>
                    </div>
                </div>
                <button type="submit" class="w-full bg-indigo-600 dark:bg-indigo-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700 dark:hover:bg-indigo-600 btn-animated">Sign In</button>
            </form>
            <p class="mt-4 text-center text-sm text-gray-600 dark:text-gray-300">
                Don't have an account? <a href="register.php" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">Sign Up</a>
            </p>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-300">
                <a href="forgot-password.php" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">Forgot Password?</a>
            </p>
        </div>
    </section>

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
        console.log('login.php script loaded');

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
                // Fallback: Make elements visible
                elements.forEach(element => {
                    element.classList.add('visible');
                    console.log('Fallback: Made element visible:', element);
                });
            }
        });

        // Client-side validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (email === '') {
                alert('Email is required.');
                e.preventDefault();
            } else if (!emailRegex.test(email)) {
                alert('Invalid email format.');
                e.preventDefault();
            } else if (password === '') {
                alert('Password is required.');
                e.preventDefault();
            }
        });

        // Password Toggle
        const togglePassword = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password');
        togglePassword.addEventListener('click', () => {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            togglePassword.textContent = type === 'password' ? '👁️' : '👁️‍🗨️';
            console.log('Password visibility toggled');
        });
    </script>
</body>
</html>
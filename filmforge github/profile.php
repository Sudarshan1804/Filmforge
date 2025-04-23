<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';
$pdo = null;

try {
    require_once 'db_connect.php';
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    $error = "Unable to connect to the database.";
}

// Fetch user details
$user = null;
if (!$error && $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT username, email, bio, profile_picture FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            $error = "User not found.";
        }
    } catch (PDOException $e) {
        error_log("User query failed: " . $e->getMessage());
        $error = "Failed to load user details.";
    }
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_picture'])) {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        $allowed_types = ['image/jpeg', 'image/png'];
        $max_size = 2 * 1024 * 1024; // 2MB

        // Validate file
        if (!in_array($file['type'], $allowed_types)) {
            $error = "Only JPEG and PNG files are allowed.";
        } elseif ($file['size'] > $max_size) {
            $error = "File size must be less than 2MB.";
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $_SESSION['user_id'] . '_' . time() . '.' . $ext;
            $upload_dir = 'uploads/profiles/';
            $upload_path = $upload_dir . $filename;

            // Create directory if not exists
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Update database
                try {
                    $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                    $stmt->execute([$upload_path, $_SESSION['user_id']]);
                    $user['profile_picture'] = $upload_path;
                    $success = "Profile picture uploaded successfully.";
                } catch (PDOException $e) {
                    error_log("Update profile picture failed: " . $e->getMessage());
                    $error = "Failed to update profile picture.";
                }
            } else {
                $error = "Failed to upload file.";
            }
        }
    } else {
        $error = "No file uploaded or upload error.";
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FilmForge - Profile</title>
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
        .profile-picture {
            width: 128px;
            height: 128px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #e5e7eb;
        }
        .dark .profile-picture {
            border-color: #4b5563;
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
                        <button onclick="window.location.href='profile.php'" class="text-gray-400 dark:text-gray-300 hover:text-gray-200 dark:hover:text-gray-100 px-4 py-2 rounded-md text-sm font-medium mr-4">Profile</button>
                        <button onclick="window.location.href='logout.php'" class="bg-red-600 dark:bg-red-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700 dark:hover:bg-red-600 btn-animated">Sign Out</button>
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
                    <a href="profile.php" class="text-gray-200 dark:text-gray-100 hover:text-indigo-400 dark:hover:text-indigo-300 text-sm font-medium">Profile</a>
                    <a href="logout.php" class="text-red-600 dark:text-red-500 hover:text-red-700 dark:hover:text-red-600 text-sm font-medium">Sign Out</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Profile Section -->
    <section class="py-16 animate-on-scroll">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8 animate__animated animate__fadeInUp">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 text-shadow">Your Profile</h2>
                    <a href="index.php" class="text-gray-500 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 text-2xl">×</a>
                </div>
                <?php if ($error): ?>
                    <div class="mb-4 text-red-600 dark:text-red-400 text-center"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="mb-4 text-green-600 dark:text-green-400 text-center"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if ($user): ?>
                    <div class="flex flex-col items-center">
                        <img src="<?php echo $user['profile_picture'] ?: 'https://via.placeholder.com/128'; ?>" alt="Profile Picture" class="profile-picture mb-4">
                        <form action="profile.php" method="POST" enctype="multipart/form-data" class="mb-6">
                            <input type="hidden" name="upload_picture" value="1">
                            <label for="profile_picture" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Upload Profile Picture</label>
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png" class="block w-full text-sm text-gray-500 dark:text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-800">
                            <button type="submit" class="mt-4 bg-indigo-600 dark:bg-indigo-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700 dark:hover:bg-indigo-600 btn-animated">Upload</button>
                        </form>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($user['username']); ?></h3>
                        <p class="text-gray-600 dark:text-gray-300"><?php echo htmlspecialchars($user['email']); ?></p>
                        <p class="text-gray-500 dark:text-gray-400 mt-2"><?php echo htmlspecialchars($user['bio'] ?: 'No bio available.'); ?></p>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600 dark:text-gray-300 text-center">Unable to load profile details.</p>
                <?php endif; ?>
            </div>
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
        // Debug
        console.log('profile.php script loaded');

        // Theme Toggle
        const htmlElement = document.documentElement;
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');

        const savedTheme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        htmlElement.classList.add(savedTheme);
        themeIcon.textContent = savedTheme === 'dark' ? '☀️' : '🌙';
        console.log('Initial theme:', savedTheme);

        themeToggle.addEventListener('click', () => {
            const currentTheme = htmlElement.classList.contains('dark') ? 'dark' : 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            htmlElement.classList.remove(currentTheme);
            htmlElement.classList.add(newTheme);
            localStorage.setItem('theme', newTheme);
            themeIcon.textContent = newTheme === 'dark' ? '☀️' : '🌙';
            console.log('Theme toggled to:', newTheme);
        });

        // Mobile Menu
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            console.log('Mobile menu toggled');
        });

        // Scroll Animation
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
                elements.forEach(element => element.classList.add('visible'));
            }
        });

        // Client-side File Validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('profile_picture');
            const file = fileInput.files[0];
            if (file) {
                const allowedTypes = ['image/jpeg', 'image/png'];
                const maxSize = 2 * 1024 * 1024; // 2MB
                if (!allowedTypes.includes(file.type)) {
                    e.preventDefault();
                    alert('Only JPEG and PNG files are allowed.');
                } else if (file.size > maxSize) {
                    e.preventDefault();
                    alert('File size must be less than 2MB.');
                }
            }
        });
    </script>
</body>
</html>
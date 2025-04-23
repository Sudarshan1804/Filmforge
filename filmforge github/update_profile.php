<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $bio = trim($_POST['bio']);
    $location = trim($_POST['location']);
    $error = '';

    // Validate inputs
    if (empty($name)) {
        $error = "Name is required.";
    } else {
        // Handle file upload
        $profile_photo_path = isset($_SESSION['profile_photo']) && !empty($_SESSION['profile_photo']) && file_exists($_SESSION['profile_photo']) ? $_SESSION['profile_photo'] : 'Uploads/default_profile.jpg';
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_photo'];
            $allowed_types = ['image/jpeg', 'image/png'];
            $max_size = 2 * 1024 * 1024; // 2MB

            if (!in_array($file['type'], $allowed_types)) {
                $error = "Only JPEG or PNG files are allowed.";
            } elseif ($file['size'] > $max_size) {
                $error = "File size must be less than 2MB.";
            } else {
                $upload_dir = 'Uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $file_name = uniqid() . '_' . basename($file['name']);
                $file_path = $upload_dir . $file_name;

                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    // Delete old profile photo if it exists and is not the default
                    if (!empty($profile_photo_path) && file_exists($profile_photo_path) && $profile_photo_path !== 'Uploads/default_profile.jpg') {
                        unlink($profile_photo_path);
                    }
                    $profile_photo_path = $file_path;
                } else {
                    $error = "Failed to upload profile photo.";
                }
            }
        }

        if (empty($error)) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, bio = ?, location = ?, profile_photo = ? WHERE id = ?");
                $stmt->execute([$name, $bio, $location, $profile_photo_path, $_SESSION['user_id']]);
                // Update session variables
                $_SESSION['user_name'] = $name;
                $_SESSION['bio'] = $bio;
                $_SESSION['location'] = $location;
                $_SESSION['profile_photo'] = $profile_photo_path;
                // Redirect to profile section
                header("Location: index.php#profile");
                exit;
            } catch (PDOException $e) {
                $error = "Failed to update profile: " . $e->getMessage();
            }
        }
    }

    // If there's an error, redirect back with error parameter
    if (!empty($error)) {
        header("Location: update_profile.php?error=" . urlencode($error));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FilmForge - Update Profile</title>
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
    </style>
</head>
<body class="font-inter bg-gray-100 dark:bg-gray-900 transition-colors duration-300 pt-16">
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
                <div class="hidden sm:ml-6 sm:flex sm:items-center">
                    <button id="theme-toggle" class="text-gray-400 hover:text-gray-200 dark:text-gray-300 dark:hover:text-gray-100 px-2 py-2 rounded-md text-sm font-medium" title="Toggle Dark/Light Mode">
                        <span id="theme-icon">☀️</span>
                    </button>
                    <button onclick="window.location.href='index.php#profile'" class="text-gray-400 dark:text-gray-300 hover:text-gray-200 dark:hover:text-gray-100 px-4 py-2 rounded-md text-sm font-medium mr-4">Profile</button>
                    <button onclick="window.location.href='logout.php'" class="bg-red-600 dark:bg-red-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700 dark:hover:bg-red-600 btn-animated">Sign Out</button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Profile Update Status -->
    <section class="min-h-screen flex items-center justify-center py-12 animate-on-scroll">
        <div class="max-w-md w-full mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-md p-8 animate__animated animate__fadeInUp">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 text-center mb-6 text-shadow">Update Profile</h2>
            <?php if (isset($_GET['error'])): ?>
                <div class="mb-4 text-red-600 dark:text-red-400 text-center"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            <form id="edit-profile-form" action="update_profile.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Full Name</label>
                    <input type="text" id="name" name="name" value="<?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : ''; ?>" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200" placeholder="Enter your full name">
                </div>
                <div>
                    <label for="bio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bio</label>
                    <textarea id="bio" name="bio" rows="4" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200" placeholder="Tell us about yourself"><?php echo isset($_SESSION['bio']) ? htmlspecialchars($_SESSION['bio']) : ''; ?></textarea>
                </div>
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Location</label>
                    <input type="text" id="location" name="location" value="<?php echo isset($_SESSION['location']) ? htmlspecialchars($_SESSION['location']) : ''; ?>" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200" placeholder="Enter your location">
                </div>
                <div>
                    <label for="profile_photo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Profile Photo</label>
                    <input type="file" id="profile_photo" name="profile_photo" accept="image/jpeg,image/png" class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-800">
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" class="bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-gray-200 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-400 dark:hover:bg-gray-500 btn-animated" onclick="window.location.href='index.php#profile'">Cancel</button>
                    <button type="submit" class="bg-indigo-600 dark:bg-indigo-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700 dark:hover:bg-indigo-600 btn-animated">Save Changes</button>
                </div>
            </form>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 dark:bg-gray-950 text-white py-12 animate-on-scroll">
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
        // Theme Toggle Logic
        const htmlElement = document.documentElement;
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');

        // Initialize theme
        const savedTheme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        htmlElement.classList.add(savedTheme);
        themeIcon.textContent = savedTheme === 'dark' ? '☀️' : '🌙';

        // Toggle theme
        themeToggle.addEventListener('click', () => {
            const currentTheme = htmlElement.classList.contains('dark') ? 'dark' : 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            htmlElement.classList.remove(currentTheme);
            htmlElement.classList.add(newTheme);
            localStorage.setItem('theme', newTheme);
            themeIcon.textContent = newTheme === 'dark' ? '☀️' : '🌙';
        });

        // Scroll Animation Trigger
        document.addEventListener('DOMContentLoaded', () => {
            const elements = document.querySelectorAll('.animate-on-scroll');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });

            elements.forEach(element => observer.observe(element));
        });
    </script>
</body>
</html>
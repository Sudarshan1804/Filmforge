<?php
session_start();
require_once 'db_connect.php';
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FilmForge - More Independent Films</title>
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
        .film-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .film-card:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
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
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button onclick="window.location.href='index.php#profile'" class="text-gray-400 dark:text-gray-300 hover:text-gray-200 dark:hover:text-gray-100 px-4 py-2 rounded-md text-sm font-medium mr-4">Profile</button>
                        <button onclick="window.location.href='logout.php'" class="bg-red-600 dark:bg-red-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700 dark:hover:bg-red-600 btn-animated">Sign Out</button>
                    <?php else: ?>
                        <button onclick="window.location.href='register.php'" class="bg-indigo-600 dark:bg-indigo-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700 dark:hover:bg-indigo-600 mr-4 btn-animated">Sign Up</button>
                        <button onclick="window.location.href='login.php'" class="text-gray-400 dark:text-gray-300 hover:text-gray-200 dark:hover:text-gray-100 px-4 py-2 rounded-md text-sm font-medium mr-4">Sign In</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Films Section -->
    <section class="py-16 animate-on-scroll">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 text-center text-shadow">More Independent Films</h2>
            <div class="mt-12 grid gap-8 lg:grid-cols-3 sm:grid-cols-2">
                <?php
                try {
                    $stmt = $pdo->query("SELECT id, title, description, video_path, thumbnail_path FROM films ORDER BY created_at DESC");
                    while ($film = $stmt->fetch(PDO::FETCH_ASSOC)) {
                ?>
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden film-card animate__animated animate__fadeInUp">
                            <video controls class="w-full h-48 object-cover" poster="<?php echo !empty($film['thumbnail_path']) && file_exists($film['thumbnail_path']) ? htmlspecialchars($film['thumbnail_path']) : 'https://via.placeholder.com/400x225'; ?>">
                                <source src="<?php echo htmlspecialchars($film['video_path']); ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($film['title']); ?></h3>
                                <p class="mt-2 text-gray-600 dark:text-gray-300"><?php echo htmlspecialchars($film['description']); ?></p>
                                <div class="mt-4 flex justify-between items-center">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Dir: Unknown</span>
                                    <a href="film-details.php?id=<?php echo $film['id']; ?>" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-sm font-medium btn-animated">Watch Now</a>
                                </div>
                            </div>
                        </div>
                <?php
                    }
                } catch (PDOException $e) {
                    echo "<div class='text-center text-red-600 dark:text-red-400'>Error loading films: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
                ?>
            </div>
            <div class="mt-8 text-center">
                <a href="index.php#films" class="inline-block bg-indigo-600 dark:bg-indigo-500 text-white px-6 py-3 rounded-md text-sm font-medium hover:bg-indigo-700 dark:hover:bg-indigo-600 btn-animated">Back to Films</a>
            </div>
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
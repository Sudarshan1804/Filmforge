<?php
session_start();
require_once 'db_connect.php';

// Get the film ID from the URL
$film_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($film_id <= 0) {
    header("Location: index.php#films");
    exit;
}

try {
    // Query to fetch film details and director's name
    $stmt = $pdo->prepare("
        SELECT f.id, f.title, f.description, f.video_path, u.name AS director
        FROM films f
        LEFT JOIN users u ON f.user_id = u.id
        WHERE f.id = ?
    ");
    $stmt->execute([$film_id]);
    $film = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$film) {
        header("Location: index.php#films");
        exit;
    }

    // Set a default thumbnail (since not stored in DB)
    $film['thumbnail'] = 'https://via.placeholder.com/400x225';
} catch (PDOException $e) {
    die("Error: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FilmForge - <?php echo htmlspecialchars($film['title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="font-inter bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-2xl font-bold text-indigo-600">FilmForge</h1>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="index.php#home" class="nav-link text-gray-500 hover:text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium">Home</a>
                        <a href="index.php#films" class="nav-link border-indigo-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Films</a>
                        <a href="index.php#schedule" class="nav-link text-gray-500 hover:text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium">Screenings</a>
                        <a href="index.php#filmmakers" class="nav-link text-gray-500 hover:text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium">Filmmakers</a>
                    </div>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:items-center">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button id="profile-btn" class="text-gray-500 hover:text-gray-900 px-4 py-2 rounded-md text-sm font-medium mr-4">Profile</button>
                        <button onclick="window.location.href='logout.php'" class="bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700">Sign Out</button>
                    <?php else: ?>
                        <button onclick="window.location.href='register.php'" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700 mr-4">Sign Up</button>
                        <button onclick="window.location.href='login.php'" class="text-gray-500 hover:text-gray-900 px-4 py-2 rounded-md text-sm font-medium mr-4">Sign In</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Film Details Section -->
    <section class="min-h-screen py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-md p-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($film['title']); ?></h2>
                    <a href="index.php#films" class="text-gray-500 hover:text-gray-900 text-2xl">×</a>
                </div>
                <video controls class="w-full h-64 object-cover rounded-md mb-6">
                    <source src="<?php echo htmlspecialchars($film['video_path']); ?>" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
                <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($film['description']); ?></p>
                <p class="text-gray-500">Directed by: <?php echo htmlspecialchars($film['director'] ?? 'Unknown'); ?></p>
                <button class="mt-6 bg-indigo-600 text-white px-6 py-3 rounded-md text-sm font-medium hover:bg-indigo-700">Watch Now</button>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-lg font-semibold">FilmForge</h3>
                    <p class="mt-4 text-gray-400">Empowering independent filmmakers to share their stories with the world.</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">Quick Links</h3>
                    <ul class="mt-4 space-y-2">
                        <li><a href="index.php#films" class="text-gray-400 hover:text-white">Films</a></li>
                        <li><a href="index.php#schedule" class="text-gray-400 hover:text-white">Screenings</a></li>
                        <li><a href="index.php#filmmakers" class="text-gray-400 hover:text-white">Filmmakers</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">Contact</h3>
                    <p class="mt-4 text-gray-400">Email: support@filmforge.com</p>
                    <p class="mt-2 text-gray-400">Phone: (123) 456-7890</p>
                </div>
            </div>
            <div class="mt-8 border-t border-gray-700 pt-8 text-center">
                <p class="text-gray-400">© 2025 FilmForge. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
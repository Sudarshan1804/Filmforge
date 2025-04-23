<?php
session_start();
require_once 'db_connect.php';

// Check if user ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php#filmmakers?error=" . urlencode("Invalid user ID."));
    exit;
}

$user_id = (int)$_GET['id'];

try {
    // Fetch user details
    $stmt = $pdo->prepare("SELECT name, bio, location, profile_photo FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        error_log("User not found for ID: $user_id");
        header("Location: index.php#filmmakers?error=" . urlencode("User not found."));
        exit;
    }

    // Log fetched user data for debugging
    error_log("Fetched user data: " . print_r($user, true));

    // Fetch user's films
    $stmt = $pdo->prepare("SELECT id, title, description, video_path FROM films WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $films = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log fetched films for debugging
    error_log("Fetched films: " . print_r($films, true));
} catch (PDOException $e) {
    error_log("Database error in filmmaker-profile.php: " . $e->getMessage());
    header("Location: index.php#filmmakers?error=" . urlencode("Error loading profile: " . $e->getMessage()));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FilmForge - Filmmaker Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        #filmmaker-profile {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 50;
            overflow-y: auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
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
                        <a href="index.php#films" class="nav-link text-gray-500 hover:text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium">Films</a>
                        <a href="index.php#schedule" class="nav-link text-gray-500 hover:text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium">Screenings</a>
                        <a href="index.php#filmmakers" class="nav-link text-gray-500 hover:text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium">Filmmakers</a>
                    </div>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:items-center">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button onclick="window.location.href='index.php#profile'" class="text-gray-500 hover:text-gray-900 px-4 py-2 rounded-md text-sm font-medium mr-4">Profile</button>
                        <button onclick="window.location.href='logout.php'" class="bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700">Sign Out</button>
                    <?php else: ?>
                        <button onclick="window.location.href='register.php'" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700 mr-4">Sign Up</button>
                        <button onclick="window.location.href='login.php'" class="text-gray-500 hover:text-gray-900 px-4 py-2 rounded-md text-sm font-medium mr-4">Sign In</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Filmmaker Profile Section -->
    <section id="filmmaker-profile">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="bg-white rounded-lg shadow-md p-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($user['name']); ?>'s Profile</h2>
                    <button onclick="window.location.href='index.php#filmmakers'" class="text-gray-500 hover:text-gray-900 text-2xl">×</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Profile Picture and Basic Info -->
                    <div class="text-center">
                        <img src="<?php echo !empty($user['profile_photo']) && file_exists($user['profile_photo']) ? htmlspecialchars($user['profile_photo']) : 'Uploads/default_profile.jpg'; ?>" alt="Profile Picture" class="w-32 h-32 rounded-full mx-auto">
                        <h3 class="mt-4 text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($user['name']); ?></h3>
                        <p class="mt-2 text-gray-600">Filmmaker</p>
                    </div>
                    <!-- Profile Details -->
                    <div class="md:col-span-2">
                        <h4 class="text-lg font-semibold text-gray-900">About</h4>
                        <p class="mt-2 text-gray-600"><?php echo !empty($user['bio']) ? htmlspecialchars($user['bio']) : 'No bio available.'; ?></p>
                        <h4 class="mt-6 text-lg font-semibold text-gray-900">Contact Information</h4>
                        <p class="mt-2 text-gray-600">Location: <?php echo !empty($user['location']) ? htmlspecialchars($user['location']) : 'Not specified'; ?></p>
                        <h4 class="mt-6 text-lg font-semibold text-gray-900">Films</h4>
                        <ul class="mt-2 space-y-2">
                            <?php if (empty($films)): ?>
                                <li class="text-gray-600">No films uploaded yet.</li>
                            <?php else: ?>
                                <?php foreach ($films as $film): ?>
                                    <li class="text-gray-600 flex justify-between items-center">
                                        <a href="film-details.php?id=<?php echo $film['id']; ?>" class="text-indigo-600 hover:text-indigo-800"><?php echo htmlspecialchars($film['title']); ?></a>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
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
</body>
</html>
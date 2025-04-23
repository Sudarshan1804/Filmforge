<?php
session_start();
require_once 'db_connect.php';

// Debug session
error_log("Session Data: " . print_r($_SESSION, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['title']) && isset($_POST['description']) && isset($_FILES['file'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $video_file = $_FILES['file'];
        $thumbnail_file = isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK ? $_FILES['thumbnail'] : null;

        // Define upload directories
        $video_upload_dir = 'Uploads/videos/';
        $thumbnail_upload_dir = 'Uploads/thumbnails/';
        if (!file_exists($video_upload_dir)) {
            mkdir($video_upload_dir, 0777, true);
        }
        if (!file_exists($thumbnail_upload_dir)) {
            mkdir($thumbnail_upload_dir, 0777, true);
        }

        // Generate unique filename for video
        $video_file_name = uniqid() . '_' . basename($video_file['name']);
        $video_target_file = $video_upload_dir . $video_file_name;
        $video_file_type = strtolower(pathinfo($video_target_file, PATHINFO_EXTENSION));

        // Validate video file
        $allowed_video_types = ['mp4', 'mov', 'webm'];
        if (!in_array($video_file_type, $allowed_video_types)) {
            header("Location: index.php#upload?error=" . urlencode("Only MP4, MOV, and WEBM files are allowed for video."));
            exit;
        }

        // Handle thumbnail upload (optional)
        $thumbnail_target_file = null;
        if ($thumbnail_file) {
            $thumbnail_file_name = uniqid() . '_' . basename($thumbnail_file['name']);
            $thumbnail_target_file = $thumbnail_upload_dir . $thumbnail_file_name;
            $thumbnail_file_type = strtolower(pathinfo($thumbnail_target_file, PATHINFO_EXTENSION));
            $allowed_thumbnail_types = ['jpg', 'jpeg', 'png'];
            $max_size = 2 * 1024 * 1024; // 2MB

            if (!in_array($thumbnail_file_type, $allowed_thumbnail_types)) {
                header("Location: index.php#upload?error=" . urlencode("Only JPEG and PNG files are allowed for thumbnail."));
                exit;
            }
            if ($thumbnail_file['size'] > $max_size) {
                header("Location: index.php#upload?error=" . urlencode("Thumbnail file size must be less than 2MB."));
                exit;
            }
        }

        // Move uploaded video file
        if (move_uploaded_file($video_file['tmp_name'], $video_target_file)) {
            // Move thumbnail file if provided
            if ($thumbnail_file && !move_uploaded_file($thumbnail_file['tmp_name'], $thumbnail_target_file)) {
                unlink($video_target_file); // Clean up video file
                header("Location: index.php#upload?error=" . urlencode("Failed to upload thumbnail."));
                exit;
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO films (title, description, user_id, created_at, video_path, thumbnail_path) VALUES (?, ?, ?, NOW(), ?, ?)");
                $stmt->execute([$title, $description, $_SESSION['user_id'], $video_target_file, $thumbnail_target_file]);
                header("Location: index.php#films");
                exit;
            } catch (PDOException $e) {
                unlink($video_target_file);
                if ($thumbnail_target_file && file_exists($thumbnail_target_file)) {
                    unlink($thumbnail_target_file);
                }
                header("Location: index.php#upload?error=" . urlencode("Failed to upload film: " . $e->getMessage()));
                exit;
            }
        } else {
            header("Location: index.php#upload?error=" . urlencode("Failed to upload video file."));
            exit;
        }
    } elseif (isset($_POST['delete_film_id'])) {
        $film_id = $_POST['delete_film_id'];
        try {
            $stmt = $pdo->prepare("SELECT video_path, thumbnail_path FROM films WHERE id = ? AND user_id = ?");
            $stmt->execute([$film_id, $_SESSION['user_id']]);
            $film = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("DELETE FROM films WHERE id = ? AND user_id = ?");
            $stmt->execute([$film_id, $_SESSION['user_id']]);

            if ($film && file_exists($film['video_path'])) {
                unlink($film['video_path']);
            }
            if ($film && !empty($film['thumbnail_path']) && file_exists($film['thumbnail_path'])) {
                unlink($film['thumbnail_path']);
            }

            header("Location: index.php#profile");
            exit;
        } catch (PDOException $e) {
            header("Location: index.php#profile?error=" . urlencode("Failed to delete film: " . $e->getMessage()));
            exit;
        }
    } elseif (isset($_POST['subscribe_email'])) {
        $success = true;
        if ($success && isset($_SESSION['user_name'])) {
            $successMessage = "Thank you, " . htmlspecialchars($_SESSION['user_name']) . ", for subscribing!";
        } else {
            $successMessage = "Subscription failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FilmForge - Independent Filmmakers Platform</title>
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
        .film-card, .filmmaker-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .film-card:hover, .filmmaker-card:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        .btn-animated {
            transition: transform 0.2s ease;
        }
        .btn-animated:hover {
            animation: pulse 0.5s;
        }
        #edit-profile-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 60;
            align-items: center;
            justify-content: center;
        }
        #edit-profile-modal.active {
            display: flex;
        }
        #edit-profile-modal .modal-content {
            max-height: 80vh;
            overflow-y: auto;
            padding: 2rem;
        }
        @media (min-width: 640px) {
            #edit-profile-modal .modal-content {
                max-width: 28rem;
            }
        }
        .text-shadow {
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }
        /* Dark mode overrides */
        .dark .bg-gray-100 { background-color: #1f2937; }
        .dark .bg-gray-200 { background-color: #111827; }
        .dark .bg-gray-800 { background-color: #1f2937; }
        .dark .bg-gray-900 { background-color: #111827; }
        .dark .bg-slate-700 { background-color: #1e1b4b; }
        .dark .bg-blue-100 { background-color: #312e81; }
        .dark .bg-indigo-100 { background-color: #3730a3; }
        .dark .bg-white { background-color: #1f2937; }
        .dark .text-gray-900 { color: #e5e7eb; }
        .dark .text-gray-600 { color: #d1d5db; }
        .dark .text-gray-500 { color: #9ca3af; }
        .dark .text-gray-400 { color: #d1d5db; }
        .dark .text-gray-200 { color: #f3f4f6; }
        .dark .text-indigo-600 { color: #818cf8; }
        .dark .text-red-600 { color: #f87171; }
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
                        <a href="#home" class="nav-link border-indigo-400 dark:border-indigo-300 text-gray-200 dark:text-gray-100 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Home</a>
                        <a href="#films" class="nav-link text-gray-400 dark:text-gray-300 hover:text-gray-200 dark:hover:text-gray-100 inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium">Films</a>
                        <a href="#schedule" class="nav-link text-gray-400 dark:text-gray-300 hover:text-gray-200 dark:hover:text-gray-100 inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium">Screenings</a>
                        <a href="#filmmakers" class="nav-link text-gray-400 dark:text-gray-300 hover:text-gray-200 dark:hover:text-gray-100 inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium">Filmmakers</a>
                    </div>
                </div>
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
    </nav>

    <!-- Profile Section (Hidden by Default) -->
    <section id="profile" class="hidden fixed inset-0 bg-gray-200 dark:bg-gray-900 z-50 overflow-y-auto animate__animated animate__fadeIn">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Your Profile</h2>
                    <button id="close-profile" class="text-gray-500 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 text-2xl">×</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="text-center">
                        <img src="<?php echo isset($_SESSION['profile_photo']) && !empty($_SESSION['profile_photo']) && file_exists($_SESSION['profile_photo']) ? htmlspecialchars($_SESSION['profile_photo']) : 'Uploads/default_profile.jpg'; ?>" alt="Profile Picture" class="w-32 h-32 rounded-full mx-auto animate__animated animate__bounceIn">
                        <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-gray-100"><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Your Name'; ?></h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">Filmmaker</p>
                        <button id="edit-profile-btn" class="mt-4 bg-indigo-600 dark:bg-indigo-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700 dark:hover:bg-indigo-600 btn-animated">Edit Profile</button>
                    </div>
                    <div class="md:col-span-2">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">About</h4>
                        <p class="mt-2 text-gray-600 dark:text-gray-300"><?php echo isset($_SESSION['bio']) && !empty($_SESSION['bio']) ? htmlspecialchars($_SESSION['bio']) : 'Passionate filmmaker with a focus on storytelling that resonates with global audiences. Experienced in directing and producing independent films.'; ?></p>
                        <h4 class="mt-6 text-lg font-semibold text-gray-900 dark:text-gray-100">Contact Information</h4>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">Email: <?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : 'your.email@example.com'; ?></p>
                        <p class="mt-1 text-gray-600 dark:text-gray-300">Location: <?php echo isset($_SESSION['location']) && !empty($_SESSION['location']) ? htmlspecialchars($_SESSION['location']) : 'Not specified'; ?></p>
                        <h4 class="mt-6 text-lg font-semibold text-gray-900 dark:text-gray-100">Your Films</h4>
                        <ul class="mt-2 space-y-2">
                            <?php
                            if (isset($_SESSION['user_id'])) {
                                try {
                                    $stmt = $pdo->prepare("SELECT id, title, description FROM films WHERE user_id = ? ORDER BY created_at DESC");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    while ($film = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<li class='text-gray-600 dark:text-gray-300 flex justify-between items-center'>";
                                        echo htmlspecialchars($film['title']);
                                        echo " <form method='POST' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete " . htmlspecialchars($film['title']) . "?\");'>";
                                        echo "<input type='hidden' name='delete_film_id' value='" . $film['id'] . "'>";
                                        echo "<button type='submit' class='ml-2 text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-sm font-medium'>Delete</button>";
                                        echo "</form>";
                                        echo "</li>";
                                    }
                                } catch (PDOException $e) {
                                    echo "<li class='text-red-600 dark:text-red-400'>Error loading films: " . htmlspecialchars($e->getMessage()) . "</li>";
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Edit Profile Modal (Hidden by Default) -->
    <div id="edit-profile-modal" class="fixed inset-0 bg-black bg-opacity-50 z-60 hidden">
        <div class="modal-content bg-white dark:bg-gray-800 rounded-lg shadow-md p-8 max-w-md w-full mx-auto my-8 animate__animated animate__zoomIn">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Edit Profile</h3>
                <button id="close-edit-profile" class="text-gray-500 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 text-2xl">×</button>
            </div>
            <form id="edit-profile-form" action="update_profile.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Full Name</label>
                    <input type="text" id="name" name="name" value="<?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : ''; ?>" required class="mt-1 block w-full border-gray-300 h-9 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200" placeholder="Enter your full name">
                </div>
                <div>
                    <label for="bio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bio</label>
                    <textarea id="bio" name="bio" rows="4" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200" placeholder="Tell us about yourself"><?php echo isset($_SESSION['bio']) ? htmlspecialchars($_SESSION['bio']) : ''; ?></textarea>
                </div>
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Location</label>
                    <input type="text" id="location" name="location" value="<?php echo isset($_SESSION['location']) ? htmlspecialchars($_SESSION['location']) : ''; ?>" class="mt-1 block h-9 w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200" placeholder="Enter your location">
                </div>
                <div>
                    <label for="profile_photo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Profile Photo</label>
                    <input type="file" id="profile_photo" name="profile_photo" accept="image/jpeg,image/png" class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-800">
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" id="cancel-edit-profile" class="bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-gray-200 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-400 dark:hover:bg-gray-500 btn-animated">Cancel</button>
                    <button type="submit" class="bg-indigo-600 dark:bg-indigo-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700 dark:hover:bg-indigo-600 btn-animated">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Hero Section -->
    <section id="home" class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white animate-on-scroll">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center">
            <h1 class="text-4xl font-bold tracking-tight sm:text-5xl md:text-6xl text-shadow">Showcase Your Films to the World</h1>
            <p class="mt-6 max-w-2xl mx-auto text-xl">FilmForge is the ultimate platform for independent filmmakers to share their stories, connect with audiences, and grow their craft.</p>
            <div class="mt-10">
                <a href="#upload" class="inline-block bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-300 px-8 py-3 rounded-md text-lg font-medium hover:bg-gray-100 dark:hover:bg-gray-700 btn-animated">Upload Your Film</a>
            </div>
        </div>
    </section>

    <!-- Films Section -->
    <section id="films" class="py-16 bg-gray-800 dark:bg-gray-900 text-white animate-on-scroll">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-center text-shadow">Discover Films </h2>
            <div class="mt-12 grid gap-8 lg:grid-cols-3 sm:grid-cols-2">
                <?php
                try {
                    $stmt = $pdo->query("SELECT id, title, description, video_path, thumbnail_path FROM films ORDER BY created_at DESC LIMIT 6");
                } catch (PDOException $e) {
                    echo "<div class='text-center text-red-400'>Error loading films: " . htmlspecialchars($e->getMessage()) . "</div>";
                    $stmt = false;
                }
                if ($stmt):
                    while ($film = $stmt->fetch(PDO::FETCH_ASSOC)):
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
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center text-gray-400 dark:text-gray-300">No films available at the moment.</div>
                <?php endif; ?>
            </div>
            <div class="mt-8 text-right">
                <a href="more-films.php" class="inline-block bg-indigo-600 dark:bg-indigo-500 text-white px-6 py-3 rounded-md text-sm font-medium hover:bg-indigo-700 dark:hover:bg-indigo-600 btn-animated">More</a>
            </div>
        </div>
    </section>

    <!-- Screening Schedule Section -->
    <section id="schedule" class="py-16 bg-blue-100 dark:bg-indigo-900 animate-on-scroll">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 text-center text-shadow">Upcoming Screenings</h2>
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 film-card animate__animated animate__fadeInUp">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">The Silent Journey</h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-300">Online Screening</p>
                    <p class="mt-1 text-gray-500 dark:text-gray-400">April 20, 2025 | 7:00 PM EST</p>
                    <a href="screening-details.php?id=1" class="mt-4 inline-block text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-sm font-medium btn-animated">Join Screening</a>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 film-card animate__animated animate__fadeInUp">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Echoes of Tomorrow</h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-300">Virtual Premiere</p>
                    <p class="mt-1 text-gray-500 dark:text-gray-400">April 22, 2025 | 8:00 PM EST</p>
                    <a href="screening-details.php?id=2" class="mt-4 inline-block text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-sm font-medium btn-animated">Join Screening</a>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 film-card animate__animated animate__fadeInUp">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Urban Shadows</h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-300">Live Q&A with Director</p>
                    <p class="mt-1 text-gray-500 dark:text-gray-400">April 25, 2025 | 6:00 PM EST</p>
                    <a href="screening-details.php?id=3" class="mt-4 inline-block text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-sm font-medium btn-animated">Join Screening</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Filmmaker Profiles Section -->
    <section id="filmmakers" class="py-16 bg-slate-700 dark:bg-gray-950 text-white animate-on-scroll">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-center text-shadow">Meet Our Filmmakers</h2>
            <div class="mt-12 grid gap-8 lg:grid-cols-3 sm:grid-cols-2">
                <?php
                if (isset($_SESSION['user_id'])) {
                    try {
                        $stmt = $pdo->prepare("SELECT id, name, bio, profile_photo FROM users WHERE id != ? LIMIT 6");
                        $stmt->execute([$_SESSION['user_id']]);
                        while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                ?>
                            <div class="bg-gray-100 dark:bg-gray-800 rounded-lg shadow-md p-6 text-center filmmaker-card animate__animated animate__bounceIn">
                                <img src="<?php echo !empty($user['profile_photo']) && file_exists($user['profile_photo']) ? htmlspecialchars($user['profile_photo']) : 'Uploads/default_profile.jpg'; ?>" alt="Filmmaker" class="w-24 h-24 rounded-full mx-auto">
                                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($user['name'] ?? 'Unknown'); ?></h3>
                                <p class="mt-2 text-gray-600 dark:text-gray-300">Filmmaker</p>
                                <p class="mt-2 text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($user['bio'] ?? 'No bio available'); ?></p>
                                <a href="filmmaker-profile.php?id=<?php echo $user['id']; ?>" class="mt-4 inline-block text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-sm font-medium btn-animated">View Profile</a>
                            </div>
                <?php
                        }
                    } catch (PDOException $e) {
                        echo "<div class='text-center text-red-400'>Error loading filmmakers: " . htmlspecialchars($e->getMessage()) . "</div>";
                    }
                }
                ?>
            </div>
            <div class="mt-8 text-right">
                <a href="more-filmmakers.php" class="inline-block bg-indigo-600 dark:bg-indigo-500 text-white px-6 py-3 rounded-md text-sm font-medium hover:bg-indigo-700 dark:hover:bg-indigo-600 btn-animated">More</a>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-20 bg-indigo-100 dark:bg-indigo-800 animate-on-scroll">
        <div class="max-w-6xl mx-auto px-4">
            <h3 class="text-4xl font-bold text-center text-indigo-800 dark:text-indigo-200 mb-10 text-shadow">What Filmmakers Are Saying</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md film-card animate__animated animate__fadeInUp">
                    <p class="text-gray-700 dark:text-gray-300 italic mb-4">"FilmForge gave me a stage to shine. My film reached audiences across the globe!"</p>
                    <h4 class="font-semibold text-indigo-700 dark:text-indigo-300">— Clara Moreno</h4>
                </div>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md film-card animate__animated animate__fadeInUp">
                    <p class="text-gray-700 dark:text-gray-300 italic mb-4">"The upload and feedback system is intuitive and powerful. A must for any indie creator."</p>
                    <h4 class="font-semibold text-indigo-700 dark:text-indigo-300">— Liam Patel</h4>
                </div>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md film-card animate__animated animate__fadeInUp">
                    <p class="text-gray-700 dark:text-gray-300 italic mb-4">"Having my profile here helped me land a spot in a major screening festival!"</p>
                    <h4 class="font-semibold text-indigo-700 dark:text-indigo-300">— Aria Zhang</h4>
                </div>
            </div>
        </div>
    </section>

    <!-- Film Upload Section -->
    <section id="upload" class="py-16 bg-gray-200 dark:bg-gray-900 animate-on-scroll">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 text-center text-shadow">Upload Your Film</h2>
            <form action="index.php#upload" method="POST" enctype="multipart/form-data" class="mt-12 max-w-lg mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-md p-8 animate__animated animate__fadeInUp">
                <div class="space-y-6">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Film Title</label>
                        <input type="text" id="title" name="title" required class="mt-1 block w-full h-9 border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200" placeholder="Enter film title">
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                    <textarea id="description" name="description" rows="4" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200" placeholder="Describe your film"></textarea>
                    </div>
                    <div>
                        <label for="file" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Upload Film</label>
                        <input type="file" id="file" name="file" accept="video/mp4,video/mov,video/webm" required class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-800">
                    </div>
                    <div>
                        <label for="thumbnail" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Upload Thumbnail (Optional)</label>
                        <input type="file" id="thumbnail" name="thumbnail" accept="image/jpeg,image/png" class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-800">
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 dark:bg-indigo-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700 dark:hover:bg-indigo-600 btn-animated">Submit Film</button>
                </div>
                <?php if (isset($_GET['error'])): ?>
                    <div class="mt-4 text-center text-red-600 dark:text-red-400"><?php echo htmlspecialchars($_GET['error']); ?></div>
                <?php endif; ?>
            </form>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="py-20 bg-indigo-600 text-white animate-on-scroll">
        <div class="max-w-xl mx-auto text-center">
            <h3 class="text-3xl font-bold mb-4 text-shadow">Stay in the Loop</h3>
            <p class="mb-6">Subscribe to get updates about new films, events, and more!</p>
            <form method="POST" class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <input type="email" name="subscribe_email" placeholder="Enter your email" class="px-4 py-2 rounded w-full sm:w-2/3 text-gray-900 dark:text-gray-200 dark:bg-gray-700" required>
                <button type="submit" class="bg-white dark:bg-gray-800 text-indigo-700 dark:text-indigo-300 font-semibold px-6 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition btn-animated">Subscribe</button>
            </form>
            <?php if (isset($successMessage)): ?>
                <div class="mt-4 text-center text-green-200"><?php echo htmlspecialchars($successMessage); ?></div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 dark:bg-gray-950 text-white py-12 animate-on-scroll">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-lg font-semibold">FilmForge</h3>
                    <p class="mt-4 text-gray-400 dark:text-gray-300">Empowering independent filmmakers to share their stories with the world.</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">Quick Links</h3>
                    <ul class="mt-4 space-y-2">
                        <li><a href="#films" class="text-gray-400 dark:text-gray-300 hover:text-white">Films</a></li>
                        <li><a href="#schedule" class="text-gray-400 dark:text-gray-300 hover:text-white">Screenings</a></li>
                        <li><a href="#filmmakers" class="text-gray-400 dark:text-gray-300 hover:text-white">Filmmakers</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">Contact</h3>
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

        // Profile Modal Logic
        const profileBtn = document.getElementById('profile-btn');
        const profileSection = document.getElementById('profile');
        const closeProfileBtn = document.getElementById('close-profile');
        const editProfileBtn = document.getElementById('edit-profile-btn');
        const editProfileModal = document.getElementById('edit-profile-modal');
        const closeEditProfileBtn = document.getElementById('close-edit-profile');
        const cancelEditProfileBtn = document.getElementById('cancel-edit-profile');

        if (profileBtn && profileSection && closeProfileBtn) {
            profileBtn.addEventListener('click', () => {
                profileSection.classList.remove('hidden');
            });
            closeProfileBtn.addEventListener('click', () => {
                profileSection.classList.add('hidden');
            });
        }

        if (editProfileBtn && editProfileModal && closeEditProfileBtn && cancelEditProfileBtn) {
            editProfileBtn.addEventListener('click', () => {
                editProfileModal.classList.add('active');
            });
            closeEditProfileBtn.addEventListener('click', () => {
                editProfileModal.classList.remove('active');
            });
            cancelEditProfileBtn.addEventListener('click', () => {
                editProfileModal.classList.remove('active');
            });
        }
    </script>
</body>
</html>
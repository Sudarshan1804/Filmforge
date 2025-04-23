document.addEventListener('DOMContentLoaded', () => {
    // Profile Section Toggle
    const profileBtn = document.getElementById('profile-btn');
    const profileSection = document.getElementById('profile');
    const closeProfileBtn = document.getElementById('close-profile');
    if (profileBtn && profileSection && closeProfileBtn) {
        profileBtn.addEventListener('click', () => {
            profileSection.classList.remove('hidden');
        });
        closeProfileBtn.addEventListener('click', () => {
            profileSection.classList.add('hidden');
        });
    }

    // Edit Profile Modal Toggle
    const editProfileBtn = document.getElementById('edit-profile-btn');
    const editProfileModal = document.getElementById('edit-profile-modal');
    const closeEditProfileBtn = document.getElementById('close-edit-profile');
    const cancelEditProfileBtn = document.getElementById('cancel-edit-profile');

    if (editProfileBtn && editProfileModal && closeEditProfileBtn && cancelEditProfileBtn) {
        editProfileBtn.addEventListener('click', () => {
            editProfileModal.classList.remove('hidden');
            editProfileModal.classList.add('active'); // Use custom class for display
        });

        closeEditProfileBtn.addEventListener('click', () => {
            editProfileModal.classList.add('hidden');
            editProfileModal.classList.remove('active');
        });

        cancelEditProfileBtn.addEventListener('click', () => {
            editProfileModal.classList.add('hidden');
            editProfileModal.classList.remove('active');
        });
    }

    // Navigation Underline Toggle
    const navLinks = document.querySelectorAll('.nav-link');
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';

    const pageToLinkMap = {
        'index.php': '#home',
        'more-films.php': '#films',
        'film-details.php': '#films',
        'screening-details.php': '#schedule',
        'more-filmmakers.php': '#filmmakers',
        'filmmaker-profile.php': '#filmmakers',
        'login.php': null,
        'register.php': null,
        'update_profile.php': null
    };

    navLinks.forEach(link => {
        link.classList.remove('border-indigo-500', 'text-gray-900');
        link.classList.add('border-transparent', 'text-gray-500');
        if (link.getAttribute('href') === pageToLinkMap[currentPage]) {
            link.classList.remove('border-transparent', 'text-gray-500');
            link.classList.add('border-indigo-500', 'text-gray-900');
        }
    });

    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            navLinks.forEach(l => {
                l.classList.remove('border-indigo-500', 'text-gray-900');
                l.classList.add('border-transparent', 'text-gray-500');
            });
            link.classList.remove('border-transparent', 'text-gray-500');
            link.classList.add('border-indigo-500', 'text-gray-900');
        });
    });
});
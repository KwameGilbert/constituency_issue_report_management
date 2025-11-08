<header class="bg-red-700 text-white z-50 sticky top-0">
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 sm:gap-4">
                    <img src="assets/images/Ghana_Parliament_Emblem.png" alt="Coat of Arms" class="w-8 h-8 sm:w-10 sm:h-10">
                    <span class="font-bold text-sm sm:text-lg">Kofi Benteh Afful - The Office of the MP</span>
                </div>
                <button id="menu-toggle" class="md:hidden focus:outline-none">
                    <!-- Hamburger Icon -->
                    <svg id="hamburger-icon" class="w-6 h-6 transition-opacity duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    <!-- X Icon (hidden by default) -->
                    <svg id="close-icon" class="w-6 h-6 transition-opacity duration-200 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <nav id="mobile-menu" class="hidden md:block w-full md:w-auto mt-4 md:mt-0 md:flex md:items-center">
                <div class="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-4 w-full md:w-auto">
                    <!-- Reordered menu: Home, Projects, Media Center, Blog, About, Contact -->
                    <a href="/"
                        class="block px-2 py-1 hover:bg-red-600 rounded transition duration-200 text-sm md:text-base">Home</a>

                    <a href="/youth/"
                        class="block px-2 py-1 hover:bg-red-600 rounded transition duration-200 text-sm md:text-base">Youth</a>

                    <div class="relative group">
                        <button
                            class="flex items-center px-2 py-1 hover:bg-red-600 rounded transition duration-200 text-sm md:text-base">
                            Media Center
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div
                            class="absolute left-0 mt-1 w-48 bg-white rounded-md shadow-lg z-30 hidden group-hover:block">
                            <div class="py-1 text-gray-800">
                                <!-- Photo Gallery removed -->
                                <a href="/events/"
                                    class="block px-4 py-2 hover:bg-red-100 text-sm md:text-base">Events</a>
                                <a href="/blog/" class="block px-4 py-2 hover:bg-red-100 text-sm md:text-base">Blog</a>
                            </div>
                        </div>
                    </div>

                    <a href="/about"
                        class="block px-2 py-1 hover:bg-red-600 rounded transition duration-200 text-sm md:text-base">About
                        Us</a>

                    <a href="/contact/"
                        class="block px-2 py-1 hover:bg-red-600 rounded transition duration-200 text-sm md:text-base">Contact
                        Us</a>
                </div>
            </nav>
        </div>
    </div>
</header>

<script>
const menuToggle = document.getElementById('menu-toggle');
const mobileMenu = document.getElementById('mobile-menu');
const hamburgerIcon = document.getElementById('hamburger-icon');
const closeIcon = document.getElementById('close-icon');

// For Mobile Menu Toggle
menuToggle.addEventListener('click', function() {
    mobileMenu.classList.toggle('hidden');
    
    // Toggle between hamburger and X icons
    if (mobileMenu.classList.contains('hidden')) {
        // Menu is closed - show hamburger icon
        hamburgerIcon.classList.remove('hidden');
        closeIcon.classList.add('hidden');
    } else {
        // Menu is open - show X icon
        hamburgerIcon.classList.add('hidden');
        closeIcon.classList.remove('hidden');
    }
});

// Close menu when clicking outside
document.addEventListener('click', function(event) {
    if (!menuToggle.contains(event.target) && !mobileMenu.contains(event.target)) {
        mobileMenu.classList.add('hidden');
        // Reset to hamburger icon when menu is closed
        hamburgerIcon.classList.remove('hidden');
        closeIcon.classList.add('hidden');
    }
});

// Close menu when window is resized to larger screen
window.addEventListener('resize', function() {
    if (window.innerWidth >= 768) { // md breakpoint
        mobileMenu.classList.add('hidden');
        // Reset to hamburger icon when menu is closed
        hamburgerIcon.classList.remove('hidden');
        closeIcon.classList.add('hidden');
    }
});

// Desktop dropdown functionality
document.addEventListener('DOMContentLoaded', function() {
    // Desktop dropdown functionality
    const desktopDropdowns = document.querySelectorAll('.group');

    desktopDropdowns.forEach(dropdown => {
        if (dropdown) {
            const button = dropdown.querySelector('button');
            const menu = dropdown.querySelector('.absolute');

            if (button && menu) {
                button.addEventListener('mouseenter', () => {
                    menu.classList.remove('hidden');
                });

                dropdown.addEventListener('mouseleave', () => {
                    menu.classList.add('hidden');
                });
            }
        }
    });
});
</script>
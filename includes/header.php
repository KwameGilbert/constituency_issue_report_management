<header class="bg-red-700 text-white z-50 sticky top-0">
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 sm:gap-4">
                    <img src="/assets/images/coat-of-arms.png" alt="Coat of Arms" class="w-8 h-8 sm:w-10 sm:h-10">
                    <span class="font-bold text-sm sm:text-lg">Kofi Benteh Afful - The Office of the MP</span>
                </div>
                <button id="menu-toggle" class="md:hidden focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
            <nav id="mobile-menu" class="hidden md:block w-full md:w-auto mt-4 md:mt-0 md:flex md:items-center">
                <div class="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-4 w-full md:w-auto">
                    <a href="/"
                        class="block px-2 py-1 hover:bg-red-600 rounded transition duration-200 text-sm md:text-base">Home</a>

                    <a href="/about"
                        class="block px-2 py-1 hover:bg-red-600 rounded transition duration-200 text-sm md:text-base">About
                        Us</a>

                    <div class="relative group">
                        <button
                            class="flex items-center px-2 py-1 hover:bg-red-600 rounded transition duration-200 text-sm md:text-base">
                            Departments/Units
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div
                            class="absolute left-0 mt-1 w-48 bg-white rounded-md shadow-lg z-30 hidden group-hover:block">
                            <div class="py-1 text-gray-800">
                                <a href="#" class="block px-4 py-2 hover:bg-red-100 text-sm md:text-base">Department
                                    1</a>
                                <a href="#" class="block px-4 py-2 hover:bg-red-100 text-sm md:text-base">Department
                                    2</a>
                            </div>
                        </div>
                    </div>

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
                                <a href="photo-gallery.php"
                                    class="block px-4 py-2 hover:bg-red-100 text-sm md:text-base">Photo Gallery</a>
                                <a href="/blog/" class="block px-4 py-2 hover:bg-red-100 text-sm md:text-base">Blog</a>
                                <a href="/events/"
                                    class="block px-4 py-2 hover:bg-red-100 text-sm md:text-base">Events</a>
                            </div>
                        </div>
                    </div>

                    <div class="relative group">
                        <button
                            class="flex items-center px-2 py-1 hover:bg-red-600 rounded transition duration-200 text-sm md:text-base">
                            Projects
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div
                            class="absolute left-0 mt-1 w-48 bg-white rounded-md shadow-lg z-30 hidden group-hover:block">
                            <div class="py-1 text-gray-800">
                                <a href="/projects" class="block px-4 py-2 hover:bg-red-100 text-sm md:text-base">All
                                    Projects</a>
                            </div>
                        </div>
                    </div>

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

// For Mobile Menu Toggle
menuToggle.addEventListener('click', function() {
    mobileMenu.classList.toggle('hidden');
});

// Close menu when clicking outside
document.addEventListener('click', function(event) {
    if (!menuToggle.contains(event.target) && !mobileMenu.contains(event.target)) {
        mobileMenu.classList.add('hidden');
    }
});

// Close menu when window is resized to larger screen
window.addEventListener('resize', function() {
    if (window.innerWidth >= 768) { // md breakpoint
        mobileMenu.classList.add('hidden');
    }
});

// Mobile dropdown functionality
document.addEventListener('DOMContentLoaded', function() {
    const dropdownButtons = document.querySelectorAll('.md:hidden button');

    dropdownButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const dropdown = this.nextElementSibling;
            dropdown.classList.toggle('hidden');

            // Close other dropdowns
            dropdownButtons.forEach(otherButton => {
                if (otherButton !== button) {
                    otherButton.nextElementSibling.classList.add('hidden');
                }
            });
        });
    });
});

// Desktop dropdown functionality
document.addEventListener('DOMContentLoaded', function() {
    const desktopDropdowns = document.querySelectorAll('.md:block .group');

    desktopDropdowns.forEach(dropdown => {
        const button = dropdown.querySelector('button');
        const menu = dropdown.querySelector('.absolute');

        button.addEventListener('mouseenter', () => {
            menu.classList.remove('hidden');
        });

        dropdown.addEventListener('mouseleave', () => {
            menu.classList.add('hidden');
        });
    });
});
</script>
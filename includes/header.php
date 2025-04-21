<header class="bg-red-700 text-white">
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <img src="assets/images/coat-of-arms.png" alt="Coat of Arms" class="w-10 h-10">
                    <span class="font-bold text-lg">Sefwi Wiawso Municipal Assembly</span>
                </div>
                <button id="menu-toggle" class="md:hidden">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
            <nav id="mobile-menu" class="hidden md:block space-y-4 md:space-y-0 md:space-x-4 mt-4 md:mt-0">
                <a href="index.php" class="block md:inline-block hover:underline">Home</a>
                <a href="blog/" class="block md:inline-block hover:underline">Blog</a>
                <a href="events/" class="block md:inline-block hover:underline">Events</a>
                <a href="projects/" class="block md:inline-block hover:underline">Projects</a>
            </nav>
        </div>
    </div>
</header>
<script>
document.getElementById('menu-toggle').addEventListener('click', function() {
    document.getElementById('mobile-menu').classList.toggle('hidden');
});
</script>
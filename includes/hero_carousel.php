<?php
// 1. Fetch carousel items (excluding hero) in position order
$items = $conn
  ->query("SELECT * FROM carousel_items ORDER BY position ASC")
  ->fetch_all(MYSQLI_ASSOC);
?>
<section class="py-8 bg-gray-100">
    <div id="hero-carousel" class="relative" data-carousel="slide" data-carousel-autoplay="true"
        data-carousel-interval="5000">
        <!-- Carousel wrapper -->
        <div class="relative h-[600px] overflow-hidden rounded-lg">

            <!-- Slide 1: Hero -->
            <div class="hidden duration-700 ease-in-out absolute inset-0 transition-transform transform"
                data-carousel-item="active">
                <img src="assets/images/banner.jpg" class="block w-full h-full object-cover" alt="Hero Banner">
                <!-- Flowbite: first slide active -->
                <div class="absolute inset-0 bg-black/50 flex flex-col items-center justify-center text-white">
                    <img src="assets/images/coat-of-arms.png" alt="Coat of Arms" class="w-24 mb-4">
                    <h1 class="text-4xl md:text-5xl font-bold">Sefwi Wiawso Municipal Assembly</h1>
                    <p class="mt-2 text-lg">Your voice. Our action.</p>
                </div>
            </div>

            <!-- Slides 2–N: Database Items -->
            <?php foreach ($items as $i): ?>
            <div class="hidden duration-700 ease-in-out absolute inset-0 transition-transform transform"
                data-carousel-item>
                <a href="<?= htmlspecialchars($i['link']) ?>" class="block w-full h-full">
                    <img src="<?= htmlspecialchars($i['image_url']) ?>" class="block w-full h-full object-cover"
                        alt="<?= htmlspecialchars($i['title']) ?>">
                    <div class="absolute bottom-5 left-5 bg-black/40 p-3 rounded">
                        <span class="text-xl font-semibold text-white"><?= htmlspecialchars($i['title']) ?></span>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>

        </div>

        <!-- Prev/Next buttons -->
        <button type="button" class="absolute top-1/2 left-4 -translate-y-1/2 bg-black/50 text-white p-2 rounded-full"
            data-carousel-prev>
            ‹
        </button>
        <button type="button" class="absolute top-1/2 right-4 -translate-y-1/2 bg-black/50 text-white p-2 rounded-full"
            data-carousel-next>
            ›
        </button>

        <!-- Indicator dots (optional) -->
        <div class="absolute bottom-4 left-1/2 flex space-x-2 -translate-x-1/2">
            <?php for ($i = 0; $i <= count($items); $i++): ?>
            <button type="button" class="w-3 h-3 rounded-full bg-white/50 hover:bg-white"
                data-carousel-slide-to="<?= $i ?>"></button>
            <?php endfor; ?>
        </div>
    </div>
</section>

<!-- init Flowbite Carousel (includes autoplay & interval) -->
<script src="https://unpkg.com/flowbite@latest/dist/flowbite.js"></script>
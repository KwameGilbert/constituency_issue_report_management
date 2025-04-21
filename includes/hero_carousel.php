<?php
// Fetch carousel items (ordered by position)
$items = $conn
  ->query("SELECT * FROM carousel_items ORDER BY position ASC")
  ->fetch_all(MYSQLI_ASSOC);
?>
<section class="pb-8 bg-gray-100">
    <div id="hero-carousel" class="relative" data-carousel="slide" data-carousel-autoplay="true"
        data-carousel-interval="5000">
        <!-- Wrapper (Flowbite carousel structure) -->
        <div class="relative w-full h-[400px] md:aspect-[16/9] overflow-hidden rounded-lg">
            <!-- Hero Slide (active by default) -->
            <div class="hidden duration-700 ease-in-out absolute inset-0 transition-transform transform"
                data-carousel-item="active">
                <img src="assets/images/carousel/banner.jpg" class="block w-full h-full object-cover" alt="Hero Banner">
                <div class="absolute inset-0 bg-black/50 flex flex-col items-center justify-center text-white">
                    <img src="assets/images/coat-of-arms.png" alt="Coat of Arms" class="w-24 mb-4">
                    <h1 class="text-4xl md:text-5xl font-bold text-center">
                        Sefwi Wiawso Municipal Assembly
                    </h1>
                    <p class="mt-2 text-lg">Your voice. Our action.</p>
                </div>
            </div>

            <!-- Dynamic Slides from DB -->
            <?php foreach ($items as $i): ?>
            <div class="hidden duration-700 ease-in-out absolute inset-0 transition-transform transform"
                data-carousel-item>
                <a href="<?= htmlspecialchars($i['link']) ?>" class="block w-full h-full">
                    <img src="<?= htmlspecialchars($i['image_url']) ?>" class="block w-full h-full object-cover"
                        alt="<?= htmlspecialchars($i['title']) ?>">
                    <div class="absolute bottom-5 left-5 bg-black/40 p-3 rounded">
                        <span class="text-xl font-semibold text-white">
                            <?= htmlspecialchars($i['title']) ?>
                        </span>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Controls -->
        <button type="button"
            class="absolute top-1/2 left-4 -translate-y-1/2 bg-amber-600 text-white p-2 rounded-full hover:bg-amber-700"
            data-carousel-prev>
            ‹
        </button>
        <button type="button"
            class="absolute top-1/2 right-4 -translate-y-1/2 bg-amber-600 text-white p-2 rounded-full hover:bg-amber-700"
            data-carousel-next>
            ›
        </button>

        <!-- Indicators -->
        <div class="absolute bottom-4 left-1/2 flex space-x-2 -translate-x-1/2">
            <?php for ($i = 0; $i <= count($items); $i++): ?>
            <button type="button" class="w-3 h-3 rounded-full bg-white/50 hover:bg-white"
                data-carousel-slide-to="<?= $i ?>"></button>
            <?php endfor; ?>
        </div>
    </div>
</section>

<!-- Flowbite JS (Carousel + Swipe support) -->
<script src="https://unpkg.com/flowbite@latest/dist/flowbite.js"></script>
<!-- Hammer.js for robust swipe fallback -->
<script src="https://unpkg.com/hammerjs@2.0.8/hammer.min.js"></script>

<script>
(function() {
    const el = document.getElementById('hero-carousel');

    // Initialize Flowbite Carousel (autoplay + swipe enabled) :contentReference[oaicite:2]{index=2}
    const items = Array.from(el.querySelectorAll('[data-carousel-item]'))
        .map((el, idx) => ({
            position: idx,
            el
        }));
    const carousel = new Carousel(el, items, {
        defaultPosition: 0,
        interval: 5000
        // swipe is enabled by default in v4.3+ :contentReference[oaicite:3]{index=3}
    });

    // Hammer.js fallback: custom swipe thresholds if needed :contentReference[oaicite:4]{index=4}
    const hammer = new Hammer(el);
    // Only horizontal swipes
    hammer.get('swipe').set({
        direction: Hammer.DIRECTION_HORIZONTAL
    });
    hammer.on('swipeleft', () => carousel.next()); // next on left swipe :contentReference[oaicite:5]{index=5}
    hammer.on('swiperight', () => carousel.prev()); // prev on right swipe :contentReference[oaicite:6]{index=6}
})();
</script>
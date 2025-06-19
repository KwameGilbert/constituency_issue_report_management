<?php
// Fetch top 3 featured projects
$projects = $conn
  ->query("
    SELECT * FROM projects
    WHERE featured = 1
    ORDER BY start_date DESC
    LIMIT 3
  ")
  ->fetch_all(MYSQLI_ASSOC);
?>
<section class="py-12 bg-white">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-semibold">Featured Projects</h2>
            <a href="projects/" class="text-amber-600 hover:underline flex items-center">
                View All Projects
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($projects as $proj): ?>
            <div class="bg-gray-50 rounded-lg shadow overflow-hidden group">
                <div class="h-48 overflow-hidden">
                    <?php
            $imgs = json_decode($proj['images'], true);
            $thumb = $imgs[0] ?? 'https://via.placeholder.com/600x400';
          ?>
                    <img src="<?= htmlspecialchars($thumb) ?>" alt=""
                        class="w-full h-full object-cover group-hover:scale-105 transition">
                </div>
                <div class="p-4">
                    <h3 class="font-bold text-lg"><?= htmlspecialchars($proj['title']) ?></h3>
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($proj['sector']) ?> &bull;
                        <?= htmlspecialchars($proj['location']) ?></p>
                    <p class="mt-2 text-sm">
                        <span class="font-medium">Start:</span> <?= date('M d, Y', strtotime($proj['start_date'])) ?>
                        <?php if ($proj['end_date']): ?>
                        <br><span class="font-medium">End:</span> <?= date('M d, Y', strtotime($proj['end_date'])) ?>
                        <?php else: ?>
                        <br><span class="font-medium">Status:</span> <?= ucfirst($proj['status']) ?>
                        <?php endif; ?>
                    </p>
                    <button type="button" data-modal-target="projModal<?= $proj['id'] ?>"
                        data-modal-show="projModal<?= $proj['id'] ?>"
                        class="mt-4 inline-block px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">
                        Details
                    </button>
                </div>
            </div>

            <!-- Modal for Project Details -->
            <div id="projModal<?= $proj['id'] ?>" tabindex="-1"
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                <div class="bg-white rounded-lg overflow-y-auto max-w-2xl w-full max-h-[90vh] shadow-lg">
                    <div class="p-6">
                        <h3 class="text-2xl font-bold mb-4"><?= htmlspecialchars($proj['title']) ?></h3>
                        <p class="text-sm text-gray-600 mb-4">
                            <?= htmlspecialchars($proj['sector']) ?> — <?= htmlspecialchars($proj['location']) ?>
                        </p>
                        <p class="text-sm text-gray-700 mb-4"><?= htmlspecialchars($proj['description']) ?></p>

                        <!-- Image Gallery -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mb-4">
                            <?php foreach ($imgs as $img): ?>
                            <img src="<?= htmlspecialchars($img) ?>" alt=""
                                class="w-full h-24 object-cover rounded cursor-pointer"
                                onclick="openLightbox('<?= htmlspecialchars($img) ?>')">
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-6 flex justify-end space-x-2">
                            <button data-modal-hide="projModal<?= $proj['id'] ?>"
                                class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Lightbox script for full-size images -->
<script>
function openLightbox(src) {
    const lb = document.createElement('div');
    lb.className = 'fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50';
    lb.innerHTML = `
    <img src="${src}" class="max-w-full max-h-full rounded" alt="">
    <button class="absolute top-4 right-4 text-white text-2xl" onclick="document.body.removeChild(this.parentNode)">
      &times;
    </button>`;
    document.body.appendChild(lb);
}

// Flowbite modal init (requires flowbite.js)
</script>
<?php
// project-detail.php
require_once 'config/db.php';             // MySQLi connection
require_once 'includes/header.php';       // Navbar + hero

// 1. Fetch the selected project
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$proj = $stmt->get_result()->fetch_assoc();
if (!$proj) {
    echo "<p class='p-8 text-center text-red-600'>Project not found.</p>";
    require_once 'includes/footer.php';
    exit;
}

// Decode images JSON
$images = json_decode($proj['images'], true) ?: [];

// 2. Fetch related projects (same sector, excluding current)
$relStmt = $conn->prepare("
    SELECT id, title, location, sector, start_date, end_date, images
    FROM projects
    WHERE sector = ? AND id <> ?
    ORDER BY start_date DESC
    LIMIT 3
");
$relStmt->bind_param('si', $proj['sector'], $id);
$relStmt->execute();
$related = $relStmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<main class="bg-gray-50 py-12">
    <div class="max-w-4xl mx-auto px-4 space-y-8">

        <!-- Back Button -->
        <a href="./index.php" class="inline-flex items-center text-amber-600 hover:underline">
            ‹ Back to Projects
        </a>

        <!-- Project Header -->
        <header class="bg-white p-6 rounded-lg shadow">
            <h1 class="text-3xl font-bold mb-2"><?= htmlspecialchars($proj['title']) ?></h1>
            <p class="text-sm text-gray-600">
                <?= htmlspecialchars($proj['sector']) ?> &bull;
                <?= htmlspecialchars($proj['location']) ?> &bull;
                <span class="font-medium"><?= ucfirst($proj['status']) ?></span>
            </p>
            <p class="mt-2 text-sm text-gray-600">
                <span class="font-medium">Start:</span>
                <?= date('M d, Y', strtotime($proj['start_date'])) ?>
                <?php if ($proj['end_date']): ?>
                &bull; <span class="font-medium">End:</span>
                <?= date('M d, Y', strtotime($proj['end_date'])) ?>
                <?php endif; ?>
            </p>
        </header>

        <!-- Description & Gallery -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow">
                <h2 class="text-2xl font-semibold mb-4">Project Details</h2>
                <p class="text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($proj['description'])) ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-2xl font-semibold mb-4">Image Gallery</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    <?php foreach ($images as $img): ?>
                    <img src="<?= htmlspecialchars($img) ?>" alt=""
                        class="w-full h-24 object-cover rounded cursor-pointer"
                        onclick="openLightbox('<?= htmlspecialchars($img) ?>')">
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Related Projects -->
        <?php if ($related): ?>
        <section>
            <h2 class="text-2xl font-semibold mb-4">Related Projects</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- responsive grid :contentReference[oaicite:8]{index=8} -->
                <?php foreach ($related as $r): ?>
                <?php $thumbs = json_decode($r['images'], true); ?>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <!-- card style :contentReference[oaicite:9]{index=9} -->
                    <img src="<?= htmlspecialchars($thumbs[0] ?? '') ?>" alt="" class="w-full h-40 object-cover">
                    <div class="p-4">
                        <h3 class="font-bold text-lg"><?= htmlspecialchars($r['title']) ?></h3>
                        <p class="text-sm text-gray-600">
                            <?= date('M Y', strtotime($r['start_date'])) ?>
                            <?php if ($r['end_date']): ?>
                            &ndash; <?= date('M Y', strtotime($r['end_date'])) ?>
                            <?php else: ?>
                            (<?= ucfirst($r['status']) ?>)
                            <?php endif; ?>
                        </p>
                        <a href="project-detail.php?id=<?= $r['id'] ?>"
                            class="mt-2 inline-block text-amber-600 hover:underline">
                            View Details
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

    </div>
</main>

<!-- Lightbox -->
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
</script>

<?php require_once './../includes/footer.php'; ?>
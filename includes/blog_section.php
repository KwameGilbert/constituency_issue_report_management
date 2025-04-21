<?php
$posts = $conn->query("SELECT * FROM blog_posts ORDER BY created_at DESC LIMIT 6")
              ->fetch_all(MYSQLI_ASSOC);
?>
<section class="py-12 bg-gray-50">
    <div class="max-w-6xl mx-auto px-4">
        <h2 class="text-2xl font-semibold mb-6">Latest News & Articles</h2>
        <div class="grid md:grid-cols-3 gap-6">
            <?php foreach ($posts as $p): ?>
            <div class="bg-white shadow rounded overflow-hidden">
                <img src="<?= htmlspecialchars($p['image_url']) ?>" class="w-full h-48 object-cover" alt="">
                <div class="p-4">
                    <h3 class="font-bold text-lg"><?= htmlspecialchars($p['title']) ?></h3>
                    <p class="mt-2 text-sm text-gray-600"><?= htmlspecialchars($p['excerpt']) ?></p>
                    <a href="blog-post.php?slug=<?= urlencode($p['slug']) ?>"
                        class="mt-4 inline-block text-red-600 hover:underline">Read more</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
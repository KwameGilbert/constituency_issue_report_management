<?php
// blog-post.php

require_once __DIR__ .'/../config/db.php';             // Database connection


// Retrieve the slug from the URL
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

// Fetch the blog post by slug
$stmt = $conn->prepare("
    SELECT id, title, content, image_url, created_at
    FROM blog_posts
    WHERE slug = ?
    LIMIT 1
");
$stmt->bind_param('s', $slug);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    echo "<div class='max-w-4xl mx-auto px-4 py-12 text-center text-red-600'>Post not found.</div>";
    require_once __DIR__ .'/../includes/footer.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" itemscope itemtype="http://schema.org/Article">
<!-- Schema.org Article -->

<head>
    <!-- 1. BASIC META -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <meta name="robots" content="index, follow">
    <meta name="author" content="<?= htmlspecialchars($post['author_name']) ?>">

    <!-- 2. SEO-CRITICAL -->
    <title><?= htmlspecialchars($post['title']) ?></title>
    <meta name="description" content="<?= htmlspecialchars(mb_substr(strip_tags($post['content']), 0, 160)) ?>">
    <link rel="canonical" href="<?= 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">

    <!-- 3. FAVICONS & WEB APP -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
    <link rel="manifest" href="/site.webmanifest">

    <!-- 4. RESOURCE HINTS for Performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap">

    <!-- 5. STRUCTURED DATA (JSON-LD Article) -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Article",
        "headline": "<?= htmlspecialchars($post['title']) ?>",
        "image": ["<?= htmlspecialchars($post['image_url']) ?>"],
        "datePublished": "<?= htmlspecialchars($post['published_at']) ?>",
        "dateModified": "<?= htmlspecialchars($post['updated_at']) ?>",
        "author": {
            "@type": "Person",
            "name": "<?= htmlspecialchars($post['author_name']) ?>"
        },
        "publisher": {
            "@type": "Organization",
            "name": "Sefwi Wiawso Municipal Assembly",
            "logo": {
                "@type": "ImageObject",
                "url": "https://<?= $_SERVER['HTTP_HOST'] ?>/assets/images/coat-of-arms.png"
            }
        },
        "description": "<?= htmlspecialchars(mb_substr(strip_tags($post['content']), 0, 160)) ?>"
    }
    </script>

    <!-- 6. OPEN GRAPH / FACEBOOK -->
    <meta property="og:locale" content="en_US">
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?= htmlspecialchars($post['title']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars(mb_substr(strip_tags($post['content']), 0, 200)) ?>">
    <meta property="og:url" content="<?= 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    <meta property="og:site_name" content="Sefwi Wiawso Municipal Assembly">
    <meta property="og:image" content="<?= htmlspecialchars($post['image_url']) ?>">
    <meta property="og:image:alt" content="<?= htmlspecialchars($post['image_alt'] ?? $post['title']) ?>">
    <meta property="article:published_time" content="<?= htmlspecialchars($post['published_at']) ?>">
    <meta property="article:modified_time" content="<?= htmlspecialchars($post['updated_at']) ?>">
    <meta property="article:author" content="<?= htmlspecialchars($post['author_url']) ?>">
    <?php foreach($post['tags'] as $tag): ?>
    <meta property="article:tag" content="<?= htmlspecialchars($tag) ?>">
    <?php endforeach; ?>

    <!-- 7. TWITTER CARD -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@YourSiteHandle">
    <meta name="twitter:creator" content="@<?= htmlspecialchars($post['author_twitter'] ?? '') ?>">
    <meta name="twitter:title" content="<?= htmlspecialchars($post['title']) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars(mb_substr(strip_tags($post['content']), 0, 200)) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($post['image_url']) ?>">
    <meta name="twitter:image:alt" content="<?= htmlspecialchars($post['image_alt'] ?? $post['title']) ?>">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com" defer></script>
</head>

<body class="bg-gray-50 text-gray-900">
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="prose lg:prose-xl max-w-4xl mx-auto py-12 px-4" itemprop="articleBody">
        <!-- Article Header -->
        <article itemscope itemtype="http://schema.org/Article">
            <!-- Schema.org wrapper -->
            <header class="space-y-2">
                <h1 class="text-4xl font-bold" itemprop="headline">
                    <?= htmlspecialchars($post['title']) ?>
                </h1>
                <p class="text-sm text-gray-600">
                    <time datetime="<?= htmlspecialchars($post['created_at']) ?>" itemprop="datePublished">
                        <?= date('F j, Y', strtotime($post['created_at'])) ?>
                    </time>
                    &middot; <span>by Sefwi Wiawso Municipal Assembly</span>
                </p>

                <!-- Share Buttons -->
                <div class="flex items-center space-x-3 mt-4">
                    <!-- Native Share API -->
                    <button
                        onclick="if(navigator.share){navigator.share({title:'<?= addslashes($post['title']) ?>',url:location.href})}"
                        class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none">
                        Share
                    </button>

                    <!-- Social Icons (FontAwesome) -->
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) ?>"
                        target="_blank" class="text-blue-800 hover:text-blue-600">
                        <i class="fa fa-facebook-square fa-lg"></i>
                    </a>
                    <a href="https://twitter.com/intent/tweet?text=<?= urlencode($post['title']) ?>&url=<?= urlencode('https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) ?>"
                        target="_blank" class="text-blue-400 hover:text-blue-200">
                        <i class="fa fa-twitter-square fa-lg"></i>
                    </a>
                    <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?= urlencode('https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) ?>&title=<?= urlencode($post['title']) ?>"
                        target="_blank" class="text-blue-700 hover:text-blue-500">
                        <i class="fa fa-linkedin-square fa-lg"></i>
                    </a>
                </div>
            </header>

            <!-- Featured Image -->
            <?php if ($post['image_url']): ?>
            <figure>
                <img src="<?= htmlspecialchars($post['image_url']) ?>" alt="<?= htmlspecialchars($post['title']) ?>"
                    class="w-full rounded-lg my-6" itemprop="image">
            </figure>
            <?php endif; ?>

            <!-- Content -->
            <section class="prose max-w-none" itemprop="articleBody">
                <?= nl2br($post['content']) ?>
            </section>
        </article>
    </main>

    <?php
// Fetch related posts (excluding the current one)
$relatedStmt = $conn->prepare("
    SELECT id, title, slug, excerpt, image_url
    FROM blog_posts
    WHERE slug != ?
    ORDER BY created_at DESC
    LIMIT 3
");
$relatedStmt->bind_param('s', $slug);
$relatedStmt->execute();
$relatedPosts = $relatedStmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

    <?php if ($relatedPosts): ?>
    <section class="py-12 bg-gray-50">
        <div class="max-w-6xl mx-auto px-4">
            <h2 class="text-2xl font-semibold mb-6">Related Articles</h2>
            <div class="grid md:grid-cols-3 gap-6">
                <?php foreach ($relatedPosts as $related): ?>
                <div class="bg-white shadow rounded overflow-hidden">
                    <?php if (!empty($related['image_url'])): ?>
                    <img src="<?= htmlspecialchars($related['image_url']) ?>"
                        alt="<?= htmlspecialchars($related['title']) ?>" class="w-full h-48 object-cover">
                    <?php endif; ?>
                    <div class="p-4">
                        <h3 class="font-bold text-lg"><?= htmlspecialchars($related['title']) ?></h3>
                        <p class="mt-2 text-sm text-gray-600"><?= htmlspecialchars($related['excerpt']) ?></p>
                        <a href="blog-post.php?slug=<?= urlencode($related['slug']) ?>"
                            class="mt-4 inline-block text-red-600 hover:underline">
                            Read more
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>
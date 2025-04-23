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
 <!-- Schema.org Article :contentReference[oaicite:6]{index=6} -->

 <head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">

     <!-- Primary Meta Tags -->
     <title><?= htmlspecialchars($post['title']) ?></title>
     <meta name="description" content="<?= htmlspecialchars(
          mb_substr(strip_tags($post['content']), 0, 160)
        ) ?>"> <!-- Standard description :contentReference[oaicite:7]{index=7} -->

     <!-- Open Graph / Facebook -->
     <meta property="og:type" content="article">
     <meta property="og:title" content="<?= htmlspecialchars($post['title']) ?>">
     <meta property="og:description" content="<?= htmlspecialchars(
                                           mb_substr(strip_tags($post['content']), 0, 200)
                                         ) ?>">
     <meta property="og:url" content="<?= 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
     <meta property="og:image" content="<?= htmlspecialchars($post['image_url']) ?>">
     <meta property="og:site_name" content="Sefwi Wiawso Municipal Assembly">
     <!-- Best practices: 40–60-char title, 160-char description :contentReference[oaicite:8]{index=8} -->

     <!-- Twitter Card -->
     <meta name="twitter:card" content="summary_large_image">
     <meta name="twitter:title" content="<?= htmlspecialchars($post['title']) ?>">
     <meta name="twitter:description" content="<?= htmlspecialchars(
                                           mb_substr(strip_tags($post['content']), 0, 200)
                                         ) ?>">
     <meta name="twitter:image" content="<?= htmlspecialchars($post['image_url']) ?>">
     <!-- Twitter falls back to OG tags if missing :contentReference[oaicite:9]{index=9} -->

     <!-- Favicon -->
     <link rel="icon" href="assets/images/coat-of-arms.png">

     <!-- Tailwind CSS -->
     <script src="https://cdn.tailwindcss.com"></script>
 </head>

 <body class="bg-gray-50 text-gray-900">
     <?php require_once __DIR__ . '/../includes/header.php'; ?>

     <main class="prose lg:prose-xl max-w-4xl mx-auto py-12 px-4" itemprop="articleBody">
         <!-- Article Header -->
         <article itemscope itemtype="http://schema.org/Article">
             <!-- Schema.org wrapper :contentReference[oaicite:10]{index=10} -->
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
                     </button> <!-- Web Share API :contentReference[oaicite:11]{index=11} -->

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
                 <?= nl2br(htmlspecialchars($post['content'])) ?>
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
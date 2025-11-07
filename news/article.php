<?php
require_once '../config/db.php';

// Get article slug from URL
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    header("Location: /news/");
    exit();
}

// Fetch the specific news article (NEWS only)
try {
    $stmt = $conn->prepare("
        SELECT id, title, slug, content, excerpt, image_url, created_at, featured, category
        FROM blog_posts 
        WHERE slug = ? AND post_type = 'news'
        LIMIT 1
    ");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    $article = $result->fetch_assoc();
    
    if (!$article) {
        header("HTTP/1.0 404 Not Found");
        include('../404.php');
        exit();
    }
    
    // Get related articles
    $relatedStmt = $conn->prepare("
        SELECT id, title, slug, excerpt, image_url, created_at
        FROM blog_posts 
        WHERE id != ?
        ORDER BY created_at DESC
        LIMIT 3
    ");
    $relatedStmt->bind_param("i", $article['id']);
    $relatedStmt->execute();
    $relatedResult = $relatedStmt->get_result();
    $relatedArticles = $relatedResult->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    header("HTTP/1.0 500 Internal Server Error");
    exit("Error loading article");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['title']) ?> - News | Kofi Benteh Afful</title>
    <meta name="description" content="<?= htmlspecialchars($article['excerpt']) ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../styles/output.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/coat-of-arms.png">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?= htmlspecialchars($article['title']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($article['excerpt']) ?>">
    <?php if (!empty($article['image_url'])): ?>
    <meta property="og:image" content="<?= htmlspecialchars($article['image_url']) ?>">
    <?php endif; ?>
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($article['title']) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($article['excerpt']) ?>">
</head>

<body class="bg-gray-50">
    <?php require_once '../includes/header.php'; ?>
    
    <main>
        <!-- Breadcrumb -->
        <section class="bg-white border-b border-gray-200 py-4">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <nav class="flex items-center space-x-2 text-sm text-gray-500">
                    <a href="../" class="hover:text-amber-600 transition-colors duration-200">Home</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <a href="../news/" class="hover:text-amber-600 transition-colors duration-200">News</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span class="text-gray-900"><?= htmlspecialchars($article['title']) ?></span>
                </nav>
            </div>
        </section>

        <!-- Article Content -->
        <article class="py-12">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Article Header -->
                <header class="mb-12">
                    <?php if ($article['featured']): ?>
                    <div class="mb-6">
                        <span class="inline-flex items-center bg-red-500 text-white px-4 py-2 rounded-full text-sm font-bold">
                            <i class="fas fa-star mr-2"></i>
                            Featured Article
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6 leading-tight">
                        <?= htmlspecialchars($article['title']) ?>
                    </h1>
                    
                    <div class="flex items-center justify-between mb-8 pb-8 border-b border-gray-200">
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-calendar-alt mr-2"></i>
                                <time datetime="<?= date('c', strtotime($article['created_at'])) ?>">
                                    <?= date('F j, Y', strtotime($article['created_at'])) ?>
                                </time>
                            </div>
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-clock mr-2"></i>
                                <span><?= date('g:i A', strtotime($article['created_at'])) ?></span>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-3">
                            <span class="text-sm text-gray-600">Share:</span>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" 
                               target="_blank" 
                               class="text-gray-400 hover:text-blue-600 transition-colors duration-200">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?= urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>&text=<?= urlencode($article['title']) ?>" 
                               target="_blank" 
                               class="text-gray-400 hover:text-blue-400 transition-colors duration-200">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="https://wa.me/?text=<?= urlencode($article['title'] . ' - ' . 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" 
                               target="_blank" 
                               class="text-gray-400 hover:text-green-600 transition-colors duration-200">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        </div>
                    </div>
                </header>

                <!-- Featured Image -->
                <?php if (!empty($article['image_url'])): ?>
                <div class="mb-12">
                    <img src="<?= htmlspecialchars($article['image_url']) ?>" 
                         alt="<?= htmlspecialchars($article['title']) ?>"
                         class="w-full h-96 object-cover rounded-2xl shadow-xl"
                         onerror="this.src='../assets/images/carousel/banner.jpg'; this.onerror=null; this.style.display='block'">
                </div>
                <?php endif; ?>

                <!-- Article Content -->
                <div class="prose prose-lg max-w-none">
                    <div class="text-xl text-gray-700 mb-8 font-medium leading-relaxed bg-amber-50 p-6 rounded-xl border-l-4 border-amber-600">
                        <?= htmlspecialchars($article['excerpt']) ?>
                    </div>
                    
                    <div class="text-gray-800 leading-relaxed">
                        <?= nl2br(htmlspecialchars($article['content'])) ?>
                    </div>
                </div>

                <!-- Article Footer -->
                <footer class="mt-12 pt-8 border-t border-gray-200">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div class="mb-4 md:mb-0">
                            <span class="inline-flex items-center bg-amber-100 text-amber-800 px-3 py-1 rounded-full text-sm font-medium">
                                <i class="fas fa-tag mr-2"></i>
                                News Update
                            </span>
                        </div>
                        
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-600">Share this article:</span>
                            <div class="flex space-x-2">
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" 
                                   target="_blank" 
                                   class="bg-blue-600 hover:bg-blue-700 text-white p-2 rounded-lg transition-colors duration-200">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="https://twitter.com/intent/tweet?url=<?= urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>&text=<?= urlencode($article['title']) ?>" 
                                   target="_blank" 
                                   class="bg-blue-400 hover:bg-blue-500 text-white p-2 rounded-lg transition-colors duration-200">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="https://wa.me/?text=<?= urlencode($article['title'] . ' - ' . 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" 
                                   target="_blank" 
                                   class="bg-green-600 hover:bg-green-700 text-white p-2 rounded-lg transition-colors duration-200">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </article>

        <!-- Related Articles -->
        <?php if (!empty($relatedArticles)): ?>
        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">Related News</h2>
                    <p class="text-gray-600">More updates from our constituency</p>
                </div>
                
                <div class="grid md:grid-cols-3 gap-8">
                    <?php foreach ($relatedArticles as $related): ?>
                    <article class="group bg-gray-50 rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                        <?php if (!empty($related['image_url'])): ?>
                        <img src="<?= htmlspecialchars($related['image_url']) ?>" 
                             class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300" 
                             alt="<?= htmlspecialchars($related['title']) ?>"
                             onerror="this.src='../assets/images/carousel/banner.jpg'">
                        <?php endif; ?>
                        
                        <div class="p-6">
                            <div class="text-xs text-amber-600 bg-amber-50 px-2 py-1 rounded-full font-medium mb-3 inline-block">
                                <?= date('M d, Y', strtotime($related['created_at'])) ?>
                            </div>
                            
                            <h3 class="font-bold text-gray-900 mb-3 group-hover:text-amber-700 transition-colors duration-200">
                                <?= htmlspecialchars($related['title']) ?>
                            </h3>
                            
                            <p class="text-gray-600 text-sm mb-4">
                                <?= htmlspecialchars(substr($related['excerpt'], 0, 120)) ?>...
                            </p>
                            
                            <a href="../blog/blog-post.php?slug=<?= urlencode($related['slug']) ?>" 
                               class="inline-flex items-center text-amber-600 hover:text-amber-700 font-medium text-sm">
                                Read Article
                                <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-12">
                    <a href="../blog/" 
                       class="inline-flex items-center px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-newspaper mr-2"></i>
                        View All News
                    </a>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </main>
    
    <?php require_once '../includes/footer.php'; ?>

    <style>
    .prose {
        line-height: 1.8;
    }
    
    .prose p {
        margin-bottom: 1.5rem;
    }
    
    .prose h2, .prose h3, .prose h4 {
        margin-top: 2rem;
        margin-bottom: 1rem;
        font-weight: bold;
    }
    
    .prose h2 {
        font-size: 1.5rem;
        color: #1f2937;
    }
    
    .prose h3 {
        font-size: 1.25rem;
        color: #374151;
    }
    
    .prose ul, .prose ol {
        margin-bottom: 1.5rem;
        padding-left: 1.5rem;
    }
    
    .prose li {
        margin-bottom: 0.5rem;
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Smooth scroll to top when navigating
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        // Add copy link functionality
        const copyLinkBtn = document.createElement('button');
        copyLinkBtn.innerHTML = '<i class="fas fa-link"></i>';
        copyLinkBtn.className = 'bg-gray-600 hover:bg-gray-700 text-white p-2 rounded-lg transition-colors duration-200';
        copyLinkBtn.title = 'Copy link';
        
        copyLinkBtn.addEventListener('click', function() {
            navigator.clipboard.writeText(window.location.href);
            copyLinkBtn.innerHTML = '<i class="fas fa-check"></i>';
            setTimeout(() => {
                copyLinkBtn.innerHTML = '<i class="fas fa-link"></i>';
            }, 2000);
        });
        
        const shareContainer = document.querySelector('footer .flex.space-x-2');
        if (shareContainer) {
            shareContainer.appendChild(copyLinkBtn);
        }
    });
    </script>
</body>
</html>
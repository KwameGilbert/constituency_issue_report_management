<?php
require_once '../config/db.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Get category filter
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Build query with filters for NEWS only
$where_clause = "WHERE post_type = 'news'";
if (!empty($category)) {
    $where_clause .= " AND category = '" . mysqli_real_escape_string($conn, $category) . "'";
}

// Fetch news articles (separate from blog posts)
try {
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM blog_posts $where_clause";
    $countResult = $conn->query($countQuery);
    $totalNews = $countResult->fetch_assoc()['total'];
    
    // Get news articles
    $newsQuery = "
        SELECT id, title, slug, excerpt, image_url, created_at, featured, category
        FROM blog_posts 
        $where_clause
        ORDER BY featured DESC, created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    $newsResult = $conn->query($newsQuery);
    $newsArticles = $newsResult->fetch_all(MYSQLI_ASSOC);
    
    // Get available categories
    $categoriesQuery = "SELECT DISTINCT category FROM blog_posts WHERE post_type = 'news' AND category IS NOT NULL ORDER BY category";
    $categories = $conn->query($categoriesQuery)->fetch_all(MYSQLI_ASSOC);
    
    $totalPages = ceil($totalNews / $limit);
} catch (Exception $e) {
    $newsArticles = [];
    $categories = [];
    $totalNews = 0;
    $totalPages = 1;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News - Kofi Benteh Afful | The Office of the MP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../styles/output.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/coat-of-arms.png">
</head>

<body class="bg-gray-50">
    <?php require_once '../includes/header.php'; ?>
    
    <main>
        <!-- Hero Section -->
        <section class="bg-linear-to-r from-amber-600 to-amber-700 py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center text-white">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-white/20 rounded-full mb-6">
                        <i class="fas fa-newspaper text-3xl"></i>
                    </div>
                    <h1 class="text-5xl font-bold mb-4">Latest News</h1>
                    <p class="text-xl text-amber-100 max-w-3xl mx-auto">
                        Stay informed with the latest developments, announcements, and important updates from our constituency
                    </p>
                </div>
            </div>
        </section>

        <!-- News Articles Section -->
        <section class="py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <?php if (!empty($newsArticles)): ?>
                <!-- Stats Bar -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-12">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div class="mb-4 md:mb-0">
                            <h2 class="text-2xl font-bold text-gray-900">All News Articles</h2>
                            <p class="text-gray-600">
                                Showing <?= count($newsArticles) ?> of <?= $totalNews ?> articles
                            </p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-600">Sort by:</span>
                            <select class="bg-gray-50 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option>Latest First</option>
                                <option>Oldest First</option>
                                <option>Featured First</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- News Grid -->
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
                    <?php foreach ($newsArticles as $index => $article): ?>
                    <article class="group bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100">
                        <!-- Image -->
                        <div class="relative overflow-hidden">
                            <?php if (!empty($article['image_url'])): ?>
                            <img src="<?= htmlspecialchars($article['image_url']) ?>" 
                                 class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300" 
                                 alt="<?= htmlspecialchars($article['title']) ?>"
                                                                  onerror="this.src='../assets/images/carousel/banner.jpg'">
                            <?php else: ?>
                            <div class="w-full h-64 bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center">
                                <i class="fas fa-newspaper text-white text-5xl"></i>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($article['featured']): ?>
                            <div class="absolute top-4 left-4">
                                <span class="bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold">
                                    FEATURED
                                </span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="absolute bottom-4 right-4">
                                <span class="bg-white/90 backdrop-blur-sm text-amber-700 px-3 py-1 rounded-full text-xs font-bold">
                                    <?= date('M d, Y', strtotime($article['created_at'])) ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Content -->
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-xs text-amber-600 bg-amber-50 px-3 py-1 rounded-full font-semibold uppercase tracking-wide">
                                    News Update
                                </span>
                                <span class="text-xs text-gray-500">
                                    <?= date('g:i A', strtotime($article['created_at'])) ?>
                                </span>
                            </div>
                            
                            <h3 class="font-bold text-gray-900 mb-4 text-xl leading-tight group-hover:text-amber-700 transition-colors duration-200">
                                <?= htmlspecialchars($article['title']) ?>
                            </h3>
                            
                            <p class="text-gray-600 mb-6 leading-relaxed">
                                <?= htmlspecialchars($article['excerpt']) ?>
                            </p>
                            
                            <a href="../blog/blog-post.php?slug=<?= urlencode($article['slug']) ?>" 
                               class="inline-flex items-center text-amber-600 hover:text-amber-700 font-semibold group-hover:translate-x-1 transition-all duration-200">
                                Read Full Article
                                <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="flex justify-center">
                    <nav class="flex items-center space-x-2">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" 
                           class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?= $i ?>" 
                           class="px-4 py-2 <?= $i === $page ? 'bg-amber-600 text-white' : 'bg-white border border-gray-300 hover:bg-gray-50' ?> rounded-lg transition-colors duration-200">
                            <?= $i ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>" 
                           class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <!-- No news available -->
                <div class="text-center py-20">
                    <div class="inline-flex items-center justify-center w-24 h-24 bg-gray-100 rounded-full mb-8">
                        <i class="fas fa-newspaper text-gray-400 text-4xl"></i>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 mb-4">No News Articles Found</h3>
                    <p class="text-gray-600 text-lg mb-8 max-w-md mx-auto">
                        We're working to bring you the latest updates. Check back soon for important announcements and news.
                    </p>
                    <a href="../" 
                       class="inline-flex items-center px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg transition-colors duration-200">
                        <i class="fas fa-home mr-2"></i>
                        Return to Homepage
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    
    <?php require_once '../includes/footer.php'; ?>

    <style>
    .animate-fadeInUp {
        animation: fadeInUp 0.6s ease-out;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add fade-in animation to articles
        const articles = document.querySelectorAll('article');
        articles.forEach((article, index) => {
            article.style.animationDelay = `${index * 0.1}s`;
            article.classList.add('animate-fadeInUp');
        });
        
        // Smooth scroll for pagination
        const paginationLinks = document.querySelectorAll('nav a');
        paginationLinks.forEach(link => {
            link.addEventListener('click', function() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    });
    </script>
</body>
</html>
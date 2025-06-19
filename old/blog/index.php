<?php
require_once '../config/db.php';

// Handle filtering and search
$search = $_GET['search'] ?? '';

// Build WHERE clause
$where = [];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where[] = "(title LIKE ? OR content LIKE ? OR excerpt LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

// Pagination
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$posts_per_page = 9;
$offset = ($current_page - 1) * $posts_per_page;

// Prepare the WHERE clause
$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Count total posts for pagination
$count_sql = "SELECT COUNT(*) as total FROM blog_posts $where_clause";
$count_stmt = $conn->prepare($count_sql);

if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}

$count_stmt->execute();
$total_posts = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_posts / $posts_per_page);

// Fetch posts with pagination
$sql = "SELECT id, title, slug, excerpt, content, image_url, created_at, featured FROM blog_posts $where_clause ORDER BY created_at DESC LIMIT ?, ?";
$stmt = $conn->prepare($sql);

// Add pagination parameters
$params[] = $offset;
$params[] = $posts_per_page;
$param_types .= 'ii';

$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch featured posts for sidebar
$featured_posts = $conn->query("SELECT id, title, slug, image_url FROM blog_posts WHERE featured = 1 ORDER BY created_at DESC LIMIT 4")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog | Sefwi Wiawso Constituency</title>
    <meta name="description" content="The latest news, articles, and updates from Sefwi Wiawso Constituency">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
     <script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/assets/css/main.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/coat-of-arms.png">
</head>

<body class="bg-gray-50">
    <?php include_once '../includes/header.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="bg-amber-600 text-white py-12 md:py-20">
            <div class="max-w-6xl mx-auto px-4">
                <h1 class="text-3xl md:text-4xl font-bold mb-4">Our Blog</h1>
                <p class="text-lg md:text-xl max-w-3xl">Stay informed about the latest news, initiatives, and updates
                    from the Sefwi Wiawso Constituency</p>
            </div>
        </section>

        <div class="max-w-6xl mx-auto px-4 py-12">
            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Main Content -->
                <div class="lg:w-2/3">
                    <!-- Search and Filters -->
                    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                        <form action="" method="GET"
                            class="space-y-4 md:space-y-0 md:flex md:flex-wrap md:gap-4 items-end">
                            <!-- Search -->
                            <div class="md:w-2/3">
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search
                                    Posts</label>
                                <input type="text" name="search" id="search" value="<?= htmlspecialchars($search) ?>"
                                    placeholder="Search by title or content"
                                    class="block w-full rounded-md border border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm">
                            </div>

                            <!-- Filter buttons -->
                            <div class="md:flex md:items-end md:gap-2">
                                <button type="submit"
                                    class="w-full md:w-auto px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700 transition-colors">
                                    <i class="fas fa-search mr-2"></i> Search
                                </button>
                                <?php if (!empty($search)): ?>
                                <a href="index.php"
                                    class="mt-2 md:mt-0 inline-block w-full md:w-auto px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition-colors text-center">
                                    <i class="fas fa-times mr-2"></i> Clear Search
                                </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>

                    <!-- Blog Posts -->
                    <h2 class="text-2xl font-semibold mb-6">
                        <?= !empty($search) ? 'Search Results' : 'Latest Articles' ?>
                        <span class="text-gray-500 text-lg">(<?= $total_posts ?>)</span>
                    </h2>

                    <?php if (count($posts) > 0): ?>
                    <div class="space-y-8">
                        <?php foreach ($posts as $post): ?>
                        <article
                            class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                            <div class="md:flex">
                                <?php if (!empty($post['image_url'])): ?>
                                <div class="md:w-1/3">
                                    <a href="blog-post.php?slug=<?= urlencode($post['slug']) ?>">
                                        <img src="<?= htmlspecialchars($post['image_url']) ?>"
                                            alt="<?= htmlspecialchars($post['title']) ?>"
                                            class="w-full h-48 md:h-full object-cover">
                                    </a>
                                </div>
                                <?php endif; ?>

                                <div class="p-6 md:w-2/3">
                                    <?php if ($post['featured']): ?>
                                    <span
                                        class="inline-block bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full mb-2">
                                        <i class="fas fa-star text-yellow-500 mr-1"></i> Featured
                                    </span>
                                    <?php endif; ?>

                                    <h3 class="text-xl font-bold mb-2 hover:text-amber-600">
                                        <a href="blog-post.php?slug=<?= urlencode($post['slug']) ?>">
                                            <?= htmlspecialchars($post['title']) ?>
                                        </a>
                                    </h3>

                                    <div class="text-sm text-gray-500 mb-3">
                                        <span><i
                                                class="far fa-calendar-alt mr-2"></i><?= date('F j, Y', strtotime($post['created_at'])) ?></span>
                                    </div>

                                    <p class="text-gray-600 mb-4 line-clamp-3">
                                        <?= htmlspecialchars($post['excerpt'] ?: mb_substr(strip_tags($post['content']), 0, 160) . '...') ?>
                                    </p>

                                    <a href="blog-post.php?slug=<?= urlencode($post['slug']) ?>"
                                        class="inline-block text-amber-600 font-medium hover:text-amber-800 hover:underline">
                                        Read More <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="mt-8">
                        <nav class="flex justify-center">
                            <ul class="flex">
                                <!-- Previous page -->
                                <?php if ($current_page > 1): ?>
                                <a href="?page=<?= $current_page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
                                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <i class="fas fa-chevron-left h-5 w-5"></i>
                                </a>
                                <?php else: ?>
                                <span
                                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                    <i class="fas fa-chevron-left h-5 w-5"></i>
                                </span>
                                <?php endif; ?>

                                <!-- Page numbers -->
                                <?php
                                $start_page = max(1, $current_page - 2);
                                $end_page = min($total_pages, $current_page + 2);

                                if ($start_page > 1) {
                                    echo '<a href="?page=1' . (!empty($search) ? '&search=' . urlencode($search) : '') . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                    
                                    if ($start_page > 2) {
                                        echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                    }
                                }

                                for ($i = $start_page; $i <= $end_page; $i++) {
                                    if ($i == $current_page) {
                                        echo '<span aria-current="page" class="relative inline-flex items-center px-4 py-2 border border-amber-500 bg-amber-50 text-sm font-medium text-amber-600">' . $i . '</span>';
                                    } else {
                                        echo '<a href="?page=' . $i . (!empty($search) ? '&search=' . urlencode($search) : '') . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $i . '</a>';
                                    }
                                }

                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) {
                                        echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                    }
                                    echo '<a href="?page=' . $total_pages . (!empty($search) ? '&search=' . urlencode($search) : '') . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $total_pages . '</a>';
                                }
                                ?>

                                <!-- Next page -->
                                <?php if ($current_page < $total_pages): ?>
                                <a href="?page=<?= $current_page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
                                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <i class="fas fa-chevron-right h-5 w-5"></i>
                                </a>
                                <?php else: ?>
                                <span
                                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                    <i class="fas fa-chevron-right h-5 w-5"></i>
                                </span>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>

                    <?php else: ?>
                    <div class="bg-white rounded-lg shadow-md p-8 text-center">
                        <div
                            class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 text-amber-600 mb-4">
                            <i class="fas fa-newspaper text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No articles found</h3>
                        <p class="text-gray-500 mb-6">
                            <?php if (!empty($search)): ?>
                            No articles match your search criteria. Try adjusting your search terms.
                            <?php else: ?>
                            There are no articles published at the moment.
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($search)): ?>
                        <a href="index.php"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-times mr-2"></i> Clear Search
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="lg:w-1/3 space-y-8">
                    <!-- Featured Posts -->
                    <?php if (count($featured_posts) > 0): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold mb-4 pb-2 border-b">Featured Articles</h3>
                        <div class="space-y-4">
                            <?php foreach ($featured_posts as $featured): ?>
                            <div class="flex items-center gap-3">
                                <?php if (!empty($featured['image_url'])): ?>
                                <a href="blog-post.php?slug=<?= urlencode($featured['slug']) ?>" class="flex-shrink-0">
                                    <img src="<?= htmlspecialchars($featured['image_url']) ?>"
                                        alt="<?= htmlspecialchars($featured['title']) ?>"
                                        class="w-16 h-16 object-cover rounded">
                                </a>
                                <?php endif; ?>
                                <div>
                                    <h4 class="font-medium text-sm">
                                        <a href="blog-post.php?slug=<?= urlencode($featured['slug']) ?>"
                                            class="hover:text-amber-600">
                                            <?= htmlspecialchars($featured['title']) ?>
                                        </a>
                                    </h4>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Newsletter Subscription -->
                    <div class="bg-amber-50 rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold mb-2">Subscribe to Newsletter</h3>
                        <p class="text-sm text-gray-600 mb-4">Stay updated with our latest news and developments</p>
                        <form action="#" method="post" class="space-y-3">
                            <div>
                                <input type="email" name="email" placeholder="Your email address" required
                                    class="w-full px-3 py-2 border border-amber-300 rounded-md focus:outline-none focus:ring-1 focus:ring-amber-500">
                            </div>
                            <button type="submit"
                                class="w-full px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700 transition-colors">
                                Subscribe <i class="fas fa-paper-plane ml-1"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Social Media Links -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold mb-4">Connect With Us</h3>
                        <div class="flex justify-between">
                            <a href="#"
                                class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#"
                                class="w-10 h-10 rounded-full bg-sky-500 text-white flex items-center justify-center hover:bg-sky-600">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#"
                                class="w-10 h-10 rounded-full bg-red-600 text-white flex items-center justify-center hover:bg-red-700">
                                <i class="fab fa-youtube"></i>
                            </a>
                            <a href="#"
                                class="w-10 h-10 rounded-full bg-pink-600 text-white flex items-center justify-center hover:bg-pink-700">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#"
                                class="w-10 h-10 rounded-full bg-green-600 text-white flex items-center justify-center hover:bg-green-700">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include_once '../includes/footer.php'; ?>

    <script>
    </script>
</body>

</html>
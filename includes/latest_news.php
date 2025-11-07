<?php
// Fetch the latest news posts for ticker (all) and cards (4) - with error handling
try {
    // Get all news for ticker
    $ticker_news = $conn
        ->query("
            SELECT id, title, slug, excerpt, image_url, created_at, featured
            FROM blog_posts 
            WHERE post_type = 'news'
            ORDER BY featured DESC, created_at DESC
        ")
        ->fetch_all(MYSQLI_ASSOC);
        
    // Get latest 4 news for cards
    $latest_news = $conn
        ->query("
            SELECT id, title, slug, excerpt, image_url, created_at, featured
            FROM blog_posts 
            WHERE post_type = 'news'
            ORDER BY featured DESC, created_at DESC
            LIMIT 4
        ")
        ->fetch_all(MYSQLI_ASSOC);
        
    // Use ticker_news for ticker, latest_news for cards
    $ticker_data = !empty($ticker_news) ? $ticker_news : $latest_news;
} catch (Exception $e) {
    // If there's an error or no table, set empty arrays
    $latest_news = [];
    $ticker_data = [];
}

// If no news found, create some sample data for demonstration
// if (empty($latest_news)) {
//     $latest_news = [
//         [
//             'id' => 1,
//             'title' => 'New Infrastructure Projects Launched in Sefwi Wiawso',
//             'slug' => 'new-infrastructure-projects-launched',
//             'excerpt' => 'Several new infrastructure projects have been launched to improve the lives of our constituents, including road improvements and water facility upgrades.',
//             'image_url' => 'assets/images/news/infrastructure-project.jpg',
//             'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
//             'featured' => 1
//         ],
//         [
//             'id' => 2,
//             'title' => 'Youth Employment Program Registration Opens',
//             'slug' => 'youth-employment-program-registration',
//             'excerpt' => 'Young people in the constituency can now register for the new youth employment program aimed at creating sustainable job opportunities.',
//             'image_url' => 'assets/images/news/youth-employment.jpg',
//             'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
//             'featured' => 1
//         ],
//         [
//             'id' => 3,
//             'title' => 'Community Health Center Receives Medical Equipment',
//             'slug' => 'community-health-center-equipment',
//             'excerpt' => 'The community health center has received new medical equipment to better serve the healthcare needs of our constituents.',
//             'image_url' => 'assets/images/news/health-equipment.jpg',
//             'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
//             'featured' => 0
//         ],
//         [
//             'id' => 4,
//             'title' => 'Educational Support Program for Students Announced',
//             'slug' => 'educational-support-program-announced',
//             'excerpt' => 'A new educational support program has been announced to help students in the constituency with their academic pursuits.',
//             'image_url' => 'assets/images/news/education-support.jpg',
//             'created_at' => date('Y-m-d H:i:s', strtotime('-4 days')),
//             'featured' => 0
//         ]
//     ];
// }
?>

<section class="py-16 bg-linear-to-b from-gray-50 to-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-amber-100 rounded-full mb-4">
                <i class="fas fa-newspaper text-amber-600 text-2xl"></i>
            </div>
            <h2 class="text-4xl font-bold text-gray-900 mb-4">Latest News</h2>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                Stay informed with the latest developments and announcements from our constituency
            </p>
            <div class="mt-4 w-24 h-1 bg-amber-600 mx-auto rounded-full"></div>
        </div>

        <?php if (!empty($ticker_data)): ?>
        <!-- Featured News Ticker -->
        <div class="relative news-ticker-background rounded-xl shadow-xl overflow-hidden mb-12">
            <!-- Breaking News Icon - Responsive -->
            <div class="absolute left-0 top-0 h-full w-20 md:w-28 bg-amber-800 flex items-center justify-center">
                <div class="text-white text-center">
                    <div class="animate-pulse">
                        <i class="fas fa-broadcast-tower text-lg md:text-2xl mb-1 md:mb-2"></i>
                    </div>
                    <p class="text-xs font-bold uppercase tracking-wide hidden md:block">Breaking</p>
                    <p class="text-xs font-bold uppercase tracking-wide block md:hidden">News</p>
                </div>
            </div>
            
            <!-- News Content Area - Responsive -->
            <div class="ml-20 md:ml-28 py-4 md:py-6 pr-4 md:pr-6 relative">
                <!-- Subtle overlay for better text contrast -->
                <div class="absolute inset-0 bg-gradient-to-r from-black/10 via-transparent to-black/5 pointer-events-none"></div>
                <div id="news-ticker" class="overflow-hidden h-auto md:h-16 relative z-10">
                    <?php foreach ($ticker_data as $index => $news): ?>
                    <div class="news-item h-auto md:h-16 flex flex-col md:flex-row items-start md:items-center py-2 md:py-0 <?= $index === 0 ? 'active' : '' ?>">
                        <div class="flex flex-col md:flex-row items-start md:items-center w-full space-y-2 md:space-y-0">
                            <!-- Featured & Date badges -->
                            <div class="flex items-center space-x-2 md:space-x-4">
                                <?php if ($news['featured']): ?>
                                <span class="bg-red-500 text-white px-2 md:px-3 py-1 rounded-full text-xs font-bold animate-pulse shadow-lg">
                                    <i class="fas fa-star mr-1 hidden md:inline"></i>FEATURED
                                </span>
                                <?php endif; ?>
                                <span class="bg-white text-amber-800 px-2 md:px-3 py-1 md:py-2 rounded-lg text-xs md:text-sm font-bold shadow-md">
                                    <i class="fas fa-calendar-alt mr-1 hidden md:inline"></i><?= date('M d, Y', strtotime($news['created_at'])) ?>
                                </span>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-1 min-w-0 md:ml-4">
                                <h3 class="text-lg md:text-xl font-black truncate text-white px-2 md:px-3 py-1 rounded-lg shadow-lg" style="text-shadow: 2px 2px 4px rgba(0,0,0,0.7), 1px 1px 2px rgba(0,0,0,0.9);">
                                    <?= htmlspecialchars($news['title']) ?>
                                </h3>
                                <p class="text-xs md:text-sm truncate font-bold text-white px-2 md:px-3 py-1 rounded-lg mt-1 shadow-md hidden md:block" style="text-shadow: 1px 1px 3px rgba(0,0,0,0.6), 1px 1px 1px rgba(0,0,0,0.8);">
                                    <?= htmlspecialchars($news['excerpt']) ?>
                                </p>
                            </div>
                            
                            <!-- Read More Button -->
                            <a href="news/article.php?slug=<?= urlencode($news['slug']) ?>" 
                               class="text-white hover:text-amber-100 text-xs md:text-sm font-bold bg-gray-900 hover:bg-gray-800 px-3 md:px-4 py-2 rounded-lg transition-colors duration-200 shrink-0 shadow-lg w-full md:w-auto text-center mt-2 md:mt-0 md:ml-4">
                                <i class="fas fa-arrow-right mr-2"></i>Read <span class="hidden md:inline">Full</span> News
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Enhanced Progress indicator -->
            <div class="absolute bottom-0 left-28 right-0 h-1 bg-amber-800">
                <div id="progress-bar" class="h-full bg-white transition-all duration-4000 ease-linear w-0"></div>
            </div>
        </div>

        <!-- News Grid -->
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php foreach ($latest_news as $index => $news): ?>
            <article class="group bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 hover:border-amber-200">
                <!-- Image Container -->
                <div class="relative overflow-hidden">
                    <?php if (!empty($news['image_url'])): ?>
                    <img src="<?= htmlspecialchars($news['image_url']) ?>" 
                         class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300" 
                         alt="<?= htmlspecialchars($news['title']) ?>"
                         onerror="this.src='assets/images/carousel/banner.jpg'; this.onerror=null;">
                    <?php else: ?>
                    <div class="w-full h-48 bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center">
                        <i class="fas fa-newspaper text-white text-4xl"></i>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Featured Badge -->
                    <?php if ($news['featured']): ?>
                    <div class="absolute top-4 left-4">
                        <span class="bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold animate-pulse">
                            FEATURED
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Date Badge -->
                    <div class="absolute bottom-4 right-4">
                        <span class="bg-white/90 backdrop-blur-sm text-amber-700 px-3 py-1 rounded-full text-xs font-bold">
                            <?= date('M d', strtotime($news['created_at'])) ?>
                        </span>
                    </div>
                </div>
                
                <!-- Content -->
                <div class="p-6">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs text-amber-600 bg-amber-50 px-3 py-1 rounded-full font-semibold uppercase tracking-wide">
                            News Update
                        </span>
                        <span class="text-xs text-gray-500">
                            <?= date('g:i A', strtotime($news['created_at'])) ?>
                        </span>
                    </div>
                    
                    <h3 class="font-bold text-gray-900 mb-3 text-lg leading-tight group-hover:text-amber-700 transition-colors duration-200 line-clamp-2">
                        <?= htmlspecialchars($news['title']) ?>
                    </h3>
                    
                    <p class="text-gray-600 mb-4 text-sm leading-relaxed line-clamp-3">
                        <?= htmlspecialchars($news['excerpt']) ?>
                    </p>
                    
                    <a href="blog/blog-post.php?slug=<?= urlencode($news['slug']) ?>" 
                       class="inline-flex items-center text-amber-600 hover:text-amber-700 font-semibold text-sm group-hover:translate-x-1 transition-all duration-200">
                        Read Full News
                        <i class="fas fa-arrow-right ml-2 text-xs"></i>
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <!-- View All News Button -->
        <div class="text-center mt-12">
            <a href="blog/" 
               class="inline-flex items-center px-8 py-4 bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <i class="fas fa-newspaper mr-3"></i>
                View All News Articles
                <i class="fas fa-arrow-right ml-3"></i>
            </a>
        </div>
        
        <?php else: ?>
        <!-- No news available -->
        <div class="text-center py-16">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-gray-100 rounded-full mb-6">
                <i class="fas fa-newspaper text-gray-400 text-3xl"></i>
            </div>
            <h3 class="text-2xl font-semibold text-gray-900 mb-4">No News Available</h3>
            <p class="text-gray-600 text-lg mb-8 max-w-md mx-auto">
                We're working to bring you the latest updates. Check back soon for important announcements and news from our constituency.
            </p>
            <a href="/contact/" 
               class="inline-flex items-center px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg transition-colors duration-200">
                <i class="fas fa-envelope mr-2"></i>
                Contact Us for Updates
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
/* Enhanced styles for news section */
.news-item {
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.6s cubic-bezier(0.4, 0.0, 0.2, 1);
    position: absolute;
    width: 100%;
}

.news-item.active {
    opacity: 1;
    transform: translateY(0);
    position: relative;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Smooth hover animations */
.group:hover .group-hover\:scale-105 {
    transform: scale(1.05);
}

.group:hover .group-hover\:translate-x-1 {
    transform: translateX(0.25rem);
}

.group:hover .group-hover\:text-amber-700 {
    color: #b45309;
}

/* Enhanced animation effects */
@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideOutLeft {
    from {
        opacity: 1;
        transform: translateX(0);
    }
    to {
        opacity: 0;
        transform: translateX(-30px);
    }
}

.slide-in {
    animation: slideInRight 0.6s ease-out;
}

.slide-out {
    animation: slideOutLeft 0.6s ease-in;
}

/* Enhanced background with subtle pattern for text visibility */
.news-ticker-background {
    background: 
        linear-gradient(135deg, rgba(0,0,0,0.1) 0%, transparent 25%, rgba(0,0,0,0.05) 50%, transparent 75%, rgba(0,0,0,0.1) 100%),
        linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 50%, rgba(255,255,255,0.05) 100%),
        linear-gradient(135deg, #f59e0b 0%, #d97706 50%, #f59e0b 100%);
    background-size: 100px 100px, 60px 60px, 200% 200%;
    animation: gradientShift 8s ease-in-out infinite, patternMove 15s linear infinite;
}

@keyframes gradientShift {
    0%, 100% { background-position: 0% 50%, 0% 0%, 0% 50%; }
    50% { background-position: 100% 50%, 50% 50%, 100% 50%; }
}

@keyframes patternMove {
    0% { background-position: 0px 0px, 0px 0px, 0% 50%; }
    100% { background-position: 100px 100px, 60px 60px, 0% 50%; }
}

/* Card stagger animation */
.news-card {
    animation: fadeInUp 0.6s ease-out;
    animation-fill-mode: both;
}

.news-card:nth-child(1) { animation-delay: 0.1s; }
.news-card:nth-child(2) { animation-delay: 0.2s; }
.news-card:nth-child(3) { animation-delay: 0.3s; }
.news-card:nth-child(4) { animation-delay: 0.4s; }

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Mobile responsive adjustments */
@media (max-width: 768px) {
    .news-item h3 {
        font-size: 1rem;
        line-height: 1.4;
    }
    
    .news-item p {
        font-size: 0.875rem;
    }
    
    #news-ticker {
        height: auto;
        min-height: 6rem;
    }
    
    .news-item {
        height: auto;
        min-height: 6rem;
        padding: 0.75rem 0;
    }
    
    .news-ticker-background {
        border-radius: 0.5rem;
    }
    
    /* Adjust progress bar for mobile */
    .absolute.bottom-0 {
        left: 5rem !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const newsItems = document.querySelectorAll('.news-item');
    const progressBar = document.getElementById('progress-bar');
    
    // Add news-card class to grid items for stagger animation
    const newsCards = document.querySelectorAll('article');
    newsCards.forEach(card => card.classList.add('news-card'));
    
    if (newsItems.length > 1) {
        let currentIndex = 0;
        const tickerInterval = 5000; // 5 seconds
        let intervalId;
        
        function showNextNews() {
            // Add slide-out animation to current item
            newsItems[currentIndex].classList.add('slide-out');
            
            setTimeout(() => {
                // Hide current item
                newsItems[currentIndex].classList.remove('active', 'slide-out');
                
                // Move to next item
                currentIndex = (currentIndex + 1) % newsItems.length;
                
                // Show next item with slide-in animation
                newsItems[currentIndex].classList.add('active', 'slide-in');
                
                // Remove slide-in class after animation
                setTimeout(() => {
                    newsItems[currentIndex].classList.remove('slide-in');
                }, 600);
                
            }, 300);
            
            // Reset and animate progress bar
            progressBar.style.width = '0%';
            progressBar.style.transition = 'none';
            setTimeout(() => {
                progressBar.style.transition = 'width 5s linear';
                progressBar.style.width = '100%';
            }, 100);
        }
        
        function startTicker() {
            // Initial progress bar animation
            progressBar.style.width = '100%';
            
            // Start the ticker
            intervalId = setInterval(showNextNews, tickerInterval);
        }
        
        function pauseTicker() {
            clearInterval(intervalId);
            progressBar.style.animationPlayState = 'paused';
        }
        
        function resumeTicker() {
            startTicker();
            progressBar.style.animationPlayState = 'running';
        }
        
        // Start the ticker
        startTicker();
        
        // Pause on hover
        const tickerContainer = document.querySelector('.relative.bg-linear-to-r');
        if (tickerContainer) {
            tickerContainer.addEventListener('mouseenter', pauseTicker);
            tickerContainer.addEventListener('mouseleave', resumeTicker);
        }
        
        // Touch/click interaction for mobile
        newsItems.forEach((item, index) => {
            item.addEventListener('click', function() {
                if (index !== currentIndex) {
                    clearInterval(intervalId);
                    
                    // Hide current
                    newsItems[currentIndex].classList.remove('active');
                    
                    // Show clicked
                    currentIndex = index;
                    newsItems[currentIndex].classList.add('active');
                    
                    // Restart ticker
                    startTicker();
                }
            });
        });
        
    } else if (newsItems.length === 1) {
        // If only one item, just show it and animate progress bar
        progressBar.style.width = '100%';
    }
    
    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationDelay = '0s';
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);
    
    // Observe all news cards
    newsCards.forEach(card => {
        observer.observe(card);
    });
    
    // Image loading with fade-in effect
    const images = document.querySelectorAll('img[src*="news/"]');
    images.forEach(img => {
        img.style.opacity = '0';
        img.style.transition = 'opacity 0.5s ease-in-out';
        
        img.addEventListener('load', function() {
            this.style.opacity = '1';
        });
        
        // If image is already loaded
        if (img.complete) {
            img.style.opacity = '1';
        }
    });
});

// Add smooth scroll behavior for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
</script>
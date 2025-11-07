<?php
// Fetch event images for the gallery
try {
    $events_query = "
        SELECT id, name, location, start_date, images, image_url
        FROM events 
        WHERE images IS NOT NULL OR image_url IS NOT NULL
        ORDER BY start_date DESC
        LIMIT 12
    ";
    
    $events_result = $conn->query($events_query);
    $events = $events_result ? $events_result->fetch_all(MYSQLI_ASSOC) : [];
    
    // Process events and prepare gallery images
    $gallery_images = [];
    foreach ($events as $event) {
        $event_images = [];
        
        // Add main image if exists
        if (!empty($event['image_url'])) {
            $event_images[] = $event['image_url'];
        }
        
        // Add additional images from JSON if exists
        if (!empty($event['images'])) {
            $additional_images = json_decode($event['images'], true);
            if (is_array($additional_images)) {
                $event_images = array_merge($event_images, $additional_images);
            }
        }
        
        // Add to gallery if we have images
        if (!empty($event_images)) {
            $gallery_images[] = [
                'event_id' => $event['id'],
                'event_name' => $event['name'],
                'event_location' => $event['location'],
                'event_date' => $event['start_date'],
                'images' => $event_images
            ];
        }
    }
    
    // If no events with images, create sample data
    if (empty($gallery_images)) {
        $gallery_images = [
            [
                'event_id' => 1,
                'event_name' => 'Community Development Project',
                'event_location' => 'Sefwi Wiawso',
                'event_date' => date('Y-m-d', strtotime('-10 days')),
                'images' => ['assets/images/carousel/banner.jpg', 'assets/images/carousel/slide2.jpg']
            ],
            [
                'event_id' => 2,
                'event_name' => 'Youth Empowerment Program',
                'event_location' => 'Central Park',
                'event_date' => date('Y-m-d', strtotime('-5 days')),
                'images' => ['assets/images/carousel/slide3.jpg', 'assets/images/carousel/banner.jpg']
            ],
            [
                'event_id' => 3,
                'event_name' => 'Health Outreach Campaign',
                'event_location' => 'Community Center',
                'event_date' => date('Y-m-d', strtotime('-2 days')),
                'images' => ['assets/images/carousel/slide2.jpg', 'assets/images/carousel/slide3.jpg']
            ]
        ];
    }
    
} catch (Exception $e) {
    // Fallback with sample data
    $gallery_images = [
        [
            'event_id' => 1,
            'event_name' => 'Community Development Project',
            'event_location' => 'Sefwi Wiawso',
            'event_date' => date('Y-m-d'),
            'images' => ['assets/images/carousel/banner.jpg']
        ]
    ];
}
?>

<!-- Event Gallery Section -->
<section class="py-16 bg-gradient-to-br from-gray-50 to-white">
    <div class="container mx-auto px-6">
        
        <!-- Section Header -->
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                <i class="fas fa-images text-amber-600 mr-3"></i>Event Gallery
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                Discover moments captured from our community events and development projects
            </p>
            <div class="mt-4 w-24 h-1 bg-amber-600 mx-auto rounded-full"></div>
        </div>

        <?php if (!empty($gallery_images)): ?>
        <!-- Gallery Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
            <?php 
            $grid_items = [];
            $item_count = 0;
            
            // Create grid items with rotating images
            foreach ($gallery_images as $event) {
                foreach ($event['images'] as $image) {
                    if ($item_count < 12) { // Limit to 12 grid items
                        $grid_items[] = [
                            'image' => $image,
                            'event' => $event,
                            'alt_images' => array_filter($event['images'], function($img) use ($image) {
                                return $img !== $image;
                            })
                        ];
                        $item_count++;
                    }
                }
            }
            
            foreach ($grid_items as $index => $item):
            ?>
            <div class="gallery-item group relative overflow-hidden rounded-xl shadow-lg cursor-pointer transform transition-all duration-300 hover:scale-105 hover:shadow-2xl"
                 data-event-id="<?= $item['event']['event_id'] ?>"
                 data-event-name="<?= htmlspecialchars($item['event']['event_name']) ?>"
                 style="animation-delay: <?= $index * 0.1 ?>s">
                
                <!-- Image Container with rotating images -->
                <div class="image-container relative aspect-square overflow-hidden">
                    <img src="<?= htmlspecialchars($item['image']) ?>" 
                         alt="<?= htmlspecialchars($item['event']['event_name']) ?>"
                         class="main-image w-full h-full object-cover transition-opacity duration-500"
                         onerror="this.src='assets/images/carousel/banner.jpg'; this.onerror=null;">
                    
                    <?php if (!empty($item['alt_images'])): ?>
                    <?php foreach (array_slice($item['alt_images'], 0, 2) as $alt_image): ?>
                    <img src="<?= htmlspecialchars($alt_image) ?>" 
                         alt="<?= htmlspecialchars($item['event']['event_name']) ?>"
                         class="alt-image absolute inset-0 w-full h-full object-cover opacity-0 transition-opacity duration-500"
                         onerror="this.src='assets/images/carousel/banner.jpg'; this.onerror=null;">
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Overlay -->
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                    <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
                        <h3 class="font-bold text-sm mb-1 line-clamp-1">
                            <?= htmlspecialchars($item['event']['event_name']) ?>
                        </h3>
                        <p class="text-xs opacity-90 flex items-center">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            <?= htmlspecialchars($item['event']['event_location']) ?>
                        </p>
                        <p class="text-xs opacity-75 mt-1">
                            <i class="fas fa-calendar mr-1"></i>
                            <?= date('M j, Y', strtotime($item['event']['event_date'])) ?>
                        </p>
                    </div>
                    
                    <!-- View Event Button -->
                    <div class="absolute top-4 right-4">
                        <div class="w-8 h-8 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center">
                            <i class="fas fa-eye text-white text-sm"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Loading indicator -->
                <div class="loading-indicator absolute top-2 left-2 opacity-0">
                    <div class="w-2 h-2 bg-amber-400 rounded-full animate-pulse"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- View All Events Button -->
        <div class="text-center">
            <a href="events/" 
               class="inline-flex items-center bg-amber-600 hover:bg-amber-700 text-white font-semibold px-8 py-3 rounded-full transition-all duration-200 transform hover:scale-105 shadow-lg">
                <i class="fas fa-calendar-check mr-3"></i>
                View All Events
                <i class="fas fa-arrow-right ml-3"></i>
            </a>
        </div>

        <?php else: ?>
        <!-- No Events Message -->
        <div class="text-center py-12">
            <i class="fas fa-images text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">No Event Images Available</h3>
            <p class="text-gray-500">Check back soon for photos from our upcoming community events.</p>
        </div>
        <?php endif; ?>

    </div>
</section>

<!-- Gallery Styles -->
<style>
    .gallery-item {
        opacity: 0;
        transform: translateY(20px);
        animation: fadeInUp 0.6s ease-out forwards;
    }
    
    @keyframes fadeInUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .line-clamp-1 {
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<!-- Gallery JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const galleryItems = document.querySelectorAll('.gallery-item');
    
    // Image rotation functionality
    function startImageRotation() {
        galleryItems.forEach((item, index) => {
            const mainImage = item.querySelector('.main-image');
            const altImages = item.querySelectorAll('.alt-image');
            
            if (altImages.length > 0) {
                let currentImageIndex = 0;
                
                // Start rotation after initial delay
                setTimeout(() => {
                    setInterval(() => {
                        // Fade out current image
                        if (currentImageIndex === 0) {
                            mainImage.style.opacity = '0';
                        } else {
                            altImages[currentImageIndex - 1].style.opacity = '0';
                        }
                        
                        // Move to next image
                        currentImageIndex = (currentImageIndex + 1) % (altImages.length + 1);
                        
                        // Fade in next image
                        setTimeout(() => {
                            if (currentImageIndex === 0) {
                                mainImage.style.opacity = '1';
                            } else {
                                altImages[currentImageIndex - 1].style.opacity = '1';
                            }
                        }, 250);
                        
                    }, 3000 + (index * 200)); // Stagger the timing
                }, 2000 + (index * 300)); // Initial delay
            }
        });
    }
    
    // Click handler for viewing events
    galleryItems.forEach(item => {
        item.addEventListener('click', function() {
            const eventId = this.dataset.eventId;
            const eventName = this.dataset.eventName;
            
            // Add loading animation
            const loadingIndicator = this.querySelector('.loading-indicator');
            loadingIndicator.style.opacity = '1';
            
            // Redirect to event detail page after short delay
            setTimeout(() => {
                window.location.href = `events/event-detail.php?id=${eventId}`;
            }, 300);
        });
        
        // Pause rotation on hover
        item.addEventListener('mouseenter', function() {
            this.style.animationPlayState = 'paused';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.animationPlayState = 'running';
        });
    });
    
    // Start the image rotation
    setTimeout(startImageRotation, 1000);
});
</script>
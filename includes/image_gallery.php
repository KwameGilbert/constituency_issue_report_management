<?php
// Fetch event images for the gallery
try {
    $events_query = "
        SELECT id, name, location, start_date, image_url
        FROM events 
        WHERE image_url IS NOT NULL AND image_url != ''
        ORDER BY start_date ASC
    ";
    
    $events_result = $conn->query($events_query);
    $events = $events_result ? $events_result->fetch_all(MYSQLI_ASSOC) : [];
    
    // Collect ALL images from all events into one pool
    $all_images = [];
    foreach ($events as $event) {
        if (!empty($event['image_url'])) {
            $all_images[] = $event['image_url'];
        }
    }
    
    // Process events and prepare gallery items
    $gallery_images = [];
    foreach ($events as $event) {
        if (!empty($event['image_url'])) {
            // Start each grid cell with a random image from the pool
            $random_start_image = !empty($all_images) ? $all_images[array_rand($all_images)] : $event['image_url'];
            
            $gallery_images[] = [
                'event_id' => $event['id'],
                'event_name' => $event['name'],
                'event_location' => $event['location'],
                'event_date' => $event['start_date'],
                'primary_image' => $random_start_image, // Start with random image instead of event's own image
                'all_available_images' => $all_images // Each grid cell can rotate through ALL images
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
            // Create one grid item per event, but each can rotate through ALL available images
            foreach ($gallery_images as $index => $event):
            ?>
            <div class="gallery-item group relative overflow-hidden rounded-xl shadow-lg cursor-pointer transform transition-all duration-300 hover:scale-105 hover:shadow-2xl"
                 data-event-id="<?= $event['event_id'] ?>"
                 data-event-name="<?= htmlspecialchars($event['event_name']) ?>"
                 data-all-images='<?= json_encode($event['all_available_images']) ?>'
                 style="animation-delay: <?= $index * 0.1 ?>s">
                
                <!-- Image Container with rotating images -->
                <div class="image-container relative aspect-square overflow-hidden">
                    <!-- Primary image (starts with this event's image) -->
                    <img src="<?= htmlspecialchars($event['primary_image']) ?>" 
                         alt="<?= htmlspecialchars($event['event_name']) ?>"
                         class="rotating-image w-full h-full object-cover transition-opacity duration-500"
                         onerror="this.src='assets/images/carousel/banner.jpg'; this.onerror=null;">
                </div>
                
                <!-- Overlay -->
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                    <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
                        <h3 class="font-bold text-sm mb-1 line-clamp-1">
                            <?= htmlspecialchars($event['event_name']) ?>
                        </h3>
                        <p class="text-xs opacity-90 flex items-center">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            <?= htmlspecialchars($event['event_location']) ?>
                        </p>
                        <p class="text-xs opacity-75 mt-1">
                            <i class="fas fa-calendar mr-1"></i>
                            <?= date('M j, Y', strtotime($event['event_date'])) ?>
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
        <!-- No Events Message (nicer layout) -->
        <div class="flex items-center justify-center py-12">
            <div class="max-w-3xl w-full bg-white rounded-xl shadow-md p-8 text-center">
                <div class="mx-auto w-32 h-32 flex items-center justify-center rounded-full bg-amber-50 mb-6">
                    <!-- simple camera SVG -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7h3l2-3h6l2 3h3v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-semibold text-gray-800 mb-2">No images available right now</h3>
                <p class="text-gray-600 mb-6">We don't have any event photos to show yet. Try visiting the events page to see upcoming activities or check back later.</p>
                <div class="flex justify-center gap-4">
                    <a href="events/" class="inline-block px-6 py-3 bg-amber-600 text-white rounded-full font-semibold hover:bg-amber-700">View Events</a>
                    <a href="contact/" class="inline-block px-6 py-3 border border-gray-200 text-gray-700 rounded-full font-medium hover:bg-gray-50">Contact Us</a>
                </div>
            </div>
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
    
    // Image rotation functionality - each grid cell rotates through ALL available images
    function startImageRotation() {
        if (galleryItems && galleryItems.length > 0) {
            galleryItems.forEach((item, index) => {
                if (item) {
                    const rotatingImage = item.querySelector('.rotating-image');
                    const allImagesData = item.dataset.allImages;
                    
                    if (allImagesData && rotatingImage) {
                        try {
                            const allImages = JSON.parse(allImagesData);
                            // Start each grid cell with a random image index
                            let currentImageIndex = Math.floor(Math.random() * allImages.length);
                            
                            // Start rotation after initial delay (staggered for each item)
                            setTimeout(() => {
                                const rotationInterval = setInterval(() => {
                                    // Get a random image (different from current)
                                    let nextImageIndex;
                                    do {
                                        nextImageIndex = Math.floor(Math.random() * allImages.length);
                                    } while (nextImageIndex === currentImageIndex && allImages.length > 1);
                                    
                                    currentImageIndex = nextImageIndex;
                                    
                                    // Fade out current image
                                    rotatingImage.style.opacity = '0.3';
                                    
                                    // Change source and fade back in
                                    setTimeout(() => {
                                        rotatingImage.src = allImages[currentImageIndex];
                                        rotatingImage.style.opacity = '1';
                                    }, 300);
                                    
                                }, 4000 + Math.random() * 3000); // Random timing between 4-7 seconds for each grid item
                                
                                // Store interval for pause/resume functionality
                                item.rotationInterval = rotationInterval;
                            }, 1000 + (index * 200) + Math.random() * 2000); // More random initial delay
                            
                        } catch (e) {
                            console.log('Error parsing images data for item:', index);
                        }
                    }
                }
            });
        }
    }
    
    // Click handler for viewing events
    if (galleryItems && galleryItems.length > 0) {
        galleryItems.forEach(item => {
            if (item) {
                item.addEventListener('click', function() {
                    const eventId = this.dataset.eventId;
                    const eventName = this.dataset.eventName;
                    
                    // Add loading animation
                    const loadingIndicator = this.querySelector('.loading-indicator');
                    if (loadingIndicator) {
                        loadingIndicator.style.opacity = '1';
                    }
                    
                    // Redirect to event detail page after short delay
                    setTimeout(() => {
                        window.location.href = `events/event-detail.php?id=${eventId}`;
                    }, 300);
                });
                
                // Pause rotation on hover
                item.addEventListener('mouseenter', function() {
                    if (this.rotationInterval) {
                        clearInterval(this.rotationInterval);
                    }
                });
                
                // Resume rotation on leave
                item.addEventListener('mouseleave', function() {
                    const rotatingImage = this.querySelector('.rotating-image');
                    const allImagesData = this.dataset.allImages;
                    
                    if (allImagesData && rotatingImage) {
                        try {
                            const allImages = JSON.parse(allImagesData);
                            // Find current image index or start random
                            let currentImageIndex = allImages.findIndex(img => 
                                rotatingImage.src.includes(img.split('/').pop())
                            );
                            if (currentImageIndex === -1) {
                                currentImageIndex = Math.floor(Math.random() * allImages.length);
                            }
                            
                            this.rotationInterval = setInterval(() => {
                                // Get a random image (different from current)
                                let nextImageIndex;
                                do {
                                    nextImageIndex = Math.floor(Math.random() * allImages.length);
                                } while (nextImageIndex === currentImageIndex && allImages.length > 1);
                                
                                currentImageIndex = nextImageIndex;
                                
                                rotatingImage.style.opacity = '0.3';
                                setTimeout(() => {
                                    rotatingImage.src = allImages[currentImageIndex];
                                    rotatingImage.style.opacity = '1';
                                }, 300);
                            }, 4000 + Math.random() * 3000); // Random timing on resume too (4-7 seconds)
                        } catch (e) {
                            console.log('Error resuming rotation');
                        }
                    }
                });
            }
        });
    }

    // Start the image rotation
    setTimeout(startImageRotation, 500);
});
</script>
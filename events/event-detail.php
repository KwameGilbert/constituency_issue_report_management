<?php
require_once '../config/db.php';

// Get event ID from URL
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($event_id <= 0) {
    header('Location: index.php');
    exit;
}

// Fetch event details
try {
    $event_query = "
        SELECT id, name, slug, description, start_date, end_date, event_time, location, image_url
        FROM events 
        WHERE id = ?
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($event_query);
    $stmt->bind_param('i', $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error_message = "Event not found. This event may have been removed or the link is incorrect.";
        $show_error_page = true;
        $event = null;
    } else {
        $event = $result->fetch_assoc();
        $show_error_page = false;
    }
    
    // Process event images only if event exists
    $event_images = [];
    
    if (!$show_error_page && $event) {
        // Add main image if exists
        if (!empty($event['image_url'])) {
            $event_images[] = [
                'url' => $event['image_url'],
                'alt' => $event['name'] . ' - Main Image'
            ];
        }
        
        // Add some additional sample images for gallery effect
        $sample_images = [
            'assets/images/carousel/banner.jpg',
            'assets/images/carousel/slide2.jpg',
            'assets/images/carousel/slide3.jpg'
        ];
        
        foreach ($sample_images as $sample_img) {
            if ($sample_img !== $event['image_url']) {
                $event_images[] = [
                    'url' => $sample_img,
                    'alt' => $event['name'] . ' - Event Photo'
                ];
            }
        }
    }
    
    // If no images found, add a placeholder
    if (empty($event_images)) {
        $event_images[] = [
            'url' => '../assets/images/carousel/banner.jpg',
            'alt' => $event['name'] . ' - Default Image'
        ];
    }
    
    // Get related events (same location or recent)
    $related_query = "
        SELECT id, name, location, start_date, image_url
        FROM events 
        WHERE id != ? AND (location = ? OR start_date >= CURDATE())
        ORDER BY start_date DESC
        LIMIT 3
    ";
    
    $related_stmt = $conn->prepare($related_query);
    $related_stmt->bind_param('is', $event_id, $event['location']);
    $related_stmt->execute();
    $related_events = $related_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    // Log the error for debugging
    error_log("Event Detail Error: " . $e->getMessage());
    
    // Show nice error page
    $error_message = "We're having trouble loading this event. Please try again later.";
    $show_error_page = true;
}

// Set page metadata
if (!$show_error_page && isset($event) && $event) {
    $page_title = $event['name'];
    $page_description = !empty($event['description']) ? substr($event['description'], 0, 160) . '...' : 'Event details for ' . $event['name'];
} else {
    $page_title = "Event Not Found";
    $page_description = "The requested event could not be found.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Events</title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page_description) ?>">
    <meta property="og:type" content="event">
    <?php if (!$show_error_page && !empty($event_images[0]['url'])): ?>
    <meta property="og:image" content="<?= htmlspecialchars($event_images[0]['url']) ?>">
    <?php endif; ?>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom Styles -->
    <style>
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }
        .image-modal {
            backdrop-filter: blur(10px);
        }
        .modal-image {
            max-height: 90vh;
            max-width: 90vw;
            object-fit: contain;
        }
    </style>
</head>
<body class="bg-gray-50">

    <?php include '../includes/header.php'; ?>

    <?php if (isset($show_error_page) && $show_error_page): ?>
    
    <!-- Error Page Content -->
    <section class="min-h-screen bg-gray-50 flex items-center justify-center py-16">
        <div class="container mx-auto px-6">
            <div class="max-w-2xl mx-auto text-center">
                
                <!-- Error Icon -->
                <div class="mb-8">
                    <div class="w-32 h-32 mx-auto bg-gradient-to-br from-amber-400 to-amber-600 rounded-full flex items-center justify-center shadow-lg">
                        <i class="fas fa-calendar-times text-white text-6xl"></i>
                    </div>
                </div>
                
                <!-- Error Message -->
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                    Oops! Event Not Found
                </h1>
                
                <p class="text-xl text-gray-600 mb-8 leading-relaxed">
                    <?= htmlspecialchars($error_message) ?>
                </p>
                
                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="index.php" 
                       class="inline-flex items-center bg-amber-600 hover:bg-amber-700 text-white font-semibold px-8 py-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg">
                        <i class="fas fa-calendar-alt mr-3"></i>
                        View All Events
                    </a>
                    
                    <a href="../" 
                       class="inline-flex items-center bg-gray-600 hover:bg-gray-700 text-white font-semibold px-8 py-4 rounded-lg transition-all duration-200 transform hover:scale-105 shadow-lg">
                        <i class="fas fa-home mr-3"></i>
                        Back to Home
                    </a>
                </div>
                
                <!-- Helpful Links -->
                <div class="mt-12 p-6 bg-white rounded-xl shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">What you can do:</h3>
                    <div class="grid sm:grid-cols-3 gap-4 text-sm">
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-search text-amber-600 mr-2"></i>
                            <span>Browse other events</span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-calendar-check text-amber-600 mr-2"></i>
                            <span>Check upcoming events</span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-envelope text-amber-600 mr-2"></i>
                            <span>Contact us for help</span>
                        </div>
                    </div>
                </div>
                
                <!-- Debug Info for Development -->
                <?php if (isset($_GET['debug']) && $_GET['debug'] === 'true'): ?>
                <div class="mt-8 p-4 bg-red-50 border border-red-200 rounded-lg text-left">
                    <h4 class="font-bold text-red-800 mb-2">Debug Information:</h4>
                    <p class="text-red-700 text-sm">
                        <strong>Event ID:</strong> <?= htmlspecialchars($event_id) ?><br>
                        <strong>Error:</strong> <?= htmlspecialchars($error_message) ?>
                    </p>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </section>
    
    <?php else: ?>

    <!-- Breadcrumb -->
    <nav class="bg-white border-b py-4">
        <div class="container mx-auto px-6">
            <div class="flex items-center text-sm text-gray-600">
                <a href="../" class="hover:text-amber-600">Home</a>
                <i class="fas fa-chevron-right mx-2"></i>
                <a href="index.php" class="hover:text-amber-600">Events</a>
                <i class="fas fa-chevron-right mx-2"></i>
                <span class="text-gray-900"><?= htmlspecialchars($event['name']) ?></span>
            </div>
        </div>
    </nav>

    <!-- Event Header -->
    <section class="bg-gradient-to-r from-amber-600 to-amber-700 text-white py-16">
        <div class="container mx-auto px-6">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">
                    <?= htmlspecialchars($event['name']) ?>
                </h1>
                
                <!-- Event Meta Information -->
                <div class="flex flex-wrap justify-center items-center gap-6 text-lg">
                    <!-- Date -->
                    <div class="flex items-center">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        <span>
                            <?= date('F j, Y', strtotime($event['start_date'])) ?>
                            <?php if (!empty($event['end_date']) && $event['end_date'] !== $event['start_date']): ?>
                            - <?= date('F j, Y', strtotime($event['end_date'])) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <!-- Time -->
                    <?php if (!empty($event['event_time'])): ?>
                    <div class="flex items-center">
                        <i class="fas fa-clock mr-2"></i>
                        <span><?= date('g:i A', strtotime($event['event_time'])) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Location -->
                    <?php if (!empty($event['location'])): ?>
                    <div class="flex items-center">
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        <span><?= htmlspecialchars($event['location']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Event Content -->
    <section class="py-16">
        <div class="container mx-auto px-6">
            <div class="max-w-6xl mx-auto">
                
                <!-- Event Description -->
                <?php if (!empty($event['description'])): ?>
                <div class="bg-white rounded-xl shadow-lg p-8 mb-12">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">
                        <i class="fas fa-info-circle text-amber-600 mr-2"></i>About This Event
                    </h2>
                    <div class="prose max-w-none text-gray-700 leading-relaxed">
                        <?= nl2br(htmlspecialchars($event['description'])) ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Event Gallery -->
                <div class="bg-white rounded-xl shadow-lg p-8 mb-12">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">
                        <i class="fas fa-images text-amber-600 mr-2"></i>Event Photos
                    </h2>
                    
                    <?php if (count($event_images) === 1): ?>
                    <!-- Single Image Display -->
                    <div class="text-center">
                        <?php 
                        $image_src = (strpos($event_images[0]['url'], 'http') === 0) ? 
                                   $event_images[0]['url'] : 
                                   '../' . $event_images[0]['url']; 
                        ?>
                        <img src="<?= htmlspecialchars($image_src) ?>" 
                             alt="<?= htmlspecialchars($event_images[0]['alt']) ?>"
                             class="max-w-full h-auto rounded-lg shadow-lg cursor-pointer hover:shadow-xl transition-shadow clickable-image"
                             data-image-url="<?= htmlspecialchars($image_src) ?>"
                             onerror="this.src='../assets/images/carousel/banner.jpg'; this.onerror=null;">
                    </div>
                    <?php else: ?>
                    <!-- Multiple Images Gallery -->
                    <div class="gallery-grid">
                        <?php foreach ($event_images as $index => $image): ?>
                        <div class="relative group">
                            <?php 
                            $image_src = (strpos($image['url'], 'http') === 0) ? 
                                       $image['url'] : 
                                       '../' . $image['url']; 
                            ?>
                            <img src="<?= htmlspecialchars($image_src) ?>" 
                                 alt="<?= htmlspecialchars($image['alt']) ?>"
                                 class="w-full h-64 object-cover rounded-lg shadow-lg cursor-pointer group-hover:shadow-xl transition-all duration-300 hover:scale-105 clickable-image"
                                 data-image-url="<?= htmlspecialchars($image_src) ?>"
                                 onerror="this.src='../assets/images/carousel/banner.jpg'; this.onerror=null;">
                            
                            <!-- Image Overlay -->
                            <div class="absolute inset-0 bg-black opacity-0 group-hover:opacity-30 transition-opacity rounded-lg"></div>
                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <i class="fas fa-search-plus text-white text-2xl"></i>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <p class="text-center text-gray-500 mt-6 text-sm">
                        <i class="fas fa-info-circle mr-1"></i>Click on any image to view in full size
                    </p>
                </div>

                <!-- Event Details -->
                <div class="grid md:grid-cols-2 gap-8 mb-12">
                    <!-- Event Information -->
                    <div class="bg-white rounded-xl shadow-lg p-8">
                        <h3 class="text-xl font-bold text-gray-900 mb-6">
                            <i class="fas fa-calendar-check text-amber-600 mr-2"></i>Event Details
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-start">
                                <i class="fas fa-calendar text-amber-600 mr-3 mt-1"></i>
                                <div>
                                    <p class="font-semibold text-gray-900">Date</p>
                                    <p class="text-gray-600">
                                        <?= date('l, F j, Y', strtotime($event['start_date'])) ?>
                                        <?php if (!empty($event['end_date']) && $event['end_date'] !== $event['start_date']): ?>
                                        <br>to <?= date('l, F j, Y', strtotime($event['end_date'])) ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            
                            <?php if (!empty($event['event_time'])): ?>
                            <div class="flex items-start">
                                <i class="fas fa-clock text-amber-600 mr-3 mt-1"></i>
                                <div>
                                    <p class="font-semibold text-gray-900">Time</p>
                                    <p class="text-gray-600"><?= date('g:i A', strtotime($event['event_time'])) ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($event['location'])): ?>
                            <div class="flex items-start">
                                <i class="fas fa-map-marker-alt text-amber-600 mr-3 mt-1"></i>
                                <div>
                                    <p class="font-semibold text-gray-900">Location</p>
                                    <p class="text-gray-600"><?= htmlspecialchars($event['location']) ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Share Event -->
                    <div class="bg-white rounded-xl shadow-lg p-8">
                        <h3 class="text-xl font-bold text-gray-900 mb-6">
                            <i class="fas fa-share-alt text-amber-600 mr-2"></i>Share This Event
                        </h3>
                        <div class="space-y-4">
                            <a href="https://facebook.com/sharer/sharer.php?u=<?= urlencode($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" 
                               target="_blank"
                               class="flex items-center w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg transition-colors">
                                <i class="fab fa-facebook-f mr-3"></i>Share on Facebook
                            </a>
                            
                            <a href="https://twitter.com/intent/tweet?url=<?= urlencode($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>&text=<?= urlencode($event['name']) ?>" 
                               target="_blank"
                               class="flex items-center w-full bg-blue-400 hover:bg-blue-500 text-white px-4 py-3 rounded-lg transition-colors">
                                <i class="fab fa-twitter mr-3"></i>Share on Twitter
                            </a>
                            
                            <a href="whatsapp://send?text=<?= urlencode($event['name'] . ' - ' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>"
                               class="flex items-center w-full bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg transition-colors">
                                <i class="fab fa-whatsapp mr-3"></i>Share on WhatsApp
                            </a>
                        </div>
                        
                        <!-- Back Button -->
                        <div class="mt-6 pt-6 border-t">
                            <a href="index.php" class="flex items-center justify-center w-full bg-amber-600 hover:bg-amber-700 text-white px-4 py-3 rounded-lg transition-colors">
                                <i class="fas fa-arrow-left mr-2"></i>Back to All Events
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Related Events -->
    <?php if (!empty($related_events)): ?>
    <section class="bg-white py-16">
        <div class="container mx-auto px-6">
            <div class="max-w-6xl mx-auto">
                <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">Related Events</h2>
                
                <div class="grid md:grid-cols-3 gap-8">
                    <?php foreach ($related_events as $related): ?>
                    <article class="group bg-gray-50 rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
                        <div class="relative overflow-hidden h-48">
                            <?php if (!empty($related['image_url'])): ?>
                            <img src="../<?= htmlspecialchars($related['image_url']) ?>" 
                                 alt="<?= htmlspecialchars($related['name']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                 onerror="this.src='../assets/images/carousel/banner.jpg'; this.onerror=null;">
                            <?php else: ?>
                            <div class="w-full h-full bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center">
                                <i class="fas fa-calendar-alt text-white text-3xl"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="p-6">
                            <div class="text-sm text-amber-600 mb-2">
                                <?= date('F j, Y', strtotime($related['start_date'])) ?>
                            </div>
                            
                            <h3 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-amber-600 transition-colors">
                                <a href="event-detail.php?id=<?= $related['id'] ?>">
                                    <?= htmlspecialchars($related['name']) ?>
                                </a>
                            </h3>
                            
                            <p class="text-gray-600 text-sm flex items-center">
                                <i class="fas fa-map-marker-alt mr-2"></i>
                                <?= htmlspecialchars($related['location']) ?>
                            </p>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php endif; // End of error page condition ?>

    <!-- Image Modal (available for all pages) -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 image-modal hidden flex items-center justify-center p-4" style="z-index: 9999;" onclick="closeImageModal()">
        <div class="relative max-w-full max-h-full" onclick="event.stopPropagation()">
            <img id="modalImage" src="" alt="" class="modal-image rounded-lg" style="max-height: 90vh; max-width: 90vw; object-fit: contain;">
            <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white bg-black bg-opacity-50 rounded-full w-10 h-10 flex items-center justify-center hover:bg-opacity-75 transition-all">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <!-- JavaScript for image modal functionality -->
    <script>
        function openImageModal(imageSrc, imageAlt) {
            console.log('Opening modal with:', imageSrc, imageAlt); // Debug log
            
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            
            if (!modal) {
                console.error('Modal element not found!');
                return;
            }
            
            if (!modalImage) {
                console.error('Modal image element not found!');
                return;
            }
            
            // Ensure the path is relative to the current context
            const fullImagePath = imageSrc.startsWith('http') ? imageSrc : '../' + imageSrc;
            
            modalImage.src = fullImagePath;
            modalImage.alt = imageAlt;
            modal.classList.remove('hidden');
            
            // Prevent body scrolling
            document.body.style.overflow = 'hidden';
            
            console.log('Modal opened successfully with path:', fullImagePath);
        }
        
        function closeImageModal() {
            console.log('Closing modal'); // Debug log
            
            const modal = document.getElementById('imageModal');
            if (!modal) {
                console.error('Modal not found for closing!');
                return;
            }
            
            modal.classList.add('hidden');
            
            // Restore body scrolling
            document.body.style.overflow = '';
            
            console.log('Modal closed successfully');
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImageModal();
            }
        });
        
        // Debug: Check if DOM is ready and elements exist
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeImageModal);
        } else {
            // DOM is already ready
            initializeImageModal();
        }
        
        function initializeImageModal() {
            // Remove any existing click listeners to prevent conflicts
            document.removeEventListener('click', handleImageClick);
            
            // Add new event delegation
            document.addEventListener('click', handleImageClick);
        }
        
        function handleImageClick(event) {
            let targetElement = event.target;
            
            // Check if clicked element has the clickable-image class
            if (targetElement && targetElement.classList && targetElement.classList.contains('clickable-image')) {
                event.preventDefault();
                event.stopPropagation();
                
                const imageUrl = targetElement.dataset.imageUrl || targetElement.src;
                const alt = targetElement.alt || 'Event Image';
                
                openImageModal(imageUrl, alt);
                return;
            }
            
            // If not a direct image click, check if we clicked on an overlay div
            // Look for the parent container that has a clickable-image as a sibling
            let parentContainer = targetElement.closest('.group');
            if (parentContainer) {
                const image = parentContainer.querySelector('.clickable-image');
                if (image) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    const imageUrl = image.dataset.imageUrl || image.src;
                    const alt = image.alt || 'Event Image';
                    
                    openImageModal(imageUrl, alt);
                    return;
                }
            }
        }
    </script>

</body>
</html>
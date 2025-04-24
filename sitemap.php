<?php
// File: sitemap.php - Place in your website root directory
// header("Content-Type: application/xml; charset=utf-8");

// Database connection
require_once 'config/db.php';

// Base URL of your website
$base_url = "https://swma.rf.gd";

// Get current date in W3C format
$date = date('c');

// Start XML
echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

    // Add static pages
    $static_pages = [
    '' => ['priority' => '1.0', 'changefreq' => 'weekly'],
    'about' => ['priority' => '0.8', 'changefreq' => 'monthly'],
    'contact' => ['priority' => '0.8', 'changefreq' => 'monthly'],
    'blog' => ['priority' => '0.8', 'changefreq' => 'daily'],
    'events' => ['priority' => '0.8', 'changefreq' => 'daily'],
    ];

    foreach ($static_pages as $page => $metadata) {
    echo '<url>' . PHP_EOL;
        echo '<loc>' . $base_url . '/' . $page . '</loc>' . PHP_EOL;
        echo '<lastmod>' . $date . '</lastmod>' . PHP_EOL;
        echo '<changefreq>' . $metadata['changefreq'] . '</changefreq>' . PHP_EOL;
        echo '<priority>' . $metadata['priority'] . '</priority>' . PHP_EOL;
        echo '</url>' . PHP_EOL;
    }

    // Add blog posts
    $blog_posts = $conn->query("SELECT slug, updated_at FROM blog_posts ORDER BY created_at DESC");
    if ($blog_posts) {
    while ($post = $blog_posts->fetch_assoc()) {
    $lastmod = date('c', strtotime($post['updated_at']));
    echo '<url>' . PHP_EOL;
        echo '<loc>' . $base_url . '/blog/blog-post.php?slug=' . htmlspecialchars($post['slug']) . '</loc>' . PHP_EOL;
        echo '<lastmod>' . $lastmod . '</lastmod>' . PHP_EOL;
        echo '<changefreq>monthly</changefreq>' . PHP_EOL;
        echo '<priority>0.6</priority>' . PHP_EOL;
        echo '</url>' . PHP_EOL;
    }
    }

    // Add events
    $events = $conn->query("SELECT slug, start_date, created_at FROM events ORDER BY start_date DESC");
    if ($events) {
    while ($event = $events->fetch_assoc()) {
    $lastmod = date('c', strtotime($event['start_date'] ?? $event['created_at']));
    echo '<url>' . PHP_EOL;
        echo '<loc>' . $base_url . '/events/' . htmlspecialchars($event['slug']) . '</loc>' . PHP_EOL;
        echo '<lastmod>' . $lastmod . '</lastmod>' . PHP_EOL;
        echo '<changefreq>monthly</changefreq>' . PHP_EOL;
        echo '<priority>0.6</priority>' . PHP_EOL;
        echo '</url>' . PHP_EOL;
    }
    }

    // Close XML
    echo '</urlset>';
?>
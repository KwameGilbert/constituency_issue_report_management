<?php
/**
 * Database Seeder Script
 * Populates the database with sample users and content for testing
 * 
 * Usage: Run this file in your browser or via command line
 * URL: http://localhost/constituency_issue_report_management/database_seeder.php
 */

require_once 'config/db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

class DatabaseSeeder {
    private $conn;
    private $results = [];

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Run all seeders
     */
    public function runAll() {
        echo "<h1>🌱 Database Seeder</h1>";
        echo "<p>Populating database with sample data...</p><br>";

        try {
            $this->seedAdmins();
            $this->seedElectoralAreas();
            $this->seedFieldOfficers();
            $this->seedPersonalAssistants();
            $this->seedSupervisors();
            $this->seedUsers();
            $this->seedEvents();
            $this->seedBlogPosts();
            $this->seedCarouselItems();
            $this->seedContactMessages();
            $this->seedNewsletterSubscribers();

            echo "<h2>✅ Seeding Complete!</h2>";
            echo "<p><strong>Summary:</strong></p>";
            foreach ($this->results as $table => $count) {
                echo "<li>{$table}: {$count} records added</li>";
            }
            
            echo "<br><p><a href='web-admin/' style='background: #f59e0b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Panel</a></p>";
            
        } catch (Exception $e) {
            echo "<div style='color: red; padding: 10px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 5px;'>";
            echo "<strong>Error:</strong> " . $e->getMessage();
            echo "</div>";
        }
    }

    /**
     * Seed Admin Users
     */
    private function seedAdmins() {
        echo "<h3>👥 Seeding Admins...</h3>";
        
        $admins = [
            [
                'username' => 'superadmin',
                'email' => 'admin@sefwi-wiawso.gov.gh',
                'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                'first_name' => 'John',
                'last_name' => 'Administrator',
                'role' => 'super_admin',
                'status' => 'active'
            ],
            [
                'username' => 'contentadmin',
                'email' => 'content@sefwi-wiawso.gov.gh',
                'password_hash' => password_hash('content123', PASSWORD_DEFAULT),
                'first_name' => 'Sarah',
                'last_name' => 'Content Manager',
                'role' => 'admin',
                'status' => 'active'
            ],
            [
                'username' => 'editor',
                'email' => 'editor@sefwi-wiawso.gov.gh',
                'password_hash' => password_hash('editor123', PASSWORD_DEFAULT),
                'first_name' => 'Michael',
                'last_name' => 'Editor',
                'role' => 'editor',
                'status' => 'active'
            ]
        ];

        $stmt = $this->conn->prepare("
            INSERT INTO admins (username, email, password_hash, first_name, last_name, role, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $count = 0;
        foreach ($admins as $admin) {
            $stmt->bind_param("sssssss", 
                $admin['username'], $admin['email'], $admin['password_hash'], 
                $admin['first_name'], $admin['last_name'], $admin['role'], $admin['status']
            );
            
            if ($stmt->execute()) {
                $count++;
                echo "✓ Added admin: {$admin['username']} ({$admin['email']})<br>";
            }
        }
        
        $this->results['Admins'] = $count;
    }

    /**
     * Seed Electoral Areas
     */
    private function seedElectoralAreas() {
        echo "<h3>🗳️ Seeding Electoral Areas...</h3>";
        
        $areas = [
            ['name' => 'Sefwi Wiawso Central', 'constituency' => 'Sefwi Wiawso', 'region' => 'Western North'],
            ['name' => 'Akontombra', 'constituency' => 'Sefwi Wiawso', 'region' => 'Western North'],
            ['name' => 'Boako', 'constituency' => 'Sefwi Wiawso', 'region' => 'Western North'],
            ['name' => 'Kwamebikrom', 'constituency' => 'Sefwi Wiawso', 'region' => 'Western North'],
            ['name' => 'Manhyia', 'constituency' => 'Sefwi Wiawso', 'region' => 'Western North'],
            ['name' => 'Nsawora', 'constituency' => 'Sefwi Wiawso', 'region' => 'Western North']
        ];

        $stmt = $this->conn->prepare("
            INSERT INTO electoral_areas (name, constituency, region, created_at) 
            VALUES (?, ?, ?, NOW())
        ");

        $count = 0;
        foreach ($areas as $area) {
            $stmt->bind_param("sss", $area['name'], $area['constituency'], $area['region']);
            
            if ($stmt->execute()) {
                $count++;
                echo "✓ Added electoral area: {$area['name']}<br>";
            }
        }
        
        $this->results['Electoral Areas'] = $count;
    }

    /**
     * Seed Field Officers
     */
    private function seedFieldOfficers() {
        echo "<h3>👮 Seeding Field Officers...</h3>";
        
        $officers = [
            [
                'name' => 'Kwame Asante',
                'email' => 'kwame.asante@sefwi-wiawso.gov.gh',
                'password' => password_hash('officer123', PASSWORD_DEFAULT),
                'phone' => '+233244123456',
                'office_location' => 'Sefwi Wiawso Municipal Assembly',
                'electoral_area_id' => 1,
                'status' => 'active'
            ],
            [
                'name' => 'Akosua Mensah',
                'email' => 'akosua.mensah@sefwi-wiawso.gov.gh',
                'password' => password_hash('officer123', PASSWORD_DEFAULT),
                'phone' => '+233244234567',
                'office_location' => 'Akontombra Sub-District Office',
                'electoral_area_id' => 2,
                'status' => 'active'
            ],
            [
                'name' => 'Emmanuel Boakye',
                'email' => 'emmanuel.boakye@sefwi-wiawso.gov.gh',
                'password' => password_hash('officer123', PASSWORD_DEFAULT),
                'phone' => '+233244345678',
                'office_location' => 'Boako Area Council',
                'electoral_area_id' => 3,
                'status' => 'active'
            ]
        ];

        $stmt = $this->conn->prepare("
            INSERT INTO field_officers (name, email, password, phone, office_location, electoral_area_id, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $count = 0;
        foreach ($officers as $officer) {
            $stmt->bind_param("sssssis", 
                $officer['name'], $officer['email'], $officer['password'], 
                $officer['phone'], $officer['office_location'], $officer['electoral_area_id'], $officer['status']
            );
            
            if ($stmt->execute()) {
                $count++;
                echo "✓ Added field officer: {$officer['name']}<br>";
            }
        }
        
        $this->results['Field Officers'] = $count;
    }

    /**
     * Seed Personal Assistants
     */
    private function seedPersonalAssistants() {
        echo "<h3>👨‍💼 Seeding Personal Assistants...</h3>";
        
        $pas = [
            [
                'name' => 'Grace Owusu',
                'email' => 'grace.owusu@sefwi-wiawso.gov.gh',
                'password' => password_hash('pa123', PASSWORD_DEFAULT),
                'phone' => '+233244456789',
                'office_location' => 'Municipal Chief Executive Office',
                'department' => 'Executive Office',
                'status' => 'active'
            ],
            [
                'name' => 'Daniel Oppong',
                'email' => 'daniel.oppong@sefwi-wiawso.gov.gh',
                'password' => password_hash('pa123', PASSWORD_DEFAULT),
                'phone' => '+233244567890',
                'office_location' => 'Administrative Block',
                'department' => 'Human Resources',
                'status' => 'active'
            ]
        ];

        $stmt = $this->conn->prepare("
            INSERT INTO personal_assistants (name, email, password, phone, office_location, department, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $count = 0;
        foreach ($pas as $pa) {
            $stmt->bind_param("sssssss", 
                $pa['name'], $pa['email'], $pa['password'], 
                $pa['phone'], $pa['office_location'], $pa['department'], $pa['status']
            );
            
            if ($stmt->execute()) {
                $count++;
                echo "✓ Added PA: {$pa['name']}<br>";
            }
        }
        
        $this->results['Personal Assistants'] = $count;
    }

    /**
     * Seed Supervisors
     */
    private function seedSupervisors() {
        echo "<h3>👨‍💼 Seeding Supervisors...</h3>";
        
        $supervisors = [
            [
                'name' => 'Hon. Joseph Kojo Yankah',
                'email' => 'mce@sefwi-wiawso.gov.gh',
                'password' => password_hash('mce123', PASSWORD_DEFAULT),
                'phone' => '+233244678901',
                'position' => 'mce',
                'office_location' => 'Municipal Chief Executive Office',
                'term_start' => '2021-01-01',
                'term_end' => '2025-12-31',
                'status' => 'active'
            ],
            [
                'name' => 'Dr. Eunice Jacqueline Buah Asomah-Hinneh',
                'email' => 'mp@parliament.gov.gh',
                'password' => password_hash('mp123', PASSWORD_DEFAULT),
                'phone' => '+233244789012',
                'position' => 'mp',
                'office_location' => 'Parliament House, Accra',
                'term_start' => '2021-01-07',
                'term_end' => '2025-01-06',
                'status' => 'active'
            ]
        ];

        $stmt = $this->conn->prepare("
            INSERT INTO supervisors (name, email, password, phone, position, office_location, term_start, term_end, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $count = 0;
        foreach ($supervisors as $supervisor) {
            $stmt->bind_param("sssssssss", 
                $supervisor['name'], $supervisor['email'], $supervisor['password'], 
                $supervisor['phone'], $supervisor['position'], $supervisor['office_location'],
                $supervisor['term_start'], $supervisor['term_end'], $supervisor['status']
            );
            
            if ($stmt->execute()) {
                $count++;
                echo "✓ Added supervisor: {$supervisor['name']} ({$supervisor['position']})<br>";
            }
        }
        
        $this->results['Supervisors'] = $count;
    }

    /**
     * Seed Generic Users
     */
    private function seedUsers() {
        echo "<h3>👥 Seeding Generic Users...</h3>";
        
        $users = [
            [
                'name' => 'Alice Johnson',
                'email' => 'alice@test.com',
                'password' => password_hash('user123', PASSWORD_DEFAULT),
                'role' => 'field_officer',
                'phone' => '+233244890123',
                'electoral_area' => 'Sefwi Wiawso Central',
                'department' => '',
                'status' => 'active'
            ],
            [
                'name' => 'Robert Brown',
                'email' => 'robert@test.com',
                'password' => password_hash('user123', PASSWORD_DEFAULT),
                'role' => 'pa',
                'phone' => '+233244901234',
                'electoral_area' => '',
                'department' => 'Finance',
                'status' => 'active'
            ]
        ];

        $stmt = $this->conn->prepare("
            INSERT INTO users (name, email, password, role, phone, electoral_area, department, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $count = 0;
        foreach ($users as $user) {
            // Use empty string instead of null for nullable fields
            $electoral_area = $user['electoral_area'] ?: '';
            $department = $user['department'] ?: '';
            
            $stmt->bind_param("ssssssss", 
                $user['name'], $user['email'], $user['password'], 
                $user['role'], $user['phone'], 
                $electoral_area, $department, $user['status']
            );
            
            if ($stmt->execute()) {
                $count++;
                echo "✓ Added user: {$user['name']}<br>";
            }
        }
        
        $this->results['Users'] = $count;
    }

    /**
     * Seed Events
     */
    private function seedEvents() {
        echo "<h3>📅 Seeding Events...</h3>";
        
        $events = [
            [
                'name' => 'Community Development Forum',
                'slug' => 'community-development-forum',
                'description' => 'Annual forum to discuss community development projects and priorities for the upcoming year.',
                'start_date' => date('Y-m-d', strtotime('+30 days')),
                'end_date' => date('Y-m-d', strtotime('+30 days')),
                'event_time' => '09:00:00',
                'location' => 'Sefwi Wiawso Community Center',
                'image_url' => 'assets/images/carousel/banner.jpg'
            ],
            [
                'name' => 'Youth Empowerment Workshop',
                'slug' => 'youth-empowerment-workshop',
                'description' => 'Workshop focused on skills development and entrepreneurship opportunities for young people.',
                'start_date' => date('Y-m-d', strtotime('+15 days')),
                'end_date' => date('Y-m-d', strtotime('+16 days')),
                'event_time' => '10:00:00',
                'location' => 'Municipal Assembly Hall',
                'image_url' => 'assets/images/carousel/slide2.jpg'
            ],
            [
                'name' => 'Health Screening Campaign',
                'slug' => 'health-screening-campaign',
                'description' => 'Free health screening and vaccination campaign for all residents.',
                'start_date' => date('Y-m-d', strtotime('+7 days')),
                'end_date' => date('Y-m-d', strtotime('+7 days')),
                'event_time' => '08:00:00',
                'location' => 'Sefwi Wiawso Hospital',
                'image_url' => 'assets/images/carousel/slide3.jpg'
            ]
        ];

        $stmt = $this->conn->prepare("
            INSERT INTO events (name, slug, description, start_date, end_date, event_time, location, image_url, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $count = 0;
        foreach ($events as $event) {
            $stmt->bind_param("ssssssss", 
                $event['name'], $event['slug'], $event['description'], 
                $event['start_date'], $event['end_date'], $event['event_time'],
                $event['location'], $event['image_url']
            );
            
            if ($stmt->execute()) {
                $count++;
                echo "✓ Added event: {$event['name']}<br>";
            }
        }
        
        $this->results['Events'] = $count;
    }

    /**
     * Seed Blog Posts and News
     */
    private function seedBlogPosts() {
        echo "<h3>📝 Seeding Blog Posts & News...</h3>";
        
        $posts = [
            // News Articles
            [
                'title' => 'New Road Construction Project Launched',
                'slug' => 'new-road-construction-project-launched',
                'excerpt' => 'Major road infrastructure project begins to improve connectivity between communities.',
                'content' => 'The Sefwi Wiawso Municipal Assembly has officially launched a major road construction project aimed at improving transportation infrastructure and connectivity between rural communities and urban centers. This project, funded through the Ghana Infrastructure Investment Fund, will benefit over 15,000 residents across six electoral areas.',
                'image_url' => 'assets/images/news/infrastructure-project.jpg',
                'featured' => 1,
                'post_type' => 'news',
                'category' => 'Infrastructure',
                'author_id' => 1
            ],
            [
                'title' => 'Community Health Insurance Enrollment Drive',
                'slug' => 'community-health-insurance-enrollment',
                'excerpt' => 'Free registration for National Health Insurance Scheme continues this month.',
                'content' => 'The Municipal Assembly is partnering with the National Health Insurance Authority to facilitate free NHIS registration for all residents. Mobile registration units will visit all electoral areas to ensure maximum coverage and accessibility.',
                'image_url' => 'assets/images/news/health-equipment.jpg',
                'featured' => 1,
                'post_type' => 'news',
                'category' => 'Health',
                'author_id' => 1
            ],
            [
                'title' => 'Youth Skills Development Program Receives Funding',
                'slug' => 'youth-skills-development-program-funding',
                'excerpt' => 'GHC 500,000 allocated for vocational training and apprenticeship programs.',
                'content' => 'The Ministry of Youth and Sports has approved funding for an ambitious skills development program targeting unemployed youth in the municipality. The program will offer training in carpentry, masonry, tailoring, and digital literacy.',
                'image_url' => 'assets/images/news/youth-employment.jpg',
                'featured' => 0,
                'post_type' => 'news',
                'category' => 'Youth Development',
                'author_id' => 2
            ],
            [
                'title' => 'School Feeding Program Expanded',
                'slug' => 'school-feeding-program-expanded',
                'excerpt' => 'Additional 10 schools join the Ghana School Feeding Programme.',
                'content' => 'Ten more primary schools in remote areas of the municipality have been enrolled in the Ghana School Feeding Programme, bringing the total number of beneficiary schools to 45. This expansion will provide nutritious meals to an additional 3,000 pupils.',
                'image_url' => 'assets/images/news/education-support.jpg',
                'featured' => 0,
                'post_type' => 'news',
                'category' => 'Education',
                'author_id' => 1
            ],
            
            // Blog Posts
            [
                'title' => 'Understanding Your Rights as a Citizen',
                'slug' => 'understanding-your-rights-as-citizen',
                'excerpt' => 'A comprehensive guide to civic rights and responsibilities in Ghana.',
                'content' => 'Every Ghanaian citizen has fundamental rights guaranteed by the 1992 Constitution. These include the right to life, liberty, dignity, education, healthcare, and participation in governance. Understanding these rights is crucial for active citizenship and democratic participation.',
                'image_url' => 'assets/images/carousel/banner.jpg',
                'featured' => 0,
                'post_type' => 'blog',
                'category' => 'Civic Education',
                'author_id' => 3
            ],
            [
                'title' => 'Sustainable Agriculture Practices for Local Farmers',
                'slug' => 'sustainable-agriculture-practices',
                'excerpt' => 'Tips and techniques for environmentally friendly farming methods.',
                'content' => 'Climate change poses significant challenges to agriculture in Ghana. This article explores sustainable farming practices that can help local farmers adapt while improving yields and protecting the environment. Topics include crop rotation, organic fertilizers, and water conservation.',
                'image_url' => 'assets/images/carousel/slide2.jpg',
                'featured' => 1,
                'post_type' => 'blog',
                'category' => 'Agriculture',
                'author_id' => 2
            ]
        ];

        $stmt = $this->conn->prepare("
            INSERT INTO blog_posts (title, slug, excerpt, content, image_url, featured, post_type, category, author_id, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $count = 0;
        foreach ($posts as $post) {
            $stmt->bind_param("sssssissi", 
                $post['title'], $post['slug'], $post['excerpt'], $post['content'],
                $post['image_url'], $post['featured'], $post['post_type'], 
                $post['category'], $post['author_id']
            );
            
            if ($stmt->execute()) {
                $count++;
                $type = ucfirst($post['post_type']);
                echo "✓ Added {$type}: {$post['title']}<br>";
            }
        }
        
        $this->results['Blog Posts & News'] = $count;
    }

    /**
     * Seed Carousel Items
     */
    private function seedCarouselItems() {
        echo "<h3>🎠 Seeding Carousel Items...</h3>";
        
        $items = [
            [
                'title' => 'Welcome to Sefwi Wiawso Municipal Assembly',
                'image_url' => 'assets/images/carousel/banner.jpg',
                'link' => '#about',
                'position' => 1
            ],
            [
                'title' => 'Community Development Projects',
                'image_url' => 'assets/images/carousel/slide2.jpg',
                'link' => 'projects/',
                'position' => 2
            ],
            [
                'title' => 'Youth Empowerment Programs',
                'image_url' => 'assets/images/carousel/slide3.jpg',
                'link' => 'youth/',
                'position' => 3
            ]
        ];

        $stmt = $this->conn->prepare("
            INSERT INTO carousel_items (title, image_url, link, position, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");

        $count = 0;
        foreach ($items as $item) {
            $stmt->bind_param("sssi", $item['title'], $item['image_url'], $item['link'], $item['position']);
            
            if ($stmt->execute()) {
                $count++;
                echo "✓ Added carousel item: {$item['title']}<br>";
            }
        }
        
        $this->results['Carousel Items'] = $count;
    }

    /**
     * Seed Contact Messages
     */
    private function seedContactMessages() {
        echo "<h3>✉️ Seeding Contact Messages...</h3>";
        
        $messages = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+233244123456',
                'subject' => 'Road Maintenance Request',
                'message' => 'The road leading to my community needs urgent attention. There are several potholes that make transportation difficult.',
                'status' => 'new',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ],
            [
                'name' => 'Mary Asante',
                'email' => 'mary@example.com',
                'phone' => '+233244234567',
                'subject' => 'Water Supply Issue',
                'message' => 'Our community has been without clean water for over a week. Please help us resolve this issue.',
                'status' => 'read',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ]
        ];

        $stmt = $this->conn->prepare("
            INSERT INTO contact_messages (name, email, phone, subject, message, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $count = 0;
        foreach ($messages as $message) {
            $stmt->bind_param("sssssss", 
                $message['name'], $message['email'], $message['phone'], 
                $message['subject'], $message['message'], $message['status'], $message['created_at']
            );
            
            if ($stmt->execute()) {
                $count++;
                echo "✓ Added message from: {$message['name']}<br>";
            }
        }
        
        $this->results['Contact Messages'] = $count;
    }

    /**
     * Seed Newsletter Subscribers
     */
    private function seedNewsletterSubscribers() {
        echo "<h3>📧 Seeding Newsletter Subscribers...</h3>";
        
        $subscribers = [
            'newsletter1@example.com',
            'newsletter2@example.com',
            'newsletter3@example.com',
            'community@sefwi-wiawso.com',
            'updates@localresidents.com'
        ];

        $stmt = $this->conn->prepare("
            INSERT INTO newsletter_subscribers (email, subscribed_at) 
            VALUES (?, NOW())
        ");

        $count = 0;
        foreach ($subscribers as $email) {
            $stmt->bind_param("s", $email);
            
            if ($stmt->execute()) {
                $count++;
                echo "✓ Added subscriber: {$email}<br>";
            }
        }
        
        $this->results['Newsletter Subscribers'] = $count;
    }
}

// Run the seeder
$seeder = new DatabaseSeeder($conn);
$seeder->runAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Seeder - Sefwi Wiawso</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
        h1 { color: #f59e0b; }
        h2 { color: #059669; }
        h3 { color: #3b82f6; margin-top: 20px; }
        .success { color: #059669; }
        .error { color: #dc2626; background: #fef2f2; padding: 10px; border-radius: 5px; }
        li { list-style: none; padding: 5px 0; }
    </style>
</head>
<body>
</body>
</html>
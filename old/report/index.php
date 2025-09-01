<?php
require_once '../config/db.php';

// Get electoral areas for dropdown
$areas_query = "SELECT * FROM electoral_areas ORDER BY name";
$areas_result = $conn->query($areas_query);
$electoral_areas = [];
while ($area = $areas_result->fetch_assoc()) {
    $electoral_areas[] = $area;
}

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $electoral_area_id = (int) $_POST['electoral_area_id'];
    $severity = trim($_POST['severity']);
    $people_affected = (int) $_POST['people_affected'];
    $additional_notes = trim($_POST['additional_notes']);
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($phone) || empty($title) || empty($description) || empty($location) || empty($electoral_area_id)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // First, check if this constituent already exists or insert a new one
            $check_constituent = "SELECT id FROM constituents WHERE email = ? OR phone = ? LIMIT 1";
            $check_stmt = $conn->prepare($check_constituent);
            $check_stmt->bind_param("ss", $email, $phone);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // Constituent exists, get their ID
                $constituent_id = $check_result->fetch_assoc()['id'];
                
                // Update their info in case it changed
                $update_constituent = "UPDATE constituents SET name = ?, email = ?, phone = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_constituent);
                $update_stmt->bind_param("sssi", $name, $email, $phone, $constituent_id);
                $update_stmt->execute();
            } else {
                // Insert new constituent
                $insert_constituent = "INSERT INTO constituents (name, email, phone, created_at) VALUES (?, ?, ?, NOW())";
                $insert_stmt = $conn->prepare($insert_constituent);
                $insert_stmt->bind_param("sss", $name, $email, $phone);
                $insert_stmt->execute();
                $constituent_id = $insert_stmt->insert_id;
            }
            
            // Now insert the issue
            $insert_query = "INSERT INTO issues (title, description, location, electoral_area_id, severity, people_affected, additional_notes, constituent_id, status, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
            
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("sssissii", $title, $description, $location, $electoral_area_id, $severity, $people_affected, $additional_notes, $constituent_id);
            
            if ($stmt->execute()) {
                $issue_id = $stmt->insert_id;
                
                // Handle file uploads
                if (!empty($_FILES['photos']['name'][0])) {
                    $upload_dir = '../uploads/issues/' . $issue_id . '/';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Upload each file
                    $total_files = count($_FILES['photos']['name']);
                    for ($i = 0; $i < $total_files; $i++) {
                        if ($_FILES['photos']['error'][$i] === 0) {
                            $tmp_name = $_FILES['photos']['tmp_name'][$i];
                            $name = time() . '_' . basename($_FILES['photos']['name'][$i]);
                            $file_path = $upload_dir . $name;
                            $db_path = '/uploads/issues/' . $issue_id . '/' . $name;
                            
                            if (move_uploaded_file($tmp_name, $file_path)) {
                                // Insert file info into database
                                $insert_file = "INSERT INTO issue_photos (issue_id, photo_url, uploaded_at) VALUES (?, ?, NOW())";
                                $file_stmt = $conn->prepare($insert_file);
                                $file_stmt->bind_param("is", $issue_id, $db_path);
                                $file_stmt->execute();
                            }
                        }
                    }
                }
                
                // Log the issue creation
                $log_query = "INSERT INTO issue_updates (issue_id, update_text, created_at) 
                             VALUES (?, 'Issue reported by constituent', NOW())";
                $log_stmt = $conn->prepare($log_query);
                $log_stmt->bind_param("i", $issue_id);
                $log_stmt->execute();
                
                // Commit the transaction
                $conn->commit();
                
                $success_message = "Thank you! Your issue has been reported successfully. Reference number: #" . $issue_id;
                
                // Clear form data after successful submission
                $name = $email = $phone = $title = $description = $location = $electoral_area_id = $severity = $additional_notes = '';
                $people_affected = 0;
            } else {
                throw new Exception("Error reporting issue: " . $conn->error);
            }
        } catch (Exception $e) {
            // Rollback the transaction if any query fails
            $conn->rollback();
            $error_message = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report an Issue | Sefwi Wiawso Constituency</title>
    <meta name="description" content="Report community issues or problems to your constituency office">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/coat-of-arms.png">
</head>

<body class="bg-gray-50">
    <?php include_once '../includes/header.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="bg-amber-600 text-white py-12 md:py-20">
            <div class="max-w-6xl mx-auto px-4">
                <h1 class="text-3xl md:text-4xl font-bold mb-4">Report an Issue</h1>
                <p class="text-lg md:text-xl max-w-3xl">Help us improve our community by reporting issues that need attention.</p>
            </div>
        </section>

        <div class="max-w-4xl mx-auto px-4 py-12">
            <?php if (!empty($success_message)): ?>
            <div class="mb-8 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md" role="alert">
                <p class="font-bold">Success!</p>
                <p><?= $success_message ?></p>
                <div class="mt-4">
                    <a href="/" class="inline-flex items-center px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700 transition-colors">
                        <i class="fas fa-home mr-2"></i> Return to Homepage
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
            <div class="mb-8 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">
                <p class="font-bold">Error</p>
                <p><?= $error_message ?></p>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6 bg-gray-50 border-b">
                    <h2 class="text-xl font-semibold text-gray-800">Issue Reporting Form</h2>
                    <p class="text-gray-600 mt-1">Please provide as much detail as possible about the issue you're reporting</p>
                </div>

                <form action="" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    Your contact information is required to follow up on the issue and provide updates.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Information Section -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">Your Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Full Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" id="name" required value="<?= isset($name) ? htmlspecialchars($name) : '' ?>" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                                <input type="email" name="email" id="email" required value="<?= isset($email) ? htmlspecialchars($email) : '' ?>"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                            </div>

                            <!-- Phone Number -->
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number <span class="text-red-500">*</span></label>
                                <input type="tel" name="phone" id="phone" required value="<?= isset($phone) ? htmlspecialchars($phone) : '' ?>"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                            </div>
                        </div>
                    </div>

                    <!-- Issue Details Section -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">Issue Details</h3>
                        
                        <!-- Issue Title -->
                        <div class="mb-6">
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Issue Title <span class="text-red-500">*</span></label>
                            <input type="text" name="title" id="title" required value="<?= isset($title) ? htmlspecialchars($title) : '' ?>"
                                placeholder="E.g., Broken Road, Water Shortage, etc." 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                            <p class="mt-1 text-sm text-gray-500">A short, descriptive title for the issue</p>
                        </div>

                        <!-- Description -->
                        <div class="mb-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-red-500">*</span></label>
                            <textarea name="description" id="description" rows="5" required 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"><?= isset($description) ? htmlspecialchars($description) : '' ?></textarea>
                            <p class="mt-1 text-sm text-gray-500">Please provide a detailed description of the issue</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Location -->
                            <div>
                                <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Specific Location <span class="text-red-500">*</span></label>
                                <input type="text" name="location" id="location" required value="<?= isset($location) ? htmlspecialchars($location) : '' ?>"
                                    placeholder="E.g., Main Street, Tanoso Junction, etc."
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                <p class="mt-1 text-sm text-gray-500">Where exactly is the issue located?</p>
                            </div>

                            <!-- Electoral Area -->
                            <div>
                                <label for="electoral_area_id" class="block text-sm font-medium text-gray-700 mb-1">Electoral Area <span class="text-red-500">*</span></label>
                                <select name="electoral_area_id" id="electoral_area_id" required
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                    <option value="">Select Electoral Area</option>
                                    <?php foreach ($electoral_areas as $area): ?>
                                    <option value="<?= $area['id'] ?>" <?= (isset($electoral_area_id) && $electoral_area_id == $area['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($area['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Severity -->
                            <div>
                                <label for="severity" class="block text-sm font-medium text-gray-700 mb-1">Issue Severity</label>
                                <select name="severity" id="severity" class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                    <option value="low" <?= (isset($severity) && $severity == 'low') ? 'selected' : '' ?>>Low - Minor issue</option>
                                    <option value="medium" <?= (!isset($severity) || (isset($severity) && $severity == 'medium')) ? 'selected' : '' ?>>Medium - Moderate issue</option>
                                    <option value="high" <?= (isset($severity) && $severity == 'high') ? 'selected' : '' ?>>High - Serious issue</option>
                                    <option value="critical" <?= (isset($severity) && $severity == 'critical') ? 'selected' : '' ?>>Critical - Urgent issue</option>
                                </select>
                                <p class="mt-1 text-sm text-gray-500">How severe or urgent is this issue?</p>
                            </div>

                            <!-- People Affected -->
                            <div>
                                <label for="people_affected" class="block text-sm font-medium text-gray-700 mb-1">Estimated People Affected</label>
                                <input type="number" name="people_affected" id="people_affected" min="0" value="<?= isset($people_affected) ? $people_affected : '' ?>"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                <p class="mt-1 text-sm text-gray-500">Approximately how many people are affected by this issue?</p>
                            </div>
                        </div>

                        <!-- Photo Upload -->
                        <div class="mb-6">
                            <label for="photos" class="block text-sm font-medium text-gray-700 mb-1">Photo Evidence</label>
                            <input type="file" name="photos[]" id="photos" multiple accept="image/*"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                            <p class="mt-1 text-sm text-gray-500">Upload up to 5 photos (optional). Accepted formats: JPG, PNG.</p>
                            
                            <!-- Preview area for selected images -->
                            <div id="image-preview" class="mt-3 grid grid-cols-2 md:grid-cols-5 gap-2"></div>
                        </div>

                        <!-- Additional Notes -->
                        <div>
                            <label for="additional_notes" class="block text-sm font-medium text-gray-700 mb-1">Additional Notes</label>
                            <textarea name="additional_notes" id="additional_notes" rows="3" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"><?= isset($additional_notes) ? htmlspecialchars($additional_notes) : '' ?></textarea>
                            <p class="mt-1 text-sm text-gray-500">Any other relevant information about the issue.</p>
                        </div>
                    </div>

                    <!-- Privacy Notice -->
                    <div class="bg-gray-50 p-4 rounded-md">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input id="privacy" name="privacy" type="checkbox" required
                                    class="h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="privacy" class="font-medium text-gray-700">Privacy Agreement <span class="text-red-500">*</span></label>
                                <p class="text-gray-500">I understand that my contact information will be used only for the purpose of addressing this issue and providing updates.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                            <i class="fas fa-paper-plane mr-2"></i> Submit Report
                        </button>
                    </div>
                </form>
            </div>

            <div class="mt-8 bg-blue-50 rounded-lg p-6">
                <h3 class="text-lg font-medium text-blue-900 mb-3">What happens next?</h3>
                <ol class="list-decimal pl-5 space-y-2 text-blue-800">
                    <li>Your issue report will be reviewed by our constituency office team</li>
                    <li>A field officer may contact you for more information if needed</li>
                    <li>Issues are prioritized based on severity and number of people affected</li>
                    <li>You will receive updates on the status of your reported issue</li>
                </ol>
                <p class="mt-4 text-blue-700">For urgent matters that require immediate attention, please call our emergency hotline: <a href="tel:+233244123456" class="font-bold">+233 244 123 456</a></p>
            </div>
        </div>
    </main>

    <?php include_once '../includes/footer.php'; ?>

    <script>
        // Image preview functionality
        document.getElementById('photos').addEventListener('change', function(e) {
            const previewContainer = document.getElementById('image-preview');
            previewContainer.innerHTML = '';
            
            const files = e.target.files;
            const maxFiles = 5;
            const filesToPreview = Math.min(files.length, maxFiles);
            
            for(let i = 0; i < filesToPreview; i++) {
                const file = files[i];
                
                // Check if file is an image
                if (!file.type.match('image.*')) continue;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'relative';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'h-32 w-full rounded object-cover';
                    img.alt = 'Image preview';
                    
                    div.appendChild(img);
                    previewContainer.appendChild(div);
                }
                reader.readAsDataURL(file);
            }
            
            if (files.length > maxFiles) {
                const notice = document.createElement('div');
                notice.className = 'col-span-full text-sm text-amber-600 mt-1';
                notice.textContent = `Note: Only the first ${maxFiles} images will be uploaded.`;
                previewContainer.appendChild(notice);
            }
        });
        
        // Form validation enhancement
        document.querySelector('form').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('photos');
            const files = fileInput.files;
            
            // Check file size (limit to 5MB per file)
            const maxSizeMB = 5;
            const maxSizeBytes = maxSizeMB * 1024 * 1024;
            
            for(let i = 0; i < files.length; i++) {
                if (files[i].size > maxSizeBytes) {
                    e.preventDefault();
                    alert(`Image "${files[i].name}" exceeds the ${maxSizeMB}MB size limit. Please select a smaller file.`);
                    return;
                }
            }
        });
    </script>
</body>
</html>
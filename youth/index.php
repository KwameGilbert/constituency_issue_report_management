<?php
// youth_registration.php - Public Youth Registration Form
require_once __DIR__ . '/../iss/config/db_connection.php';

$database = new Database();
$conn = $database->getConnection();

// Initialize message variables
$message = '';
$message_type = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $required_fields = ['name', 'date_of_birth', 'national_id', 'home_town', 'residential_community', 'phone_number'];
    $validation_errors = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $validation_errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    
    // Validate national ID uniqueness
    if (empty($validation_errors)) {
        $national_id = $_POST['national_id'];
        try {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM youth_records WHERE national_id = ?");
            $stmt->execute([$national_id]);
            if ($stmt->fetchColumn() > 0) {
                $validation_errors[] = 'A record with this National ID already exists in our system';
            }
        } catch (Exception $e) {
            $validation_errors[] = 'Error validating National ID: ' . $e->getMessage();
        }
    }
    
    // If validation passes, process the data
    if (empty($validation_errors)) {
        try {
            // Prepare data array
            $youth_data = [
                'name' => $_POST['name'],
                'date_of_birth' => $_POST['date_of_birth'],
                'national_id' => $_POST['national_id'],
                'home_town' => $_POST['home_town'],
                'residential_community' => $_POST['residential_community'],
                'phone_number' => $_POST['phone_number'],
                'jhs_completed' => isset($_POST['jhs_completed']) ? 1 : 0,
                'shs_qualification' => $_POST['shs_qualification'] ?? '',
                'certificate_qualification' => $_POST['certificate_qualification'] ?? '',
                'diploma_qualification' => $_POST['diploma_qualification'] ?? '',
                'first_degree' => $_POST['first_degree'] ?? '',
                'postgraduate_qualification' => $_POST['postgraduate_qualification'] ?? '',
                'professional_qualification' => $_POST['professional_qualification'] ?? '',
                'work_experience_1' => $_POST['work_experience_1'] ?? '',
                'work_experience_2' => $_POST['work_experience_2'] ?? '',
                'work_experience_3' => $_POST['work_experience_3'] ?? '',
                'work_experience_4' => $_POST['work_experience_4'] ?? '',
                'work_experience_5' => $_POST['work_experience_5'] ?? '',
                'work_experience_6' => $_POST['work_experience_6'] ?? '',
                'employment_status' => $_POST['employment_status'] ?? 'unemployed',
                'current_employment' => $_POST['current_employment'] ?? '',
                'employment_notes' => $_POST['employment_notes'] ?? '',
                'skills' => $_POST['skills'] ?? '',
                'interests' => $_POST['interests'] ?? '',
                'availability_status' => $_POST['availability_status'] ?? 'available',
                'preferred_work_location' => $_POST['preferred_work_location'] ?? '',
                'salary_expectation' => !empty($_POST['salary_expectation']) ? floatval($_POST['salary_expectation']) : null,
                'status' => 'pending'
            ];
            
            // Insert new record
            $sql_fields = implode(', ', array_keys($youth_data));
            $sql_placeholders = implode(', ', array_fill(0, count($youth_data), '?'));
            
            $stmt = $conn->prepare("INSERT INTO youth_records ($sql_fields) VALUES ($sql_placeholders)");
            $stmt->execute(array_values($youth_data));
            
            $message = "Your information has been successfully submitted. Our team will review your submission and may contact you for further information or opportunities.";
            $message_type = 'success';
            
            // Clear form data after successful submission
            $_POST = [];
        } catch (Exception $e) {
            $message = "Error submitting your information: " . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message = "Please fix the following errors:<br>" . implode('<br>', $validation_errors);
        $message_type = 'error';
    }
}

// Page title and meta
$page_title = "Youth Registration Form";
$page_description = "Register for youth employment and skills database";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Constituency Management System</title>
    <meta name="description" content="<?php echo $page_description; ?>">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="/styles/output.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        
        .required-field::after {
            content: '*';
            color: #e53e3e;
            margin-left: 2px;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
   <?php include_once '../includes/header.php'; ?>
    
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Page Header -->
            <div class="text-center mb-10">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Youth Registration Form</h1>
                <p class="text-gray-600">Register your details to be included in our constituency youth database for employment opportunities and skills development</p>
            </div>
            
            <!-- Alert Messages -->
            <?php if (!empty($message)): ?>
                <div class="rounded-md p-4 mb-6 <?php echo $message_type === 'success' ? 'bg-green-50' : 'bg-red-50'; ?>">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <?php if ($message_type === 'success'): ?>
                                <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            <?php else: ?>
                                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium <?php echo $message_type === 'success' ? 'text-green-800' : 'text-red-800'; ?>">
                                <?php echo $message; ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Form Section -->
            <form method="POST" class="bg-white shadow-md rounded-lg overflow-hidden">
                <!-- Form Instructions -->
                <div class="bg-blue-50 p-4 border-l-4 border-blue-500">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                Please fill out all required fields marked with an asterisk (*). Your information will be kept confidential and used only for constituency development purposes.
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Personal Information -->
                <div class="p-6">
                    <h2 class="text-xl font-semibold mb-4 text-gray-900 border-b pb-2">Personal Information</h2>
                    
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 required-field">Full Name</label>
                            <input type="text" name="name" id="name" required
                                value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label for="date_of_birth" class="block text-sm font-medium text-gray-700 required-field">Date of Birth</label>
                            <input type="date" name="date_of_birth" id="date_of_birth" required
                                value="<?php echo isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : ''; ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label for="national_id" class="block text-sm font-medium text-gray-700 required-field">National ID Number</label>
                            <input type="text" name="national_id" id="national_id" required
                                value="<?php echo isset($_POST['national_id']) ? htmlspecialchars($_POST['national_id']) : ''; ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label for="phone_number" class="block text-sm font-medium text-gray-700 required-field">Phone Number</label>
                            <input type="tel" name="phone_number" id="phone_number" required
                                value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label for="home_town" class="block text-sm font-medium text-gray-700 required-field">Home Town</label>
                            <input type="text" name="home_town" id="home_town" required
                                value="<?php echo isset($_POST['home_town']) ? htmlspecialchars($_POST['home_town']) : ''; ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label for="residential_community" class="block text-sm font-medium text-gray-700 required-field">Residential Community</label>
                            <input type="text" name="residential_community" id="residential_community" required
                                value="<?php echo isset($_POST['residential_community']) ? htmlspecialchars($_POST['residential_community']) : ''; ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
                
                <!-- Educational Qualifications -->
                <div class="p-6 border-t border-gray-200">
                    <h2 class="text-xl font-semibold mb-4 text-gray-900 border-b pb-2">Educational Qualifications</h2>
                    
                    <div class="mb-4">
                        <div class="relative flex items-start">
                            <div class="flex items-center h-5">
                                <input id="jhs_completed" name="jhs_completed" type="checkbox"
                                    <?php echo isset($_POST['jhs_completed']) ? 'checked' : ''; ?>
                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="jhs_completed" class="font-medium text-gray-700">JHS Completed</label>
                                <p class="text-gray-500">Check this if you have completed Junior High School</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label for="shs_qualification" class="block text-sm font-medium text-gray-700">SHS Qualification</label>
                            <input type="text" name="shs_qualification" id="shs_qualification"
                                value="<?php echo isset($_POST['shs_qualification']) ? htmlspecialchars($_POST['shs_qualification']) : ''; ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="E.g., General Arts, Science, Visual Arts, etc.">
                        </div>
                        
                        <div>
                            <label for="certificate_qualification" class="block text-sm font-medium text-gray-700">Certificate Qualification</label>
                            <input type="text" name="certificate_qualification" id="certificate_qualification"
                                value="<?php echo isset($_POST['certificate_qualification']) ? htmlspecialchars($_POST['certificate_qualification']) : ''; ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="E.g., Certificate in Computer Science">
                        </div>
                        
                        <div>
                            <label for="diploma_qualification" class="block text-sm font-medium text-gray-700">Diploma Qualification</label>
                            <input type="text" name="diploma_qualification" id="diploma_qualification"
                                value="<?php echo isset($_POST['diploma_qualification']) ? htmlspecialchars($_POST['diploma_qualification']) : ''; ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="E.g., Diploma in Business Administration">
                        </div>
                        
                        <div>
                            <label for="first_degree" class="block text-sm font-medium text-gray-700">First Degree</label>
                            <input type="text" name="first_degree" id="first_degree"
                                value="<?php echo isset($_POST['first_degree']) ? htmlspecialchars($_POST['first_degree']) : ''; ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="E.g., BSc Computer Science, BA Economics">
                        </div>
                        
                        <div>
                            <label for="postgraduate_qualification" class="block text-sm font-medium text-gray-700">Postgraduate Qualification</label>
                            <input type="text" name="postgraduate_qualification" id="postgraduate_qualification"
                                value="<?php echo isset($_POST['postgraduate_qualification']) ? htmlspecialchars($_POST['postgraduate_qualification']) : ''; ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="E.g., MSc, MBA, PhD">
                        </div>
                        
                        <div>
                            <label for="professional_qualification" class="block text-sm font-medium text-gray-700">Professional Qualification</label>
                            <input type="text" name="professional_qualification" id="professional_qualification"
                                value="<?php echo isset($_POST['professional_qualification']) ? htmlspecialchars($_POST['professional_qualification']) : ''; ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="E.g., ACCA, CIM">
                        </div>
                    </div>
                </div>
                
                <!-- Employment Information -->
                <div class="p-6 border-t border-gray-200">
                    <h2 class="text-xl font-semibold mb-4 text-gray-900 border-b pb-2">Employment Information</h2>
                    
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label for="employment_status" class="block text-sm font-medium text-gray-700">Employment Status</label>
                            <select id="employment_status" name="employment_status" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="unemployed" <?php echo isset($_POST['employment_status']) && $_POST['employment_status'] === 'unemployed' ? 'selected' : ''; ?>>Unemployed</option>
                                <option value="employed" <?php echo isset($_POST['employment_status']) && $_POST['employment_status'] === 'employed' ? 'selected' : ''; ?>>Employed</option>
                                <option value="self_employed" <?php echo isset($_POST['employment_status']) && $_POST['employment_status'] === 'self_employed' ? 'selected' : ''; ?>>Self Employed</option>
                                <option value="student" <?php echo isset($_POST['employment_status']) && $_POST['employment_status'] === 'student' ? 'selected' : ''; ?>>Student</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="availability_status" class="block text-sm font-medium text-gray-700">Availability Status</label>
                            <select id="availability_status" name="availability_status" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="available" <?php echo isset($_POST['availability_status']) && $_POST['availability_status'] === 'available' ? 'selected' : ''; ?>>Available for Work</option>
                                <option value="unavailable" <?php echo isset($_POST['availability_status']) && $_POST['availability_status'] === 'unavailable' ? 'selected' : ''; ?>>Not Available</option>
                                <option value="part_time" <?php echo isset($_POST['availability_status']) && $_POST['availability_status'] === 'part_time' ? 'selected' : ''; ?>>Available for Part Time Only</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="current_employment" class="block text-sm font-medium text-gray-700">Current Employment</label>
                            <input type="text" name="current_employment" id="current_employment"
                                value="<?php echo isset($_POST['current_employment']) ? htmlspecialchars($_POST['current_employment']) : ''; ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="E.g., Teacher at ABC School">
                        </div>
                        
                        <div>
                            <label for="preferred_work_location" class="block text-sm font-medium text-gray-700">Preferred Work Location</label>
                            <input type="text" name="preferred_work_location" id="preferred_work_location"
                                value="<?php echo isset($_POST['preferred_work_location']) ? htmlspecialchars($_POST['preferred_work_location']) : ''; ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="E.g., Within constituency, Accra">
                        </div>
                        
                        <div>
                            <label for="salary_expectation" class="block text-sm font-medium text-gray-700">Salary Expectation (GHS)</label>
                            <input type="number" name="salary_expectation" id="salary_expectation" step="0.01" min="0"
                                value="<?php echo isset($_POST['salary_expectation']) ? htmlspecialchars($_POST['salary_expectation']) : ''; ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Monthly salary expectation">
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <label for="employment_notes" class="block text-sm font-medium text-gray-700">Employment Notes</label>
                        <textarea id="employment_notes" name="employment_notes" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Additional notes about your employment history, preferences, etc."><?php echo isset($_POST['employment_notes']) ? htmlspecialchars($_POST['employment_notes']) : ''; ?></textarea>
                    </div>
                </div>
                
                <!-- Skills and Interests -->
                <div class="p-6 border-t border-gray-200">
                    <h2 class="text-xl font-semibold mb-4 text-gray-900 border-b pb-2">Skills and Interests</h2>
                    
                    <div>
                        <label for="skills" class="block text-sm font-medium text-gray-700">Skills</label>
                        <textarea id="skills" name="skills" rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="List your professional skills, technical abilities, etc. Separate each with a comma."><?php echo isset($_POST['skills']) ? htmlspecialchars($_POST['skills']) : ''; ?></textarea>
                    </div>
                    
                    <div class="mt-6">
                        <label for="interests" class="block text-sm font-medium text-gray-700">Interests</label>
                        <textarea id="interests" name="interests" rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="List your personal interests, hobbies, etc. Separate each with a comma."><?php echo isset($_POST['interests']) ? htmlspecialchars($_POST['interests']) : ''; ?></textarea>
                    </div>
                </div>
                
                <!-- Work Experience -->
                <div class="p-6 border-t border-gray-200">
                    <h2 class="text-xl font-semibold mb-4 text-gray-900 border-b pb-2">Work Experience (Optional)</h2>
                    <p class="text-sm text-gray-600 mb-4">You can add up to 6 previous work experiences.</p>
                    
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                        <div class="mb-6">
                            <label for="work_experience_<?php echo $i; ?>" class="block text-sm font-medium text-gray-700">
                                Work Experience <?php echo $i; ?>
                            </label>
                            <textarea id="work_experience_<?php echo $i; ?>" name="work_experience_<?php echo $i; ?>" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Company name, position, duration, and key responsibilities"><?php echo isset($_POST['work_experience_' . $i]) ? htmlspecialchars($_POST['work_experience_' . $i]) : ''; ?></textarea>
                        </div>
                    <?php endfor; ?>
                </div>
                
                <!-- Terms and Submit -->
                <div class="p-6 border-t border-gray-200 bg-gray-50">
                    <div class="flex items-start mb-6">
                        <div class="flex items-center h-5">
                            <input id="terms" name="terms" type="checkbox" required
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="terms" class="font-medium text-gray-700">I agree to the terms and conditions</label>
                            <p class="text-gray-500">By submitting this form, you agree that your information may be used for constituency development and employment purposes.</p>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Submit Registration
                        </button>
                    </div>
                </div>
            </form>
            
            <!-- Additional Information -->
            <div class="mt-8 bg-white shadow-md rounded-lg overflow-hidden">
                <div class="p-6">
                    <h2 class="text-xl font-semibold mb-4 text-gray-900">What Happens Next?</h2>
                    <div class="space-y-4 text-gray-600">
                        <p>After submitting your information:</p>
                        <ol class="list-decimal list-inside space-y-2 ml-4">
                            <li>Our team will review your submission</li>
                            <li>Your information will be added to our youth database</li>
                            <li>You may be contacted for relevant employment or skills development opportunities</li>
                            <li>Your data will be kept confidential and only used for constituency development purposes</li>
                        </ol>
                        <p class="mt-4">If you have any questions about this process, please contact our office at <a href="tel:+233XXXXXXXXX" class="text-blue-600 hover:underline">+233 XX XXX XXXX</a> or email <a href="mailto:youth@constituency.gov.gh" class="text-blue-600 hover:underline">youth@constituency.gov.gh</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
       <?php include_once '../includes/footer.php'; ?>

    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            
            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', function() {
                    mobileMenu.classList.toggle('hidden');
                });
            }
            
            // Form validation
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(event) {
                    const requiredFields = form.querySelectorAll('[required]');
                    let valid = true;
                    
                    requiredFields.forEach(field => {
                        if (!field.value) {
                            valid = false;
                            field.classList.add('border-red-500');
                        } else {
                            field.classList.remove('border-red-500');
                        }
                    });
                    
                    if (!valid) {
                        event.preventDefault();
                        alert('Please fill in all required fields.');
                    }
                });
            }
        });
    </script>
</body>
</html>
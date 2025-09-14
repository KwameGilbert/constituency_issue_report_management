<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> | Admin - Youth Records</title>
    <link href="https://fonts.googleapis.com/css?family=Inter:400,500,600,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-50 font-sans antialiased">
<?php
// ...existing code...
renderAdminSidebar($current_page, $pendingIssuesCount, $activeUsersCount);
?>
<div class="lg:pl-64 flex flex-col flex-1">
    <?php renderAdminHeader($current_page); ?>
    <main class="flex-1 pb-8 px-4 sm:px-6 lg:px-8 bg-gray-50">
        <!-- Page header -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex flex-wrap items-center justify-between">
                    <div class="flex-1 min-w-0">
                        <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                            <?php echo $title; ?>
                        </h1>
                        <div class="mt-1 flex flex-col sm:flex-row sm:flex-wrap sm:mt-0 sm:space-x-6">
                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <?php echo $is_new ? 'Creating new youth record' : 'Editing youth record for ' . htmlspecialchars($youth['name']); ?>
                            </div>
                        </div>
                    </div>
                    <!-- Action Buttons -->
                    <div class="mt-4 flex-shrink-0 flex md:mt-0 md:ml-4 space-x-3">
                        <a href="<?php echo $is_new ? 'index.php' : 'view_youth.php?id=' . $id; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Alert Messages -->
        <?php if (!empty($message)): ?>
            <div class="rounded-md p-4 mb-4 <?php echo $message_type === 'success' ? 'bg-green-50' : ($message_type === 'error' ? 'bg-red-50' : ($message_type === 'warning' ? 'bg-yellow-50' : 'bg-blue-50')); ?>">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <?php if ($message_type === 'success'): ?>
                            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        <?php elseif ($message_type === 'error'): ?>
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        <?php else: ?>
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium <?php echo $message_type === 'success' ? 'text-green-800' : ($message_type === 'error' ? 'text-red-800' : ($message_type === 'warning' ? 'text-yellow-800' : 'text-blue-800')); ?>">
                            <?php echo $message; ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <!-- Form -->
        <form method="POST" class="space-y-6">
            <!-- Personal Information -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 bg-gray-50">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Personal Information
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Basic details about the youth.
                    </p>
                </div>
                <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-3">
                            <label for="name" class="block text-sm font-medium text-gray-700">Full Name *</label>
                        </div>
                    </div>
                </div>
            </div>
          
                            <div class="mt-1">
                                <input type="text" name="name" id="name" required
                                    value="<?php echo htmlspecialchars($youth['name']); ?>"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            </div>
                        </div>
                        
                        <div class="sm:col-span-3">
                            <label for="date_of_birth" class="block text-sm font-medium text-gray-700">Date of Birth *</label>
                            <div class="mt-1">
                                <input type="date" name="date_of_birth" id="date_of_birth" required
                                    value="<?php echo htmlspecialchars($youth['date_of_birth']); ?>"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            </div>
                        </div>
                        
                        <div class="sm:col-span-3">
                            <label for="national_id" class="block text-sm font-medium text-gray-700">National ID Number *</label>
                            <div class="mt-1">
                                <input type="text" name="national_id" id="national_id" required
                                    value="<?php echo htmlspecialchars($youth['national_id']); ?>"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            </div>
                        </div>
                        
                        <div class="sm:col-span-3">
                            <label for="phone_number" class="block text-sm font-medium text-gray-700">Phone Number *</label>
                            <div class="mt-1">
                                <input type="tel" name="phone_number" id="phone_number" required
                                    value="<?php echo htmlspecialchars($youth['phone_number']); ?>"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            </div>
                        </div>
                        
                        <div class="sm:col-span-3">
                            <label for="home_town" class="block text-sm font-medium text-gray-700">Home Town *</label>
                            <div class="mt-1">
                                <input type="text" name="home_town" id="home_town" required
                                    value="<?php echo htmlspecialchars($youth['home_town']); ?>"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            </div>
                        </div>
                        
                        <div class="sm:col-span-3">
                            <label for="residential_community" class="block text-sm font-medium text-gray-700">Residential Community *</label>
                            <div class="mt-1">
                                <input type="text" name="residential_community" id="residential_community" required
                                    value="<?php echo htmlspecialchars($youth['residential_community']); ?>"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Educational Qualifications -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 bg-gray-50">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Educational Qualifications
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Academic achievements and certifications.
                    </p>
                </div>
                <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-6">
                            <div class="relative flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="jhs_completed" name="jhs_completed" type="checkbox"
                                        <?php echo $youth['jhs_completed'] ? 'checked' : ''; ?>
                                        class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="jhs_completed" class="font-medium text-gray-700">JHS Completed</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="sm:col-span-6">
                            <label for="shs_qualification" class="block text-sm font-medium text-gray-700">SHS Qualification</label>
                            <div class="mt-1">
                                <input type="text" name="shs_qualification" id="shs_qualification"
                                    value="<?php echo htmlspecialchars($youth['shs_qualification']); ?>"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                    placeholder="E.g., General Arts, Science, Visual Arts, etc.">
                            </div>
                        </div>
                        
                        <div class="sm:col-span-3">
                            <label for="certificate_qualification" class="block text-sm font-medium text-gray-700">Certificate Qualification</label>
                            <div class="mt-1">
                                <input type="text" name="certificate_qualification" id="certificate_qualification"
                                    value="<?php echo htmlspecialchars($youth['certificate_qualification']); ?>"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            </div>
                        </div>
                        
                        <div class="sm:col-span-3">
                            <label for="diploma_qualification" class="block text-sm font-medium text-gray-700">Diploma Qualification</label>
                            <div class="mt-1">
                                <input type="text" name="diploma_qualification" id="diploma_qualification"
                                    value="<?php echo htmlspecialchars($youth['diploma_qualification']); ?>"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            </div>
                        </div>
                        
                        <div class="sm:col-span-3">
                            <label for="first_degree" class="block text-sm font-medium text-gray-700">First Degree</label>
                            <div class="mt-1">
                                <input type="text" name="first_degree" id="first_degree"
                                    value="<?php echo htmlspecialchars($youth['first_degree']); ?>"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                    placeholder="E.g., BSc Computer Science, BA Economics, etc.">
                            </div>
                        </div>
                        
                        <div class="sm:col-span-3">
                            <label for="postgraduate_qualification" class="block text-sm font-medium text-gray-700">Postgraduate Qualification</label>
                            <div class="mt-1">
                                <input type="text" name="postgraduate_qualification" id="postgraduate_qualification"
                                    value="<?php echo htmlspecialchars($youth['postgraduate_qualification']); ?>"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                    placeholder="E.g., MSc, MBA, PhD, etc.">
                            </div>
                        </div>
                        
                        <div class="sm:col-span-6">
                            <label for="professional_qualification" class="block text-sm font-medium text-gray-700">Professional Qualification</label>
                            <div class="mt-1">
                                <input type="text" name="professional_qualification" id="professional_qualification"
                                    value="<?php echo htmlspecialchars($youth['professional_qualification']); ?>"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                    placeholder="E.g., ACCA, CIM, etc.">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Employment Status -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 bg-gray-50">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Employment Information
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Current employment status and preferences.
                    </p>
                </div>
                <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-3">
                            <label for="employment_status" class="block text-sm font-medium text-gray-700">Employment Status</label>
                            <div class="mt-1">
                                <select id="employment_status" name="employment_status" 
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    <option value="unemployed" <?php echo $youth['employment_status'] === 'unemployed' ? 'selected' : ''; ?>>Unemployed</option>
                                    <option value="employed" <?php echo $youth['employment_status'] === 'employed' ? 'selected' : ''; ?>>Employed</option>
                                    <option value="self_employed" <?php echo $youth['employment_status'] === 'self_employed' ? 'selected' : ''; ?>>Self Employed</option>
                                    <option value="student" <?php echo $youth['employment_status'] === 'student' ? 'selected' : ''; ?>>Student</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="sm:col-span-3">
                            <label for="availability_status" class="block text-sm font-medium text-gray-700">Availability Status</label>
                            <div class="mt-1">
                                <select id="availability_status" name="availability_status" 
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    <option value="available" <?php echo $youth['availability_status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="unavailable" <?php echo $youth['availability_status'] === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                                    <option value="part_time" <?php echo $youth['availability_status'] === 'part_time' ? 'selected' : ''; ?>>Available Part Time</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="sm:col-span-3">
                            <label for="current_employment" class="block text-sm font-medium text-gray-700">Current Employment</label>
                            <div class="mt-1">
                                <input type="text" name="current_employment" id="current_employment"
                                    value="<?php echo htmlspecialchars($youth['current_employment']); ?>"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                    placeholder="E.g., Teacher at ABC School">
                            </div>
                        </div>
                        
                        <div class="sm:col-span-3">
                            <label for="preferred_work_location" class="block text-sm font-medium text-gray-700">Preferred Work Location</label>
                            <div class="mt-1">
                                <input type="text" name="preferred_work_location" id="preferred_work_location"
                                    value="<?php echo htmlspecialchars($youth['preferred_work_location']); ?>"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                    placeholder="E.g., Within constituency, Accra, etc.">
                            </div>
                        </div>
                        
                        <div class="sm:col-span-3">
                            <label for="salary_expectation" class="block text-sm font-medium text-gray-700">Salary Expectation (GHS)</label>
                            <div class="mt-1">
                                <input type="number" name="salary_expectation" id="salary_expectation" step="0.01" min="0"
                                    value="<?php echo $youth['salary_expectation'] ? htmlspecialchars($youth['salary_expectation']) : ''; ?>"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                    placeholder="Monthly salary expectation">
                            </div>
                        </div>
                        
                        <div class="sm:col-span-6">
                            <label for="employment_notes" class="block text-sm font-medium text-gray-700">Employment Notes</label>
                            <div class="mt-1">
                                <textarea id="employment_notes" name="employment_notes" rows="3"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                    placeholder="Additional notes about employment history, preferences, etc."><?php echo htmlspecialchars($youth['employment_notes']); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Skills and Interests -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 bg-gray-50">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Skills and Interests
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Professional skills and personal interests.
                    </p>
                </div>
                <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-6">
                            <label for="skills" class="block text-sm font-medium text-gray-700">Skills</label>
                            <div class="mt-1">
                                <textarea id="skills" name="skills" rows="4"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                    placeholder="List professional skills, technical abilities, etc."><?php echo htmlspecialchars($youth['skills']); ?></textarea>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">Separate each skill with a comma or new line.</p>
                        </div>
                        
                        <div class="sm:col-span-6">
                            <label for="interests" class="block text-sm font-medium text-gray-700">Interests</label>
                            <div class="mt-1">
                                <textarea id="interests" name="interests" rows="4"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                    placeholder="List personal interests, hobbies, etc."><?php echo htmlspecialchars($youth['interests']); ?></textarea>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">Separate each interest with a comma or new line.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Work Experience -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 bg-gray-50">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Work Experience
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Previous employment history (up to 6 entries).
                    </p>
                </div>
                <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4">
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <div>
                                <label for="work_experience_<?php echo $i; ?>" class="block text-sm font-medium text-gray-700">
                                    Work Experience <?php echo $i; ?>
                                </label>
                                <div class="mt-1">
                                    <textarea id="work_experience_<?php echo $i; ?>" name="work_experience_<?php echo $i; ?>" rows="3"
                                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                        placeholder="Company name, position, duration, and key responsibilities"><?php echo htmlspecialchars($youth['work_experience_' . $i]); ?></textarea>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            
            <!-- Administrative Information -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 bg-gray-50">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Administrative Information
                    </h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Status and administrative notes.
                    </p>
                </div>
                <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <div class="sm:col-span-3">
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <div class="mt-1">
                                <select id="status" name="status" 
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    <option value="pending" <?php echo $youth['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $youth['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $youth['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="archived" <?php echo $youth['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="sm:col-span-6">
                            <label for="admin_notes" class="block text-sm font-medium text-gray-700">Administrative Notes</label>
                            <div class="mt-1">
                                <textarea id="admin_notes" name="admin_notes" rows="4"
                                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                    placeholder="Internal notes for administrators only"><?php echo htmlspecialchars($youth['admin_notes']); ?></textarea>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">These notes are visible only to administrators.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="flex justify-end">
                <a href="<?php echo $is_new ? 'index.php' : 'view_youth.php?id=' . $id; ?>" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mr-3">
                    Cancel
                </a>
                <button type="submit" class="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <?php echo $is_new ? 'Create Record' : 'Update Record'; ?>
                </button>
            </div>
        </form>
    </main>
</div>
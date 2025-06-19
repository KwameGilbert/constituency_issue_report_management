<?php
// Start a PHP session
session_start();

// Database connection details
// IMPORTANT: Replace with your actual database credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'your_db_username'); // e.g., 'root'
define('DB_PASSWORD', 'your_db_password'); // e.g., '' for no password
define('DB_NAME', 'your_database_name');   // e.g., 'constituency_system'

// Establish database connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form was submitted using POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and get input from the form
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password']; // Get raw password for verification

    // Prepare a SQL statement to prevent SQL injection
    // Select user where email matches and role is 'agent'
    $sql = "SELECT id, name, email, password, role FROM users WHERE email = ? AND role = 'agent'";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $email); // 's' denotes string type
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            // User found, fetch the row
            $user = $result->fetch_assoc();

            // Verify the hashed password
            // In your schema, `password` is VARCHAR(255), implying it stores a hash.
            // You should hash passwords using password_hash() when storing them.
            // For verification, use password_verify().
            if (password_verify($password, $user['password'])) {
                // Password is correct, start a new session
                $_SESSION['loggedin'] = TRUE;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                // Update last_login timestamp in the database
                $update_sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                if ($update_stmt) {
                    $update_stmt->bind_param("i", $user['id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                }

                // Redirect to the agent dashboard page
                header("Location: dashboard.html");
                exit();
            } else {
                // Password is not valid
                header("Location: login.html?error=invalid_credentials");
                exit();
            }
        } else {
            // No user found with that email and role
            header("Location: login.html?error=invalid_credentials");
            exit();
        }
        $stmt->close();
    } else {
        // Error preparing the statement
        // Log this error in a real application
        header("Location: login.html?error=database_error");
        exit();
    }
} else {
    // If someone tries to access this page directly without POST submission
    header("Location: login.html");
    exit();
}

// Close database connection
$conn->close();

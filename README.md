# SWMA - Constituency Issue Report Management System

This project is a web-based application designed to help field officers and administrators manage constituency issues effectively. It includes features for reporting, tracking, and analyzing issues, as well as generating detailed reports.

## Features

- **Issue Reporting**: Field officers can report new issues with details such as title, description, location, severity, and more.
- **Issue Management**: View, edit, and delete reported issues.
- **Reports & Analytics**: Generate detailed reports and visualize data through charts (e.g., status breakdown, severity distribution, etc.).
- **User Authentication**: Secure login for field officers and administrators.
- **Responsive Design**: Optimized for both desktop and mobile devices.

## File Structure

### Key Directories and Files

- **`admin/officer/`**

  - `create-issue/`: Form for field officers to report new issues.
  - `edit-issue/`: Edit existing issues.
  - `issues/`: List and manage reported issues.
  - `reports/`: Generate and view analytics and reports.
  - `includes/`: Shared components like `header.php` and `sidebar.php`.

- **`web-admin/`**

  - `modules/blog/`: Manage blog posts for the public-facing site.
  - `modules/events/`: Manage events for the public-facing site.
  - `dashboard/`: Admin dashboard with statistics and recent updates.

- **`includes/`**

  - `header.php`: Shared header for the public-facing site.
  - `footer.php`: Shared footer for the public-facing site.

- **`config/db.php`**
  - Database connection file.

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/your-repo/swma.git
   ```
2. Set up the database:

   - Import the provided SQL file into your MySQL database.
   - Update the database credentials in config/db.php.

3. Start a local server:

   - Use XAMPP or any other local server to host the project.

4. Access the application:
   - Field Officer Dashboard: http://localhost/swma/admin/officer/
   - Admin Dashboard: http://localhost/swma/web-admin/

## Usage

### Reporting Issues

1. Navigate to the "Report New Issue" page.
2. Fill in the required fields (e.g., title, description, location).
3. Submit the form to add the issue to the system.

### Managing Issues

1. View all reported issues on the "My Issues" page.
2. Edit or delete issues as needed.

### Generating Reports

1. Go to the "Reports" page.
2. Select a time period and report format (PDF, Excel, or Printable HTML).
3. Generate and download the report.

## Dependencies

PHP: Server-side scripting.
MySQL: Database management.

### Common Errors

#### Database Connection Error:

- Ensure the database credentials in config/db.php are correct.
- Verify that the MySQL server is running.

#### Ambiguous Column Error:

- Ensure all SQL queries explicitly reference table names or aliases.

#### Missing Libraries:

- Install required libraries (e.g., TCPDF for PDF generation).

## License

Missing Libraries:

Install required libraries (e.g., TCPDF for PDF generation).
License
This project is licensed under the MIT License. See the LICENSE file for details.

Contact
For questions or support, please contact the development team at support@swma.com.

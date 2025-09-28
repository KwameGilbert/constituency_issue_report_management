# SWMA Project Setup Guide

This guide will help you install XAMPP, set up a virtual host for the SWMA project, and configure the database.

## 1. Install XAMPP

1. Download XAMPP from [https://www.apachefriends.org/index.html](https://www.apachefriends.org/index.html)
2. Run the installer and follow the prompts to complete installation.
3. After installation, launch the XAMPP Control Panel and start **Apache** and **MySQL** services.

## 2. Copy Project Files

1. Copy the entire SWMA project folder (`swma`) to your XAMPP `htdocs` directory (usually `C:\xampp\htdocs\`).

## 3. Set Up Virtual Host

1. Open a command prompt as **Administrator**.
2. Navigate to the SWMA project folder.
3. Run the `create-vhost.bat` script:
   ```
   create-vhost.bat
   ```
4. Follow the interactive prompts:
   - **Project name**: Enter `swma`
   - **Project directory**: Enter the full path to your SWMA project (e.g., `C:\xampp\htdocs\swma`)
5. The script will:
   - Add a virtual host entry for `app.swma.com` in Apache config
   - Map `app.swma.com` to `127.0.0.1` in your Windows hosts file
   - Restart Apache automatically

## 4. Create the Database

1. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin) in your browser.
2. Create a new database named `constituency_system`.
3. Click the new database, then go to the **Import** tab.
4. Select the `schema.sql` file from your SWMA project folder and import it.

## 5. Access the Application


You can access the different dashboards by adding the relevant folder to the host URL:

- **Admin Dashboard:** [http://app.swma.com/admin](http://app.swma.com/admin)
- **Agent Dashboard:** [http://app.swma.com/agent](http://app.swma.com/agent)
- **Officer Dashboard:** [http://app.swma.com/officer](http://app.swma.com/officer)

For example, to view the admin dashboard, go to `http://app.swma.com/admin` in your browser.

---

**Troubleshooting:**
- If you get a permission error running the batch file, right-click and choose "Run as Administrator".
- If Apache fails to start, check for port conflicts (e.g., Skype, IIS).
- Ensure your hosts file and Apache config were updated by the script.

---

For further help, see the comments in `create-vhost.bat` or contact the project maintainer.

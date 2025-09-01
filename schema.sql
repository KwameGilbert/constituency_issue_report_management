-- Core User and Admin Tables
CREATE TABLE web_admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    role VARCHAR(50),
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    profile_image TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE admins_activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT,
    action TEXT,
    details TEXT,
    ip_address VARCHAR(100),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES web_admins(id) ON DELETE CASCADE
);

CREATE TABLE blog_posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    slug VARCHAR(255),
    excerpt TEXT,
    content TEXT,
    image_url TEXT,
    featured BOOLEAN DEFAULT FALSE,
    author_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES web_admins(id) ON DELETE SET NULL
);

CREATE TABLE blog_comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT,
    content TEXT,
    author_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE
);

-- Location-related Tables
CREATE TABLE communities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE suburbs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    community_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE CASCADE
);

CREATE TABLE smaller_communities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE cottages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    smaller_community_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (smaller_community_id) REFERENCES smaller_communities(id) ON DELETE CASCADE
);

-- Primary User and Constituency Tables
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    email VARCHAR(100),
    password VARCHAR(255),
    role ENUM('mp', 'mce', 'pa', 'officer', 'agent', 'admin'),
    phone VARCHAR(50),
    profile_image TEXT,
    main_community_id INT,
    smaller_community_id INT,
    suburb_id INT,
    cottage_id INT,
    department VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    password_reset_required TINYINT(1) DEFAULT 0,
    FOREIGN KEY (main_community_id) REFERENCES communities(id) ON DELETE SET NULL,
    FOREIGN KEY (smaller_community_id) REFERENCES smaller_communities(id) ON DELETE SET NULL,
    FOREIGN KEY (suburb_id) REFERENCES suburbs(id) ON DELETE SET NULL,
    FOREIGN KEY (cottage_id) REFERENCES cottages(id) ON DELETE SET NULL
);

CREATE TABLE constituents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    phone VARCHAR(50),
    location VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Issue & Project-related Tables
CREATE TABLE issue_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE issue_sectors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE issue_subsectors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    sector_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sector_id) REFERENCES issue_sectors(id) ON DELETE CASCADE
);

CREATE TABLE issues (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    description TEXT,
    location_description VARCHAR(255),
    main_community_id INT,
    smaller_community_id INT,
    suburb_id INT,
    cottage_id INT,
    category_id INT,
    sector_id INT,
    subsector_id INT,
    type ENUM('personal', 'community'),
    severity ENUM('low', 'medium', 'high'),
    status ENUM('pending', 'reviewed', 'approved', 'rejected', 'resolved'),
    status_description TEXT,
    agent_id INT,
    officer_id INT,
    constituent_id INT,
    people_affected INT,
    budget_estimate DECIMAL(12,2),
    resolution_notes TEXT,
    additional_notes TEXT,
    public_visibility BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (main_community_id) REFERENCES communities(id) ON DELETE SET NULL,
    FOREIGN KEY (smaller_community_id) REFERENCES smaller_communities(id) ON DELETE SET NULL,
    FOREIGN KEY (suburb_id) REFERENCES suburbs(id) ON DELETE SET NULL,
    FOREIGN KEY (cottage_id) REFERENCES cottages(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES issue_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (sector_id) REFERENCES issue_sectors(id) ON DELETE SET NULL,
    FOREIGN KEY (subsector_id) REFERENCES issue_subsectors(id) ON DELETE SET NULL,
    FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (officer_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (constituent_id) REFERENCES constituents(id) ON DELETE SET NULL
);

CREATE TABLE issue_updates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    issue_id INT,
    user_id INT,
    action TEXT,
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE issue_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    issue_id INT,
    update_id INT,
    file_name VARCHAR(255),
    file_path VARCHAR(255),
    file_type VARCHAR(50),
    file_size INT,
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE CASCADE,
    FOREIGN KEY (update_id) REFERENCES issue_updates(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Project-related Tables
CREATE TABLE projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    description TEXT,
    location VARCHAR(255),
    type ENUM('infrastructure', 'service', 'initiative'),
    sector_id INT,
    subsector_id INT,
    budget DECIMAL(12,2),
    contractor_name VARCHAR(255),
    status ENUM('planned', 'ongoing', 'completed', 'abandoned'),
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    is_public BOOLEAN DEFAULT FALSE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sector_id) REFERENCES issue_sectors(id) ON DELETE SET NULL,
    FOREIGN KEY (subsector_id) REFERENCES issue_subsectors(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE project_updates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT,
    description TEXT,
    progress_percent INT,
    status ENUM('on_track', 'delayed', 'completed'),
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE project_photos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT,
    image_url TEXT,
    caption VARCHAR(255),
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE project_history_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT,
    user_id INT,
    action TEXT,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Employment & Idea Bank
CREATE TABLE employment_opportunities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    description TEXT,
    requirements TEXT,
    location VARCHAR(255),
    deadline DATE,
    posted_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE employment_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    opportunity_id INT,
    name VARCHAR(255),
    email VARCHAR(100),
    phone VARCHAR(50),
    resume_url TEXT,
    status ENUM('pending', 'reviewed', 'accepted', 'rejected') DEFAULT 'pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (opportunity_id) REFERENCES employment_opportunities(id) ON DELETE CASCADE
);

CREATE TABLE idea_bank (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    description TEXT,
    submitted_by INT,
    reviewed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submitted_by) REFERENCES constituents(id) ON DELETE SET NULL
);

-- Other utility tables
CREATE TABLE carousel_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    image_url TEXT,
    link TEXT,
    position INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    email VARCHAR(100),
    phone VARCHAR(50),
    subject VARCHAR(255),
    message TEXT,
    status ENUM('pending', 'reviewed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    message TEXT,
    type VARCHAR(50),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE exports_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    export_type ENUM('issue', 'project', 'employment'),
    file_url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action TEXT,
    ip_address VARCHAR(100),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE activity_logs(
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action TEXT,
    details TEXT,
    ip_address VARCHAR(100),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
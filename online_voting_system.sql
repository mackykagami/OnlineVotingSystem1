-- Online Voting System Database Schema
-- Create database
CREATE DATABASE online_voting_system;
USE online_voting_system;

-- 1. Users table - stores voter information
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    date_of_birth DATE,
    voter_id VARCHAR(20) UNIQUE NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    verification_token VARCHAR(64) DEFAULT NULL,
    password_reset_token VARCHAR(64) DEFAULT NULL,
    password_reset_expires DATETIME DEFAULT NULL,
    otp_code VARCHAR(10) DEFAULT NULL,
    otp_expires DATETIME DEFAULT NULL
);

-- 2. Elections table - stores election information
CREATE TABLE elections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    status ENUM('upcoming', 'active', 'completed') DEFAULT 'upcoming',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 3. Candidates table - stores candidate information
CREATE TABLE candidates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    election_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    party VARCHAR(100),
    biography TEXT,
    photo VARCHAR(255),
    manifesto TEXT,
    position INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE
);

-- 4. Votes table - stores voting records
CREATE TABLE votes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    election_id INT NOT NULL,
    candidate_id INT NOT NULL,
    vote_hash VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_election (user_id, election_id)
);

-- 5. Vote results view - for displaying results
CREATE VIEW vote_results AS
SELECT 
    e.id as election_id,
    e.title as election_title,
    c.id as candidate_id,
    c.name as candidate_name,
    c.party as candidate_party,
    COUNT(v.id) as vote_count,
    ROUND((COUNT(v.id) * 100.0 / (
        SELECT COUNT(*) 
        FROM votes v2 
        WHERE v2.election_id = e.id
    )), 2) as vote_percentage
FROM elections e
LEFT JOIN candidates c ON e.id = c.election_id
LEFT JOIN votes v ON c.id = v.candidate_id
GROUP BY e.id, c.id
ORDER BY e.id, vote_count DESC;

-- 6. Audit log table - for tracking system activities
CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 7. System settings table
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, full_name, voter_id, is_verified, is_admin) 
VALUES ('admin', 'admin@votingsystem.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'ADMIN001', TRUE, TRUE);

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('site_title', 'Online Voting System', 'Website title'),
('max_elections_per_user', '1', 'Maximum concurrent elections a user can participate in'),
('vote_verification_required', '1', 'Whether vote verification is required'),
('registration_enabled', '1', 'Whether new user registration is enabled');

-- Create indexes for better performance
CREATE INDEX idx_votes_election_id ON votes(election_id);
CREATE INDEX idx_votes_user_id ON votes(user_id);
CREATE INDEX idx_candidates_election_id ON candidates(election_id);
CREATE INDEX idx_elections_status ON elections(status);
CREATE INDEX idx_users_voter_id ON users(voter_id);
CREATE INDEX idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at);
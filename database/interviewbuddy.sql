-- Interview Buddy Database Schema
-- Import this file in phpMyAdmin (create DB `interviewbuddy` first, or run the CREATE DATABASE line below)

CREATE DATABASE IF NOT EXISTS interviewbuddy;
USE interviewbuddy;

-- ---------------------------------------------------------------
-- USERS (students)
-- ---------------------------------------------------------------
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(100) DEFAULT NULL,          -- target job role e.g. "Software Engineer"
    college VARCHAR(150) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- ADMIN
-- ---------------------------------------------------------------
CREATE TABLE admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- COMPANIES
-- ---------------------------------------------------------------
CREATE TABLE companies (
    company_id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(150) NOT NULL UNIQUE,
    website VARCHAR(255) DEFAULT NULL,
    about TEXT DEFAULT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- SUBJECTS
-- ---------------------------------------------------------------
CREATE TABLE subjects (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- TOPICS
-- ---------------------------------------------------------------
CREATE TABLE topics (
    topic_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    topic_name VARCHAR(150) NOT NULL,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- INTERVIEW EXPERIENCES (raw submissions from students)
-- ---------------------------------------------------------------
CREATE TABLE interview_experiences (
    exp_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_id INT NOT NULL,
    role VARCHAR(150) NOT NULL,
    rounds INT DEFAULT 1,
    tips TEXT DEFAULT NULL,
    difficulty ENUM('Easy','Medium','Hard') DEFAULT 'Medium',
    status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- QUESTIONS (individual questions inside an experience)
-- ---------------------------------------------------------------
CREATE TABLE questions (
    question_id INT AUTO_INCREMENT PRIMARY KEY,
    exp_id INT NOT NULL,
    subject_id INT DEFAULT NULL,
    topic_id INT DEFAULT NULL,
    question TEXT NOT NULL,
    type ENUM('Technical','HR','Aptitude','Coding') DEFAULT 'Technical',
    difficulty ENUM('Easy','Medium','Hard') DEFAULT 'Medium',
    status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exp_id) REFERENCES interview_experiences(exp_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE SET NULL,
    FOREIGN KEY (topic_id) REFERENCES topics(topic_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- ANSWERS (admin-approved ideal answers)
-- ---------------------------------------------------------------
CREATE TABLE answers (
    answer_id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL UNIQUE,
    ideal_answer TEXT NOT NULL,
    approved_by INT DEFAULT NULL,
    approved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES admin(admin_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- MOCK TESTS (a mock interview session)
-- ---------------------------------------------------------------
CREATE TABLE mock_tests (
    mock_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_id INT NOT NULL,
    role VARCHAR(150) DEFAULT NULL,
    total_questions INT DEFAULT 0,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL DEFAULT NULL,
    status ENUM('In Progress','Completed') DEFAULT 'In Progress',
    score DECIMAL(5,2) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- RESPONSES (student answers given during a mock test)
-- ---------------------------------------------------------------
CREATE TABLE responses (
    response_id INT AUTO_INCREMENT PRIMARY KEY,
    mock_id INT NOT NULL,
    question_id INT NOT NULL,
    student_answer TEXT DEFAULT NULL,
    score DECIMAL(5,2) DEFAULT NULL,
    feedback TEXT DEFAULT NULL,
    FOREIGN KEY (mock_id) REFERENCES mock_tests(mock_id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- RESULTS (aggregated performance report per mock test)
-- ---------------------------------------------------------------
CREATE TABLE results (
    result_id INT AUTO_INCREMENT PRIMARY KEY,
    mock_id INT NOT NULL UNIQUE,
    overall_score DECIMAL(5,2) DEFAULT NULL,
    subject_wise_score TEXT DEFAULT NULL,   -- JSON string e.g. {"DBMS":80,"OS":60}
    strengths TEXT DEFAULT NULL,
    weaknesses TEXT DEFAULT NULL,
    suggestions TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mock_id) REFERENCES mock_tests(mock_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- BOOKMARKS
-- ---------------------------------------------------------------
CREATE TABLE bookmarks (
    bookmark_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    question_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_bookmark (user_id, question_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- SEED DATA
-- ---------------------------------------------------------------

-- Default admin login -> email: admin@interviewbuddy.com | password: admin123
INSERT INTO admin (name, email, password) VALUES
('Super Admin', 'admin@interviewbuddy.com', '$2b$12$qzH7OnIFdkztTL4BNgbM4ela4VRFP3OrLtm6hgm2WGuXm7DSGwISC');
-- If this password ever stops working (e.g. after editing), open
-- database/generate_admin_hash.php in your browser once to print a fresh
-- bcrypt hash for any password, then UPDATE admin SET password='<hash>' ...

INSERT INTO companies (company_name, website, about) VALUES
('TCS', 'https://www.tcs.com', 'Tata Consultancy Services'),
('Infosys', 'https://www.infosys.com', 'Infosys Limited'),
('Wipro', 'https://www.wipro.com', 'Wipro Limited'),
('Accenture', 'https://www.accenture.com', 'Accenture plc');

INSERT INTO subjects (subject_name) VALUES
('DBMS'), ('Operating System'), ('Computer Networks'), ('OOP'), ('DSA'), ('Aptitude'), ('HR');

INSERT INTO topics (subject_id, topic_name) VALUES
(1, 'SQL'), (1, 'Joins'), (1, 'Normalization'), (1, 'Transactions'),
(2, 'Process Management'), (2, 'Deadlock'), (2, 'Memory Management'),
(3, 'TCP/IP'), (3, 'OSI Model'),
(4, 'Inheritance'), (4, 'Polymorphism'),
(5, 'Arrays'), (5, 'Linked List'), (5, 'Trees'), (5, 'Sorting'),
(6, 'Quantitative'), (6, 'Logical Reasoning'),
(7, 'Behavioral');

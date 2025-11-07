USE <yourDBNAME>;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(70) NOT NULL,
    account_status ENUM('active', 'suspended', 'deleted') DEFAULT 'active',
    role ENUM('user', 'admin') DEFAULT 'user',
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE support_tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    subject VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open', 'in_progress', 'resolved') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE profile (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    role ENUM('Studente', 'Insegnante', 'Genitore', 'Tutor', 'Ricercatore', 'Coordinatore', 'Bibliotecario', 'Amministrativo', 'Ospite', 'Altro') DEFAULT 'Studente',
    bio TEXT,
    avatar_url VARCHAR(255),
    phone VARCHAR(20),
    country VARCHAR(100),
    date_of_birth DATE,
    timezone VARCHAR(50) DEFAULT 'Europe/Rome',
    language VARCHAR(5) DEFAULT 'it',
    theme ENUM('light', 'dark', 'auto') DEFAULT 'light',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE document (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(50),
    file_size BIGINT UNSIGNED,
    hash_sha256 CHAR(64),
    mime_type VARCHAR(100),
    status ENUM('uploaded', 'scanned', 'safe', 'infected', 'processed', 'rejected') DEFAULT 'uploaded',
    threat_info TEXT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE article (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    summary TEXT,
    content LONGTEXT NOT NULL,
    category ENUM('informatica', 'logica', 'matematica', 'filosofia', 'altro') DEFAULT 'altro',
    visibility ENUM('public', 'private', 'draft') DEFAULT 'public',
    views INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE quiz (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    document_id INT NOT NULL,
    title VARCHAR(255),
    num_questions INT NOT NULL,
    difficulty ENUM('facile', 'medio', 'difficile') NOT NULL,
    quiz_type VARCHAR(50) NOT NULL,
    is_timed BOOLEAN DEFAULT FALSE,
    time_limit INT,
    quiz_data MEDIUMTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (document_id) REFERENCES document(id) ON DELETE CASCADE
);

CREATE TABLE quiz_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    quiz_id INT NOT NULL,
    score DECIMAL(5,2),
    time_taken INT,
    answers TEXT,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quiz(id) ON DELETE CASCADE
);

CREATE TRIGGER update_document_status
BEFORE UPDATE ON document
FOR EACH ROW
BEGIN
    IF NEW.threat_info IS NOT NULL AND NEW.status = 'scanned' THEN
        SET NEW.status = CASE
            WHEN NEW.threat_info = '' THEN 'safe'
            ELSE 'infected'
        END;
    END IF;
END;
//
DELIMITER ;

DELIMITER //
CREATE TRIGGER update_article_timestamp
BEFORE UPDATE ON article
FOR EACH ROW
BEGIN
    IF NEW.content <> OLD.content OR NEW.title <> OLD.title OR NEW.summary <> OLD.summary THEN
        SET NEW.updated_at = NOW();
    END IF;
END;
//
DELIMITER ;

DELIMITER //

CREATE PROCEDURE UpdateUserProfile(
    IN p_user_id INT,
    IN p_bio TEXT,
    IN p_phone VARCHAR(20),
    IN p_country VARCHAR(100),
    IN p_timezone VARCHAR(50),
    IN p_language VARCHAR(5),
    IN p_theme ENUM('light','dark','auto'),
    IN p_role ENUM('Studente','Insegnante','Genitore','Tutor','Ricercatore','Coordinatore','Bibliotecario','Amministrativo','Ospite','Altro'),
    IN p_date_of_birth DATE
)
BEGIN
    UPDATE profile
    SET bio = p_bio,
        phone = p_phone,
        country = p_country,
        timezone = p_timezone,
        language = p_language,
        theme = p_theme,
        role = p_role,
        date_of_birth = p_date_of_birth,
        updated_at = CURRENT_TIMESTAMP
    WHERE user_id = p_user_id;
END;
//

DELIMITER ;
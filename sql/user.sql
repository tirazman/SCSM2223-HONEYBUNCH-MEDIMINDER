USE mediminder;

DROP TABLE IF EXISTS User;
CREATE TABLE User (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('patient', 'caregiver', 'admin') NOT NULL,
    dob DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO User (name, email, password_hash, role, dob) VALUES 
    ('Encik Ahmad bin Ali', 'ahmad58@gmail.com', 'hashed_password_1', 'patient', '1958-04-12'),
    ('Puan Haida binti Kamal', 'haidakamal@gmail.com', 'hashed_password_2', 'patient', '1945-09-23');

CREATE TABLE IF NOT EXISTS PatientCaregiver (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    caregiver_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (caregiver_id) REFERENCES User(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES User(id) ON DELETE CASCADE
);

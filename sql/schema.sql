CREATE DATABASE IF NOT EXISTS mediminder 
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; 
USE mediminder;  
  
-- 1. Create the Medications table
CREATE TABLE IF NOT EXISTS Medications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    form VARCHAR(50),
    strength VARCHAR(50),
    default_unit VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB; 
  
-- 2. Insert the initial seed data
INSERT INTO Medications (name, form, strength, default_unit) VALUES 
  ('Ibuprofen', 'Tablet', '200mg', 'tablet'),
  ('Paracetamol', 'Suspension', '125mg/5ml', 'teaspoon'),
  ('Amoxicillin', 'Capsule', '500mg', 'capsule'),
  ('Lisinopril', 'Tablet', '10mg', 'tablet'),
  ('Metformin', 'Tablet', '500mg', 'pill'),
  ('Lysin', 'Syrup', '5ml', 'tsp'),
  ('Amlodipine', 'Capsule', '10mg', 'capsule');

-- 3. Create the Prescription table
CREATE TABLE IF NOT EXISTS Prescription (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    medication_id INT,
    drug_name VARCHAR(100) NOT NULL,
    dose VARCHAR(50) NOT NULL,
    frequency VARCHAR(50) NOT NULL,
    start_date DATE,
    end_date DATE,
    FOREIGN KEY (patient_id) REFERENCES User(id) ON DELETE CASCADE,
    FOREIGN KEY (medication_id) REFERENCES Medications(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Create the DoseLog table
CREATE TABLE IF NOT EXISTS DoseLog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT,
    scheduled_at DATETIME NOT NULL,
    taken_at DATETIME NULL,
    status ENUM('scheduled', 'taken', 'skipped') DEFAULT 'scheduled',
    FOREIGN KEY (prescription_id) REFERENCES Prescription(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. Create the Notification table
CREATE TABLE IF NOT EXISTS Notification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type ENUM('reminder', 'alert', 'info') NOT NULL,
    body TEXT NOT NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES User(id) ON DELETE CASCADE
) ENGINE=InnoDB;
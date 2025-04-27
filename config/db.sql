-- CREATE DATABASE IF NOT EXISTS gestion_etudiants;
-- USE gestion_etudiants;

-- CREATE TABLE users (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     name VARCHAR(100) NOT NULL,
--     email VARCHAR(100) NOT NULL UNIQUE,
--     password VARCHAR(255) NOT NULL,
--     role ENUM('admin', 'etudiant') NOT NULL,
--     created_at date
-- );

-- CREATE TABLE filiere (
--     id_filiere INT AUTO_INCREMENT PRIMARY KEY,
--     filiere VARCHAR(100) NOT NULL
-- );

-- CREATE TABLE etudiants (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     user_id INT NOT NULL,
--     nom_complet VARCHAR(100) NOT NULL,
--     date_naissance DATE NOT NULL,
--     id_filiere INT NOT NULL,
--     is_validated BOOLEAN DEFAULT FALSE,
--     created_at date,
--     FOREIGN KEY (user_id) REFERENCES users(id),
--     FOREIGN KEY (id_filiere) REFERENCES filiere(id_filiere)
-- );

-- -- Insertion des utilisateurs (admin et étudiants)
-- INSERT INTO users (name, email, password, role, created_at) VALUES
-- ('Admin Principal', 'admin123@gmail.com', 'admin123', 'admin', CURDATE());

-- -- Insertion des filières
-- INSERT INTO filiere (filiere) VALUES
-- ('Développement Digital'),
-- ('UI/UX'),
-- ('Infrastructure Digital'),
-- ('Intéligence Artificielle');

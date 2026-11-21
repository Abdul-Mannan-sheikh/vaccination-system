-- =====================================================================
-- VaxTrack - Vaccination Management System
-- Database schema + seed data
-- Import via phpMyAdmin (Import tab) or:  mysql -u root < vaxtrack.sql
-- =====================================================================

CREATE DATABASE IF NOT EXISTS vaxtrack
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vaxtrack;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS notifications, bookings, child_vaccinations, vaccines, children, hospitals, users;
SET FOREIGN_KEY_CHECKS = 1;

-- users: one login table for all three roles (admin / parent / hospital)
CREATE TABLE users (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100)  NOT NULL,
  email       VARCHAR(150)  NOT NULL UNIQUE,
  password    VARCHAR(255)  NOT NULL,              -- bcrypt hash, never plain text
  phone       VARCHAR(20)   NULL,
  address     VARCHAR(255)  NULL,
  city        VARCHAR(80)   NULL,
  role        ENUM('admin','parent','hospital') NOT NULL,
  status      ENUM('active','blocked') NOT NULL DEFAULT 'active',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_users_role (role)
) ENGINE=InnoDB;

-- hospitals: hospital profile (linked to its login in users).
-- user_id is NULL when admin adds a hospital that has no login yet.
CREATE TABLE hospitals (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NULL UNIQUE,
  name        VARCHAR(150) NOT NULL,
  address     VARCHAR(255) NOT NULL,
  city        VARCHAR(80)  NOT NULL,
  phone       VARCHAR(20)  NULL,
  latitude    DECIMAL(10,7) NULL,
  longitude   DECIMAL(10,7) NULL,
  status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_hospital_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_hospitals_city (city)
) ENGINE=InnoDB;

-- children: belong to a parent
CREATE TABLE children (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id   INT UNSIGNED NOT NULL,
  name        VARCHAR(100) NOT NULL,
  dob         DATE NOT NULL,
  gender      ENUM('male','female','other') NOT NULL,
  blood_group VARCHAR(5) NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_child_parent FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_children_parent (parent_id)
) ENGINE=InnoDB;

-- vaccines: master schedule (age_days = recommended age in days from birth)
-- is_available lets admin mark a vaccine available / unavailable
CREATE TABLE vaccines (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  protects_from VARCHAR(150) NOT NULL,
  age_days      INT UNSIGNED NOT NULL,
  is_available  TINYINT(1) NOT NULL DEFAULT 1,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_vaccines_age (age_days)
) ENGINE=InnoDB;

-- child_vaccinations: personal schedule generated for each child
CREATE TABLE child_vaccinations (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  child_id       INT UNSIGNED NOT NULL,
  vaccine_id     INT UNSIGNED NOT NULL,
  due_date       DATE NOT NULL,
  status         ENUM('pending','booked','vaccinated') NOT NULL DEFAULT 'pending',
  vaccinated_on  DATE NULL,
  hospital_id    INT UNSIGNED NULL,
  CONSTRAINT fk_cv_child    FOREIGN KEY (child_id)    REFERENCES children(id)  ON DELETE CASCADE,
  CONSTRAINT fk_cv_vaccine  FOREIGN KEY (vaccine_id)  REFERENCES vaccines(id)  ON DELETE CASCADE,
  CONSTRAINT fk_cv_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE SET NULL,
  UNIQUE KEY uq_child_vaccine (child_id, vaccine_id),
  INDEX idx_cv_due (due_date)
) ENGINE=InnoDB;

-- bookings: parent request -> admin approves/rejects -> hospital completes
CREATE TABLE bookings (
  id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  child_vaccination_id  INT UNSIGNED NOT NULL,
  parent_id             INT UNSIGNED NOT NULL,
  hospital_id           INT UNSIGNED NOT NULL,
  preferred_date        DATE NOT NULL,
  preferred_time        TIME NOT NULL,
  status                ENUM('pending','approved','rejected','completed','cancelled') NOT NULL DEFAULT 'pending',
  admin_remark          VARCHAR(255) NULL,
  created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_bk_cv       FOREIGN KEY (child_vaccination_id) REFERENCES child_vaccinations(id) ON DELETE CASCADE,
  CONSTRAINT fk_bk_parent   FOREIGN KEY (parent_id)   REFERENCES users(id)     ON DELETE CASCADE,
  CONSTRAINT fk_bk_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
  INDEX idx_bk_status (status),
  INDEX idx_bk_date (preferred_date)
) ENGINE=InnoDB;

-- notifications: dashboard alerts for any user
CREATE TABLE notifications (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  title       VARCHAR(120) NOT NULL,
  message     VARCHAR(255) NOT NULL,
  is_read     TINYINT(1) NOT NULL DEFAULT 0,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_notif_user (user_id, is_read)
) ENGINE=InnoDB;

-- ===================== SEED DATA =====================

-- Default admin  ->  email: admin@vaxtrack.local   password: Admin@123
INSERT INTO users (name, email, password, role)
VALUES ('System Admin', 'admin@vaxtrack.local', '$2y$10$OCbV14eY1WzfmBNwppMUk.VpxS9cIPut25uY64qDKlmZYNp58D8i2', 'admin');

-- Demo vaccination schedule (EPI-style; age in days from birth)
INSERT INTO vaccines (name, protects_from, age_days) VALUES
('BCG',                     'Tuberculosis', 0),
('Hepatitis B (Birth)',     'Hepatitis B', 0),
('OPV-0',                   'Polio', 0),
('Pentavalent-1',           'Diphtheria, Tetanus, Pertussis, Hib, Hepatitis B', 42),
('OPV-1',                   'Polio', 42),
('PCV-1',                   'Pneumococcal disease', 42),
('Rotavirus-1',             'Rotavirus diarrhoea', 42),
('Pentavalent-2',           'Diphtheria, Tetanus, Pertussis, Hib, Hepatitis B', 70),
('OPV-2',                   'Polio', 70),
('PCV-2',                   'Pneumococcal disease', 70),
('Rotavirus-2',             'Rotavirus diarrhoea', 70),
('Pentavalent-3',           'Diphtheria, Tetanus, Pertussis, Hib, Hepatitis B', 98),
('OPV-3',                   'Polio', 98),
('PCV-3',                   'Pneumococcal disease', 98),
('IPV',                     'Polio (injectable)', 98),
('Measles / MR-1',          'Measles, Rubella', 270),
('Typhoid Conjugate (TCV)', 'Typhoid fever', 270),
('Measles / MR-2',          'Measles, Rubella', 450);

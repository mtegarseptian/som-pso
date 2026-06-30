CREATE DATABASE IF NOT EXISTS som_pso_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE som_pso_db;

-- Tabel dataset utama
CREATE TABLE IF NOT EXISTS crop_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    N FLOAT NOT NULL,
    P FLOAT NOT NULL,
    K FLOAT NOT NULL,
    temperature FLOAT NOT NULL,
    humidity FLOAT NOT NULL,
    ph FLOAT NOT NULL,
    rainfall FLOAT NOT NULL,
    label VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel hasil normalisasi
CREATE TABLE IF NOT EXISTS crop_data_normalized (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_id INT NOT NULL,
    N_norm FLOAT NOT NULL,
    P_norm FLOAT NOT NULL,
    K_norm FLOAT NOT NULL,
    temperature_norm FLOAT NOT NULL,
    humidity_norm FLOAT NOT NULL,
    ph_norm FLOAT NOT NULL,
    rainfall_norm FLOAT NOT NULL,
    FOREIGN KEY (original_id) REFERENCES crop_data(id)
);

-- Tabel hasil clustering SOM standar
CREATE TABLE IF NOT EXISTS som_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_id INT NOT NULL,
    cluster_id INT NOT NULL,
    productivity_label VARCHAR(20) NOT NULL,
    FOREIGN KEY (data_id) REFERENCES crop_data(id)
);

-- Tabel hasil clustering Hybrid SOM-PSO
CREATE TABLE IF NOT EXISTS hybrid_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_id INT NOT NULL,
    cluster_id INT NOT NULL,
    productivity_label VARCHAR(20) NOT NULL,
    FOREIGN KEY (data_id) REFERENCES crop_data(id)
);

-- Tabel parameter SOM standar
CREATE TABLE IF NOT EXISTS som_parameters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    learning_rate FLOAT NOT NULL,
    sigma FLOAT NOT NULL,
    epoch INT NOT NULL,
    grid_x INT NOT NULL,
    grid_y INT NOT NULL,
    silhouette_score FLOAT,
    dbi_score FLOAT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel parameter PSO hasil optimasi
CREATE TABLE IF NOT EXISTS pso_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    iteration INT NOT NULL,
    best_learning_rate FLOAT NOT NULL,
    best_sigma FLOAT NOT NULL,
    best_epoch INT NOT NULL,
    best_fitness FLOAT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel evaluasi perbandingan
CREATE TABLE IF NOT EXISTS evaluation_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    method VARCHAR(50) NOT NULL,
    silhouette_score FLOAT NOT NULL,
    dbi_score FLOAT NOT NULL,
    cluster_low INT NOT NULL,
    cluster_medium INT NOT NULL,
    cluster_high INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert parameter default SOM standar
INSERT INTO som_parameters (learning_rate, sigma, epoch, grid_x, grid_y, silhouette_score, dbi_score)
VALUES (0.5, 1.0, 100, 5, 5, 0.1510, 2.1715);

-- Insert hasil PSO
INSERT INTO pso_results (iteration, best_learning_rate, best_sigma, best_epoch, best_fitness)
VALUES (30, 0.0116, 2.4497, 1964, 0.2623);

-- Insert evaluasi perbandingan
INSERT INTO evaluation_results (method, silhouette_score, dbi_score, cluster_low, cluster_medium, cluster_high)
VALUES 
('SOM Standar', 0.1510, 2.1715, 720, 800, 680),
('Hybrid SOM-PSO', 0.2623, 1.3998, 710, 820, 670);

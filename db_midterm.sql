Create database db_midterm;
use db_midterm;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    password VARCHAR(255)
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50)
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(100),
    description TEXT,
    price DECIMAL(10,2),
    category_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
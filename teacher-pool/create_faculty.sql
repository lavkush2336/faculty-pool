CREATE DATABASE IF NOT EXISTS faculty_pool CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE faculty_pool;

CREATE TABLE IF NOT EXISTS faculty_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sno VARCHAR(10),
  name VARCHAR(255),
  email VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO faculty_members (sno, name, email) VALUES
('1','Dr.shalini batra','sbatra@thapar.edu'),
('2','Dr. Rinkle Rani','raggarwal@thapar.edu'),
('3','Dr. Seema Bawa','seema@thapar.edu'),
('4','Dr. Sushma Jain','sjain@thapar.edu');

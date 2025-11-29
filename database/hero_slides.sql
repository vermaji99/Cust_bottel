-- Hero Slides Table for Dynamic Hero Section
CREATE TABLE IF NOT EXISTS hero_slides (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NULL,
  image VARCHAR(255) NOT NULL,
  display_order INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_active_order (is_active, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default hero slides (optional)
-- INSERT INTO hero_slides (title, image, display_order, is_active) VALUES
-- ('Hero Slide 1', 'hero1.png', 1, 1),
-- ('Hero Slide 2', 'hero2.png', 2, 1),
-- ('Hero Slide 3', 'hero3.png', 3, 1);


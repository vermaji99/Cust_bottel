-- Create Products Table
-- Run this SQL in your phpMyAdmin or MySQL console to create the missing products table

CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT 100,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add some sample products (optional)
INSERT INTO `products` (`name`, `slug`, `description`, `price`, `category`, `image`, `stock`, `is_active`) VALUES
('Premium Water Bottle - Blue', 'premium-water-bottle-blue', 'High quality custom water bottle with premium finish', 599.00, 'Premium', 'bottle-blue.jpg', 50, 1),
('Eco-Friendly Bottle', 'eco-friendly-bottle', 'Environmentally friendly water bottle', 499.00, 'Eco', 'bottle-blue.jpg', 75, 1),
('Custom Branded Bottle', 'custom-branded-bottle', 'Perfect for restaurants and events', 699.00, 'Restaurant', 'custom-bottle.jpg', 100, 1);


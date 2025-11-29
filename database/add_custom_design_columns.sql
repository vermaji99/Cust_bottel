-- Add custom design columns to orders table
-- Run this SQL to add support for custom design image and description

ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS custom_design_image VARCHAR(255) NULL AFTER shipping_address,
ADD COLUMN IF NOT EXISTS custom_design_description TEXT NULL AFTER custom_design_image;


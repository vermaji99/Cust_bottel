-- Add OTP Verification Table
-- Run this SQL to add OTP functionality to existing database

CREATE TABLE IF NOT EXISTS otp_verifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  email VARCHAR(150) NOT NULL,
  otp_code VARCHAR(6) NOT NULL,
  purpose ENUM('email_verification','login') NOT NULL DEFAULT 'email_verification',
  attempts INT DEFAULT 0,
  verified_at DATETIME NULL,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email_otp (email, otp_code, purpose),
  INDEX idx_user_purpose (user_id, purpose),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


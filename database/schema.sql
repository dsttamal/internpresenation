-- Database Schema for PHP Form Builder Backend
-- Execute these SQL statements to create the required tables

-- Users table
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin','super_admin','form_manager','payment_approver','submission_viewer','submission_editor','notification_manager') DEFAULT 'user',
  `permissions` json DEFAULT NULL,
  `isActive` tinyint(1) DEFAULT 1,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Forms table
CREATE TABLE `forms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `fields` json NOT NULL,
  `isActive` tinyint(1) DEFAULT 1,
  `allowEditing` tinyint(1) DEFAULT 1,
  `createdBy` int(11) NOT NULL,
  `settings` json DEFAULT NULL,
  `submissionCount` int(11) DEFAULT 0,
  `analytics` json DEFAULT NULL,
  `customUrl` varchar(255) DEFAULT NULL,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customUrl` (`customUrl`),
  KEY `forms_created_at` (`createdAt`),
  KEY `forms_created_by_is_active` (`createdBy`,`isActive`),
  KEY `forms_custom_url` (`customUrl`),
  CONSTRAINT `forms_ibfk_1` FOREIGN KEY (`createdBy`) REFERENCES `users` (`id`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Submissions table
CREATE TABLE `submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniqueId` varchar(32) NOT NULL,
  `editCode` varchar(6) NOT NULL,
  `formId` int(11) NOT NULL,
  `data` json NOT NULL,
  `submitterInfo` json DEFAULT NULL,
  `paymentInfo` json DEFAULT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `files` json DEFAULT NULL,
  `adminNotes` text DEFAULT NULL,
  `editHistory` json DEFAULT '[]',
  `paymentMethod` enum('card','stripe','bkash','bank_transfer') DEFAULT 'card',
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniqueId` (`uniqueId`),
  KEY `submissions_form_id_created_at` (`formId`,`createdAt`),
  KEY `submissions_status` (`status`),
  KEY `submissions_unique_id` (`uniqueId`),
  KEY `submissions_edit_code` (`editCode`),
  CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`formId`) REFERENCES `forms` (`id`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- bKash tokens table
CREATE TABLE `bkash_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service` varchar(50) NOT NULL DEFAULT 'payment',
  `authToken` text DEFAULT NULL,
  `refreshToken` text DEFAULT NULL,
  `tokenExpiresAt` datetime DEFAULT NULL,
  `lastTokenCall` datetime DEFAULT NULL,
  `tokenCallCount` int(11) DEFAULT 0,
  `isActive` tinyint(1) DEFAULT 1,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_service` (`service`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings table
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` json NOT NULL,
  `category` varchar(50) DEFAULT 'general',
  `isActive` tinyint(1) DEFAULT 1,
  `createdAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default super admin user (password: admin123)
INSERT INTO `users` (`username`, `email`, `password`, `role`, `isActive`) VALUES
('admin', 'admin@bsmmupathalumni.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 1);

-- Insert default settings
INSERT INTO `settings` (`key`, `value`, `category`) VALUES
('app_name', '"BSMMU Alumni Form Builder"', 'general'),
('app_description', '"Dynamic form builder for BSMMU Alumni"', 'general'),
('payment_methods', '["stripe", "bkash", "bank_transfer"]', 'payment'),
('file_upload_max_size', '10485760', 'upload'),
('allowed_file_types', '["jpg", "jpeg", "png", "pdf", "doc", "docx"]', 'upload');

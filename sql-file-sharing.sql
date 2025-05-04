USE cloudbox;
-- Create shared_files table to track file sharing
CREATE TABLE IF NOT EXISTS `shared_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) NOT NULL,
  `shared_by` int(11) NOT NULL,
  `shared_with` int(11) NOT NULL,
  `permission` enum('read','edit','download') NOT NULL DEFAULT 'read',
  `share_date` datetime NOT NULL,
  `expiration_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `file_id` (`file_id`),
  KEY `shared_by` (`shared_by`),
  KEY `shared_with` (`shared_with`),
  CONSTRAINT `shared_files_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shared_files_ibfk_2` FOREIGN KEY (`shared_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shared_files_ibfk_3` FOREIGN KEY (`shared_with`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add a unique constraint to prevent duplicate shares
ALTER TABLE `shared_files` 
ADD UNIQUE `unique_file_share` (`file_id`, `shared_with`);

ALTER TABLE `emails` ADD `sender` VARCHAR(255) NULL DEFAULT NULL COMMENT 'The Sender email address for bounces and SMTP delivery emails.' AFTER `replyto_name`;
ALTER TABLE `blacklist` ADD KEY `email` (`email`(5));

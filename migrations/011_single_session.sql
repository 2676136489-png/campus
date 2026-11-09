ALTER TABLE `user` ADD COLUMN `active_token` varchar(64) NOT NULL DEFAULT '' AFTER `status`;
ALTER TABLE `user` ADD COLUMN `active_token_time` datetime DEFAULT NULL AFTER `active_token`;
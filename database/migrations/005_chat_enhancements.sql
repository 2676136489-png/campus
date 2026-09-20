-- 聊天增强：撤回状态
ALTER TABLE `message`
  ADD COLUMN `status` enum('normal','recalled') NOT NULL DEFAULT 'normal' AFTER `content`,
  ADD COLUMN `recalledAt` datetime NULL DEFAULT NULL AFTER `status`;
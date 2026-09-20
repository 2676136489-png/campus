-- 聊天图片消息
ALTER TABLE `message`
  ADD COLUMN `image` varchar(255) NOT NULL DEFAULT '' AFTER `content`;
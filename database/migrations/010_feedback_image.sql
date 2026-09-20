-- 反馈图片附件
ALTER TABLE `feedback`
  ADD COLUMN `image` varchar(255) NOT NULL DEFAULT '' AFTER `content`;
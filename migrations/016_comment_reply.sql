-- 评论回复
ALTER TABLE `comment`
  ADD COLUMN `parentPk` INT UNSIGNED NULL DEFAULT NULL,
  ADD KEY `idx_comment_parent` (`parentPk`);
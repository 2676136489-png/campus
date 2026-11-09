-- 动态草稿箱
CREATE TABLE IF NOT EXISTS `dynamic_draft` (
  `pk` int unsigned NOT NULL AUTO_INCREMENT,
  `userPk` int unsigned NOT NULL,
  `content` text NOT NULL,
  `tags` varchar(255) NOT NULL DEFAULT '',
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`pk`),
  UNIQUE KEY `uq_draft_user` (`userPk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
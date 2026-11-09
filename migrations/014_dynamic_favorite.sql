-- 动态收藏
CREATE TABLE IF NOT EXISTS `dynamic_favorite` (
  `pk` int unsigned NOT NULL AUTO_INCREMENT,
  `dynamicPk` int unsigned NOT NULL,
  `userPk` int unsigned NOT NULL,
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pk`),
  UNIQUE KEY `uq_favorite` (`dynamicPk`, `userPk`),
  KEY `idx_favorite_user` (`userPk`, `createTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 校园公告
CREATE TABLE IF NOT EXISTS `announcement` (
  `pk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `adminPk` int(10) unsigned NOT NULL,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `isPublished` tinyint(1) NOT NULL DEFAULT 1,
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`pk`),
  KEY `idx_announcement_published_time` (`isPublished`, `createTime`),
  CONSTRAINT `fk_announcement_admin` FOREIGN KEY (`adminPk`) REFERENCES `user` (`pk`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
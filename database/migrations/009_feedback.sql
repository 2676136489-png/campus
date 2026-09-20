-- 用户反馈
CREATE TABLE IF NOT EXISTS `feedback` (
  `pk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userPk` int(10) unsigned NOT NULL,
  `type` enum('bug','suggestion','complaint','other') NOT NULL DEFAULT 'other',
  `content` varchar(1000) NOT NULL,
  `status` enum('open','processing','closed') NOT NULL DEFAULT 'open',
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`pk`),
  KEY `idx_feedback_user` (`userPk`, `createTime`),
  KEY `idx_feedback_status` (`status`, `createTime`),
  CONSTRAINT `fk_feedback_user` FOREIGN KEY (`userPk`) REFERENCES `user` (`pk`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
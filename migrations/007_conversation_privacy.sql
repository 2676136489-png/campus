-- 会话设置：置顶、免打扰
CREATE TABLE IF NOT EXISTS `conversation_setting` (
  `pk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userPk` int(10) unsigned NOT NULL,
  `otherPk` int(10) unsigned NOT NULL,
  `isPinned` tinyint(1) NOT NULL DEFAULT 0,
  `isMuted` tinyint(1) NOT NULL DEFAULT 0,
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pk`),
  UNIQUE KEY `idx_conversation_pair` (`userPk`, `otherPk`),
  CONSTRAINT `fk_conversation_user` FOREIGN KEY (`userPk`) REFERENCES `user` (`pk`) ON DELETE CASCADE,
  CONSTRAINT `fk_conversation_other` FOREIGN KEY (`otherPk`) REFERENCES `user` (`pk`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 隐私设置
CREATE TABLE IF NOT EXISTS `privacy` (
  `pk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userPk` int(10) unsigned NOT NULL,
  `allowMessages` enum('all','followed') NOT NULL DEFAULT 'all',
  `showProfile` tinyint(1) NOT NULL DEFAULT 1,
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pk`),
  UNIQUE KEY `idx_privacy_user` (`userPk`),
  CONSTRAINT `fk_privacy_user` FOREIGN KEY (`userPk`) REFERENCES `user` (`pk`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
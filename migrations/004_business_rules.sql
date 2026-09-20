-- 行为频控记录
CREATE TABLE IF NOT EXISTS `action_log` (
  `pk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userPk` int(10) unsigned NOT NULL,
  `action` varchar(50) NOT NULL,
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pk`),
  KEY `idx_action_user_time` (`userPk`, `action`, `createTime`),
  CONSTRAINT `fk_action_user` FOREIGN KEY (`userPk`) REFERENCES `user` (`pk`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 拉黑关系
CREATE TABLE IF NOT EXISTS `block` (
  `pk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `blockerPk` int(10) unsigned NOT NULL,
  `blockedPk` int(10) unsigned NOT NULL,
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pk`),
  UNIQUE KEY `idx_block_pair` (`blockerPk`, `blockedPk`),
  KEY `idx_block_blocked` (`blockedPk`),
  CONSTRAINT `fk_block_blocker` FOREIGN KEY (`blockerPk`) REFERENCES `user` (`pk`) ON DELETE CASCADE,
  CONSTRAINT `fk_block_blocked` FOREIGN KEY (`blockedPk`) REFERENCES `user` (`pk`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
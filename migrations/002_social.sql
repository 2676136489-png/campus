-- 关注关系表
CREATE TABLE IF NOT EXISTS `follow` (
  `pk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `followerPk` int(10) unsigned NOT NULL,
  `followingPk` int(10) unsigned NOT NULL,
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pk`),
  UNIQUE KEY `idx_follow_pair` (`followerPk`, `followingPk`),
  KEY `idx_follow_following` (`followingPk`),
  CONSTRAINT `fk_follow_follower` FOREIGN KEY (`followerPk`) REFERENCES `user` (`pk`) ON DELETE CASCADE,
  CONSTRAINT `fk_follow_following` FOREIGN KEY (`followingPk`) REFERENCES `user` (`pk`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 通知偏好表
CREATE TABLE IF NOT EXISTS `notification_pref` (
  `pk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userPk` int(10) unsigned NOT NULL,
  `type` varchar(30) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`pk`),
  UNIQUE KEY `idx_pref_user_type` (`userPk`, `type`),
  CONSTRAINT `fk_pref_user` FOREIGN KEY (`userPk`) REFERENCES `user` (`pk`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
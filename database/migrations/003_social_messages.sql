-- 站内私信
CREATE TABLE IF NOT EXISTS `message` (
  `pk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `senderPk` int(10) unsigned NOT NULL,
  `receiverPk` int(10) unsigned NOT NULL,
  `content` varchar(1000) NOT NULL,
  `isRead` tinyint(1) NOT NULL DEFAULT 0,
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pk`),
  KEY `idx_message_receiver` (`receiverPk`, `isRead`, `createTime`),
  KEY `idx_message_sender` (`senderPk`, `createTime`),
  CONSTRAINT `fk_message_sender` FOREIGN KEY (`senderPk`) REFERENCES `user` (`pk`) ON DELETE CASCADE,
  CONSTRAINT `fk_message_receiver` FOREIGN KEY (`receiverPk`) REFERENCES `user` (`pk`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 动态多图
CREATE TABLE IF NOT EXISTS `dynamic_photo` (
  `pk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dynamicPk` int(10) unsigned NOT NULL,
  `path` varchar(255) NOT NULL,
  `sort` tinyint unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`pk`),
  KEY `idx_dynamic_photo_dynamic` (`dynamicPk`),
  CONSTRAINT `fk_dynamic_photo_dynamic` FOREIGN KEY (`dynamicPk`) REFERENCES `dynamic` (`pk`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 敏感词库
CREATE TABLE IF NOT EXISTS `sensitive_word` (
  `pk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `word` varchar(100) NOT NULL,
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pk`),
  UNIQUE KEY `idx_sensitive_word` (`word`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `sensitive_word` (`word`) VALUES
('广告'), ('诈骗'), ('代考'), ('刷单'), ('赌博'), ('色情'), ('暴力威胁'), ('个人隐私泄露');
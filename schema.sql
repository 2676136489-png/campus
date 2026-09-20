
CREATE DATABASE IF NOT EXISTS `circle` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `circle`;


-- pk: 主键，自增ID
-- userName: 用户名（唯一）
-- password: 密码（bcrypt哈希存储）
-- userType: 用户类型（s=学生，a=管理员）
-- status: 用户状态（N=待审核，V=已通过，U=已停用）
-- createTime: 创建时间
CREATE TABLE IF NOT EXISTS `user` (
  `pk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userName` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `userType` enum('s','a') NOT NULL DEFAULT 's',
  `status` enum('N','V','U') NOT NULL DEFAULT 'N',
  `active_token` varchar(64) NOT NULL DEFAULT '',
  `active_token_time` datetime DEFAULT NULL,
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pk`),
  UNIQUE KEY `idx_username` (`userName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `student` (
  `pk` int(10) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  `gender` tinyint(4) NOT NULL DEFAULT 0,
  `birth_date` date NOT NULL,
  `college` varchar(100) NOT NULL,
  `grade` varchar(20) NOT NULL,
  `major` varchar(100) NOT NULL,
  `stuNo` varchar(20) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `QQ` varchar(20) NOT NULL,
  `avatar` varchar(255) NOT NULL DEFAULT '',
  `student_card` varchar(255) NOT NULL DEFAULT '',
  `tags` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`pk`),
  UNIQUE KEY `idx_stuno` (`stuNo`),
  CONSTRAINT `fk_student_user` FOREIGN KEY (`pk`) REFERENCES `user` (`pk`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 动态表（学生发布的动态内容）
-- pk: 主键，自增ID
-- userPk: 发布者ID（外键关联user表）
-- content: 动态内容
-- tags: 标签（逗号分隔）
-- photo: 照片路径
-- createTime: 创建时间
-- status: 状态（normal=正常，deleted=已删除）
CREATE TABLE IF NOT EXISTS `dynamic` (
  `pk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userPk` int(10) unsigned NOT NULL,
  `content` text NOT NULL,
  `tags` varchar(255) NOT NULL DEFAULT '',
  `photo` varchar(255) NOT NULL DEFAULT '',
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('normal','deleted') NOT NULL DEFAULT 'normal',
  PRIMARY KEY (`pk`),
  KEY `idx_dynamic_user` (`userPk`),
  KEY `idx_dynamic_status_time` (`status`, `createTime`),
  CONSTRAINT `fk_dynamic_user` FOREIGN KEY (`userPk`) REFERENCES `user` (`pk`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 评论表（动态评论）
-- pk: 主键，自增ID
-- dynamicPk: 动态ID（外键关联dynamic表）
-- userPk: 评论者ID（外键关联user表）
-- content: 评论内容
-- createTime: 创建时间
CREATE TABLE IF NOT EXISTS `comment` (
  `pk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dynamicPk` int(10) unsigned NOT NULL,
  `userPk` int(10) unsigned NOT NULL,
  `content` text NOT NULL,
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pk`),
  KEY `idx_comment_dynamic` (`dynamicPk`),
  KEY `idx_comment_user` (`userPk`),
  CONSTRAINT `fk_comment_dynamic` FOREIGN KEY (`dynamicPk`) REFERENCES `dynamic` (`pk`) ON DELETE CASCADE,
  CONSTRAINT `fk_comment_user` FOREIGN KEY (`userPk`) REFERENCES `user` (`pk`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 点赞表（动态点赞）
-- pk: 主键，自增ID
-- dynamicPk: 动态ID（外键关联dynamic表）
-- userPk: 点赞者ID（外键关联user表）
-- createTime: 创建时间
-- 唯一约束：同一用户对同一动态只能点赞一次
CREATE TABLE IF NOT EXISTS `like` (
  `pk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dynamicPk` int(10) unsigned NOT NULL,
  `userPk` int(10) unsigned NOT NULL,
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pk`),
  UNIQUE KEY `idx_like_dynamic_user` (`dynamicPk`, `userPk`),
  KEY `idx_like_user` (`userPk`),
  CONSTRAINT `fk_like_dynamic` FOREIGN KEY (`dynamicPk`) REFERENCES `dynamic` (`pk`) ON DELETE CASCADE,
  CONSTRAINT `fk_like_user` FOREIGN KEY (`userPk`) REFERENCES `user` (`pk`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- 通知表
CREATE TABLE IF NOT EXISTS `notification` (
  `pk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userPk` int(10) unsigned NOT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'system',
  `content` varchar(500) NOT NULL,
  `link` varchar(255) NOT NULL DEFAULT '',
  `isRead` tinyint(1) NOT NULL DEFAULT 0,
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pk`),
  KEY `idx_notification_user` (`userPk`, `isRead`, `createTime`),
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`userPk`) REFERENCES `user` (`pk`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 管理员审计日志表
CREATE TABLE IF NOT EXISTS `audit_log` (
  `pk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `adminPk` int(10) unsigned NOT NULL,
  `targetType` varchar(30) NOT NULL,
  `targetPk` int(10) unsigned NOT NULL,
  `action` varchar(50) NOT NULL,
  `detail` varchar(500) NOT NULL DEFAULT '',
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pk`),
  KEY `idx_audit_target` (`targetType`, `targetPk`),
  KEY `idx_audit_admin` (`adminPk`),
  CONSTRAINT `fk_audit_admin` FOREIGN KEY (`adminPk`) REFERENCES `user` (`pk`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 登录尝试表（登录限流）
CREATE TABLE IF NOT EXISTS `login_attempt` (
  `pk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `loginName` varchar(50) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pk`),
  KEY `idx_login_attempt_ip_time` (`ip`, `createTime`),
  KEY `idx_login_attempt_name_time` (`loginName`, `createTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- 密码重置验证码表
CREATE TABLE IF NOT EXISTS `password_reset` (
  `pk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userPk` int(10) unsigned NOT NULL,
  `channel` enum('phone','email') NOT NULL,
  `contact` varchar(100) NOT NULL,
  `codeHash` varchar(255) NOT NULL,
  `attempts` tinyint unsigned NOT NULL DEFAULT 0,
  `expiresAt` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pk`),
  KEY `idx_reset_user_channel` (`userPk`, `channel`),
  KEY `idx_reset_expires` (`expiresAt`),
  CONSTRAINT `fk_reset_user` FOREIGN KEY (`userPk`) REFERENCES `user` (`pk`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
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
-- 站内私信
CREATE TABLE IF NOT EXISTS `message` (
  `pk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `senderPk` int(10) unsigned NOT NULL,
  `receiverPk` int(10) unsigned NOT NULL,
  `content` varchar(1000) NOT NULL,
  `image` varchar(255) NOT NULL DEFAULT '',
  `status` enum('normal','recalled') NOT NULL DEFAULT 'normal',
  `recalledAt` datetime NULL DEFAULT NULL,
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
-- 用户反馈
CREATE TABLE IF NOT EXISTS `feedback` (
  `pk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userPk` int(10) unsigned NOT NULL,
  `type` enum('bug','suggestion','complaint','other') NOT NULL DEFAULT 'other',
  `content` varchar(1000) NOT NULL,
  `image` varchar(255) NOT NULL DEFAULT '',
  `status` enum('open','processing','closed') NOT NULL DEFAULT 'open',
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updateTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`pk`),
  KEY `idx_feedback_user` (`userPk`, `createTime`),
  KEY `idx_feedback_status` (`status`, `createTime`),
  CONSTRAINT `fk_feedback_user` FOREIGN KEY (`userPk`) REFERENCES `user` (`pk`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- 019: 登录邮箱验证码（每次登录二步验证）
-- 密码验证通过后，向 student.email 发送 6 位验证码；
-- 一人最多一条未用验证码，发新码前作废旧码，验证时只认最新一条。
-- codeHash 存 password_hash 后的值，明文验证码不落库。

CREATE TABLE IF NOT EXISTS `login_otp` (
  `pk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userPk` int(10) unsigned NOT NULL,
  `codeHash` varchar(255) NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `expiresAt` datetime NOT NULL,
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pk`),
  KEY `idx_login_otp_user` (`userPk`, `used`, `pk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

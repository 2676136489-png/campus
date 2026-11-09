-- 受信任设备：登录 OTP 验证成功一次后，给当前设备签发长效信任 Cookie，
-- 之后同设备登录密码正确即直进，只有新设备（或信任失效）才需要验证码。
-- 安全设计：Cookie 里放原始随机 token，库里只存 SHA-256 hash；
-- 绑定 User-Agent 指纹，Cookie 被复制到其他设备无法通过校验；
-- 改密码时清空该用户全部信任记录（与「改密使会话失效」同一安全口径）。
CREATE TABLE IF NOT EXISTS `trusted_device` (
  `pk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userPk` int(10) unsigned NOT NULL,
  `tokenHash` char(64) NOT NULL,
  `uaDigest` char(16) NOT NULL DEFAULT '',
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lastUseTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expiresAt` datetime NOT NULL,
  PRIMARY KEY (`pk`),
  UNIQUE KEY `uk_trusted_token` (`tokenHash`),
  KEY `idx_trusted_user` (`userPk`,`expiresAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

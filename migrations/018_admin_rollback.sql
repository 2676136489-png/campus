-- 管理员操作回滚
CREATE TABLE IF NOT EXISTS `admin_rollback` (
  `pk` int unsigned NOT NULL AUTO_INCREMENT,
  `auditPk` int unsigned NOT NULL,
  `targetType` varchar(30) NOT NULL,
  `targetPk` int unsigned NOT NULL,
  `payload` longtext NOT NULL,
  `createTime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pk`),
  KEY `idx_rollback_audit` (`auditPk`),
  KEY `idx_rollback_target` (`targetType`, `targetPk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
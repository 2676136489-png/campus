-- 管理员权限分级
ALTER TABLE `user`
  ADD COLUMN `adminRole` VARCHAR(20) NOT NULL DEFAULT 'operator';
UPDATE `user` SET `adminRole` = 'super' WHERE `userType` = 'a';
-- 高频查询性能索引
ALTER TABLE `comment` ADD KEY `idx_comment_dynamic_time` (`dynamicPk`, `createTime`);
ALTER TABLE `student` ADD KEY `idx_student_college` (`college`);
ALTER TABLE `student` ADD KEY `idx_student_grade` (`grade`);
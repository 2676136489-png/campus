<?php

use PHPUnit\Framework\TestCase;

final class HelperTest extends TestCase
{
    public function testTextLengthCountsMultibyteChars(): void
    {
        $this->assertSame(7, textLength('校园圈子abc'));
    }

    public function testNormalizeTagsReplacesChineseCommas(): void
    {
        $this->assertSame('篮球,摄影,阅读,音乐', normalizeTags('篮球，摄影、阅读 音乐'));
    }

    public function testNormalizeTagsDeduplicatesAndLimits(): void
    {
        $this->assertSame('篮球,摄影', normalizeTags('篮球,篮球,摄影'));
        $this->assertCount(10, explode(',', normalizeTags('1,2,3,4,5,6,7,8,9,10,11')));
    }

    public function testValidateStudentFieldsAcceptsValidData(): void
    {
        $this->assertTrue(validateStudentFields([
            'userName' => 'student01',
            'pwd' => 'abc123',
            'stuNo' => '20240001',
            'name' => '张三',
            'gender' => '1',
            'birth_date' => '2004-01-01',
            'college' => '计算机学院',
            'grade' => '2023级',
            'major' => '软件工程',
            'phone' => '13800138000',
            'email' => 'stu@example.com',
            'QQ' => '123456789'
        ], true));
    }

    public function testValidateStudentFieldsRejectsBadPhone(): void
    {
        $data = [
            'userName' => 'student01',
            'pwd' => 'abc123',
            'stuNo' => '20240001',
            'name' => '张三',
            'gender' => '1',
            'birth_date' => '2004-01-01',
            'college' => '计算机学院',
            'grade' => '2023级',
            'major' => '软件工程',
            'phone' => '123',
            'email' => 'stu@example.com',
            'QQ' => '123456789'
        ];
        $this->assertSame('手机号需为 11-13 位数字。', validateStudentFields($data, true));
    }

    public function testAssetUrlRejectsTraversal(): void
    {
        $this->assertSame('', assetUrl('../secret.php'));
        $this->assertSame('uploads/avatar_1.png', assetUrl('uploads/avatar_1.png'));
    }

    public function testMaskContact(): void
    {
        $this->assertSame('138****8000', maskContact('13800138000'));
        $this->assertSame('zh***@example.com', maskContact('zhangsan@example.com'));
    }

    public function testSensitiveWordFilter(): void
    {
        $this->assertTrue(filterSensitiveText('这里有广告内容')['blocked']);
        $this->assertFalse(filterSensitiveText('正常的校园生活分享')['blocked']);
    }

    public function testReservedUserName(): void
    {
        $this->assertTrue(isReservedUserName('admin'));
        $data = [
            'userName' => 'admin',
            'pwd' => 'abc123',
            'stuNo' => '20240001',
            'name' => '张三',
            'gender' => '1',
            'birth_date' => '2004-01-01',
            'college' => '计算机学院',
            'grade' => '2023级',
            'major' => '软件工程',
            'phone' => '13800138000',
            'email' => 'stu@example.com',
            'QQ' => '123456789'
        ];
        $this->assertSame('该用户名已被系统保留。', validateStudentFields($data, true));
    }
}
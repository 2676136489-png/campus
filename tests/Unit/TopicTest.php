<?php

use PHPUnit\Framework\TestCase;

final class TopicTest extends TestCase
{
    public function testExtractTopics(): void
    {
        $this->assertSame(['学习', '校园'], extractTopics('今天去图书馆 #学习 #校园'));
        $this->assertSame([], extractTopics('没有话题的普通内容'));
    }

    public function testTopicHtmlLinksToTagSearch(): void
    {
        $html = topicHtml('今天 #学习 打卡');
        $this->assertStringContainsString('topic-link', $html);
        $this->assertStringContainsString('p_dynamics.php?tag=', $html);
        $this->assertStringContainsString('#学习', $html);
    }
}
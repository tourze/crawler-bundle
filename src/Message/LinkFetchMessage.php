<?php

namespace CrawlerBundle\Message;

use Tourze\Symfony\Async\Message\AsyncMessageInterface;

class LinkFetchMessage implements AsyncMessageInterface
{
    /**
     * @var string 初始访问URL
     */
    private string $link;

    /**
     * @var int 关联任务ID
     */
    private int $taskId;

    public function getLink(): string
    {
        return $this->link;
    }

    public function setLink(string $link): void
    {
        $this->link = $link;
    }

    public function getTaskId(): int
    {
        return $this->taskId;
    }

    public function setTaskId(int $taskId): void
    {
        $this->taskId = $taskId;
    }
}

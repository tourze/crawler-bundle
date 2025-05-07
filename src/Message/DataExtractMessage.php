<?php

namespace CrawlerBundle\Message;

use Tourze\Symfony\Async\Message\AsyncMessageInterface;

class DataExtractMessage implements AsyncMessageInterface
{
    /**
     * @var int 数据ID
     */
    private int $dataId;

    public function getDataId(): int
    {
        return $this->dataId;
    }

    public function setDataId(int $dataId): void
    {
        $this->dataId = $dataId;
    }
}

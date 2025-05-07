<?php

namespace CrawlerBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum SaveState: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case WAITING = 'waiting';
    case RUNNING = 'running';
    case FINISHED = 'finished';
    case EXCEPTION = 'exception';

    public function getLabel(): string
    {
        return match ($this) {
            self::WAITING => '等待开始',
            self::RUNNING => '执行中',
            self::FINISHED => '已完成',
            self::EXCEPTION => '异常退出',
        };
    }
}

<?php

namespace CrawlerBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum TaskType: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case WEB = 'web';
    case DOUYIN_WEB = 'douyin-web';

    public function getLabel(): string
    {
        return match ($this) {
            self::WEB => '常规网页',
            self::DOUYIN_WEB => '抖音网页版',
        };
    }
}

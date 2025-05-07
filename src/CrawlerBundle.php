<?php

namespace CrawlerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;

class CrawlerBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            \Tourze\Symfony\CronJob\CronJobBundle::class => ['all' => true],
            \AntdCpBundle\AntdCpBundle::class => ['all' => true],
            \CmsBundle\CmsBundle::class => ['all' => true],
        ];
    }
}

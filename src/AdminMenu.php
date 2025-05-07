<?php

namespace CrawlerBundle;

use CrawlerBundle\Entity\Data;
use CrawlerBundle\Entity\Task;
use Knp\Menu\ItemInterface;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;

class AdminMenu implements MenuProviderInterface
{
    public function __construct(private readonly LinkGeneratorInterface $linkGenerator)
    {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (!$item->getChild('爬虫')) {
            $item->addChild('爬虫', [
                'attributes' => [
                    'icon' => 'icon icon-focus',
                ],
            ]);
        }
        $item->getChild('爬虫')->addChild('任务管理')->setUri($this->linkGenerator->getCurdListPage(Task::class));
        $item->getChild('爬虫')->addChild('原始数据')->setUri($this->linkGenerator->getCurdListPage(Data::class));
    }
}

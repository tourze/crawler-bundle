<?php

namespace CrawlerBundle\MessageHandler;

use CrawlerBundle\Message\DataExtractMessage;
use CrawlerBundle\Repository\DataRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DataExtractHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly DataRepository $dataRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(DataExtractMessage $message): void
    {
        $data = $this->dataRepository->find($message->getDataId());
        if (!$data) {
            $this->logger->warning('找不到要分析的数据');

            return;
        }

        $task = $data->getTask();

        $item = [];
        $crawler = new Crawler($data->getResponseBody());
        foreach ($task->getMatchRules() as $matchRule) {
            $pattern = $matchRule->getPattern();
            // var_dump($pattern);
            $c = (clone $crawler)->filterXPath($pattern);
            if ($c->count() > 0) {
                $val = $c->html();
                $val = trim($val);

                // 执行过滤规则
                if ($matchRule->getRemoveWords()) {
                    $_words = explode("\n", trim($matchRule->getRemoveWords()));
                    foreach ($_words as $_word) {
                        $_word = trim($_word);
                        if (empty($_word)) {
                            continue;
                        }

                        $_tmp = explode('|', $_word, 2);
                        $val = str_replace($_tmp[0], $_tmp[1] ?? '', $val);
                    }
                }

                $val = trim($val);
                $item[$matchRule->getName()] = $val;
            }
        }

        if (empty($item)) {
            $this->logger->warning("{$data->getUrl()}解析不出数据，跳过");

            return;
        }

        $data->setExtractList($item);
        $this->entityManager->persist($data);
        $this->entityManager->flush();
    }
}

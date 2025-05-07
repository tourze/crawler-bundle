<?php

namespace CrawlerBundle\MessageHandler;

use CrawlerBundle\Entity\Data;
use CrawlerBundle\Message\DataExtractMessage;
use CrawlerBundle\Message\LinkFetchMessage;
use CrawlerBundle\Repository\DataRepository;
use CrawlerBundle\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsMessageHandler]
class LinkFetchHandler
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly LoggerInterface $logger,
        private readonly DataRepository $dataRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly HttpClientInterface $httpClient,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(LinkFetchMessage $message): void
    {
        $task = $this->taskRepository->find($message->getTaskId());
        if (!$task) {
            $this->logger->warning('找不到爬虫任务', [
                'message' => $message,
            ]);

            return;
        }

        $fetchLink = $message->getLink();

        // 开始读取
        $data = $this->dataRepository->findOneBy([
            'task' => $task,
            'url' => $fetchLink,
        ]);
        if ($data) {
            $this->logger->warning("{$fetchLink}已经入库，跳过处理");

            return;
        }

        $data = new Data();
        $data->setTask($task);
        $data->setType('web-url');
        $data->setUrl($fetchLink);
        $data->setResponseBody('');
        $data->setResponseHeaders([]);
        $data->setSaved(false);

        $response = $this->httpClient->request('GET', $fetchLink);
        $data->setResponseHeaders($response->getHeaders(false));
        $data->setResponseBody($response->getContent(false));
        $data->setFetched(true);
        $this->logger->debug("获取远程地址{$fetchLink}");
        $this->entityManager->persist($data);
        $this->entityManager->flush();

        // 异步解析数据
        $extractMessage = new DataExtractMessage();
        $extractMessage->setDataId($data->getId());
        $this->messageBus->dispatch($extractMessage);

        // 拿到HTML了，我们分析提取其中的链接
        $crawler = new Crawler($data->getResponseBody(), $data->getUrl());
        $links = $crawler->filter('a')->links();
        foreach ($links as $link) {
            if (!$task->shouldStoreData($link->getUri())) {
                $this->logger->warning("{$link->getUri()}不符合入库要求");
                continue;
            }

            $check = $this->dataRepository->findOneBy([
                'task' => $task,
                'url' => $link->getUri(),
            ]);
            if ($check) {
                $this->logger->warning("{$link->getUri()}已入库，不重复插入");
                continue;
            }

            $this->logger->debug("{$link->getUri()}地址新入库");
            // 继续请求其他URL
            $nextMessage = new LinkFetchMessage();
            $nextMessage->setLink($link->getUri());
            $nextMessage->setTaskId($task->getId());
            $this->messageBus->dispatch($nextMessage);
        }
    }
}

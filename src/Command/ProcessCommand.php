<?php

namespace CrawlerBundle\Command;

use PHPCreeper\Downloader;
use PHPCreeper\Parser;
use PHPCreeper\Producer;
use PHPCreeper\Timer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Workerman\Worker;

#[AsCommand(name: 'crawler:process', description: '启动爬虫进程')]
class ProcessCommand extends Command
{
    public function __construct(private readonly KernelInterface $kernel)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('status')
            ->addOption('stop')
            ->addOption('start');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->startAppProducer();
        $this->startAppDownloader();
        $this->startAppParser();

        Worker::runAll();

        return Command::SUCCESS;
    }

    /**
     * 这个进程，主动用于源源不断地生产任务
     *
     * @throws \RedisException
     */
    private function startAppProducer(): void
    {
        $producer = new Producer();
        $redis = new \Redis();
        $redis->connect('127.0.0.1');
        $producer->setConfig([
            'redis' => [
                'scheme' => 'tcp',
                'host' => '127.0.0.1',
                'port' => 6379,
            ],
        ]);
        $producer->setName('AppProducer');
        $producer->setCount(1);
        $producer->setInterval(5);
        $producer->onProducerStart = function (Producer $producer) {
            Timer::add(10, function () use ($producer) {
                $task = [
                    'url' => [
                        'r1' => 'http://www.weather.com.cn/weather/101010100.shtml',
                    ],
                    'rule' => [
                        'r1' => [
                            'time' => ['div#7d ul.t.clearfix h1', 'text'],
                            'wea' => ['div#7d ul.t.clearfix p.wea', 'text'],
                            'tem' => ['div#7d ul.t.clearfix p.tem', 'text'],
                            'wind' => ['div#7d ul.t.clearfix p.win i', 'text'],
                        ],
                    ],
                ];

                $context = [
                    'cache_enabled' => true,
                    'cache_directory' => $this->kernel->getCacheDir() . '/' . date('Ymd'),
                    'allow_url_repeat' => true, // 允许重复喔
                ];
                $producer->newTaskMan()->setContext($context)->createMultiTask($task);
            });
        };
    }

    private function startAppDownloader(): void
    {
        $downloader = new Downloader();
        $redis = new \Redis();
        $redis->connect('127.0.0.1');
        $downloader->setConfig([
            'redis' => [
                'scheme' => 'tcp',
                'host' => '127.0.0.1',
                'port' => 6379,
            ],
        ]);
        $downloader->setTaskCrawlInterval(2);
        $downloader->setName('AppDownloader')
            ->setCount(2)
            ->setClientSocketAddress([
                'ws://127.0.0.1:8888',
            ]);
        $downloader->onDownloaderStart = function (Downloader $downloader) {
            // $worker = new Downloader();
            // $worker->setServerSocketAddress("text://0.0.0.0:3333");
            // $worker->serve();
        };
    }

    private function startAppParser()
    {
        $parser = new Parser();
        $parser->setName('AppParser')->setCount(1);
        $parser->setServerSocketAddress('websocket://0.0.0.0:8888');
        $parser->onParserExtractField = function ($parser, $download_data, $fields) {
            echo json_encode($fields, JSON_UNESCAPED_UNICODE);
        };
    }
}

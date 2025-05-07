<?php

namespace CrawlerBundle\Command;

use CrawlerBundle\Enum\SaveState;
use CrawlerBundle\Repository\TaskRepository;
use CrawlerBundle\Saver\CmsSaver;
use Doctrine\ORM\EntityManagerInterface;
use Nyholm\Dsn\DsnParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\VarExporter\VarExporter;

#[AsCommand(name: 'crawler:save:start')]
class SaveRunnerCommand extends Command
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly CmsSaver $cmsSaver,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('入库任务开始执行')
            ->addArgument('taskId', InputArgument::REQUIRED, '任务ID')
            ->addOption('print');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('taskId');
        $task = $this->taskRepository->find($id);
        if (!$task) {
            throw new \Exception('找不到任务');
        }

        if (!$task->getSaveTarget()) {
            throw new \Exception('找不到入库规则');
        }

        if (empty($task->getMatchRules())) {
            throw new \Exception('找不到入库字段');
        }

        $hasMust = false;
        foreach ($task->getMatchRules() as $matchRule) {
            if ($matchRule->getRequired()) {
                $hasMust = true;
            }
        }

        if (!$hasMust) {
            throw new \Exception('在入库时，必须起码有一个字段是必须的');
        }

        $task->setSaveState(SaveState::RUNNING);
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $dsn = DsnParser::parse($task->getSaveTarget());
        $this->cmsSaver->prepare($task, $dsn);

        foreach ($task->getDatas() as $data) {
            $output->writeln("正在解析URL {$data->getUrl()}");
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

            if ($input->getOption('print')) {
                $task->setSaveState(SaveState::EXCEPTION);
                $this->entityManager->persist($task);
                $this->entityManager->flush();

                $output->writeln(VarExporter::export($item));

                return Command::SUCCESS;
            }

            $data->setSaved(false);
            if (!empty($item)) {
                $output->writeln('正在入库数据：' . VarExporter::export($item));
                $ok = $this->cmsSaver->process($task, $dsn, $item);
                $data->setSaved((bool) $ok);
            }

            $this->entityManager->persist($data);
            $this->entityManager->flush();
        }

        $task->setSaveState(SaveState::FINISHED);
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}

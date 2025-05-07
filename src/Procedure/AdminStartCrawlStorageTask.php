<?php

namespace CrawlerBundle\Procedure;

use AntdCpBundle\Builder\Action\ApiCallAction;
use AppBundle\Procedure\Base\ApiCallActionProcedure;
use CrawlerBundle\Entity\Task;
use CrawlerBundle\Repository\TaskRepository;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPCLogBundle\Attribute\Log;
use Tourze\JsonRPCSecurityBundle\Attribute\MethodPermission;

#[Log]
#[MethodExpose(AdminStartCrawlStorageTask::NAME)]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[MethodPermission(permission: Task::class . '::renderCrawlAction', title: '入库')]
class AdminStartCrawlStorageTask extends ApiCallActionProcedure
{
    public const NAME = 'AdminStartCrawlStorageTask';

    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly KernelInterface $kernel,
    ) {
    }

    public function getAction(): ApiCallAction
    {
        return ApiCallAction::gen()
            ->setLabel('入库')
            ->setConfirmText('是否确认开始执行入库任务')
            ->setApiName(AdminStartCrawlStorageTask::NAME);
    }

    public function execute(): array
    {
        $that = $this->taskRepository->findOneBy(['id' => $this->id]);
        if (!$that) {
            throw new ApiException('找不到任务');
        }

        $rootDir = $this->kernel->getContainer()->getParameter('kernel.project_dir');

        $finder = new PhpExecutableFinder();
        $phpExecutable = $finder->find();
        $phpExecutable = escapeshellarg($phpExecutable);

        $cmd = "{$phpExecutable} {$rootDir}/bin/console crawler:save:start {$that->getId()} --verbose &";
        //                $pid = popen($cmd, 'r');
        //                pclose($pid);
        $process = Process::fromShellCommandline($cmd);
        $process->setOptions(['create_new_console' => true]);
        $process->start();

        return [
            'cmd' => $cmd,
            '__message' => '正在入库，请稍后查看结果',
        ];
    }
}

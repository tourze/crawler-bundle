<?php

namespace CrawlerBundle\Procedure;

use AntdCpBundle\Builder\Action\ApiCallAction;
use AppBundle\Procedure\Base\ApiCallActionProcedure;
use Carbon\Carbon;
use CrawlerBundle\Entity\Task;
use CrawlerBundle\Enum\FetchState;
use CrawlerBundle\Message\LinkFetchMessage;
use CrawlerBundle\Repository\DataRepository;
use CrawlerBundle\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPCLogBundle\Attribute\Log;
use Tourze\JsonRPCSecurityBundle\Attribute\MethodPermission;

#[Log]
#[MethodExpose(AdminStartCrawlStartTask::NAME)]
#[IsGranted('ROLE_OPERATOR')]
#[MethodPermission(permission: Task::class . '::renderCrawlAction', title: '采集')]
class AdminStartCrawlStartTask extends ApiCallActionProcedure
{
    public const NAME = 'AdminStartCrawlStartTask';

    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly DataRepository $dataRepository,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function getAction(): ApiCallAction
    {
        return ApiCallAction::gen()
            ->setLabel('采集')
            ->setConfirmText('是否确认开始执行采集任务')
            ->setApiName(AdminStartCrawlStartTask::NAME);
    }

    public function execute(): array
    {
        $that = $this->taskRepository->findOneBy(['id' => $this->id]);
        if (!$that) {
            throw new ApiException('找不到记录');
        }

        // 我们要从 listLinks 中找出第一个可以访问的地址
        $entryLinks = [];
        foreach (explode("\n", $that->getListLinks()) as $listLink) {
            $listLink = trim($listLink);
            if (!str_contains($listLink, '${')) {
                $entryLinks[] = $listLink;
            }
        }

        if (empty($entryLinks)) {
            throw new \Exception('找不到任何入口地址');
        }

        $that->setFetchStartTime(Carbon::now());
        $that->setFetchEndTime(null);
        $that->setFetchState(FetchState::RUNNING);
        $this->entityManager->persist($that);
        $this->entityManager->flush();

        // 删除本地所有采集好的数据
        $this->dataRepository->createQueryBuilder('a')
            ->delete()
            ->where('a.task = :task')
            ->setParameter('task', $that)
            ->getQuery()
            ->execute();

        foreach ($entryLinks as $entryLink) {
            $message = new LinkFetchMessage();
            $message->setTaskId($that->getId());
            $message->setLink($entryLink);
            $this->messageBus->dispatch($message);
        }

        return [
            '__message' => '正在采集，请稍后查看结果',
        ];
    }
}

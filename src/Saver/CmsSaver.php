<?php

namespace CrawlerBundle\Saver;

use CmsBundle\Entity\Attribute;
use CmsBundle\Entity\Entity;
use CmsBundle\Entity\Value;
use CmsBundle\Enum\EntityState;
use CmsBundle\Repository\EntityRepository;
use CmsBundle\Repository\ModelRepository;
use CrawlerBundle\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Nyholm\Dsn\Configuration\Dsn;
use Psr\Log\LoggerInterface;
use Tourze\DoctrineAsyncBundle\Service\DoctrineService;

class CmsSaver
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ?ModelRepository $modelRepository,
        private readonly ?EntityRepository $entityRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly DoctrineService $doctrineService,
    ) {
    }

    public function prepare(Task $task, Dsn $dsn): void
    {
        if ('cms' !== $dsn->getScheme()) {
            return;
        }

        $entities = $this->entityRepository->findBy([
            'remark' => "crawler-task:{$task->getId()}",
        ]);
        foreach ($entities as $entity) {
            $this->entityManager->remove($entity);
        }
        $this->entityManager->flush();
    }

    public function process(Task $task, Dsn $dsn, array $item): bool
    {
        if ('cms' !== $dsn->getScheme()) {
            return false;
        }
        if (!$this->entityRepository) {
            $this->logger->error('找不到CmsBundle');

            return false;
        }

        $model = $this->modelRepository->findOneBy(['code' => $dsn->getHost()]);
        if (!$model) {
            throw new \Exception("找不到CMS模型[{$dsn->getHost()}]");
        }

        // 先创建一个记录
        $entity = new Entity();
        // title是固定的
        $entity->setTitle($item['title'] ?? md5(uniqid($model->getCode())));
        unset($item['title']);
        $entity->setModel($model);
        $entity->setState(EntityState::DRAFT);
        $entity->setRemark("crawler-task:{$task->getId()}");
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        foreach ($item as $k => $v) {
            /** @var Attribute $attr */
            $attr = $model->getAttributes()[$k] ?? null;
            if (!$attr) {
                throw new \Exception("模型[{$model->getCode()}]中找不到属性[{$k}]");
            }

            $value = new Value();
            $value->setModel($model);
            $value->setEntity($entity);
            $value->setAttribute($attr);
            $value->setData((string) $v);
            // 采集入库的数据，不关心原始数据了吧
            // $value->setRawData(['v' => $v]);
            $this->doctrineService->directInsert($value);
        }

        return true;
    }
}

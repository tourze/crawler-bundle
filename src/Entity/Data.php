<?php

namespace CrawlerBundle\Entity;

use CrawlerBundle\Repository\DataRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\EasyAdmin\Attribute\Action\BatchDeletable;
use Tourze\EasyAdmin\Attribute\Action\Creatable;
use Tourze\EasyAdmin\Attribute\Action\Deletable;
use Tourze\EasyAdmin\Attribute\Action\Editable;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;
use Tourze\EasyAdmin\Attribute\Filter\Filterable;
use Tourze\EasyAdmin\Attribute\Filter\Keyword;
use Tourze\EasyAdmin\Attribute\Permission\AsPermission;

#[AsPermission(title: '原始数据')]
#[Deletable]
#[Editable]
#[Creatable]
#[BatchDeletable]
#[ORM\Entity(repositoryClass: DataRepository::class)]
#[ORM\Table(name: 'crawler_data', options: ['comment' => '原始数据'])]
#[ORM\UniqueConstraint(name: 'crawler_data_idx_uniq', columns: ['task_id', 'url'])]
class Data
{
    #[ExportColumn]
    #[ListColumn(order: -1, sorter: true)]
    #[Groups(['restful_read', 'admin_curd', 'recursive_view', 'api_tree'])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[ListColumn(title: '任务')]
    #[ORM\ManyToOne(targetEntity: Task::class, inversedBy: 'datas')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Task $task = null;

    #[Keyword]
    #[ListColumn]
    #[ORM\Column(type: Types::STRING, length: 60, options: ['comment' => '类型'])]
    private ?string $type = null;

    #[Keyword]
    #[ListColumn]
    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '链接'])]
    private ?string $url = null;

    #[Ignore]
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $responseHeaders = [];

    #[Ignore]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $responseBody = null;

    #[ListColumn]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '已抓取'])]
    private ?bool $fetched = null;

    #[ListColumn]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '已入库'])]
    private ?bool $saved = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '提取数据'])]
    private ?array $extractList = null;

    #[Filterable]
    #[IndexColumn]
    #[ListColumn(order: 98, sorter: true)]
    #[ExportColumn]
    #[CreateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '创建时间'])]
    private ?\DateTimeInterface $createTime = null;

    #[UpdateTimeColumn]
    #[ListColumn(order: 99, sorter: true)]
    #[Filterable]
    #[ExportColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '更新时间'])]
    private ?\DateTimeInterface $updateTime = null;

    public function setCreateTime(?\DateTimeInterface $createdAt): void
    {
        $this->createTime = $createdAt;
    }

    public function getCreateTime(): ?\DateTimeInterface
    {
        return $this->createTime;
    }

    public function setUpdateTime(?\DateTimeInterface $updateTime): void
    {
        $this->updateTime = $updateTime;
    }

    public function getUpdateTime(): ?\DateTimeInterface
    {
        return $this->updateTime;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTask(): ?Task
    {
        return $this->task;
    }

    public function setTask(?Task $task): self
    {
        $this->task = $task;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getResponseHeaders(): ?array
    {
        return $this->responseHeaders;
    }

    public function setResponseHeaders(?array $responseHeaders): self
    {
        $this->responseHeaders = $responseHeaders;

        return $this;
    }

    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }

    public function setResponseBody(?string $responseBody): self
    {
        $this->responseBody = $responseBody;

        return $this;
    }

    public function getFetched(): ?bool
    {
        return $this->fetched;
    }

    public function setFetched(bool $fetched): self
    {
        $this->fetched = $fetched;

        return $this;
    }

    public function getSaved(): ?bool
    {
        return $this->saved;
    }

    public function setSaved(?bool $saved): self
    {
        $this->saved = $saved;

        return $this;
    }

    public function getExtractList(): ?array
    {
        return $this->extractList;
    }

    public function setExtractList(?array $extractList): self
    {
        $this->extractList = $extractList;

        return $this;
    }
}

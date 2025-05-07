<?php

namespace CrawlerBundle\Entity;

use AntdCpBundle\Builder\Field\DynamicFieldSet;
use CrawlerBundle\Enum\FetchState;
use CrawlerBundle\Enum\SaveState;
use CrawlerBundle\Enum\TaskType;
use CrawlerBundle\Repository\TaskRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineUserBundle\Attribute\CreatedByColumn;
use Tourze\DoctrineUserBundle\Attribute\UpdatedByColumn;
use Tourze\EasyAdmin\Attribute\Action\Creatable;
use Tourze\EasyAdmin\Attribute\Action\Deletable;
use Tourze\EasyAdmin\Attribute\Action\Editable;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;
use Tourze\EasyAdmin\Attribute\Field\FormField;
use Tourze\EasyAdmin\Attribute\Filter\Filterable;
use Tourze\EasyAdmin\Attribute\Filter\Keyword;
use Tourze\EasyAdmin\Attribute\Permission\AsPermission;

#[AsPermission(title: '爬虫任务')]
#[Deletable]
#[Creatable]
#[Editable(drawerWidth: 900)]
#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'crawler_task', options: ['comment' => '爬虫任务'])]
class Task implements \Stringable
{
    #[ExportColumn]
    #[ListColumn(order: -1, sorter: true)]
    #[Groups(['restful_read', 'admin_curd', 'recursive_view', 'api_tree'])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[CreatedByColumn]
    #[Groups(['restful_read'])]
    #[ORM\Column(nullable: true, options: ['comment' => '创建人'])]
    private ?string $createdBy = null;

    #[UpdatedByColumn]
    #[Groups(['restful_read'])]
    #[ORM\Column(nullable: true, options: ['comment' => '更新人'])]
    private ?string $updatedBy = null;

    #[FormField(span: 8)]
    #[ListColumn]
    #[ORM\Column(type: Types::STRING, length: 60, enumType: TaskType::class, options: ['comment' => '类型'])]
    private TaskType $type;

    #[FormField(span: 16)]
    #[ListColumn]
    #[ORM\Column(type: Types::STRING, length: 100, unique: true, options: ['comment' => '标题'])]
    private ?string $title = null;

    #[FormField]
    #[Keyword]
    #[ListColumn]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '关键词'])]
    private array $keywords = [];

    #[FormField]
    #[ListColumn]
    #[ORM\Column(type: Types::STRING, length: 50, enumType: FetchState::class, options: ['comment' => '抓取状态'])]
    private FetchState $fetchState;

    #[FormField]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '列表链接'])]
    private ?string $listLinks = null;

    #[FormField]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '详情链接'])]
    private ?string $detailLinks = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fetchStartTime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fetchEndTime = null;

    /**
     * @var Collection<Data>
     */
    #[Ignore]
    #[ORM\OneToMany(mappedBy: 'task', targetEntity: Data::class)]
    private Collection $datas;

    #[FormField]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '存档目标'])]
    private ?string $saveTarget = null;

    #[FormField]
    #[ListColumn]
    #[ORM\Column(type: Types::STRING, length: 50, enumType: SaveState::class, options: ['comment' => '存库状态'])]
    private SaveState $saveState;

    /**
     * @DynamicFieldSet
     *
     * @var Collection<MatchRule>
     */
    #[FormField(title: '匹配规则')]
    #[ORM\OneToMany(mappedBy: 'task', targetEntity: MatchRule::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $matchRules;

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

    public function __construct()
    {
        $this->datas = new ArrayCollection();
        $this->matchRules = new ArrayCollection();
    }

    public function __toString(): string
    {
        return "#{$this->getId()} {$this->getTitle()}";
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setCreatedBy(?string $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function setUpdatedBy(?string $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    public function getType(): TaskType
    {
        return $this->type;
    }

    public function setType(TaskType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getKeywords(): ?array
    {
        return $this->keywords;
    }

    public function setKeywords(?array $keywords): self
    {
        $this->keywords = $keywords;

        return $this;
    }

    public function getFetchState(): FetchState
    {
        return $this->fetchState;
    }

    public function setFetchState(FetchState $fetchState): self
    {
        $this->fetchState = $fetchState;

        return $this;
    }

    public function getListLinks(): ?string
    {
        return $this->listLinks;
    }

    public function setListLinks(?string $listLinks): self
    {
        $this->listLinks = $listLinks;

        return $this;
    }

    public function getDetailLinks(): ?string
    {
        return $this->detailLinks;
    }

    public function setDetailLinks(?string $detailLinks): self
    {
        $this->detailLinks = $detailLinks;

        return $this;
    }

    /**
     * 检查指定的URL，在这个任务内应不应该存库.
     */
    public function shouldStoreData(string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        $patterns = [];
        foreach (explode("\n", $this->getListLinks()) as $item) {
            $patterns[] = trim($item);
        }

        foreach (explode("\n", $this->getDetailLinks()) as $item) {
            $patterns[] = trim($item);
        }

        $patterns = array_unique($patterns);

        foreach ($patterns as $pattern) {
            if ($url === $pattern) {
                return true;
            }

            $pattern = str_replace('${整数}', '(\d+)', $pattern);
            $pattern = "@{$pattern}@";
            preg_match($pattern, $url, $match);
            if ($match) {
                // 能匹配到，入库
                return true;
            }
        }

        return false;
    }

    public function getFetchStartTime(): ?\DateTimeInterface
    {
        return $this->fetchStartTime;
    }

    public function setFetchStartTime(?\DateTimeInterface $fetchStartTime): self
    {
        $this->fetchStartTime = $fetchStartTime;

        return $this;
    }

    public function getFetchEndTime(): ?\DateTimeInterface
    {
        return $this->fetchEndTime;
    }

    public function setFetchEndTime(?\DateTimeInterface $fetchEndTime): self
    {
        $this->fetchEndTime = $fetchEndTime;

        return $this;
    }

    /**
     * @return Collection<int, Data>
     */
    public function getDatas(): Collection
    {
        return $this->datas;
    }

    public function addData(Data $data): self
    {
        if (!$this->datas->contains($data)) {
            $this->datas[] = $data;
            $data->setTask($this);
        }

        return $this;
    }

    public function removeData(Data $data): self
    {
        if ($this->datas->removeElement($data)) {
            // set the owning side to null (unless already changed)
            if ($data->getTask() === $this) {
                $data->setTask(null);
            }
        }

        return $this;
    }

    public function getSaveTarget(): ?string
    {
        return $this->saveTarget;
    }

    public function setSaveTarget(?string $saveTarget): self
    {
        $this->saveTarget = $saveTarget;

        return $this;
    }

    public function getSaveState(): SaveState
    {
        return $this->saveState;
    }

    public function setSaveState(SaveState $saveState): self
    {
        $this->saveState = $saveState;

        return $this;
    }

    /**
     * @return Collection<int, MatchRule>
     */
    public function getMatchRules(): Collection
    {
        return $this->matchRules;
    }

    public function addMatchRule(MatchRule $matchRule): self
    {
        if (!$this->matchRules->contains($matchRule)) {
            $this->matchRules[] = $matchRule;
            $matchRule->setTask($this);
        }

        return $this;
    }

    public function removeMatchRule(MatchRule $matchRule): self
    {
        if ($this->matchRules->removeElement($matchRule)) {
            // set the owning side to null (unless already changed)
            if ($matchRule->getTask() === $this) {
                $matchRule->setTask(null);
            }
        }

        return $this;
    }
}

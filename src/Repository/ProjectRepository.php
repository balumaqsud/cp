<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * @return list<Project>
     */
    public function findByOwner(User $owner): array
    {
        return $this->findBy(['owner' => $owner], ['startDate' => 'DESC']);
    }

    /**
     * @return list<string>
     */
    public function findDistinctTags(): array
    {
        $tags = $this->getEntityManager()->getConnection()->fetchFirstColumn(
            <<<'SQL'
            SELECT DISTINCT lower(tag) AS tag
            FROM (
                SELECT jsonb_array_elements_text(technology_tags) AS tag FROM projects
                UNION ALL
                SELECT jsonb_array_elements_text(project_tags) AS tag FROM positions
            ) t
            WHERE tag <> ''
            ORDER BY tag
            SQL,
        );

        return array_map(static fn (mixed $tag): string => (string) $tag, $tags);
    }

    /**
     * @return array<string, int>
     */
    public function tagCloud(): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            <<<'SQL'
            SELECT lower(tag) AS tag, COUNT(*)::int AS cnt
            FROM (
                SELECT jsonb_array_elements_text(technology_tags) AS tag FROM projects
                UNION ALL
                SELECT jsonb_array_elements_text(project_tags) AS tag FROM positions
            ) t
            WHERE tag <> ''
            GROUP BY lower(tag)
            ORDER BY cnt DESC, tag ASC
            SQL,
        );

        $cloud = [];
        foreach ($rows as $row) {
            $cloud[(string) $row['tag']] = (int) $row['cnt'];
        }

        return $cloud;
    }
}

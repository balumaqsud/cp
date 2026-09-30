<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Position;
use App\Enum\CvStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Position>
 */
class PositionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Position::class);
    }

    /**
     * @return list<Position>
     */
    public function findPublic(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isPublic = true')
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Position>
     */
    public function findLatest(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isPublic = true')
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Position>
     */
    public function findPopular(int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.cvs', 'cv', 'WITH', 'cv.status = :published')
            ->setParameter('published', CvStatus::Published->value)
            ->andWhere('p.isPublic = true')
            ->groupBy('p.id')
            ->orderBy('COUNT(cv.id)', 'DESC')
            ->addOrderBy('p.title', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Position>
     */
    public function findAllManaged(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Position>
     */
    public function findLatestManaged(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Position>
     */
    public function findByTag(string $tag): array
    {
        $ids = $this->getEntityManager()->getConnection()->fetchFirstColumn(
            <<<'SQL'
            SELECT id
            FROM positions
            WHERE EXISTS (
                SELECT 1
                FROM jsonb_array_elements_text(project_tags::jsonb) t
                WHERE lower(t) = :tag
            )
            ORDER BY updated_at DESC
            SQL,
            ['tag' => mb_strtolower($tag)],
        );

        return $this->findByIds($ids);
    }

    /**
     * @return list<Position>
     */
    public function searchFullText(string $query): array
    {
        $ids = $this->getEntityManager()->getConnection()->fetchFirstColumn(
            <<<'SQL'
            SELECT id
            FROM positions
            WHERE to_tsvector('simple', coalesce(title, '') || ' ' || coalesce(short_description, '') || ' ' || coalesce(project_tags::text, ''))
                  @@ plainto_tsquery('simple', :q)
            ORDER BY updated_at DESC
            SQL,
            ['q' => $query],
        );

        return $this->findByIds($ids);
    }

    /**
     * Positions a candidate may open.
     *
     * Matches PositionAccessEvaluator: public positions pass, a non-public position
     * with no rules passes, and every other rule must match that user's value.
     * An empty or missing value fails the rule. Boolean, then numeric, then text.
     *
     * @return list<Position>
     */
    public function findVisibleToCandidate(int $userId, ?int $limit = null): array
    {
        $sql = <<<'SQL'
            SELECT p.id
            FROM positions p
            WHERE p.is_public = TRUE
               OR NOT EXISTS (
                    SELECT 1
                    FROM position_access_rules r
                    LEFT JOIN attribute_values av
                      ON av.attribute_id = r.attribute_id
                     AND av.user_id = :userId
                    CROSS JOIN LATERAL (
                        SELECT
                            CASE WHEN av.value IS NULL THEN NULL ELSE av.value::jsonb END AS actual,
                            CASE WHEN r.compare_value IS NULL THEN NULL ELSE r.compare_value::jsonb END AS expected
                    ) raw
                    CROSS JOIN LATERAL (
                        SELECT
                            CASE
                                WHEN raw.actual IS NULL OR jsonb_typeof(raw.actual) = 'null' THEN ''
                                WHEN jsonb_typeof(raw.actual) = 'boolean' THEN CASE WHEN raw.actual = 'true'::jsonb THEN '1' ELSE '' END
                                WHEN jsonb_typeof(raw.actual) IN ('string', 'number') THEN raw.actual #>> '{}'
                                WHEN jsonb_typeof(raw.actual) = 'array' THEN coalesce((
                                    SELECT trim(string_agg(elem, ' '))
                                    FROM jsonb_array_elements_text(raw.actual) elem
                                ), '')
                                WHEN jsonb_typeof(raw.actual) = 'object' THEN coalesce((
                                    SELECT trim(string_agg(field.value, ' ' ORDER BY field.key))
                                    FROM jsonb_each_text(raw.actual) field
                                ), '')
                                ELSE ''
                            END AS actual_text,
                            CASE
                                WHEN raw.expected IS NULL OR jsonb_typeof(raw.expected) = 'null' THEN ''
                                WHEN jsonb_typeof(raw.expected) = 'boolean' THEN CASE WHEN raw.expected = 'true'::jsonb THEN '1' ELSE '' END
                                WHEN jsonb_typeof(raw.expected) IN ('string', 'number') THEN raw.expected #>> '{}'
                                WHEN jsonb_typeof(raw.expected) = 'array' THEN coalesce((
                                    SELECT trim(string_agg(elem, ' '))
                                    FROM jsonb_array_elements_text(raw.expected) elem
                                ), '')
                                WHEN jsonb_typeof(raw.expected) = 'object' THEN coalesce((
                                    SELECT trim(string_agg(field.value, ' ' ORDER BY field.key))
                                    FROM jsonb_each_text(raw.expected) field
                                ), '')
                                ELSE ''
                            END AS expected_text,
                            CASE
                                WHEN raw.actual IS NOT NULL AND jsonb_typeof(raw.actual) = 'boolean' THEN raw.actual = 'true'::jsonb
                                WHEN raw.actual IS NOT NULL AND jsonb_typeof(raw.actual) = 'number' AND (raw.actual #>> '{}') = '1' THEN TRUE
                                WHEN raw.actual IS NOT NULL AND jsonb_typeof(raw.actual) = 'string' AND (raw.actual #>> '{}') IN ('1', 'true', 'on') THEN TRUE
                                ELSE FALSE
                            END AS actual_bool,
                            CASE
                                WHEN raw.expected IS NOT NULL AND jsonb_typeof(raw.expected) = 'boolean' THEN raw.expected = 'true'::jsonb
                                WHEN raw.expected IS NOT NULL AND jsonb_typeof(raw.expected) = 'number' AND (raw.expected #>> '{}') = '1' THEN TRUE
                                WHEN raw.expected IS NOT NULL AND jsonb_typeof(raw.expected) = 'string' AND (raw.expected #>> '{}') IN ('1', 'true', 'on') THEN TRUE
                                ELSE FALSE
                            END AS expected_bool,
                            (
                                raw.actual IS NOT NULL
                                AND raw.expected IS NOT NULL
                                AND jsonb_typeof(raw.actual) <> 'boolean'
                                AND jsonb_typeof(raw.expected) <> 'boolean'
                                AND (raw.actual #>> '{}') ~ '^[+-]??([0-9]+(\.[0-9]*)??|\.[0-9]+)([eE][+-]??[0-9]+)??$'
                                AND (raw.expected #>> '{}') ~ '^[+-]??([0-9]+(\.[0-9]*)??|\.[0-9]+)([eE][+-]??[0-9]+)??$'
                            ) AS both_numeric
                    ) parsed
                    CROSS JOIN LATERAL (
                        SELECT
                            CASE
                                WHEN raw.actual IS NOT NULL AND raw.expected IS NOT NULL
                                 AND (jsonb_typeof(raw.actual) = 'boolean' OR jsonb_typeof(raw.expected) = 'boolean')
                                    THEN (CASE WHEN parsed.actual_bool THEN 1 ELSE 0 END)
                                       - (CASE WHEN parsed.expected_bool THEN 1 ELSE 0 END)
                                WHEN parsed.both_numeric THEN
                                    CASE
                                        WHEN (raw.actual #>> '{}')::numeric > (raw.expected #>> '{}')::numeric THEN 1
                                        WHEN (raw.actual #>> '{}')::numeric < (raw.expected #>> '{}')::numeric THEN -1
                                        ELSE 0
                                    END
                                WHEN parsed.actual_text = parsed.expected_text THEN 0
                                WHEN parsed.actual_text > parsed.expected_text THEN 1
                                ELSE -1
                            END AS cmp,
                            lower(parsed.actual_text) AS actual_fold,
                            lower(parsed.expected_text) AS expected_fold
                    ) compared
                    WHERE r.position_id = p.id
                      AND NOT (
                            raw.actual IS NOT NULL
                        AND raw.actual <> 'null'::jsonb
                        AND raw.actual <> '""'::jsonb
                        AND raw.actual <> '[]'::jsonb
                        AND NOT (
                            jsonb_typeof(raw.actual) = 'object'
                            AND (raw.actual ?? 'from' OR raw.actual ?? 'to')
                            AND coalesce(raw.actual ->> 'from', '') = ''
                            AND coalesce(raw.actual ->> 'to', '') = ''
                        )
                        AND CASE r.operator
                            WHEN 'neq' THEN compared.cmp <> 0
                            WHEN 'gt' THEN compared.cmp > 0
                            WHEN 'gte' THEN compared.cmp >= 0
                            WHEN 'lt' THEN compared.cmp < 0
                            WHEN 'lte' THEN compared.cmp <= 0
                            WHEN 'contains' THEN compared.expected_fold <> '' AND strpos(compared.actual_fold, compared.expected_fold) > 0
                            WHEN 'starts_with' THEN compared.expected_fold <> '' AND starts_with(compared.actual_fold, compared.expected_fold)
                            ELSE compared.cmp = 0
                        END
                      )
               )
            ORDER BY p.updated_at DESC
            SQL;

        $params = ['userId' => $userId];
        $types = ['userId' => ParameterType::INTEGER];
        if ($limit !== null) {
            $sql .= ' LIMIT :limit';
            $params['limit'] = $limit;
            $types['limit'] = ParameterType::INTEGER;
        }

        $ids = $this->getEntityManager()->getConnection()->fetchFirstColumn($sql, $params, $types);

        return $this->findOrdered($ids);
    }

    public function findOneWithTemplate(int $id): ?Position
    {
        $position = $this->createQueryBuilder('p')
            ->leftJoin('p.positionAttributes', 'pa')->addSelect('pa')
            ->leftJoin('pa.attribute', 'attr')->addSelect('attr')
            ->andWhere('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if ($position === null) {
            return null;
        }

        $this->createQueryBuilder('p')
            ->leftJoin('p.accessRules', 'r')->addSelect('r')
            ->leftJoin('r.attribute', 'ruleAttr')->addSelect('ruleAttr')
            ->andWhere('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        return $position;
    }

    /**
     * @param list<int|string> $ids
     * @return list<Position>
     */
    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        /** @var list<Position> $rows */
        $rows = $this->createQueryBuilder('p')
            ->leftJoin('p.accessRules', 'r')->addSelect('r')
            ->leftJoin('r.attribute', 'a')->addSelect('a')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        return $this->orderRows($rows, $ids);
    }

    /**
     * @param list<int|string> $ids
     * @return list<Position>
     */
    private function findOrdered(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        /** @var list<Position> $rows */
        $rows = $this->createQueryBuilder('p')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        return $this->orderRows($rows, $ids);
    }

    /**
     * @param list<Position> $rows
     * @param list<int|string> $ids
     * @return list<Position>
     */
    private function orderRows(array $rows, array $ids): array
    {
        $byId = [];
        foreach ($rows as $row) {
            $byId[$row->getId()] = $row;
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[(int) $id])) {
                $ordered[] = $byId[(int) $id];
            }
        }

        return $ordered;
    }
}

<?php

namespace App\Service;

use App\DTO\SearchFilterDTO;
use App\DTO\SortOptionDTO;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

class PaginationService
{
    public const DEFAULT_PER_PAGE = 15;

    /**
     * @param SearchFilterDTO[] $filters
     * @param SortOptionDTO[]   $sortOptions
     */
    public function paginate(
        QueryBuilder $qb,
        Request $request,
        ?string $searchField = null,
        array $searchFields = [],
        ?string $countField = null,
        array $filters = [],
        array $sortOptions = [],
        ?string $defaultSort = null,
        string $defaultSortDir = 'DESC'
    ): array {
        $page = max(1, $request->query->getInt('page', 1));
        $perPage = max(1, min(100, $request->query->getInt('perPage', self::DEFAULT_PER_PAGE)));
        $search = trim($request->query->get('search', ''));

        // Apply legacy search (text search across fields)
        if ($search && ($searchField || $searchFields)) {
            $fields = $searchFields ?: [$searchField];
            $orConditions = [];
            foreach ($fields as $i => $field) {
                $orConditions[] = "LOWER({$field}) LIKE LOWER(:search_{$i})";
                $qb->setParameter("search_{$i}", "%{$search}%");
            }
            $qb->andWhere(implode(' OR ', $orConditions));
        }

        // Apply advanced filters
        $activeFilters = [];
        $alias = $qb->getRootAliases()[0];
        foreach ($filters as $filter) {
            $value = $request->query->get($filter->name, '');
            if ($value === '' || $value === null) {
                $activeFilters[$filter->name] = '';
                continue;
            }
            $activeFilters[$filter->name] = $value;
            $dqlField = $filter->dqlField ?? "{$alias}.{$filter->name}";
            $paramName = 'f_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $filter->name);

            switch ($filter->operator) {
                case 'LIKE':
                    $qb->andWhere("LOWER({$dqlField}) LIKE LOWER(:{$paramName})")
                       ->setParameter($paramName, "%{$value}%");
                    break;
                case 'EXACT':
                    $qb->andWhere("{$dqlField} = :{$paramName}")
                       ->setParameter($paramName, $value);
                    break;
                case 'GTE':
                    $qb->andWhere("{$dqlField} >= :{$paramName}")
                       ->setParameter($paramName, $value);
                    break;
                case 'LTE':
                    $qb->andWhere("{$dqlField} <= :{$paramName}")
                       ->setParameter($paramName, $value);
                    break;
                case 'IN':
                    $values = is_array($value) ? $value : [$value];
                    $qb->andWhere("{$dqlField} IN (:{$paramName})")
                       ->setParameter($paramName, $values);
                    break;
                case 'BOOL':
                    $qb->andWhere("{$dqlField} = :{$paramName}")
                       ->setParameter($paramName, $value === '1' || $value === 'true');
                    break;
                case 'MONTH_GTE':
                    $qb->andWhere("{$dqlField} >= :{$paramName}")
                       ->setParameter($paramName, $value . '-01');
                    break;
                case 'MONTH_LTE':
                    $qb->andWhere("{$dqlField} <= :{$paramName}")
                       ->setParameter($paramName, $value . '-31');
                    break;
            }
        }

        // Apply sorting
        $sortField = $request->query->get('sort', $defaultSort ?? '');
        $sortDir = strtoupper($request->query->get('dir', $defaultSortDir));
        if (!in_array($sortDir, ['ASC', 'DESC'])) {
            $sortDir = 'DESC';
        }

        $validSortFields = array_map(fn(SortOptionDTO $s) => $s->field, $sortOptions);
        if ($sortField && in_array($sortField, $validSortFields)) {
            $qb->resetDQLPart('orderBy');
            // If sort field contains a dot, use as-is; otherwise prefix with alias
            $sortDql = str_contains($sortField, '.') ? $sortField : "{$alias}.{$sortField}";
            $qb->orderBy($sortDql, $sortDir);
        }

        // Count total
        $countQb = clone $qb;
        $countExpr = $countField ?? "{$alias}.id";
        $countQb->select("COUNT(DISTINCT {$countExpr})");
        $countQb->resetDQLPart('orderBy');
        $totalItems = (int) $countQb->getQuery()->getSingleScalarResult();

        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $page = min($page, $totalPages);

        // Apply pagination
        $items = $qb->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return [
            'items' => $items,
            'totalItems' => $totalItems,
            'currentPage' => $page,
            'itemsPerPage' => $perPage,
            'totalPages' => $totalPages,
            'search' => $search,
            'filters' => $activeFilters,
            'sortField' => $sortField,
            'sortDir' => $sortDir,
            'filterDefs' => $filters,
            'sortOptions' => $sortOptions,
        ];
    }
}

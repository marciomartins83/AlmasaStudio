<?php
namespace App\Service;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

class PaginationService
{
    public const DEFAULT_PER_PAGE = 15;

    public function paginate(
        QueryBuilder $qb,
        Request $request,
        string $searchField = null,
        array $searchFields = [],
        string $countField = null
    ): array {
        $page = max(1, $request->query->getInt('page', 1));
        $perPage = max(1, min(100, $request->query->getInt('perPage', self::DEFAULT_PER_PAGE)));
        $search = trim($request->query->get('search', ''));

        // Apply search
        if ($search && ($searchField || $searchFields)) {
            $fields = $searchFields ?: [$searchField];
            $orConditions = [];
            foreach ($fields as $i => $field) {
                $orConditions[] = "LOWER({$field}) LIKE LOWER(:search_{$i})";
                $qb->setParameter("search_{$i}", "%{$search}%");
            }
            $qb->andWhere(implode(' OR ', $orConditions));
        }

        // Count total
        $countQb = clone $qb;
        $alias = $qb->getRootAliases()[0];
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
        ];
    }
}

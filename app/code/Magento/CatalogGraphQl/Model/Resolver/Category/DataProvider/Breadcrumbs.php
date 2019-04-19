<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Category\DataProvider;

use Magento\CatalogGraphQl\Model\Resolver\CategoryTree\DataProvider\CategoryAttributeQuery;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\GraphQl\Model\Query\Resolver\DataProviderInterface;

/**
 * Breadcrumbs data provider
 */
class Breadcrumbs implements DataProviderInterface
{
    /**
     * @var CategoryAttributeQuery
     */
    private $categoryAttributeQuery;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param CategoryAttributeQuery $categoryAttributeQuery
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        CategoryAttributeQuery $categoryAttributeQuery,
        ResourceConnection $resourceConnection
    ) {
        $this->categoryAttributeQuery = $categoryAttributeQuery;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @inheritdoc
     */
    public function fetch(array $requests): array
    {
        $categoryPaths = \array_column($requests, 'path');

        // retrieve ids from path ignoring first 2 levels and last level: 1/2/3/4 => 3, 1/2/3/4/5 => 3, 4
        // build path map: 1/2/3/4, 1/2/3/4/5 => [1/2/3/4 => 1/2/3, 1/2/3/4/5 => 1/2/3/4]
        $entityIds = [];
        $pathMap = [];
        foreach ($categoryPaths as $path) {
            $pathArray = \explode('/', $path);
            array_pop($pathArray);
            $pathMap[$path]  = \implode('/', $pathArray);
            $pathArray = \array_slice($pathArray, 2);
            $entityIds[] = $pathArray;
        }

        $entityIds = \array_unique(\array_merge(...$entityIds));

        $select = $this->categoryAttributeQuery->getQuery($entityIds, ['name', 'url_key']);
        $union = $select->getPart(Select::SQL_UNION);
        foreach ($union as $partialSelect) {
            $partialSelect[0]->columns(['e.path', 'e.level']);
        }

        $entities = [];
        $statement = $this->resourceConnection->getConnection()->query($select);
        while ($row = $statement->fetch()) {
            $entities[$row['entity_id']]['category_' . $row['attribute_code']] = $row['value'];
            $entities[$row['entity_id']]['category_id'] = $row['entity_id'];
            $entities[$row['entity_id']]['category_path'] = $row['path'];
            $entities[$row['entity_id']]['category_level'] = $row['level'];
        }

        $categories = [];
        foreach ($entities as $entity) {
            $thread = [];
            $path = \explode('/', $entity['category_path']);
            $path = \array_slice($path, 2);
            foreach ($path as $node) {
                $thread[$node] = $entities[$node];
            }
            $categories[$entity['category_path']] = $thread;
        }

        $output = [];
        foreach ($requests as $requestIdentifier => $request) {
            $output[$requestIdentifier] =  $categories[$pathMap[$request['path']]] ?? null;
        }

        return $output;
    }
}

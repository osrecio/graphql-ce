<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\CategoryTree\DataProvider;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\DB\Select;

/**
 * Provide category data for specified category. Optionally includes children categories
 */
class Category
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param int $categoryId
     * @param bool $includeChildren
     * @return array
     * @throws \Zend_Db_Select_Exception
     */
    public function getCategoryData(int $categoryId, bool $includeChildren = false) : array
    {
        $connection = $this->resourceConnection->getConnection();
        $categoryTable = $this->resourceConnection->getTableName('catalog_category_entity');

        $select = $connection->select()
            ->from(['c' => $categoryTable])
            ->where('c.entity_id = ?', $categoryId)
            ->columns(
                [
                    'relevant_path' => new Expression("CAST(c.entity_id as CHAR)")
                ]);

        if ($includeChildren) {
            $children = $connection->select()
                ->from(['c' => $categoryTable])
                ->join(
                    ['p' => $categoryTable],
                    "c.path LIKE CONCAT(p.path, '/%')",
                    []
                )
                ->where('p.entity_id = ?', $categoryId)
                ->columns(
                    [
                        'relevant_path' => new Expression(
                            "SUBSTR(c.path, LENGTH(p.path) - LENGTH(CAST(p.entity_id as CHAR)) + 1)"
                        )
                    ]);
            $select = $connection->select()->union([$children, $select], Select::SQL_UNION_ALL);
        }
        return $connection->fetchAll($select);
    }
}

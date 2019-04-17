<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\CategoryTree\DataProvider;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Provide category data for specified category. Optionally includes children categories.
 * Do not return data for tree root category (id = 1)
 */
class Category
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var Config
     */
    private $eavConfig;
    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param ResourceConnection $resourceConnection
     * @param Config $eavConfig
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        Config $eavConfig,
        MetadataPool $metadataPool
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->eavConfig = $eavConfig;
        $this->metadataPool = $metadataPool;
    }

    /**
     * @param int $categoryId
     * @param int $storeId
     * @param bool $includeChildren
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Select_Exception
     */
    public function getCategoryData(int $categoryId, int $storeId, bool $includeChildren = false) : array
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
        $this->joinIsActiveAttribute($select, $storeId, 1);
        $categories = $connection->fetchAll($select);

        if ($includeChildren && $categories) {
            $subquery = $connection->select()
                ->from(['c' => $categoryTable], [])
                ->join(
                    ['p' => $categoryTable],
                    "c.path LIKE CONCAT(p.path, '/%')",
                    []
                )
                ->where('p.entity_id = ?', $categoryId)
                ->columns(
                    [
                        'paths' => new Expression(
                            "CONCAT(GROUP_CONCAT(c.path  SEPARATOR '$|'), '$|', GROUP_CONCAT(c.path  SEPARATOR '/|'))"
                        )
                    ]);

            $this->joinIsActiveAttribute($subquery, $storeId, 0);

            $children = $connection->select()
                ->from(['c' => $categoryTable])
                ->join(
                    ['p' => $categoryTable],
                    "c.path LIKE CONCAT(p.path, '/%')",
                    []
                )
                ->where('p.entity_id = ?', $categoryId)
                ->where('c.path NOT REGEXP (?)', $connection->getIfNullSql($subquery, "'no-path'"))
                ->columns(
                    [
                        'relevant_path' => new Expression(
                            "SUBSTR(c.path, LENGTH(p.path) - LENGTH(CAST(p.entity_id as CHAR)) + 1)"
                        )
                    ]);

            $categories = \array_merge($categories, $connection->fetchAll($children));
        }

        return $categories;
    }

    /**
     * @param Select $select
     * @param int $storeId
     * @param int $value
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function joinIsActiveAttribute(Select $select, int $storeId, int $value): void
    {
        $attribute = $this->eavConfig->getAttribute(\Magento\Catalog\Model\Category::ENTITY, 'is_active');
        $attributeId = $attribute->getAttributeId();
        $attributeTable = $attribute->getBackend()->getTable();
        $connection = $this->resourceConnection->getConnection();
        $linkFieldId = $this->metadataPool->getMetadata(CategoryInterface::class)->getLinkField();

        $defaultAlias = 'default_is_active';
        $storeAlias = 'store_is_active';

        $select->join(
            [$defaultAlias => $attributeTable],
            "{$defaultAlias}.{$linkFieldId} = c.{$linkFieldId} AND {$defaultAlias}.attribute_id = {$attributeId}" .
            " AND {$defaultAlias}.store_id = 0",
            []
        );
        $select->joinLeft(
            [$storeAlias => $attributeTable],
            "{$storeAlias}.{$linkFieldId} = c.{$linkFieldId} AND {$storeAlias}.attribute_id = {$attributeId}" .
            " AND {$storeAlias}.store_id = {$storeId}",
            []
        );
        $whereExpression = $connection->getCheckSql(
            $connection->getIfNullSql("{$storeAlias}.value_id", -1) . ' > 0',
            "{$storeAlias}.value",
            "{$defaultAlias}.value"
        );

        $select->where("{$whereExpression} = ?", $value);
    }
}

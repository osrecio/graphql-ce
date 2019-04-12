<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\CategoryTree\DataProvider;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Provide category attributes for specified category ids and attributes
 */
class CategoryAttribute
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
     * @return array
     */
    private function getAttributeTables(): array
    {
        return [
            'catalog_category_entity_int',
            'catalog_category_entity_decimal',
            'catalog_category_entity_text',
            'catalog_category_entity_varchar',
            'catalog_category_entity_datetime',
        ];
    }

    /**
     * @param array $entityIds
     * @param array $attributeCodes
     * @return array
     * @throws \Zend_Db_Select_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function getAttributesData(array $entityIds, array $attributeCodes): array
    {
        $attributes = [];
        $connection = $this->resourceConnection->getConnection();
        $entityTableName = $this->resourceConnection->getTableName('catalog_category_entity');
        $attributeMetadataTable = $this->resourceConnection->getTableName('eav_attribute');
        $selects = [];
        $linkField = $connection->getAutoIncrementField($entityTableName);
        foreach ($this->getAttributeTables() as $attributeTable) {
            $selects[] = $connection->select()
                ->from(['e' => $this->resourceConnection->getTableName($entityTableName)], [])
                ->join(
                    ['v' => $this->resourceConnection->getTableName($attributeTable)],
                    sprintf('e.%1$s = v.%1$s', $linkField),
                    []
                )
                ->join(
                    ['a' => $attributeMetadataTable],
                    'v.attribute_id = a.attribute_id AND a.entity_type_id = 3',
                    []
                )
                ->where('e.entity_id IN (?)', $entityIds)
                ->where('a.attribute_code IN (?)', $attributeCodes)
                ->columns(
                    [
                        'entity_id' => 'e.entity_id',
                        'attribute_code' => 'a.attribute_code',
                        'value' => 'v.value'
                    ]
                );
        }
        $statement = $connection->query(
            $connection->select()->union($selects, Select::SQL_UNION_ALL)
        );
        while ($row = $statement->fetch()) {
            $attributes[$row['entity_id']][$row['attribute_code']] = $row['value'];
        }

        return $attributes;
    }
}

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
class CategoryAttributeQuery
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * @var int|null
     */
    private $entityTypeId;

    /**
     * @param ResourceConnection $resourceConnection
     * @param \Magento\Eav\Model\Config $eavConfig
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        \Magento\Eav\Model\Config $eavConfig
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->eavConfig = $eavConfig;
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getQuery(array $entityIds, array $attributeCodes): Select
    {
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
                    'v.attribute_id = a.attribute_id',
                    []
                )
                ->where('e.entity_id IN (?)', $entityIds)
                ->where('a.entity_type_id = ?', $this->getEntityTypeId())
                ->where('a.attribute_code IN (?)', $attributeCodes)
                ->columns(
                    [
                        'entity_id' => 'e.entity_id',
                        'attribute_code' => 'a.attribute_code',
                        'value' => 'v.value'
                    ]
                );
        }

        return $connection->select()->union($selects, Select::SQL_UNION_ALL);
    }

    /**
     * Retrieve catalog_product entity type id
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getEntityTypeId()
    {
        if ($this->entityTypeId === null) {
            $this->entityTypeId = (int)$this->eavConfig->getEntityType(\Magento\Catalog\Model\Category::ENTITY)
                ->getId();
        }
        return $this->entityTypeId;
    }
}

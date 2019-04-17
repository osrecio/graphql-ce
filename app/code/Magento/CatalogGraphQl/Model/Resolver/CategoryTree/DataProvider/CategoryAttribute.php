<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\CategoryTree\DataProvider;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\GraphQl\Config\Element\Type;
use Magento\Framework\GraphQl\ConfigInterface;
use Magento\Framework\GraphQl\Config\Element\InterfaceType;

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
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * @var ConfigInterface
     */
    private $graphqlConfig;

    /**
     * @var int|null
     */
    private $entityTypeId;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ConfigInterface $graphqlConfig
     * @param \Magento\Eav\Model\Config $eavConfig
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ConfigInterface $graphqlConfig,
        \Magento\Eav\Model\Config $eavConfig
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->eavConfig = $eavConfig;
        $this->graphqlConfig = $graphqlConfig;
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAttributesData(array $entityIds, array $attributeCodes): array
    {
        $attributes = [];
        $connection = $this->resourceConnection->getConnection();
        $entityTableName = $this->resourceConnection->getTableName('catalog_category_entity');
        $attributeMetadataTable = $this->resourceConnection->getTableName('eav_attribute');
        $selects = [];
        $linkField = $connection->getAutoIncrementField($entityTableName);
        $bind = [
            ':entity_type_id' => $this->getEntityTypeId(),
        ];
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
                    'v.attribute_id = a.attribute_id AND a.entity_type_id = :entity_type_id',
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
            $connection->select()->union($selects, Select::SQL_UNION_ALL),
            $bind
        );

        while ($row = $statement->fetch()) {
            $attributes[$row['entity_id']][$row['attribute_code']] = $row['value'];
        }

        $arrayTypeAttributes = $this->getFieldsOfArrayType();

        return $arrayTypeAttributes
            ? array_map(function ($data) use ($arrayTypeAttributes) {
                foreach ($arrayTypeAttributes as $attributeCode) {
                    $data[$attributeCode] = $this->valueToArray($data[$attributeCode] ?? null);
                }
                return $data;
            }, $attributes)
            : $attributes;
    }

    /**
     * @param string|null $value
     * @return array
     */
    private function valueToArray($value): array
    {
        return $value ? \explode(',', $value) : [];
    }

    /**
     * Get fields that should be converted to array type
     *
     * @return array
     */
    private function getFieldsOfArrayType(): array
    {
        $categoryTreeSchema = $this->graphqlConfig->getConfigElement('CategoryTree');
        if (!$categoryTreeSchema instanceof Type) {
            throw new \LogicException('CategoryTree type not defined in schema.');
        }

        $fields = [];
        foreach ($categoryTreeSchema->getInterfaces() as $interface) {
            /** @var InterfaceType $configElement */
            $configElement = $this->graphqlConfig->getConfigElement($interface['interface']);

            foreach ($configElement->getFields() as $field) {
                if ($field->isList()) {
                    $fields[] = $field->getName();
                }
            }
        }

        return $fields;
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

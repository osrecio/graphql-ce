<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\CategoryTree\DataProvider;

use Magento\Framework\App\ResourceConnection;
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
     * @var ConfigInterface
     */
    private $graphqlConfig;

    /**
     * @var CategoryAttributeQuery
     */
    private $categoryAttributeQuery;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ConfigInterface $graphqlConfig
     * @param CategoryAttributeQuery $categoryAttributeQuery
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ConfigInterface $graphqlConfig,
        CategoryAttributeQuery $categoryAttributeQuery
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->graphqlConfig = $graphqlConfig;
        $this->categoryAttributeQuery = $categoryAttributeQuery;
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

        $statement = $connection->query(
            $this->categoryAttributeQuery->getQuery($entityIds, $attributeCodes)
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
}

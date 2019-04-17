<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\CategoryTree\DataProvider;

use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\GraphQl\Model\Query\Resolver\DataProviderInterface;

/**
 * Provide category tree by given category
 */
class CategoryTree implements DataProviderInterface
{
    /**
     * @var Category
     */
    private $category;

    /**
     * @var CategoryAttribute
     */
    private $categoryAttribute;

    /**
     * @param Category $category
     * @param CategoryAttribute $categoryAttribute
     */
    public function __construct(
        Category $category,
        CategoryAttribute $categoryAttribute
    ) {
        $this->category = $category;
        $this->categoryAttribute = $categoryAttribute;
    }

    /**
     * @inheritdoc
     */
    public function fetch(array $requests): array
    {
        $output = [];
        foreach ($requests as $requestIdentifier => $request) {
            $output[$requestIdentifier] = $this->getCategoryTree(
                $request['categoryId'],
                $request['attributeCodes'],
                $request['storeId'],
                $request['includeChildren']
            );
        }
        return $output;
    }

    /**
     * @param int $categoryId
     * @param array $attributeCodes
     * @param int $storeId
     * @param bool $includeChildren
     * @return array
     * @throws GraphQlNoSuchEntityException
     * @throws \Zend_Db_Select_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    private function getCategoryTree(int $categoryId, array $attributeCodes, int $storeId, bool $includeChildren = false): array
    {
        $categories = [];
        $entities = $this->category->getCategoryData($categoryId, $storeId, $includeChildren);
        if (empty($entities)) {
            throw new GraphQlNoSuchEntityException(__('Category doesn\'t exist'));
        }
        $entityIds = [];
        foreach ($entities as $entity) {
            $entityIds[] = $entity['entity_id'];
        }
        $attributes = $this->categoryAttribute->getAttributesData($entityIds, $attributeCodes);

        foreach ($entities as $entity) {
            $thread = null;
            $path = explode('/', $entity['relevant_path']);
            $path = array_reverse($path);
            foreach ($path as $node) {
                if ($thread === null) {
                    $data = array_replace_recursive($entity, $attributes[$entity['entity_id']] ?? []);
                    $data['id'] = $data['entity_id'];
                    $data['children'] = [];
                    $thread['children'] = [$node => $data];
                } else {
                    $thread['children'] = [$node => $thread];
                }
            }
            $categories = array_replace_recursive($categories, $thread);
        }
        $categories = $categories['children'][$categoryId];
        return $categories;
    }
}

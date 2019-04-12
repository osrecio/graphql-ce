<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Category\DataProvider;

use Magento\Framework\App\ResourceConnection;
use Magento\GraphQl\Model\Query\Resolver\DataProviderInterface;
use Magento\Catalog\Model\Product\Visibility;

/**
 * Provide product count per category
 */
class ProductsCount implements DataProviderInterface
{
    /**
     * @var Visibility
     */
    private $catalogProductVisibility;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * ProductCount constructor.
     * @param Visibility $catalogProductVisibility
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Visibility $catalogProductVisibility,
        ResourceConnection $resourceConnection
    ) {

        $this->catalogProductVisibility = $catalogProductVisibility;
        $this->resourceConnection = $resourceConnection;
    }

    public function fetch(array $requests): array
    {
        $output = [];
        $categoryIds = array_column($requests, 'categoryId');

        $productCount = $this->getProductCount($categoryIds);
        foreach ($requests as $requestIdentifier => $request) {
            $output[$requestIdentifier] = $productCount[$request['categoryId']] ?? null;
        }

        return $output;
    }

    /**
     * Get count of products per category. Return data in format [category_id => product_count, ...]
     *
     * @param array $categoryIds
     * @return array
     */
    private function getProductCount(array $categoryIds): array
    {
        $connection = $this->resourceConnection->getConnection();
        $categoryTable = $this->resourceConnection->getTableName('catalog_category_product_index');

        $select = $connection->select()
            ->from(
                ['cat_index' => $categoryTable],
                ['category_id' => 'cat_index.category_id', 'count' => 'count(cat_index.product_id)']
            )
            // TODO: fix modularity
            ->joinInner(
                ['stock_status_index' => $this->resourceConnection->getTableName('cataloginventory_stock_status')],
                'stock_status_index.product_id = cat_index.product_id',
                []
            )
            ->where('stock_status_index.website_id = ?', 0)
            ->where('stock_status_index.stock_id = ?', 1)
            ->where('stock_status_index.stock_status = ?', 1)
            ->where('cat_index.visibility in (?)', $this->catalogProductVisibility->getVisibleInSiteIds())
            ->where('cat_index.category_id in (?)',  array_map('intval', $categoryIds))
            ->group('cat_index.category_id');

        return $connection->fetchPairs($select);
    }
}

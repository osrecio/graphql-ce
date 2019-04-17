<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Category\DataProvider;

use Magento\Framework\App\ResourceConnection;
use Magento\GraphQl\Model\Query\Resolver\DataProviderInterface;

/**
 * Provide product count per category
 */
class ProductsCount implements DataProviderInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductsCountQuery
     */
    private $productsCountQuery;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ProductsCountQuery $productsCountQuery
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ProductsCountQuery $productsCountQuery
    ) {

        $this->resourceConnection = $resourceConnection;
        $this->productsCountQuery = $productsCountQuery;
    }

    /**
     * Get count of products per category. Return data in format [category_id => product_count, ...]
     *
     * @param array $requests
     * @return array
     */
    public function fetch(array $requests): array
    {
        $output = [];
        $categoryIds = array_column($requests, 'categoryId');

        $connection = $this->resourceConnection->getConnection();
        $productCount = $connection->fetchPairs($this->productsCountQuery->getQuery($categoryIds));

        foreach ($requests as $requestIdentifier => $request) {
            $output[$requestIdentifier] = $productCount[$request['categoryId']] ?? null;
        }

        return $output;
    }
}

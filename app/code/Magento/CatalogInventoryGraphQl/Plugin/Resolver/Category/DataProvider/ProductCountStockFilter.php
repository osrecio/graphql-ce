<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogInventoryGraphQl\Plugin\Resolver\Category\DataProvider;

use Magento\CatalogGraphQl\Model\Resolver\Category\DataProvider\ProductsCountQuery;
use Magento\CatalogInventory\Model\Configuration;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 * Add stock filter to product count query
 */
class ProductCountStockFilter
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @param ResourceConnection $resourceConnection
     * @param Configuration $configuration
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        Configuration $configuration
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->configuration = $configuration;
    }

    /**
     * @param ProductsCountQuery $subject
     * @param Select $select
     * @return Select
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetQuery(ProductsCountQuery $subject, Select $select)
    {
        if ($this->configuration->isShowOutOfStock()) {
            return $select;
        }
        $select
            ->joinInner(
                ['stock_status_index' => $this->resourceConnection->getTableName('cataloginventory_stock_status')],
                'stock_status_index.product_id = cat_index.product_id',
                []
            )
            ->where('stock_status_index.website_id = ?', 0)
            ->where('stock_status_index.stock_id = ?', 1)
            ->where('stock_status_index.stock_status = ?', 1);

        return $select;
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Category;

use Magento\CatalogGraphQl\Model\Resolver\Category\DataProvider\ProductsCount as ProductsCountDataProvider;
use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\GraphQl\Model\Query\Resolver\RequestRepository;

/**
 * Retrieves products count for a category
 */
class ProductsCount implements ResolverInterface
{
    /**
     * @var ValueFactory
     */
    private $valueFactory;

    /**
     * ProductsCount constructor.
     * @param ValueFactory $valueFactory
     */
    public function __construct(
        ValueFactory $valueFactory
    ) {
        $this->valueFactory = $valueFactory;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $queryIdentifier = uniqid('request-', true);
        /** @var \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $requestRepository */
        $requestRepository = $context->getExtensionAttributes()->getRequestRepository();

        /** @var RequestRepository $requestRepository*/
        $requestRepository->registerRequest(
            $queryIdentifier,
            ProductsCountDataProvider::class,
            [
                'categoryId' => $value['entity_id'],
            ]
        );
        $result = function () use ($queryIdentifier, $requestRepository) {
            return $requestRepository->getRequestedData($queryIdentifier);
        };
        return $this->valueFactory->create($result);
    }
}

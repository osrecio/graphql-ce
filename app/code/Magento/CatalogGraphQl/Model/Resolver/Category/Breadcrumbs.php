<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Category;

use Magento\CatalogGraphQl\Model\Resolver\Category\DataProvider\Breadcrumbs as BreadcrumbsDataProvider;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;
use Magento\GraphQl\Model\Query\Resolver\RequestRepository;

/**
 * Retrieves breadcrumbs
 */
class Breadcrumbs implements ResolverInterface
{
    /**
     * @var ValueFactory
     */
    private $valueFactory;

    /**
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
        if (!isset($value['path'])) {
            throw new LocalizedException(__('"path" value should be specified'));
        }

        $queryIdentifier = uniqid('request-', true);
        /** @var \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $requestRepository */
        $requestRepository = $context->getExtensionAttributes()->getRequestRepository();
        /** @var RequestRepository $requestRepository */
        $requestRepository->registerRequest(
            $queryIdentifier,
            BreadcrumbsDataProvider::class,
            [
                'path' => $value['path'],
            ]
        );
        $result = function () use ($queryIdentifier, $requestRepository) {
            return $requestRepository->getRequestedData($queryIdentifier);
        };
        return $this->valueFactory->create($result);
    }
}

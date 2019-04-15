<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\Resolver\FieldResolver;
use Magento\GraphQl\Model\Query\Resolver\RequestRepository;
use Magento\CatalogGraphQl\Model\Resolver\CategoryTree\DataProvider\CategoryTree as CategoryTreeDataProvider;

/**
 * Class CategoryTree
 */
class CategoryTree implements ResolverInterface
{
    /**
     * @var ValueFactory
     */
    private $valueFactory;

    /**
     * @var FieldResolver
     */
    private $fieldResolver;

    /**
     * @param ValueFactory $valueFactory
     * @param FieldResolver $fieldResolver
     */
    public function __construct(
        ValueFactory $valueFactory,
        FieldResolver $fieldResolver
    ) {
        $this->valueFactory = $valueFactory;
        $this->fieldResolver = $fieldResolver;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($args['id'])) {
            return null;
        }
        $queryIdentifier = uniqid('request-', true);
        /** @var \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $requestRepository */
        $requestRepository = $context->getExtensionAttributes()->getRequestRepository();
        $attributes = $this->fieldResolver->getFields($info);
        /** @var RequestRepository $requestRepository*/
        $requestRepository->registerRequest(
            $queryIdentifier,
            CategoryTreeDataProvider::class,
            [
                'categoryId' => $args['id'],
                'attributeCodes' => $attributes,
                'includeChildren' => \in_array('children', $attributes, true)
            ]
        );
        $result = function () use ($queryIdentifier, $requestRepository) {
            return $requestRepository->getRequestedData($queryIdentifier);
        };
        return $this->valueFactory->create($result);
    }
}

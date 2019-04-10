<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\Resolver\DummyDataProvider;
use Magento\GraphQl\Model\Query\Resolver\RequestRepository;

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
     * CategoryTree constructor.
     * @param ValueFactory $valueFactory
     */
    public function __construct(
        ValueFactory $valueFactory
    ) {
        $this->valueFactory = $valueFactory;
    }

    /**
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return Value|mixed|null
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($args['id'])) {
            return null;
        }
        $queryIdentifier = uniqid('request-', true);
        /** @var \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $requestRepository */
        $requestRepository = $context->getExtensionAttributes()->getRequestRepository();
        $attributes = array_keys($info->getFieldSelection());
        /** @var RequestRepository $requestRepository*/
        $requestRepository->registerRequest(
            $queryIdentifier,
            DummyDataProvider::class,
            [
                'categoryId' => $args['id'],
                'attributeCodes' => $attributes,
                'includeChildren' => in_array('children', $attributes)
            ]
        );
        $result = function () use ($queryIdentifier, $requestRepository) {
            return $requestRepository->getRequestedData($queryIdentifier);
        };
        return $this->valueFactory->create($result);
    }
}

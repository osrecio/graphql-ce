<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Model\Query\Resolver;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\ContextExtension;

/**
 * Class ContextFactory
 */
class ContextFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * ContextFactory constructor.
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * @return ContextInterface
     */
    public function create() : ContextInterface
    {
        /** @var RequestRepository $requestRepository */
        $requestRepository = $this->objectManager->create(RequestRepository::class);
        /** @var ContextExtension $extension */
        $extension = $this->objectManager->create(ContextExtension::class);
        /** @var ContextInterface $context */
        $context = $this->objectManager->create(ContextInterface::class);
        $extension->setRequestRepository($requestRepository);
        $context->setExtensionAttributes($extension);
        return $context;
    }
}

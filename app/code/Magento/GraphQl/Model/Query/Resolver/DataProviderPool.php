<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Model\Query\Resolver;

use Magento\Framework\ObjectManagerInterface;

/**
 * Class DataProviderPool
 */
class DataProviderPool
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * DataProviderFactory constructor.
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * @param string $providerName
     * @return DataProviderInterface
     */
    public function get(string $providerName) : DataProviderInterface
    {
        return $this->objectManager->get($providerName);
    }
}
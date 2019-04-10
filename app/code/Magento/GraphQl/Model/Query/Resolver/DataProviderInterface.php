<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Model\Query\Resolver;

/**
 * Interface DataProviderInterface
 * @package Magento\GraphQl\Model
 */
interface DataProviderInterface
{
    /**
     * Fetch data from provider to satisfy requests,
     * Structure of data must follows the next pattern
     * [
     *      'requestIdentifier' => [
     *          [ ... row 1 ... ],
     *          [ ... row 2 ... ]
     *      ]
     * ]
     *
     * @param array $requests
     * @return array
     */
    public function fetch(array $requests) : array;
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerGraphQl\Model\Customer;

use Magento\Customer\Model\CustomerExtractor;
use Magento\Customer\Model\Customer\Mapper;
use Magento\Framework\App\RequestInterfaceFactory;

class MergeCustomerData
{
    /**
     * @var CustomerExtractor
     */
    private $customerExtractor;

    /**
     * @var Mapper
     */
    private $mapper;

    /**
     * @var RequestInterfaceFactory
     */
    private $requestFactory;

    public function __construct(
        CustomerExtractor $customerExtractor,
        Mapper $mapper,
        RequestInterfaceFactory $requestFactory
    ) {
        $this->customerExtractor = $customerExtractor;
        $this->mapper = $mapper;
        $this->requestFactory = $requestFactory;
    }

    /**
     * Merge existing customer data with the new data array
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $cutomerDataObject
     * @param array $newData
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    public function execute($cutomerDataObject, $newData)
    {
        /**
         * Put data array to request
         */
        /** @var \Magento\Framework\App\RequestInterface $request */
        $request = $this->requestFactory->create();
        $request->setParams($newData);
        $customerAttributeValues = $this->mapper->toFlatArray($cutomerDataObject);
        /**
         * Merge data
         */
        $newCustomerDataObject = $this->customerExtractor->extract(
            'customer_account_edit',
            $request,
            $customerAttributeValues
        );

        return $newCustomerDataObject;
    }
}
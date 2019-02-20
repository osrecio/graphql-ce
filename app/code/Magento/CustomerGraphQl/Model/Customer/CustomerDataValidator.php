<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerGraphQl\Model\Customer;

use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Framework\App\RequestInterfaceFactory;

class CustomerDataValidator
{
    /**
     * @var FormFactory
     */
    private $formFactory;


    public function __construct(
        FormFactory $formFactory,
        RequestInterfaceFactory $requestFactory
    ) {
        $this->formFactory = $formFactory;
        $this->requestFactory = $requestFactory;
    }

    /**
     * @param array $customerData
     *
     * @return bool|array
     */
    public function validate($customerData)
    {
        /**
         * Put data array to request
         */
        /** @var \Magento\Framework\App\RequestInterface $request */
        $request = $this->requestFactory->create();
        $request->setParams($customerData);
        /** @var \Magento\Customer\Model\Metadata\Form $customerForm */
        $customerForm = $this->formFactory->create('customer', 'customer_account_edit');
        /**
         *  Feed the request data to the customer form for the further validation
         */
        $customerData = $customerForm->extractData($request);
        $validationResult = $customerForm->validateData($customerData);

        return $validationResult;
    }
}
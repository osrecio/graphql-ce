<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Model\Query\Resolver;

/**
 * Class RequestRepository
 */
class RequestRepository
{
    /**
     * @var
     */
    private $requestProviders;

    /**
     * @var array
     */
    protected $providerInfo;

    /**
     * @var array
     */
    private $data = [];
    /**
     * @var DataProviderPool
     */
    private $dataProviderPool;

    /**
     * RequestRepository constructor.
     *
     * @param DataProviderPool $dataProviderPool
     */
    public function __construct(
        DataProviderPool $dataProviderPool
    ) {
        $this->dataProviderPool = $dataProviderPool;
    }

    /**
     * @param string $requestIdentifier
     * @param string $providerName
     * @param array $arguments
     */
    public function registerRequest(string $requestIdentifier, string $providerName, array $arguments) : void
    {
        $this->requestProviders[$requestIdentifier] = $providerName;
        $this->providerInfo[$providerName][$requestIdentifier] = $arguments;
    }

    /**
     * Get data for specified request.
     * Return mixed type: array for "parent" resolver
     * and array OR scalar type for "child" resolver (e.g. product count for category)
     *
     * @param string $queryIdentifier
     * @return mixed
     */
    public function getRequestedData(string $queryIdentifier)
    {
        if (!isset($this->data[$queryIdentifier])) {
            $this->fetchProvider($this->requestProviders[$queryIdentifier]);
        }
        return $this->data[$queryIdentifier];
    }

    /**
     * @param string $providerName
     */
    private function fetchProvider(string $providerName) : void
    {
        $provider = $this->dataProviderPool->get($providerName);
        $data = $provider->fetch($this->providerInfo[$providerName]);
        $this->data = array_replace_recursive($this->data, $data);
    }
}

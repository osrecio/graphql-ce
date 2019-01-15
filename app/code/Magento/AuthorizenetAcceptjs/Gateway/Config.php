<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\AuthorizenetAcceptjs\Gateway;

use \Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Houses configuration for this gateway
 */
class Config extends \Magento\Payment\Gateway\Config\Config
{
    private const KEY_LOGIN_ID = 'login';
    private const KEY_TRANSACTION_KEY = 'trans_key';
    private const KEY_API_URL = 'api_url';
    private const KEY_LEGACY_TRANSACTION_HASH = 'trans_md5';
    private const KEY_SIGNATURE_KEY = 'signature_key';
    private const KEY_PAYMENT_ACTION = 'payment_action';
    private const KEY_SHOULD_EMAIL_CUSTOMER = 'email_customer';
    private const KEY_ADDITIONAL_INFO_KEYS = 'additional_info_keys';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param null|string $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $methodCode = null,
        $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
    }

    /**
     * Gets the login id
     *
     * @param int|null $storeId
     * @return string
     */
    public function getLoginId($storeId = null): ?string
    {
        return $this->getValue(Config::KEY_LOGIN_ID, $storeId);
    }

    /**
     * Gets the transaction key
     *
     * @param int|null $storeId
     * @return string
     */
    public function getTransactionKey($storeId = null): ?string
    {
        return $this->getValue(Config::KEY_TRANSACTION_KEY, $storeId);
    }

    /**
     * Gets the API endpoint URL
     *
     * @param int|null $storeId
     * @return string
     */
    public function getApiUrl($storeId = null): ?string
    {
        return $this->getValue(Config::KEY_API_URL, $storeId);
    }

    /**
     * Gets the configured signature key
     *
     * @param int|null $storeId
     * @return string
     */
    public function getTransactionSignatureKey($storeId = null): ?string
    {
        return $this->getValue(Config::KEY_SIGNATURE_KEY, $storeId);
    }

    /**
     * Gets the configured legacy transaction hash
     *
     * @param int|null $storeId
     * @return string
     */
    public function getLegacyTransactionHash($storeId = null): ?string
    {
        return $this->getValue(Config::KEY_LEGACY_TRANSACTION_HASH, $storeId);
    }

    /**
     * Gets the configured payment action
     *
     * @param int|null $storeId
     * @return string
     */
    public function getPaymentAction($storeId = null): ?string
    {
        return $this->getValue(Config::KEY_PAYMENT_ACTION, $storeId);
    }

    /**
     * Should authorize.net email the customer their receipt.
     *
     * @param int|null $storeId
     * @return string
     */
    public function shouldEmailCustomer($storeId = null): ?string
    {
        return $this->getValue(Config::KEY_SHOULD_EMAIL_CUSTOMER, $storeId);
    }

    /**
     * Returns the keys to be pulled from the transaction and displayed
     *
     * @param int|null $storeId
     * @return string[]
     */
    public function getAdditionalInfoKeys($storeId = null): array
    {
        return explode(',', $this->getValue(Config::KEY_ADDITIONAL_INFO_KEYS, $storeId) ?? '');
    }
}

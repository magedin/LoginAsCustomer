<?php

declare(strict_types = 1);

namespace MagedIn\LoginAsCustomer\Model\Validator;

use MagedIn\LoginAsCustomer\Model\SecretManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class ParametersValidator
 *
 * @package MagedIn\LoginAsCustomer\Model\Validator
 */
class ParametersValidator
{
    /**
     * @var string
     */
    const PARAM_CUSTOMER_ID = 'customer_id';

    /**
     * @var string
     */
    const PARAM_STORE_ID = 'store_id';

    /**
     * @var string
     */
    const PARAM_SECRET = 'secret';

    /**
     * @var string
     */
    const PARAM_ADMIN_USER_ID = 'admin_user_id';

    /**
     * @var string
     */
    const PARAM_HASH = 'hash';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var SecretManager
     */
    private $secretManager;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer
     */
    private $customerResource;

    /**
     * @var \Magento\User\Model\ResourceModel\User\CollectionFactory
     */
    private $userCollectionFactory;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\ResourceModel\Customer $customerResource,
        \Magento\User\Model\ResourceModel\User\CollectionFactory $userCollectionFactory,
        SecretManager $secretManager
    ) {
        $this->storeManager = $storeManager;
        $this->secretManager = $secretManager;
        $this->customerResource = $customerResource;
        $this->userCollectionFactory = $userCollectionFactory;
    }

    /**
     * @param array $params
     *
     * @return bool
     * @throws LocalizedException|NoSuchEntityException
     */
    public function validate(array $params = []) : bool
    {
        if (empty($params)) {
            throw new LocalizedException(__('Invalid parameters.'));
        }

        if (!$this->validateCustomerIdParam($params)) {
            throw new NoSuchEntityException(__('Customer does not exist or customer parameters are invalid.'));
        }

        if (!$this->validateStoreIdParam($params)) {
            throw new NoSuchEntityException(__('Store does not exist or store parameters are invalid.'));
        }

        if (!$this->validateSecretParam($params)) {
            throw new LocalizedException(__('Secret parameter is not valid.'));
        }

        if (!$this->validateAdminUserIdParam($params)) {
            throw new LocalizedException(__('Admin user parameter is not valid.'));
        }

        $customerId  = (int)    $params[self::PARAM_CUSTOMER_ID];
        $storeId     = (int)    $params[self::PARAM_STORE_ID];
        $adminUserId = (int)    $params[self::PARAM_ADMIN_USER_ID];
        $secret      = (string) $params[self::PARAM_SECRET];

        if (!$this->validateSecret($customerId, $storeId, $secret, $adminUserId)) {
            throw new NoSuchEntityException(__('Secret was not found or is expired.'));
        }

        return true;
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    private function validateCustomerIdParam(array $params) : bool
    {
        if (!isset($params[self::PARAM_CUSTOMER_ID])) {
            return false;
        }

        $customerId = $params[self::PARAM_CUSTOMER_ID];

        if (!$this->customerResource->checkCustomerId($customerId)) {
            return false;
        }

        return true;
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    private function validateStoreIdParam(array $params) : bool
    {
        if (!isset($params[self::PARAM_STORE_ID])) {
            return false;
        }

        $storeId = $params[self::PARAM_STORE_ID];
        if (null === $storeId || !$this->validateStoreId($storeId)) {
            return false;
        }

        return true;
    }

    /**
     * @param int $storeId
     *
     * @return bool
     */
    private function validateStoreId(int $storeId) : bool
    {
        try {
            $store = $this->storeManager->getStore($storeId);
        } catch (\Exception $e) {
            return false;
        }

        if (!is_subclass_of($store, \Magento\Store\Api\Data\StoreInterface::class)) {
            return false;
        }

        return true;
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    private function validateSecretParam(array $params) : bool
    {
        if (!isset($params[self::PARAM_SECRET])) {
            return false;
        }

        if (SecretManager::HASH_LENGTH != strlen($params[self::PARAM_SECRET])) {
            return false;
        }

        return true;
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    private function validateAdminUserIdParam(array $params) : bool
    {
        if (!isset($params[self::PARAM_ADMIN_USER_ID])) {
            return false;
        }

        $adminUserId = (int) $params[self::PARAM_ADMIN_USER_ID];

        /** @var \Magento\User\Model\ResourceModel\User\Collection $collection */
        $collection = $this->userCollectionFactory->create();
        $collection->addFieldToFilter('main_table.user_id', $adminUserId)
            ->getSelect()
            ->limit(1);

        if (!$collection->getSize()) {
            return false;
        }

        return true;
    }

    /**
     * @param int    $customerId
     * @param int    $storeId
     * @param string $secret
     * @param int    $adminUserId
     *
     * @return bool
     */
    private function validateSecret(int $customerId, int $storeId, string $secret, int $adminUserId) : bool
    {
        if (!$this->secretManager->match($customerId, $storeId, $secret, $adminUserId)) {
            return false;
        }

        return true;
    }
}
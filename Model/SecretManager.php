<?php
/**
 * Copyright © MagedIn. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @author Tiago Sampaio <tiago.sampaio@magedin.com>
 */

declare(strict_types=1);

namespace MagedIn\LoginAsCustomer\Model;

use MagedIn\LoginAsCustomer\Api\Data\LoginInterface;

class SecretManager implements SecretManagerInterface
{
    /**
     * @var int
     */
    const HASH_LENGTH = 128;

    /**
     * @var HashGeneratorInterface
     */
    private $hashGenerator;

    /**
     * @var LoginRepositoryInterface
     */
    private $loginRepository;

    /**
     * @var LoginFactory
     */
    private $loginFactory;

    /**
     * @var ResourceModel\Login
     */
    private $loginResource;

    /**
     * @var ExpirationTimeManagerInterface
     */
    private $expirationTimeManager;

    /**
     * @var Validator\ExpirationTimeValidator
     */
    private $expirationTimeValidator;

    public function __construct(
        HashGeneratorInterface $hashGenerator,
        LoginRepositoryInterface $loginRepository,
        LoginFactory $loginFactory,
        ResourceModel\Login $loginResource,
        ExpirationTimeManagerInterface $expirationTimeManager,
        Validator\ExpirationTimeValidator $expirationTimeValidator
    ) {
        $this->hashGenerator = $hashGenerator;
        $this->loginRepository = $loginRepository;
        $this->loginFactory = $loginFactory;
        $this->loginResource = $loginResource;
        $this->expirationTimeManager = $expirationTimeManager;
        $this->expirationTimeValidator = $expirationTimeValidator;
    }

    /**
     * @param int      $customerId
     * @param int      $storeId
     * @param int|null $userId
     *
     * @return LoginInterface
     */
    public function create(int $customerId, int $storeId, int $userId = null) : LoginInterface
    {
        $this->loginResource->deleteByCustomerId($customerId);

        $login = $this->loginFactory->create();
        $login->setCustomerId($customerId);
        $login->setStoreId($storeId);
        $login->setAdminUserId($userId);
        $login->setSecret($this->generate());
        $login->setExpiresAt($this->expirationTimeManager->get());

        $this->loginRepository->save($login);

        return $login;
    }

    /**
     * @inheritDoc
     */
    public function generate() : string
    {
        return $this->hashGenerator->generateHash(self::HASH_LENGTH);
    }

    /**
     * @inheritDoc
     */
    public function match(int $customerId, int $storeId, string $secret, int $adminUserId) : bool
    {
        /** @var LoginInterface $login */
        $login = $this->loginFactory->create();
        $this->loginResource->loadBySecret($login, $secret);

        if (!$login->getId()) {
            return false;
        }

        if ($login->getCustomerId() !== $customerId) {
            return false;
        }

        if ($login->getStoreId() !== $storeId) {
            return false;
        }

        if ($login->getAdminUserId() !== $adminUserId) {
            return false;
        }

        if (!$this->expirationTimeValidator->validate($login->getExpiresAt())) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteByCustomerId(int $customerId) : bool
    {
        return (bool) $this->loginResource->deleteByCustomerId($customerId);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $secret) : bool
    {
        return (bool) $this->loginResource->deleteBySecret($secret);
    }
}

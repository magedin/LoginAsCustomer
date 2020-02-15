<?php

declare(strict_types = 1);

namespace MagedIn\LoginAsCustomer\Service;

use MagedIn\LoginAsCustomer\Model\Session;
use Magento\User\Api\Data\UserInterface;

/**
 * Class AdminUserService
 *
 * @package MagedIn\LoginAsCustomer\Service
 */
class AdminUserService
{
    /**
     * @var UserInterface
     */
    private $user;

    /**
     * @var \Magento\User\Model\UserFactory
     */
    private $userFactory;

    /**
     * @var \Magento\User\Model\ResourceModel\User
     */
    private $userResource;

    /**
     * @var Session
     */
    private $session;

    public function __construct(
        \MagedIn\LoginAsCustomer\Model\Session $session,
        \Magento\User\Model\UserFactory $userFactory,
        \Magento\User\Model\ResourceModel\User $userResource
    ) {
        $this->userFactory = $userFactory;
        $this->userResource = $userResource;
        $this->session = $session;
    }

    /**
     * @return UserInterface
     */
    public function getRegisteredAdminUser() : UserInterface
    {
        if ($this->user && $this->user->getId()) {
            return $this->user;
        }

        $this->user = $this->userFactory->create();

        if ($this->getAdminUserId()) {
            $this->userResource->load($this->user, $this->getAdminUserId());
        }

        return $this->user;
    }

    /**
     * @param int $adminUserId
     *
     * @return $this
     */
    public function registerAdminUser(int $adminUserId) : self
    {
        $this->initSession();
        $this->session->setLoggedAsCustomerAdminUserId($adminUserId);
        return $this;
    }

    /**
     * @return int
     */
    private function getAdminUserId()
    {
        $this->initSession();
        $adminUserId = (int) $this->session->getLoggedAsCustomerAdminUserId();
        return $adminUserId;
    }

    /**
     * @return $this
     */
    private function initSession() : self
    {
        try {
            $this->session->start();
        } catch (\Exception $e) {
        }

        return $this;
    }
}
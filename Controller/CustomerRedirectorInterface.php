<?php
/**
 * MagedIn Technology
 *
 * Do not edit this file if you want to update this module for future new versions.
 *
 * @category  MagedIn
 * @copyright Copyright (c) 2021 MagedIn Technology.
 *
 * @author    MagedIn Support <support@magedin.com>
 */
declare(strict_types=1);

namespace MagedIn\LoginAsCustomer\Controller;

use Magento\Framework\App\ResponseInterface;

/**
 * Class CustomerRedirectorInterface
 */
interface CustomerRedirectorInterface
{
    /**
     * @param array $arguments
     *
     * @return ResponseInterface
     */
    public function redirectOnSuccess(array $arguments = []) : ResponseInterface;

    /**
     * @param array $arguments
     *
     * @return ResponseInterface
     */
    public function redirectOnFail(array $arguments = []) : ResponseInterface;
}

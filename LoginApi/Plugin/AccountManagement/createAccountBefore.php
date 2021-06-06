<?php

namespace AHT\LoginApi\Plugin\AccountManagement;

use Magento\Framework\Exception\LocalizedException;

class createAccountBefore
{
    /**
     * @param \Magento\Framework\Session\SessionManager
     */
    private $sessionManager;

    /**
     * @param AHT\LoginApi\Helper\Data
     */
    private $helperData;

    public function __construct(
        \Magento\Framework\Session\SessionManager $sessionManager,
        \AHT\LoginApi\Helper\Data $helperData
    ) {

        $this->sessionManager = $sessionManager;
        $this->helperData = $helperData;
    }
    public function beforeCreateAccount(
        \Magento\Customer\Model\AccountManagement $accountManagement,
        $customer,
        $password = null,
        $redirectUrl = ''
    ) {
        if ($this->helperData->getConfigStatusModule()) {
            $field = $this->sessionManager->getLoginApi();
            $result = $this->helperData->postToApi($field);

            $customer->setCustomAttribute('mpc_member_id', $result->id);
        }
        return [
            $customer,
            $password,
            $redirectUrl
        ];
    }
}

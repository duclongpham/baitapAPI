<?php

namespace AHT\LoginApi\Plugin\CustomerExtractor;

class extractAfter
{
    const STORE_MORE_FIELD = ['firstname', 'lastname', 'email', 'password'];
    const CONVERT_FIELD = [
        'firstname' => 'first_name',
        'lastname' => 'last_name',
    ];
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
    public function afterExtract(
        \Magento\Customer\Model\CustomerExtractor $subject,
        $result,
        $formCode,
        \Magento\Framework\App\RequestInterface $request
    ) {
        if ($this->helperData->getConfigStatusModule()) {
            $data = $request->getParam('loginApi');
            foreach (self::STORE_MORE_FIELD as $value) {
                if ($getRequest = $request->getParam($value)) {
                    if (isset(self::CONVERT_FIELD[$value])) {
                        $data['properties'][self::CONVERT_FIELD[$value]] = $getRequest;
                    } else {
                        $data['properties'][$value] = $getRequest;
                    }
                }
            }

            foreach ($data['properties'] as $key => &$value) {
                if ($this->helperData->isDate($key)) {
                    $value = date('Y-m-d', strtotime($value));
                }
            }

            $this->sessionManager->setLoginApi($data);
        }

        return $result;
    }
}

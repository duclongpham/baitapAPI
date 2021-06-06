<?php

namespace AHT\LoginApi\Helper;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Exception\LocalizedException;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONFIG_ACTION = 'loginapi/integration/action';
    const CONFIG_REGISTRATION_SCHEMA = 'loginapi/integration/registration_schema';
    const CONFIG_URL = 'loginapi/general/base_url';
    const CONFIG_API_AUTHORIZATION = 'loginapi/general/x_client_authorization';
    const CONFIG_API_PRODUCT_NAME = 'loginapi/general/x_product_name';
    const CONFIG_API_USER_AGENT = 'loginapi/general/x_user_agent';
    const CONFIG_API_LOYALTY_CLUB_SLUG = 'loginapi/general/x_loyalty_club_slug';

    /**
     * @param \Magento\Framework\HTTP\Client\Curl
     */
    private $curl;

    /**
     * @param \Magento\Framework\App\Helper\Context
     */
    private $helperContext;

    /**
     * @param Magento\Customer\Model\ResourceModel\Customer
     */
    private $customer;

    /**
     * @param \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\Helper\Context $helperContext,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customer,
        \Psr\Log\LoggerInterface $logger
    ) {

        $this->curl = $curl;
        $this->helperContext = $helperContext;
        $this->customer = $customer;
        $this->logger = $logger;
        parent::__construct($helperContext);
    }

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getConfigStatusModule()
    {
        return $this->getConfigValue(self::CONFIG_ACTION);
    }
    public function getConfigRegistrationSchema()
    {
        $json = $this->getConfigValue(self::CONFIG_REGISTRATION_SCHEMA);
        try {
            $json = json_decode($json);
        } catch (\Exception $e) {
            throw new \RuntimeException(__('decode json failed'));
        }
        return $json;
    }
    public function getConfigUrl()
    {
        return $this->getConfigValue(self::CONFIG_URL);
    }
    public function getConfigApiAuthorization()
    {
        return $this->getConfigValue(trim(self::CONFIG_API_AUTHORIZATION));
    }
    public function getConfigApiUserAgent()
    {
        return $this->getConfigValue(self::CONFIG_API_USER_AGENT);
    }
    public function getConfigApiProductName()
    {
        return $this->getConfigValue(self::CONFIG_API_PRODUCT_NAME);
    }
    public function getConfigLoyaltyClubSlug()
    {
        return $this->getConfigValue(self::CONFIG_API_LOYALTY_CLUB_SLUG);
    }

    public function isEmailInApi($email)
    {
    }

    public function postToApi($data)
    {
        $require_properties = ['email', 'gender', 'birthday', 'msisdn', 'first_name', 'last_name'];
        $require_consents = ['dmp_profiling', 'sms_marketing', 'cookie_tracking', 'email_marketing'];
        $sendData = [
            'properties' => [],
            'consents' => [],
        ];
        foreach ($require_properties as $value) {
            $sendData['properties'][$value] = $data['properties'][$value];
            if ($value == 'email') {
                if ($this->checkEmail($data['properties'][$value])) {
                    throw new LocalizedException(
                        __('email exsist')
                    );
                }
            }
        }
        foreach ($require_consents as $value) {
            if (!isset($data['consents'][$value])) continue;
            $sendData['consents'][$value] = [
                'status' => $data['consents'][$value] ? true : false
            ];
        }
        $result = json_decode($this->callApi($sendData));
        if (isset($result->errors)) {
            $message = json_encode($result);
            $this->logger->debug($message);
            throw new LocalizedException(
                __($message)
            );
        }
        return $result;
    }

    public function isDate($key)
    {
        $json_schema = $this->getConfigRegistrationSchema();

        if (isset($json_schema->properties) && isset($json_schema->properties->$key)) {
            $getField = $json_schema->properties->$key;
            if (isset($getField->format) && $getField->format == 'date') {
                return true;
            }
        }

        return false;
    }

    public function callApi($data)
    {
        try {
            $this->curl->setHeaders([
                'x-client-authorization' => $this->getConfigApiAuthorization(),
                'x-product-name' => $this->getConfigApiProductName(),
                'x-user-agent' => $this->getConfigApiUserAgent(),
            ]);
            $base_url = $this->getConfigUrl();
            $x_loyalty_club_slug = $this->getConfigLoyaltyClubSlug();
            $this->curl->post($base_url . 'v3/' . $x_loyalty_club_slug . '/members', $data);
            return $this->curl->getBody();
        } catch (\Exception $e) {
            throw new LocalizedException(
                __("Something went wrong")
            );
        }
    }

    public function checkEmail($email)
    {
        $customerData = $this->customer->create()->addFieldToFilter('email', $email)->toArray();
        if (count($customerData)) {
            return true;
        } else {
            return false;
        }
    }
}

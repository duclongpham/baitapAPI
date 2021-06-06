<?php

namespace AHT\LoginApi\Block;

class Extend extends \Magento\Framework\View\Element\Template
{
    const SKIPPED_FIELDS = ['email', 'first_name', 'last_name', 'password'];

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param \AHT\LoginApi\Helper\Data
     */
    private $helperData;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \AHT\LoginApi\Helper\Data $helperData,
        array $data = []
    ) {

        $this->scopeConfig = $scopeConfig;
        $this->helperData = $helperData;
        parent::__construct($context, $data);
    }
    public function isEnabledModule()
    {
        return $this->helperData->getConfigStatusModule();
    }
    public function getConfigJson()
    {
        try {
            $config = $this->helperData->getConfigRegistrationSchema();

            foreach ($config->properties as $key => $value) {
                if (in_array($key, self::SKIPPED_FIELDS)) {
                    unset($config->properties->$key);
                }
            }
            return $config;
        } catch (\Exception $e) {
        }
    }
}

<?php

namespace AHT\LoginApi\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    private $eavSetupFactory;

    /**
     * @param \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * @param \Magento\Customer\Model\ResourceModel\Attribute
     */
    private $attributeResource;

    public function __construct(
        EavSetupFactory $eavSetupFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Customer\Model\ResourceModel\Attribute $attributeResource
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
        $this->attributeResource = $attributeResource;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $attributes = [
            'mpc_member_id' => [
                'type'         => 'int',
                'label'        => 'Mpc member',
                'input'        => 'text',
                'position'     => 1003,
                'visible'      => true,
                'required'     => false,
                'system'       => 0
            ],
        ];

        foreach ($attributes as $key => $value) {
            $eavSetup->removeAttribute(\Magento\Customer\Model\Customer::ENTITY, $key);
            $eavSetup->addAttribute(\Magento\Customer\Model\Customer::ENTITY, $key, $value);
            $customerAttr = $this->eavConfig->getAttribute(\Magento\Customer\Model\Customer::ENTITY, $key);
            $customerAttr->setData(
                'used_in_forms',
                ['adminhtml_customer', 'customer_account_edit']
            );
            $this->attributeResource->save($customerAttr);
        }

        $setup->endSetup();
    }
}

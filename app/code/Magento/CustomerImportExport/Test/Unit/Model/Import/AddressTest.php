<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerImportExport\Test\Unit\Model\Import;

use Magento\ImportExport\Model\Import\AbstractEntity;
use Magento\CustomerImportExport\Model\Import\Address;

/**
 * Class AddressTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddressTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Customer address entity adapter mock
     *
     * @var Address|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_model;

    /**
     * Websites array (website id => code)
     *
     * @var array
     */
    protected $_websites = [1 => 'website1', 2 => 'website2'];

    /** @var \PHPUnit_Framework_MockObject_MockObject |\Magento\Store\Model\StoreManager  */
    protected $_storeManager;

    /**
     * Attributes array
     *
     * @var array
     */
    protected $_attributes = [
        'country_id' => [
            'id' => 1,
            'attribute_code' => 'country_id',
            'table' => '',
            'is_required' => true,
            'is_static' => false,
            'validate_rules' => false,
            'type' => 'select',
            'attribute_options' => null,
        ],
    ];

    /**
     * Customers array
     *
     * @var array
     */
    protected $_customers = [
        ['id' => 1, 'email' => 'test1@email.com', 'website_id' => 1],
        ['id' => 2, 'email' => 'test2@email.com', 'website_id' => 2],
    ];

    /**
     * Customer addresses array
     *
     * @var array
     */
    protected $_addresses = [1 => ['id' => 1, 'parent_id' => 1]];

    /**
     * Customers array
     *
     * @var array
     */
    protected $_regions = [
        ['id' => 1, 'country_id' => 'c1', 'code' => 'code1', 'default_name' => 'region1'],
        ['id' => 2, 'country_id' => 'c1', 'code' => 'code2', 'default_name' => 'region2'],
    ];

    /**
     * Available behaviours
     *
     * @var array
     */
    protected $_availableBehaviors = [
        \Magento\ImportExport\Model\Import::BEHAVIOR_ADD_UPDATE,
        \Magento\ImportExport\Model\Import::BEHAVIOR_DELETE,
        \Magento\ImportExport\Model\Import::BEHAVIOR_CUSTOM,
    ];

    /**
     * Customer behaviours parameters
     *
     * @var array
     */
    protected $_customBehaviour = ['update_id' => 1, 'delete_id' => 2];

    /**
     * @var \Magento\Framework\Stdlib\String
     */
    protected $_stringLib;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $_objectManagerMock;

    /**
     * Init entity adapter model
     */
    protected function setUp()
    {
        $this->_objectManagerMock = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->_stringLib = new \Magento\Framework\Stdlib\String();
        $this->_storeManager = $this->getMockBuilder('Magento\Store\Model\StoreManager')
            ->disableOriginalConstructor()
            ->setMethods(['getWebsites'])
            ->getMock();
        $this->_storeManager->expects($this->any())
            ->method('getWebsites')
            ->will($this->returnCallback([$this, 'getWebsites']));
        $this->_model = $this->_getModelMock();
    }

    /**
     * Unset entity adapter model
     */
    protected function tearDown()
    {
        unset($this->_model);
    }

    /**
     * Create mocks for all $this->_model dependencies
     *
     * @return array
     */
    protected function _getModelDependencies()
    {
        $dataSourceModel = $this->getMock('stdClass', ['getNextBunch']);
        $connection = $this->getMock('stdClass');
        $attributeCollection = $this->_createAttrCollectionMock();
        $customerStorage = $this->_createCustomerStorageMock();
        $customerEntity = $this->_createCustomerEntityMock();
        $addressCollection = new \Magento\Framework\Data\Collection(
            $this->getMock('Magento\Framework\Data\Collection\EntityFactory', [], [], '', false)
        );
        foreach ($this->_addresses as $address) {
            $addressCollection->addItem(new \Magento\Framework\Object($address));
        }

        $regionCollection = new \Magento\Framework\Data\Collection(
            $this->getMock('Magento\Framework\Data\Collection\EntityFactory', [], [], '', false)
        );
        foreach ($this->_regions as $region) {
            $regionCollection->addItem(new \Magento\Framework\Object($region));
        }

        $data = [
            'data_source_model' => $dataSourceModel,
            'connection' => $connection,
            'page_size' => 1,
            'max_data_size' => 1,
            'bunch_size' => 1,
            'attribute_collection' => $attributeCollection,
            'entity_type_id' => 1,
            'customer_storage' => $customerStorage,
            'customer_entity' => $customerEntity,
            'address_collection' => $addressCollection,
            'entity_table' => 'not_used',
            'region_collection' => $regionCollection,
        ];

        return $data;
    }

    /**
     * Create mock of attribute collection, so it can be used for tests
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Data\Collection
     */
    protected function _createAttrCollectionMock()
    {
        $entityFactory = $this->getMock('Magento\Framework\Data\Collection\EntityFactory', [], [], '', false);
        $attributeCollection = $this->getMock(
            'Magento\Framework\Data\Collection',
            ['getEntityTypeCode'],
            [$entityFactory]
        );
        foreach ($this->_attributes as $attributeData) {
            $arguments = $this->_objectManagerMock->getConstructArguments(
                'Magento\Eav\Model\Entity\Attribute\AbstractAttribute',
                [
                    $this->getMock('Magento\Framework\Model\Context', [], [], '', false, false),
                    $this->getMock('Magento\Framework\Registry'),
                    $this->getMock('Magento\Eav\Model\Config', [], [], '', false, false),
                    $this->getMock('Magento\Eav\Model\Entity\TypeFactory', [], [], '', false),
                    $this->getMock('Magento\Store\Model\StoreManager', [], [], '', false, false),
                    $this->getMock('Magento\Eav\Model\Resource\Helper', [], [], '', false, false),
                    $this->getMock('Magento\Framework\Validator\UniversalFactory', [], [], '', false, false)
                ]
            );
            $arguments['data'] = $attributeData;
            $attribute = $this->getMockForAbstractClass(
                'Magento\Eav\Model\Entity\Attribute\AbstractAttribute',
                $arguments,
                '',
                true,
                true,
                true,
                ['_construct', 'getBackend']
            );
            $attribute->expects($this->any())->method('getBackend')->will($this->returnSelf());
            $attribute->expects($this->any())->method('getTable')->will($this->returnValue($attributeData['table']));
            $attributeCollection->addItem($attribute);
        }
        return $attributeCollection;
    }

    /**
     * Create mock of customer storage, so it can be used for tests
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function _createCustomerStorageMock()
    {
        $customerStorage = $this->getMock(
            'Magento\CustomerImportExport\Model\Resource\Import\Customer\Storage',
            ['load'],
            [],
            '',
            false
        );
        $resourceMock = $this->getMock(
            'Magento\Customer\Model\Resource\Customer',
            ['getIdFieldName'],
            [],
            '',
            false
        );
        $resourceMock->expects($this->any())->method('getIdFieldName')->will($this->returnValue('id'));
        foreach ($this->_customers as $customerData) {
            $data = [
                'resource' => $resourceMock,
                'data' => $customerData,
                $this->getMock('Magento\Customer\Model\Config\Share', [], [], '', false),
                $this->getMock('Magento\Customer\Model\AddressFactory', [], [], '', false),
                $this->getMock(
                    'Magento\Customer\Model\Resource\Address\CollectionFactory',
                    [],
                    [],
                    '',
                    false
                ),
                $this->getMock('Magento\Customer\Model\GroupFactory', [], [], '', false),
                $this->getMock('Magento\Customer\Model\AttributeFactory', [], [], '', false),
            ];
            /** @var $customer \Magento\Customer\Model\Customer */
            $customer = $this->_objectManagerMock->getObject('Magento\Customer\Model\Customer', $data);
            $customerStorage->addCustomer($customer);
        }
        return $customerStorage;
    }

    /**
     * Create simple mock of customer entity, so it can be used for tests
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function _createCustomerEntityMock()
    {
        $customerEntity = $this->getMock('stdClass', ['filterEntityCollection', 'setParameters']);
        $customerEntity->expects($this->any())->method('filterEntityCollection')->will($this->returnArgument(0));
        $customerEntity->expects($this->any())->method('setParameters')->will($this->returnSelf());
        return $customerEntity;
    }

    /**
     * Get websites stub
     *
     * @param bool $withDefault
     * @return array
     */
    public function getWebsites($withDefault = false)
    {
        $websites = [];
        if (!$withDefault) {
            unset($websites[0]);
        }
        foreach ($this->_websites as $id => $code) {
            if (!$withDefault && $id == \Magento\Store\Model\Store::DEFAULT_STORE_ID) {
                continue;
            }
            $websiteData = ['id' => $id, 'code' => $code];
            $websites[$id] = new \Magento\Framework\Object($websiteData);
        }

        return $websites;
    }

    /**
     * Iterate stub
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \Magento\Framework\Data\Collection $collection
     * @param int $pageSize
     * @param array $callbacks
     */
    public function iterate(\Magento\Framework\Data\Collection $collection, $pageSize, array $callbacks)
    {
        foreach ($collection as $customer) {
            foreach ($callbacks as $callback) {
                call_user_func($callback, $customer);
            }
        }
    }

    /**
     * Create mock for custom behavior test
     *
     * @return Address|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getModelMockForTestImportDataWithCustomBehaviour()
    {
        // input data
        $customBehaviorRows = [
            [
                AbstractEntity::COLUMN_ACTION => 'update',
                Address::COLUMN_ADDRESS_ID => $this->_customBehaviour['update_id'],
            ],
            [
                AbstractEntity::COLUMN_ACTION => AbstractEntity::COLUMN_ACTION_VALUE_DELETE,
                Address::COLUMN_ADDRESS_ID => $this->_customBehaviour['delete_id']
            ],
        ];
        $updateResult = [
            'entity_row' => $this->_customBehaviour['update_id'],
            'attributes' => [],
            'defaults' => [],
        ];

        // entity adapter mock
        $modelMock = $this->getMock(
            'Magento\CustomerImportExport\Model\Import\Address',
            [
                'validateRow',
                '_prepareDataForUpdate',
                '_saveAddressEntities',
                '_saveAddressAttributes',
                '_saveCustomerDefaults',
                '_deleteAddressEntities',
                '_mergeEntityAttributes'
            ],
            [],
            '',
            false,
            true,
            true
        );

        $availableBehaviors = new \ReflectionProperty($modelMock, '_availableBehaviors');
        $availableBehaviors->setAccessible(true);
        $availableBehaviors->setValue($modelMock, $this->_availableBehaviors);

        // mock to imitate data source model
        $dataSourceMock = $this->getMock(
            'Magento\ImportExport\Model\Resource\Import\Data',
            ['getNextBunch', '__wakeup'],
            [],
            '',
            false
        );
        $dataSourceMock->expects($this->at(0))->method('getNextBunch')->will($this->returnValue($customBehaviorRows));
        $dataSourceMock->expects($this->at(1))->method('getNextBunch')->will($this->returnValue(null));

        $dataSourceModel = new \ReflectionProperty(
            'Magento\CustomerImportExport\Model\Import\Address',
            '_dataSourceModel'
        );
        $dataSourceModel->setAccessible(true);
        $dataSourceModel->setValue($modelMock, $dataSourceMock);

        // mock expects for entity adapter
        $modelMock->expects($this->any())->method('validateRow')->will($this->returnValue(true));

        $modelMock->expects($this->any())->method('_prepareDataForUpdate')->will($this->returnValue($updateResult));

        $modelMock->expects(
            $this->any()
        )->method(
            '_saveAddressEntities'
        )->will(
            $this->returnCallback([$this, 'validateSaveAddressEntities'])
        );

        $modelMock->expects($this->any())->method('_saveAddressAttributes')->will($this->returnValue($modelMock));

        $modelMock->expects($this->any())->method('_saveCustomerDefaults')->will($this->returnValue($modelMock));

        $modelMock->expects(
            $this->any()
        )->method(
            '_deleteAddressEntities'
        )->will(
            $this->returnCallback([$this, 'validateDeleteAddressEntities'])
        );

        $modelMock->expects($this->any())->method('_mergeEntityAttributes')->will($this->returnValue([]));

        return $modelMock;
    }

    /**
     * Create mock for customer address model class
     *
     * @return Address|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getModelMock()
    {
        $scopeConfig = $this->getMock('Magento\Framework\App\Config\ScopeConfigInterface');
        $modelMock = new \Magento\CustomerImportExport\Model\Import\Address(
            $this->_stringLib,
            $scopeConfig,
            $this->getMock('Magento\ImportExport\Model\ImportFactory', [], [], '', false),
            $this->getMock('Magento\ImportExport\Model\Resource\Helper', [], [], '', false),
            $this->getMock('Magento\Framework\App\Resource', [], [], '', false),
            $this->_storeManager,
            $this->getMock('Magento\ImportExport\Model\Export\Factory', [], [], '', false),
            $this->getMock('Magento\Eav\Model\Config', [], [], '', false),
            $this->getMock(
                'Magento\CustomerImportExport\Model\Resource\Import\Customer\StorageFactory',
                [],
                [],
                '',
                false
            ),
            $this->getMock('Magento\Customer\Model\AddressFactory', [], [], '', false),
            $this->getMock('Magento\Directory\Model\Resource\Region\CollectionFactory', [], [], '', false),
            $this->getMock('Magento\Customer\Model\CustomerFactory', [], [], '', false),
            $this->getMock('Magento\Customer\Model\Resource\Address\CollectionFactory', [], [], '', false),
            $this->getMock(
                'Magento\Customer\Model\Resource\Address\Attribute\CollectionFactory',
                [],
                [],
                '',
                false
            ),
            new \Magento\Framework\Stdlib\DateTime(),
            $this->_getModelDependencies()
        );

        $property = new \ReflectionProperty($modelMock, '_availableBehaviors');
        $property->setAccessible(true);
        $property->setValue($modelMock, $this->_availableBehaviors);

        return $modelMock;
    }

    /**
     * Data provider of row data and errors for add/update action
     *
     * @return array
     */
    public function validateRowForUpdateDataProvider()
    {
        return [
            'valid' => [
                '$rowData' => include __DIR__ . '/_files/row_data_address_update_valid.php',
                '$errors' => [],
                '$isValid' => true,
            ],
            'empty address id' => [
                '$rowData' => include __DIR__ . '/_files/row_data_address_update_empty_address_id.php',
                '$errors' => [],
                '$isValid' => true,
            ],
            'no customer' => [
                '$rowData' => include __DIR__ . '/_files/row_data_address_update_no_customer.php',
                '$errors' => [
                    Address::ERROR_CUSTOMER_NOT_FOUND => [
                        [1, null],
                    ],
                ],
            ],
            'absent required attribute' => [
                '$rowData' => include __DIR__ . '/_files/row_data_address_update_absent_required_attribute.php',
                '$errors' => [
                    Address::ERROR_VALUE_IS_REQUIRED => [
                        [1, Address::COLUMN_COUNTRY_ID],
                    ],
                ],
            ],
            'invalid region' => [
                '$rowData' => include __DIR__ . '/_files/row_data_address_update_invalid_region.php',
                '$errors' => [
                    Address::ERROR_INVALID_REGION => [
                        [1, Address::COLUMN_REGION],
                    ],
                ],
            ]
        ];
    }

    /**
     * Data provider of row data and errors for add/update action
     *
     * @return array
     */
    public function validateRowForDeleteDataProvider()
    {
        return [
            'valid' => [
                '$rowData' => include __DIR__ . '/_files/row_data_address_update_valid.php',
                '$errors' => [],
                '$isValid' => true,
            ],
            'empty address id' => [
                '$rowData' => include __DIR__ . '/_files/row_data_address_delete_empty_address_id.php',
                '$errors' => [
                    Address::ERROR_ADDRESS_ID_IS_EMPTY => [
                        [1, null],
                    ],
                ],
            ],
            'invalid address' => [
                '$rowData' => include __DIR__ . '/_files/row_data_address_delete_address_not_found.php',
                '$errors' => [
                    Address::ERROR_ADDRESS_NOT_FOUND => [
                        [1, null],
                    ],
                ],
            ],
            'no customer' => [
                '$rowData' => include __DIR__ . '/_files/row_data_address_delete_no_customer.php',
                '$errors' => [
                    Address::ERROR_CUSTOMER_NOT_FOUND => [
                        [1, null],
                    ],
                ],
            ]
        ];
    }

    /**
     * @dataProvider validateRowForUpdateDataProvider
     *
     * @param array $rowData
     * @param array $errors
     * @param boolean $isValid
     */
    public function testValidateRowForUpdate(array $rowData, array $errors, $isValid = false)
    {
        $this->_model->setParameters(['behavior' => \Magento\ImportExport\Model\Import::BEHAVIOR_ADD_UPDATE]);

        if ($isValid) {
            $this->assertTrue($this->_model->validateRow($rowData, 0));
        } else {
            $this->assertFalse($this->_model->validateRow($rowData, 0));
        }
        $this->assertAttributeEquals($errors, '_errors', $this->_model);
    }

    /**
     * Test Address::validateRow()
     * with 2 rows with identical PKs in case when add/update behavior is performed
     *
     * covers \Magento\CustomerImportExport\Model\Import\Address::validateRow
     * covers \Magento\CustomerImportExport\Model\Import\Address::_validateRowForUpdate
     */
    public function testValidateRowForUpdateDuplicateRows()
    {
        $behavior = \Magento\ImportExport\Model\Import::BEHAVIOR_ADD_UPDATE;

        $this->_model->setParameters(['behavior' => $behavior]);

        $secondRow = $firstRow = [
            '_website' => 'website1',
            '_email' => 'test1@email.com',
            '_entity_id' => '1',
            'city' => 'Culver City',
            'company' => '',
            'country_id' => 'C1',
            'fax' => '',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'middlename' => '',
            'postcode' => '90232',
            'prefix' => '',
            'region' => 'region1',
            'region_id' => '1',
            'street' => '10441 Jefferson Blvd. Suite 200 Culver City',
            'suffix' => '',
            'telephone' => '12312313',
            'vat_id' => '',
            'vat_is_valid' => '',
            'vat_request_date' => '',
            'vat_request_id' => '',
            'vat_request_success' => '',
            '_address_default_billing_' => '1',
            '_address_default_shipping_' => '1',
        ];
        $secondRow['postcode'] = '90210';

        $errors = [
            Address::ERROR_DUPLICATE_PK => [[2, null]],
        ];

        $this->assertTrue($this->_model->validateRow($firstRow, 0));
        $this->assertFalse($this->_model->validateRow($secondRow, 1));

        $this->assertAttributeEquals($errors, '_errors', $this->_model);
    }

    /**
     * Test Address::validateRow() with delete action
     *
     * covers \Magento\CustomerImportExport\Model\Import\Address::validateRow
     * @dataProvider validateRowForDeleteDataProvider
     *
     * @param array $rowData
     * @param array $errors
     * @param boolean $isValid
     */
    public function testValidateRowForDelete(array $rowData, array $errors, $isValid = false)
    {
        $this->_model->setParameters(['behavior' => \Magento\ImportExport\Model\Import::BEHAVIOR_DELETE]);

        if ($isValid) {
            $this->assertTrue($this->_model->validateRow($rowData, 0));
        } else {
            $this->assertFalse($this->_model->validateRow($rowData, 0));
        }
        $this->assertAttributeEquals($errors, '_errors', $this->_model);
    }

    /**
     * Test entity type code getter
     */
    public function testGetEntityTypeCode()
    {
        $this->assertEquals('customer_address', $this->_model->getEntityTypeCode());
    }

    /**
     * Test default address attribute mapping array
     */
    public function testGetDefaultAddressAttributeMapping()
    {
        $attributeMapping = $this->_model->getDefaultAddressAttributeMapping();
        $this->assertInternalType('array', $attributeMapping, 'Default address attribute mapping must be an array.');
        $this->assertArrayHasKey(
            Address::COLUMN_DEFAULT_BILLING,
            $attributeMapping,
            'Default address attribute mapping array must have a default billing column.'
        );
        $this->assertArrayHasKey(
            Address::COLUMN_DEFAULT_SHIPPING,
            $attributeMapping,
            'Default address attribute mapping array must have a default shipping column.'
        );
    }

    /**
     * Test if correct methods are invoked according to different custom behaviours
     *
     * covers \Magento\CustomerImportExport\Model\Import\Address::_importData
     */
    public function testImportDataWithCustomBehaviour()
    {
        $this->_model = $this->_getModelMockForTestImportDataWithCustomBehaviour();
        $this->_model->setParameters(['behavior' => \Magento\ImportExport\Model\Import::BEHAVIOR_CUSTOM]);

        // validation in validateSaveAddressEntities and validateDeleteAddressEntities
        $this->_model->importData();
    }

    /**
     * Validation method for _saveAddressEntities (callback for _saveAddressEntities)
     *
     * @param array $addUpdateRows
     * @return Address|\PHPUnit_Framework_MockObject_MockObject
     */
    public function validateSaveAddressEntities(array $addUpdateRows)
    {
        $this->assertCount(1, $addUpdateRows);
        $this->assertContains($this->_customBehaviour['update_id'], $addUpdateRows);
        return $this->_model;
    }

    /**
     * Validation method for _deleteAddressEntities (callback for _deleteAddressEntities)
     *
     * @param array $deleteRowIds
     * @return Address|\PHPUnit_Framework_MockObject_MockObject
     */
    public function validateDeleteAddressEntities(array $deleteRowIds)
    {
        $this->assertCount(1, $deleteRowIds);
        $this->assertContains($this->_customBehaviour['delete_id'], $deleteRowIds);
        return $this->_model;
    }
}

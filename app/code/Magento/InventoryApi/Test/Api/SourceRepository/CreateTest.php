<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Test\Api\SourceRepository;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Webapi\Rest\Request;
use Magento\InventoryApi\Api\Data\SourceCarrierLinkInterface;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\TestFramework\Assert\AssertArrayContains;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

class CreateTest extends WebapiAbstract
{
    /**#@+
     * Service constants
     */
    const RESOURCE_PATH = '/V1/inventory/source';
    const SERVICE_NAME = 'inventoryApiSourceRepositoryV1';
    /**#@-*/

    public function testCreate()
    {
        $expectedData = [
            SourceInterface::NAME => 'source-name-1',
            SourceInterface::CONTACT_NAME => 'source-contact-name',
            SourceInterface::EMAIL => 'source-email',
            SourceInterface::ENABLED => true,
            SourceInterface::DESCRIPTION => 'source-description',
            SourceInterface::LATITUDE => 11.123456,
            SourceInterface::LONGITUDE => 12.123456,
            SourceInterface::COUNTRY_ID => 'US',
            SourceInterface::REGION_ID => 10,
            SourceInterface::CITY => 'source-city',
            SourceInterface::STREET => 'source-street',
            SourceInterface::POSTCODE => 'source-postcode',
            SourceInterface::PHONE => 'source-phone',
            SourceInterface::FAX => 'source-fax',
            SourceInterface::PRIORITY => 200,
            SourceInterface::USE_DEFAULT_CARRIER_CONFIG => 0,
            SourceInterface::CARRIER_LINKS => [
                [
                    SourceCarrierLinkInterface::CARRIER_CODE => 'ups',
                    SourceCarrierLinkInterface::POSITION => 100,
                ],
                [
                    SourceCarrierLinkInterface::CARRIER_CODE => 'usps',
                    SourceCarrierLinkInterface::POSITION => 200,
                ],
            ],
        ];
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'operation' => self::SERVICE_NAME . 'Save',
            ],
        ];
        $sourceId = $this->_webApiCall($serviceInfo, ['source' => $expectedData]);

        self::assertNotEmpty($sourceId);
        AssertArrayContains::assert($expectedData, $this->getSourceDataById($sourceId));
    }

    protected function tearDown()
    {
        /** @var ResourceConnection $connection */
        $connection = Bootstrap::getObjectManager()->get(ResourceConnection::class);
        $connection->getConnection()->delete($connection->getTableName('inventory_source'), [
            SourceInterface::NAME . ' IN (?)' => ['source-name-1'],
        ]);
        parent::tearDown();
    }

    /**
     * @param int $sourceId
     * @return array
     */
    private function getSourceDataById(int $sourceId): array
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/' . $sourceId,
                'httpMethod' => Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'operation' => self::SERVICE_NAME . 'Get',
            ],
        ];
        $response = (TESTS_WEB_API_ADAPTER == self::ADAPTER_REST)
            ? $this->_webApiCall($serviceInfo)
            : $this->_webApiCall($serviceInfo, ['sourceId' => $sourceId]);
        self::assertArrayHasKey(SourceInterface::SOURCE_ID, $response);
        return $response;
    }
}

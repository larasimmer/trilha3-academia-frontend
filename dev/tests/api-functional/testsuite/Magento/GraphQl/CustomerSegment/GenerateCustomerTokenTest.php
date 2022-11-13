<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\CustomerSegment;

use Magento\CustomerSegment\Model\ResourceModel\Customer;
use Magento\CustomerSegment\Model\Segment;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test customer login with GraphQL
 */
class GenerateCustomerTokenTest extends GraphQlAbstract
{
    /**
     * Verify that customer is assigned to matching segments after login
     *
     * @magentoApiDataFixture Magento/CustomerSegment/_files/segment_with_zero_orders.php
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    public function testShouldAssignCustomerToMatchingSegmentsAfterLogin()
    {
        $email = 'customer@example.com';
        $password = 'password';
        $segmentName = 'Customer Segment with zero orders';
        $customerId = 1;
        $websiteId = 1;
        $objectManager = Bootstrap::getObjectManager();
        /** @var $segment Segment */
        $segment = $objectManager->create(Segment::class)->load($segmentName, 'name');
        /** @var Customer $customerSegment */
        $customerSegment = $objectManager->create(Customer::class);
        $segmentIds = $customerSegment->getCustomerWebsiteSegments($customerId, $websiteId);
        $this->assertContains($segment->getId(), $segmentIds);
        $mutation = $this->getQuery($email, $password);
        $response = $this->graphQlMutation($mutation);
        $this->assertArrayHasKey('generateCustomerToken', $response);
        $this->assertIsArray($response['generateCustomerToken']);
        $this->assertArrayHasKey('token', $response['generateCustomerToken']);
        $segmentIds = $customerSegment->getCustomerWebsiteSegments($customerId, $websiteId);
        $this->assertContains($segment->getId(), $segmentIds);
    }

    /**
     * @param string $email
     * @param string $password
     * @return string
     */
    private function getQuery(string $email, string $password) : string
    {
        return <<<MUTATION
mutation {
	generateCustomerToken(
        email: "{$email}"
        password: "{$password}"
    ) {
        token
    }
}
MUTATION;
    }
}

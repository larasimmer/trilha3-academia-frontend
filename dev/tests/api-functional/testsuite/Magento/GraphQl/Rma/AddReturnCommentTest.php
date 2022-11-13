<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GraphQl\Rma;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\ObjectManagerInterface;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Model\Rma;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for Adding return comment
 */
class AddReturnCommentTest extends GraphQlAbstract
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var GetCustomerAuthenticationHeader
     */
    private $getCustomerAuthenticationHeader;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var RmaRepositoryInterface
     */
    private $rmaRepository;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * Setup
     */
    public function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->getCustomerAuthenticationHeader = $this->objectManager->get(GetCustomerAuthenticationHeader::class);
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $this->rmaRepository = $this->objectManager->get(RmaRepositoryInterface::class);
        $this->idEncoder = $this->objectManager->get(Uid::class);
    }

    /**
     * Test add comment to return with unauthorized customer
     *
     * @magentoConfigFixture default_store sales/magento_rma/enabled 1
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     */
    public function testUnauthorized()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The current customer isn\'t authorized.');

        $customerEmail = 'customer_uk_address@test.com';
        $rma = $this->getCustomerReturn($customerEmail);
        $rmaUid = $this->idEncoder->encode((string)$rma->getEntityId());

        $mutation = <<<MUTATION
mutation {
  addReturnComment(
    input: {
      return_uid: "{$rmaUid}",
      comment_text: "Additional return comment"
    }
  ) {
    return {
      uid
      comments {
        created_at
        author_name
        text
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation($mutation);
    }

    /**
     * Test add comment to return when RMA is disabled
     *
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     * @magentoConfigFixture default_store sales/magento_rma/enabled 0
     */
    public function testRmaDisabled()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('RMA is disabled.');

        $customerEmail = 'customer_uk_address@test.com';
        $rma = $this->getCustomerReturn($customerEmail);
        $rmaUid = $this->idEncoder->encode((string)$rma->getEntityId());

        $mutation = <<<MUTATION
mutation {
  addReturnComment(
    input: {
      return_uid: "{$rmaUid}",
      comment_text: "Additional return comment"
    }
  ) {
    return {
      uid
      comments {
        created_at
        author_name
        text
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );
    }

    /**
     * Test add comment to return
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     * @magentoConfigFixture sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/use_store_address 1
     * @magentoConfigFixture shipping/origin/name test
     * @magentoConfigFixture shipping/origin/phone +380003434343
     * @magentoConfigFixture shipping/origin/street_line1 street
     * @magentoConfigFixture shipping/origin/street_line2 1
     * @magentoConfigFixture shipping/origin/city Montgomery
     * @magentoConfigFixture shipping/origin/region_id 1
     * @magentoConfigFixture shipping/origin/postcode 12345
     * @magentoConfigFixture shipping/origin/country_id US
     *
     */
    public function testAddComment()
    {
        $customerEmail = 'customer_uk_address@test.com';
        $rma = $this->getCustomerReturn($customerEmail);
        $rmaUid = $this->idEncoder->encode((string)$rma->getEntityId());

        $mutation = <<<MUTATION
mutation {
  addReturnComment(
    input: {
      return_uid: "{$rmaUid}",
      comment_text: "Additional return comment"
    }
  ) {
    return {
      uid
      comments {
        created_at
        author_name
        text
      }
    }
  }
}
MUTATION;

        $response = $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );

        self::assertEquals($rmaUid, $response['addReturnComment']['return']['uid']);
        $this->assertRmaComments($response['addReturnComment']['return']['comments']);
    }

    /**
     * Test add comment to return with wrong RMA uid
     *
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     * @magentoConfigFixture sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/use_store_address 1
     * @magentoConfigFixture shipping/origin/name test
     * @magentoConfigFixture shipping/origin/phone +380003434343
     * @magentoConfigFixture shipping/origin/street_line1 street
     * @magentoConfigFixture shipping/origin/street_line2 1
     * @magentoConfigFixture shipping/origin/city Montgomery
     * @magentoConfigFixture shipping/origin/region_id 1
     * @magentoConfigFixture shipping/origin/postcode 12345
     * @magentoConfigFixture shipping/origin/country_id US
     */
    public function testWithWrongId()
    {
        $customerEmail = 'customer_uk_address@test.com';
        $rma = $this->getCustomerReturn($customerEmail);
        $rmaId = $rma->getEntityId() + 10;
        $rmaUid = $this->idEncoder->encode((string)$rmaId);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You selected the wrong RMA.');

        $mutation = <<<MUTATION
mutation {
  addReturnComment(
    input: {
      return_uid: "{$rmaUid}",
      comment_text: "Additional return comment"
    }
  ) {
    return {
      uid
      comments {
        created_at
        author_name
        text
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );
    }

    /**
     * Test add comment to return with not encoded RMA uid
     *
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     * @magentoConfigFixture sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/use_store_address 1
     * @magentoConfigFixture shipping/origin/name test
     * @magentoConfigFixture shipping/origin/phone +380003434343
     * @magentoConfigFixture shipping/origin/street_line1 street
     * @magentoConfigFixture shipping/origin/street_line2 1
     * @magentoConfigFixture shipping/origin/city Montgomery
     * @magentoConfigFixture shipping/origin/region_id 1
     * @magentoConfigFixture shipping/origin/postcode 12345
     * @magentoConfigFixture shipping/origin/country_id US
     *
     */
    public function testWithNotEncodedId()
    {
        $customerEmail = 'customer_uk_address@test.com';
        $rma = $this->getCustomerReturn($customerEmail);
        $rmaId = $rma->getEntityId();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Value of uid \"{$rmaId}\" is incorrect");

        $mutation = <<<MUTATION
mutation {
  addReturnComment(
    input: {
      return_uid: "{$rmaId}",
      comment_text: "Additional return comment"
    }
  ) {
    return {
      uid
      comments {
        created_at
        author_name
        text
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );
    }

    /**
     * Get customer return
     *
     * @param string $customerEmail
     * @return RmaInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getCustomerReturn(string $customerEmail): RmaInterface
    {
        $customer = $this->customerRepository->get($customerEmail);
        $this->searchCriteriaBuilder->addFilter(Rma::CUSTOMER_ID, $customer->getId());
        $searchResults = $this->rmaRepository->getList($this->searchCriteriaBuilder->create());

        return $searchResults->getFirstItem();
    }

    /**
     * Assert RMA comments
     *
     * @param array $actualComments
     */
    private function assertRmaComments(array $actualComments): void
    {
        $expectedComments = [
            [
                'created_at' => date('Y-m-d H:i:s'),
                'author_name' => 'Customer Service',
                'text' => 'Test comment'
            ],
            [
                'created_at' => date('Y-m-d H:i:s'),
                'author_name' => 'John Smith',
                'text' => 'Additional return comment'
            ]
        ];

        foreach ($expectedComments as $key => $expectedComment) {
            self::assertEqualsWithDelta(
                strtotime($expectedComment['created_at']),
                strtotime($actualComments[$key]['created_at']),
                5,
            );
            self::assertEquals($expectedComment['author_name'], $actualComments[$key]['author_name']);
            self::assertEquals($expectedComment['text'], $actualComments[$key]['text']);
        }
    }
}

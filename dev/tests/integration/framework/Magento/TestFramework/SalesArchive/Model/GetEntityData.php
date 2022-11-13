<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TestFramework\SalesArchive\Model;

use Magento\SalesArchive\Model\ResourceModel\Archive;

/**
 * Loads entity data by entity code and field.
 */
class GetEntityData
{
    /**
     * @var Archive
     */
    private $archiveResource;

    /**
     * @param Archive $archiveResource
     */
    public function __construct(
        Archive $archiveResource
    ) {
        $this->archiveResource = $archiveResource;
    }

    /**
     * Loads archive entity data by entity type.
     *
     * @param string $entityType
     * @param string $field
     * @param string $value
     * @return array
     */
    public function execute(string $entityType, string $field, string $value): array
    {
        $select =  $this->archiveResource->getConnection()->select()
            ->from(['e' => $this->archiveResource->getArchiveEntityTable($entityType)])
            ->where(sprintf('e.%s = ?', $field), $value);

        return  $this->archiveResource->getConnection()->fetchRow($select);
    }
}

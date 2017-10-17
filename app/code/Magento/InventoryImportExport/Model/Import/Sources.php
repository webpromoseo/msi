<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryImportExport\Model\Import;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\ImportExport\Helper\Data as DataHelper;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ResourceModel\Helper as ResourceHelper;
use Magento\ImportExport\Model\ResourceModel\Import\Data as ImportData;
use Magento\InventoryImportExport\Model\Import\Command\CommandInterface;
use Magento\InventoryImportExport\Model\Import\Validator\ValidatorInterface;

/**
 * @inheritdoc
 */
class Sources extends AbstractEntity
{
    /**
     * Column names for import file
     */
    const COL_SKU = 'sku';
    const COL_SOURCE = 'source';
    const COL_QTY = 'qty';
    const COL_STATUS = 'status';

    /**
     * @var SerializerInterface
     */
    protected $jsonHelper;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var CommandInterface[]
     */
    private $commands = [];

    /**
     * @param SerializerInterface $jsonHelper
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param ResourceHelper $resourceHelper
     * @param DataHelper $dataHelper
     * @param ImportData $importData
     * @param ValidatorInterface $validator
     * @param array $commands
     * @throws LocalizedException
     */
    public function __construct(
        SerializerInterface $jsonHelper,
        ProcessingErrorAggregatorInterface $errorAggregator,
        ResourceHelper $resourceHelper,
        DataHelper $dataHelper,
        ImportData $importData,
        ValidatorInterface $validator,
        array $commands = []
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->errorAggregator = $errorAggregator;
        $this->_resourceHelper = $resourceHelper;
        $this->_importExportData = $dataHelper;
        $this->_dataSourceModel = $importData;
        $this->validator = $validator;

        foreach ($commands as $command) {
            if (!$command instanceof CommandInterface) {
                throw new LocalizedException(
                    __('Source Import Commands must implement %interface.', ['interface' => CommandInterface::class])
                );
            }
        }
        $this->commands = $commands;
    }

    /**
     * Import data rows.
     * @return boolean
     * @throws LocalizedException
     */
    protected function _importData()
    {
        $command = $this->getCommandByBehavior($this->getBehavior());
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $command->execute($bunch);
        }

        return true;
    }

    /**
     * @param string $behavior
     * @return CommandInterface
     * @throws LocalizedException
     */
    private function getCommandByBehavior($behavior)
    {
        if (!isset($this->commands[$behavior])) {
            throw new LocalizedException(
                __('There is no command registered for behavior "%behavior".', ['behavior' => $behavior])
            );
        }

        return $this->commands[$behavior];
    }

    /**
     * EAV entity type code getter.
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'stock_sources';
    }

    /**
     * Validate data row.
     *
     * @param array $rowData
     * @param int $rowNum
     * @return boolean
     */
    public function validateRow(array $rowData, $rowNum)
    {
        $result = $this->validator->validate($rowData, $rowNum);
        if ($result->isValid()) {
            return true;
        }

        foreach ($result->getErrors() as $error) {
            $this->addRowError($error, $rowNum);
        }

        return false;
    }
}

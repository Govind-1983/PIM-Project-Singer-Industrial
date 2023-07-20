<?php

namespace App\Services;

use Elements\Bundle\ProcessManagerBundle\Model\MonitoringItem;
use Exception;
use Pimcore\Model\DataObject;
use Psr\Log\LoggerInterface;
use Elements\Bundle\ProcessManagerBundle\ElementsProcessManagerBundle;



class LogService
{
    /**
     * @var LoggerInterface:
     */
    protected $logger;

    /**
     * @var MonitoringItem
     */
    protected $monitoringItem;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->monitoringItem = ElementsProcessManagerBundle::getMonitoringItem();
        $this->logger = $this->monitoringItem->getLogger();
    }

    /**
     * @param $class
     *
     * @return void
     *
     * @throws \Exception
     */
    public function createLogs($exportFamilySetting, $channelObject, $objectsData)
    {
    }
}

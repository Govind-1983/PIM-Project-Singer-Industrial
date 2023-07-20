<?php

namespace App\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Model\DataObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue;

class TaxonomyAttributeCommand extends AbstractCommand
{
    const FILE_TYPE = 'Xlsx';
    const SHEET_NAME = 'Schema Sample';
    const ASSET_FOLDER_NAME = "/MasterDataFolder/TaxonomySchemas";
    const DATA_OBJECT_FOLDER_NAME = "MasterData/TaxonomySchemas";

    const ATTRIBUTE_MAPPING = [
        'END_NODE' => 'End Node',
        'ATTRIBUTE' => 'Attribute',
        'DATA_TYPE' => 'Data Type',
        'DISPLAY_ORDER' => 'Display Order',
        'NAVIGATION_ORDER' => 'Navigation Order',
        'UOM' => 'Uom',
        'T2' => 'T2',
        'TAXONOMY_LEVELS_FIRST' => 'T'
    ];

    const DATA_TYPES_MAPPING = [
        'Text' => 'Input',
        'Alphanumeric' => 'Input',
        'Number' => 'Numeric',
    ];

    const FILE_PATH = "/TaxonomyAttributes.xlsx";

    private $endNode;

    protected function configure(): void
    {
        $this
            ->setName('taxonomy-attributes:import')
            ->setDescription('Run a Taxonomy Attribute Import.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {

            $filePath =  self::ASSET_FOLDER_NAME . self::FILE_PATH;

            $taxonomyFile = \Pimcore\Model\Asset::getByPath($filePath);

            $taxonomyAttributeFile = PIMCORE_WEB_ROOT . '/var/assets/' . $taxonomyFile->getFullPath();

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(self::FILE_TYPE);

            $reader->setReadDataOnly(true);

            $worksheetData = $reader->listWorksheetInfo($taxonomyAttributeFile);

            if (!empty($worksheetData[0])) {

                $reader->setLoadSheetsOnly($worksheetData[0]);

                $spreadsheet = $reader->load($taxonomyAttributeFile);

                $taxonomyAttributes = $spreadsheet->getActiveSheet()->toArray();

                $this->createTaxonomyAttributeObjects($taxonomyAttributes);
            } else {
                $this->setLogger('error', "No Active sheet found");
            }

            return 1;
        } catch (\Exception $e) {
            $this->setLogger('error', $e->getMessage());
            $this->setLogger('error', $e->getTraceAsString());
        }
    }

    /**
     * @param array $taxonomyAttributes
     */
    protected function createTaxonomyAttributeObjects($taxonomyAttributes = []): void
    {

        $headers = array_flip($taxonomyAttributes[0]);
        unset($taxonomyAttributes[0]);

        $taxonomyAttributes = array_chunk($taxonomyAttributes, 100);

        $this->setLogger('info', "PROCESS_START :: To import taxonomy attributes data");

        foreach ($taxonomyAttributes as $batch => $taxonomy) {

            $this->setLogger('info', " BATCH " . $batch . ":: To import taxonomy attributes data");

            foreach ($taxonomy as $data) {
                try {
                    $this->endNode = $this->getEndNode($headers, $data);
                    if (
                        !empty($this->endNode)
                        && !empty($data[$headers[self::ATTRIBUTE_MAPPING['ATTRIBUTE']]])
                    ) {
                        $taxonomyAttributeObject = new DataObject\TaxonomyAttributes();

                        $key = \Pimcore\Model\DataObject\Service::getValidKey(
                            $this->endNode . "-" . $data[$headers[self::ATTRIBUTE_MAPPING['ATTRIBUTE']]],
                            'object'
                        );

                        if (!$this->isTaxonomyAttributeExist($key)) {

                            $attributeID = $this->importInClassificationStore($headers, $data);

                            $taxonomyAttributeObject->setKey($key);

                            $taxonomyAttributeObject->setName($data[$headers[self::ATTRIBUTE_MAPPING['ATTRIBUTE']]]);

                            $taxonomyAttributeObject->setAttributeId($attributeID);

                            $taxonomyAttributeObject->setDataType($data[$headers[self::ATTRIBUTE_MAPPING['DATA_TYPE']]]);

                            $taxonomyAttributeObject->setDisplayOrder($data[$headers[self::ATTRIBUTE_MAPPING['DISPLAY_ORDER']]]);

                            $taxonomyAttributeObject->setNavigationOrder($data[$headers[self::ATTRIBUTE_MAPPING['NAVIGATION_ORDER']]]);

                            $taxonomyAttributeObject->setUom($data[$headers[self::ATTRIBUTE_MAPPING['UOM']]]);

                            $taxonomyAttributeObject->setParent($this->getFolderPath($this->endNode));

                            $taxonomyAttributeObject->setPublished(true);

                            $taxonomyAttributeObject->save();

                            unset($taxonomyAttributeObject);
                        }
                    }
                } catch (\Exception $e) {
                    $this->setLogger('error', $e->getMessage());
                    $this->setLogger('error', $e->getTraceAsString());
                }
            }

            $this->setLogger('info', " BATCH " . $batch . ":: To import taxonomy attributes data finish");

            \Pimcore::collectGarbage();
        }

        $this->setLogger('info', "PROCESS_FINISH :: To import taxonomy attributes data");
    }

    /**
     * @param string $endNode
     * @return mixed
     */
    protected function getFolderPath($endNode = '')
    {
        return \Pimcore\Model\DataObject\Service::createFolderByPath(self::DATA_OBJECT_FOLDER_NAME . "/" . $endNode);
    }

    /**
     * @param $key
     * @return bool
     * @throws \Exception
     */
    protected function isTaxonomyAttributeExist($key): bool
    {

        $list = new DataObject\TaxonomyAttributes\Listing();

        $list->filterByKey($key);

        $list->setLimit(1);

        $taxonomyAttributeList = $list->load();

        return (!empty($taxonomyAttributeList)) ? 1 : 0;
    }

    /**
     * @param string $level
     * @param mixed $message
     */
    protected function setLogger($level = '', $message = ''): void
    {
        if ($level == 'info') {
            p_r($message);
        }

        if ($level == 'error') {
            p_r($message);
        }

        if ($level == 'dump') {
            p_r($message);
        }
    }

    /**
     * @param array $headers
     * @param array $data
     * @return int
     */
    protected function importInClassificationStore($headers = [], $data = []): int
    {

        try {
            $keyId = '';
            $groupId = '';
            $collectionId = '';

            $definitionClassTypeName = !empty(self::DATA_TYPES_MAPPING[$data[$headers[self::ATTRIBUTE_MAPPING['DATA_TYPE']]]]) ?
                self::DATA_TYPES_MAPPING[$data[$headers[self::ATTRIBUTE_MAPPING['DATA_TYPE']]]] :
                self::DATA_TYPES_MAPPING['Text'];

            if (!empty($data[$headers[self::ATTRIBUTE_MAPPING['UOM']]])) {

                $units = $data[$headers[self::ATTRIBUTE_MAPPING['UOM']]];
                $definitionClassTypeName = "QuantityValue";

                $definitionClassTypeName = '\\Pimcore\\Model\\DataObject\\ClassDefinition\\Data\\' . $definitionClassTypeName;
                $definition = new $definitionClassTypeName();

                $name = \Pimcore\Model\DataObject\Service::getValidKey($data[$headers[self::ATTRIBUTE_MAPPING['ATTRIBUTE']]], 'object');
                $definition->setName($name);
                $definition->setTitle($data[$headers[self::ATTRIBUTE_MAPPING['ATTRIBUTE']]]);

                foreach (explode(",", $units) as $unit) {
                    $unitId[] = $this->setUnits($unit);
                }

                $definition->setValidUnits($unitId);
                $definition->setDefaultUnit($unitId[0]);
            } else {
                $definitionClassTypeName = '\\Pimcore\\Model\\DataObject\\ClassDefinition\\Data\\' . $definitionClassTypeName;

                $definition = new $definitionClassTypeName();

                $name = \Pimcore\Model\DataObject\Service::getValidKey($data[$headers[self::ATTRIBUTE_MAPPING['ATTRIBUTE']]], 'object');
                $definition->setName($name);
                $definition->setTitle($data[$headers[self::ATTRIBUTE_MAPPING['ATTRIBUTE']]]);
            }



            $keyId = $this->checkKeyExistInClassificationStore($name);

            if (empty($keyId)) {
                $keyConfig = new \Pimcore\Model\DataObject\Classificationstore\KeyConfig();
                $keyConfig->setName($name);
                $keyConfig->setEnabled(true);
                $keyConfig->setType($definition->getFieldtype());
                $keyConfig->setDefinition(json_encode($definition));
                $keyConfig->save();
                $keyId = $keyConfig->getId();
            }

            $groupName = $this->endNode;
            $groupId = $this->checkGroupExistInClassificationStore($groupName);

            if (empty($groupId)) {
                $groupConfig = new \Pimcore\Model\DataObject\Classificationstore\GroupConfig();
                $groupConfig->setName($groupName);
                $groupConfig->save();
                $groupId = $groupConfig->getId();
            }

            $collectionName = \Pimcore\Model\DataObject\Service::getValidKey($data[$headers[self::ATTRIBUTE_MAPPING['T2']]], 'object');
            $collectionId = $this->checkCollectionExistInClassificationStore($collectionName);

            if (empty($collectionId)) {
                $collectionConfig = new \Pimcore\Model\DataObject\Classificationstore\CollectionConfig();
                $collectionConfig->setName($collectionName);
                $collectionConfig->save();
                $collectionId = $collectionConfig->getId();
            }


            $rel = new \Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation();
            $rel->setGroupId($groupId);
            $rel->setColId($collectionId);
            $rel->save();

            $rel = new \Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation();
            $rel->setGroupId($groupId);
            $rel->setKeyId($keyId);
            $rel->setName($groupName . "-" . $name);
            $rel->setSorter($data[$headers[self::ATTRIBUTE_MAPPING['DISPLAY_ORDER']]]);
            $rel->save();

            return $keyId;
        } catch (\Exception $e) {
            $this->setLogger('error', $e->getMessage());
            $this->setLogger('error', $e->getTraceAsString());
        }
    }

    /**
     * @param $name
     * @return int|null
     * @throws \Exception
     */
    protected function checkGroupExistInClassificationStore($name)
    {
        $config = \Pimcore\Model\DataObject\Classificationstore\GroupConfig::getByName($name);
        return !empty($config) ? $config->getId() : null;
    }

    /**
     * @param $name
     * @return int|null
     * @throws \Exception
     */
    protected function checkKeyExistInClassificationStore($name)
    {
        $config = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName($name);
        return !empty($config) ? $config->getId() : null;
    }

    /**
     * @param $name
     * @return int|null
     * @throws \Exception
     */
    protected function checkCollectionExistInClassificationStore($name)
    {
        $config = \Pimcore\Model\DataObject\Classificationstore\CollectionConfig::getByName($name);
        return !empty($config) ? $config->getId() : null;
    }


    /**
     * @param array $header
     * @param array $data
     * @return string
     */
    protected function getEndNode($header = [], $data = [])
    {

        $endNode = null;
        $taxonomyData = array_combine(array_keys($header), $data);
        foreach ($taxonomyData as $key => $value) {
            if ($key[0] == self::ATTRIBUTE_MAPPING['TAXONOMY_LEVELS_FIRST']) {
                if (!empty($value)) {
                    $endNode = $value;
                }
            }
        }
        return $endNode;
    }

    /**
     * @param $unitAbbreviation
     * @return mixed
     */
    protected function setUnits($unitAbbreviation)
    {
        $unit = \Pimcore\Model\DataObject\QuantityValue\Unit::getByAbbreviation($unitAbbreviation);

        if (empty($unit)) {
            $unit = new \Pimcore\Model\DataObject\QuantityValue\Unit();
            $unit->setAbbreviation($unitAbbreviation);
            $unit->save();
        }
        return $unit->getId();
    }
}

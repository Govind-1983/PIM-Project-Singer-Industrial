<?php

namespace App\Command;

use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Model\DataObject;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Psr\Log\LoggerInterface;
use Exception;
use Symfony\Component\Console\Input\InputOption;

class TaxonomyCommandImport extends AbstractCommand
{
    const FILE_TYPE = 'Xlsx';
    const SHEET_NAME = 'Taxonomy';
    const ASSET_FOLDER_NAME = "/MasterDataFolder/Taxonomies/";
    const FILE_NAME = "taxonomy_import";
    const DATA_OBJECT_FOLDER_NAME = "MasterData/Taxonomies";

    const TAXONOMY_MAPPING = [
        'Taxonomy' => 'T',
    ];

    /**
     * Logger for order
     *
     * @var LoggerInterface
     */
    protected $taxonomyLogger;

    public function __construct(LoggerInterface $taxonomyLogger)
    {
        parent::__construct();
        $this->taxonomyLogger = $taxonomyLogger;
    }

    protected function configure()
    {
        $this->setName('Import:Taxonomy:import')->setDescription('Using this command you can import the taxonomy data.')
            ->addArgument('product_asset_id' , InputOption::VALUE_OPTIONAL, 'Product Asset Excel ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->taxonomyLogger->info("PROCESS_START :: To import taxonomy data");
            $inputAssetId = $input->getArgument('product_asset_id');

            $filePath = self::ASSET_FOLDER_NAME . self::FILE_NAME . '.xlsx';
            $asset = \Pimcore\Model\Asset::getByPath($filePath);

            if (!empty($inputAssetId)) {
                $filePath = \Pimcore\Model\Asset::getById($inputAssetId[0])->getFullPath();
                $asset = \Pimcore\Model\Asset::getByPath($filePath);
            }

            $sourceFile = PIMCORE_WEB_ROOT . '/var/assets' . $filePath;

            if (!empty($asset)) {
                $inputFileType = IOFactory::identify($sourceFile);

                $reader = IOFactory::createReader($inputFileType);
                $spreadsheet = $reader->load($sourceFile);
                $workSheets = $spreadsheet->getSheetNames();

                $this->taxonomyLogger->info("Reading '" . $workSheets[0] . "' sheet");
                $firstSheet = $spreadsheet->getSheet(0);
                $taxonomySheetData = $firstSheet->toArray();
                $this->taxonomyData($taxonomySheetData);

                $this->taxonomyLogger->info("PROCESS_END :: taxonomy data completely imported!");
            } else {
                $this->taxonomyLogger->info("Data file not found to import!");
            }
        } catch (\Exception $e) {
            $this->taxonomyLogger->error($e->getMessage());
        }
        return 1;
    }

    protected function taxonomyData($taxonomySheetData = [])
    {
        try {
            $headers = $taxonomySheetData[0];

            //Unset header from excel sheet
            unset($taxonomySheetData[0]);
            foreach ($taxonomySheetData as $taxonomydata) {
                $index = 0;
                foreach ($taxonomydata as $key => $data) {
                    $taxonomyDataIndex = self::TAXONOMY_MAPPING['Taxonomy'] .  $index;
                    if (array_search($taxonomyDataIndex, $headers) && $data) {
                        //Check existing data
                        $alreadyExist = $this->checkExistingKey($data);
                        if ($alreadyExist) {
                            $parentId = $alreadyExist->getId();
                        } else {
                            $taxonomyObj = new DataObject\Taxonomy();
                            $taxonomyObj->setKey($data);
                            if (empty($parentId)) {
                                $taxonomyObj->setParent(\Pimcore\Model\DataObject\Service::createFolderByPath(self::DATA_OBJECT_FOLDER_NAME));
                            } else {
                                $taxonomyObj->setParentId($parentId);
                            }
                            $taxonomyObj->setName($data);
                            $taxonomyObj->setPublished(true);
                            $taxonomyObj->save();
                            $parentId = $taxonomyObj->getId();
                        }
                    }
                    if ($index % 5 == 0) {
                        \Pimcore::collectGarbage();
                    }
                    $index++;
                }
            }
        } catch (Exception $e) {
            $this->taxonomyLogger->error('Error while importing taxonomies: ' . $e->getMessage());
        }
    }
    /**
     * For check existing taxonomy
     * @param string $key
     *
     */
    public function checkExistingKey($key)
    {
        $list = new DataObject\Taxonomy\Listing();
        $list->filterByKey($key);
        $list->setLimit(1);
        $master = $list->load();
        return (!empty($master)) ? $master[0] : null;
    }
}

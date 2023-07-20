<?php

namespace App\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Model\Asset\Document;
use Pimcore\Model\Asset\Video;
use Pimcore\Model\DataObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue;
use \Pimcore\Model\Asset\Image;

class ProductImportCommand extends AbstractCommand
{
    const FILE_TYPE = 'Xlsx';
    const ASSET_FOLDER_NAME = "/Continental/OutputFolder";
    const OBJECT_FOLDER_NAME = "Continental";
    const IMAGES_FOLDER = "/Continental/Images/";
    const DOCUMENTS_FOLDER = "/Continental/Documents/";
    const VIDEOS_FOLDER = "/Continental/Videos/";
    const BATCHES_LIMIT = 20;

    const ATTRIBUTE_MAPPING = [
        'END_NODE' => 'End Node',
        'SSIN' => 'SSIN (Unique ID)',
        'MANUFACTURER_NAME' => 'Manufacturer name',
        'BRAND' => 'Brand',
        'MANUFACTURER_PART_NUMBER' => 'Manufacturer part number ',
        'SHORT_DESCRIPTION' => 'Short Description',
        'LONG_DESCRIPTION' => 'Long Description',
        'FEATURES' => 'Feature & Benefit Bullets',
        'UPC' => 'UPC/EAN',
        'UNSPSC' => 'UNSPSC',
        'US_TARRIF_CODE' => 'US Tariff Code',
        'VENDOR_CAGE_CODE' => 'Vendor Cage Code',
        'NMFC' => 'NMFC',
        'PACKAGE_HEIGHT' => 'Package Height (Inches)',
        'PACKAGE_LENGTH' => 'Package Length (Inches)',
        'PACKAGE_WIDTH' => 'Package Width (Inches)',
        'PACKAGE_WEIGHT' => 'Package Weight (pounds)',
        'COUNTRY_OF_ORIGIN' => 'Country of Origin',
        'REGULAR_PRICE' => 'Regular Price',
        'SALE_PRICE' => 'Sale Price',
        'NET_PACK_QUANTITY' => 'Net Pack Quantity',
        'PART_STATUS' => 'Part Status',
        'LEAD_TIME' => 'Lead Time (days)',
        'MIN_ORDER_QUANTITY' => 'Min Order Quantity',
        'STOCK_STATUS' => 'Stock Status',
        'PRODUCT_BROCHURE' => 'Product Brochure',
        'PRODUCT_CATALOG' => 'Product Catalog',
        'FEATURED_IMAGE' => 'Featured Image',
        'DIMENSIONAL_IMAGE' => 'Dimensional Image',
        'CAD_MODEL' => '3D/CAD Model',
        'ISOMETRIC_IMAGE' => 'Isometric Image',
        'GALLERY_IMAGE' => 'Gallery Image',
        'WARNING_IMAGE' => 'Warning Image',
        'PROPISITION' => 'Proposition 65? (Y/N)',
        'COMPATIBILITY_CHART' => 'Compatibility Chart',
        'PRODUCT_VIDEO' => 'Product Video',
        'KEYWORDS' => 'Keywords',
        'URL_SLUG' => 'URL Slug',
        'META_TITLE' => 'Meta Title',
        'META_DESCRIPTION' => 'Meta Description',
        'ATTRIBUTE_NAME' => 'Attribute Name',
        'ATTRIBUTE_VALUE' => 'Attribute Value',
        'ATTRIBUTE_UOM' => 'Attribute UOM',
        'TAXONOMY_LEVELS_FIRST' => 'Taxonomy',
        'PRODUCT_NAME' => 'Product Name',
        'STANDARDS' => 'Standards',
        'APPROVALS' => 'Approvals',
        'CERTIFICATIONS' => 'Certifications'
    ];

    const DATA_TYPES_MAPPING = [
        'Text' => 'Input',
        'Alphanumeric' => 'Input',
        'Number' => 'Numeric',
    ];

    const FILE_PATH = "ProductData.xlsx";

    private $endNode;

    private $headers;

    private $activeGroup;

    protected function configure(): void
    {
        $this
            ->setName('product-data:import')
            ->setDescription('Run a Product Import.')
            ->addArgument('product_asset_id' , InputOption::VALUE_OPTIONAL, 'Product Asset Excel ID');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {

            $inputAssetId = $input->getArgument('product_asset_id');

            $filePath = self::ASSET_FOLDER_NAME . "/" . self::FILE_PATH;

            $productFileObj = \Pimcore\Model\Asset::getByPath($filePath);

            if (!empty($inputAssetId)) {
                $productFileObj = \Pimcore\Model\Asset::getById($inputAssetId[0]);
            }

            $productImportFile = PIMCORE_WEB_ROOT . '/var/assets/' . $productFileObj->getFullPath();

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(self::FILE_TYPE);

            $reader->setReadDataOnly(true);

            $worksheetData = $reader->listWorksheetInfo($productImportFile);

            if (!empty($worksheetData[0])) {

                $reader->setLoadSheetsOnly($worksheetData[0]);

                $spreadsheet = $reader->load($productImportFile);

                $productData = $spreadsheet->getActiveSheet()->toArray();

                $this->importProducts($productData);

            } else {
                $this->setLogger('error', "No Active sheet found");
            }

            return 1;

        } catch (\Exception $e) {
            $this->setLogger('error', $e->getMessage());
            $this->setLogger('error', $e->getTraceAsString());
        }

    }

    protected function importProducts($productData): void
    {
        if (!empty($productData)) {

            $this->headers = array_flip($productData[0]);
            unset($productData[0]);

            $products = array_chunk($productData, self::BATCHES_LIMIT);

            $this->setLogger('info', "PROCESS_START :: To import product data");

            foreach ($products as $batch => $product) {

                $this->setLogger('info', " BATCH " . $batch . ":: To import product  data");

                foreach ($product as $data) {
                    try {
                        if (!empty($data)) {

                            $this->endNode = $this->getEndNode($this->headers, $data);

                            $this->getClassificationGroup($this->endNode);

                            if (!empty($this->endNode)) {

                                $productObject = new DataObject\Product();

                                $key = $this->getUniqueSSINNumber();

                                if (!$this->isProductExist($key)) {

                                    $productObject->setKey($key);

                                    $productObject->setSsin($key);

                                    $productObject->setManufacturerName($data[$this->headers[self::ATTRIBUTE_MAPPING['MANUFACTURER_NAME']]]);

                                    $productObject->setBrand($data[$this->headers[self::ATTRIBUTE_MAPPING['BRAND']]]);

                                    $productObject->setManufacturerPartNumber($data[$this->headers[self::ATTRIBUTE_MAPPING['MANUFACTURER_PART_NUMBER']]]);

                                    $productObject->setShortDescriptions($data[$this->headers[self::ATTRIBUTE_MAPPING['SHORT_DESCRIPTION']]]);

                                    $productObject->setLongDescriptions($data[$this->headers[self::ATTRIBUTE_MAPPING['LONG_DESCRIPTION']]]);

                                    $productObject->setFeaturesAndBenefit(implode("\n", explode("|", $data[$this->headers[self::ATTRIBUTE_MAPPING['FEATURES']]])));

                                    $productObject->setUpc($data[$this->headers[self::ATTRIBUTE_MAPPING['UPC']]]);

                                    $productObject->setUnspsc($data[$this->headers[self::ATTRIBUTE_MAPPING['UNSPSC']]]);

                                    $productObject->setUsTariffCode($data[$this->headers[self::ATTRIBUTE_MAPPING['US_TARRIF_CODE']]]);

                                    $productObject->setVendorCageCode($data[$this->headers[self::ATTRIBUTE_MAPPING['VENDOR_CAGE_CODE']]]);

                                    $productObject->setNmfc($data[$this->headers[self::ATTRIBUTE_MAPPING['NMFC']]]);

                                    $productObject->setPackage_Height($data[$this->headers[self::ATTRIBUTE_MAPPING['PACKAGE_HEIGHT']]]);

                                    $productObject->setPackageWidth($data[$this->headers[self::ATTRIBUTE_MAPPING['PACKAGE_WEIGHT']]]);

                                    $productObject->setPackageLength($data[$this->headers[self::ATTRIBUTE_MAPPING['PACKAGE_LENGTH']]]);

                                    $productObject->setPackageWeight($data[$this->headers[self::ATTRIBUTE_MAPPING['PACKAGE_WEIGHT']]]);

                                    $productObject->setCountryofOrigin($data[$this->headers[self::ATTRIBUTE_MAPPING['COUNTRY_OF_ORIGIN']]]);

                                    $productObject->setRegularPrice($data[$this->headers[self::ATTRIBUTE_MAPPING['REGULAR_PRICE']]]);

                                    $productObject->setSalePrice($data[$this->headers[self::ATTRIBUTE_MAPPING['SALE_PRICE']]]);

                                    $productObject->setNetPackQuantity($data[$this->headers[self::ATTRIBUTE_MAPPING['NET_PACK_QUANTITY']]]);

                                    $productObject->setPartStatus($data[$this->headers[self::ATTRIBUTE_MAPPING['PART_STATUS']]]);

                                    $productObject->setLeadTime($data[$this->headers[self::ATTRIBUTE_MAPPING['LEAD_TIME']]]);

                                    $productObject->setMinOrderQuantity($data[$this->headers[self::ATTRIBUTE_MAPPING['MIN_ORDER_QUANTITY']]]);

                                    $productObject->setStockstatus($data[$this->headers[self::ATTRIBUTE_MAPPING['STOCK_STATUS']]]);

                                    $productObject->setProposition65($data[$this->headers[self::ATTRIBUTE_MAPPING['PROPISITION']]]);

                                    $productObject->setKeywords($data[$this->headers[self::ATTRIBUTE_MAPPING['KEYWORDS']]]);

                                    $productObject->setUrlSlug($data[$this->headers[self::ATTRIBUTE_MAPPING['URL_SLUG']]]);

                                    $productObject->setMetaDescription($data[$this->headers[self::ATTRIBUTE_MAPPING['META_DESCRIPTION']]]);

                                    $productObject->setMetaTitle($data[$this->headers[self::ATTRIBUTE_MAPPING['META_TITLE']]]);

                                    $productObject->setStandards($data[$this->headers[self::ATTRIBUTE_MAPPING['STANDARDS']]]);

                                    $productObject->setCertifications($data[$this->headers[self::ATTRIBUTE_MAPPING['CERTIFICATIONS']]]);

                                    $productObject->setApprovals($data[$this->headers[self::ATTRIBUTE_MAPPING['APPROVALS']]]);

                                    $productObject->setProductName($data[$this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_NAME']]]);
                                    
                                    $productObject->setParent($this->getFolderPath($this->endNode));


                                    $productObject->setTaxonomy([$this->getTaxonomy($this->endNode)]);

                                    if (!empty($data[$this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_VIDEO']]])) {
                                        if (Video::getByPath(self::VIDEOS_FOLDER . $data[$this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_VIDEO']]])) {
                                            $videoData = new \Pimcore\Model\DataObject\Data\Video();
                                            $videoData->setData(Video::getByPath(self::VIDEOS_FOLDER . $data[$this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_VIDEO']]]));
                                            $videoData->setType("asset");
                                            $videoData->setTitle("My Title");
                                            $videoData->setDescription("My Description");
                                            $productObject->setProductVideo($videoData);
                                        }
                                    }


                                    $productObject->setProductCatalog(Document::getByPath(self::DOCUMENTS_FOLDER . $data[$this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_CATALOG']]]));
                                    $productObject->setProductBrochure(Document::getByPath(self::DOCUMENTS_FOLDER . $data[$this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_BROCHURE']]]));
                                    $productObject->setCompatibilityChart(Document::getByPath(self::DOCUMENTS_FOLDER . $data[$this->headers[self::ATTRIBUTE_MAPPING['COMPATIBILITY_CHART']]]));

                                    $productObject->setFeaturedImage(Image::getByPath(self::IMAGES_FOLDER . $data[$this->headers[self::ATTRIBUTE_MAPPING['FEATURED_IMAGE']]]));

                                    $galleryImagesArray = $this->getImageArray($data, self::ATTRIBUTE_MAPPING['GALLERY_IMAGE']);
                                    $galleryImages = [];

                                    foreach($galleryImagesArray as $galImages) {
                                        $image = Image::getByPath(self::IMAGES_FOLDER . $galImages);
                                        $advancedImage = new \Pimcore\Model\DataObject\Data\Hotspotimage();
                                        $advancedImage->setImage($image);
                                        $galleryImages[] = $advancedImage;
                                    }

                                    $productObject->setGalleryImage(new \Pimcore\Model\DataObject\Data\ImageGallery($galleryImages));

                                    $isometricImagesArray = $this->getImageArray($data, self::ATTRIBUTE_MAPPING['ISOMETRIC_IMAGE']);
                                    $isometricImages = [];
                                    foreach($isometricImagesArray as $galImages) {
                                        $image = Image::getByPath(self::IMAGES_FOLDER . $galImages);
                                        $advancedImage = new \Pimcore\Model\DataObject\Data\Hotspotimage();
                                        $advancedImage->setImage($image);
                                        $isometricImages[] = $advancedImage;
                                    }

                                    $productObject->setIsometricImage(new \Pimcore\Model\DataObject\Data\ImageGallery($isometricImages));

                                    $warningImagesArray = $this->getImageArray($data, self::ATTRIBUTE_MAPPING['WARNING_IMAGE']);
                                    $warningImages = [];
                                    foreach($warningImagesArray as $galImages) {
                                        $image = Image::getByPath(self::IMAGES_FOLDER . $galImages);
                                        $advancedImage = new \Pimcore\Model\DataObject\Data\Hotspotimage();
                                        $advancedImage->setImage($image);
                                        $warningImages[] = $advancedImage;
                                    }

                                    $productObject->setWarningImage(new \Pimcore\Model\DataObject\Data\ImageGallery($warningImages));

                                    $cadModelImagesArray = $this->getImageArray($data, self::ATTRIBUTE_MAPPING['CAD_MODEL']);
                                    $cadModelImages = [];
                                    foreach($cadModelImagesArray as $galImages) {
                                        $image = Image::getByPath(self::IMAGES_FOLDER . $galImages);
                                        $advancedImage = new \Pimcore\Model\DataObject\Data\Hotspotimage();
                                        $advancedImage->setImage($image);
                                        $cadModelImages[] = $advancedImage;
                                    }

                                    $productObject->setCadModel(new \Pimcore\Model\DataObject\Data\ImageGallery($cadModelImages));

                                    $dimensionalImagesArray = $this->getImageArray($data, self::ATTRIBUTE_MAPPING['DIMENSIONAL_IMAGE']);
                                    $dimensionalImages = [];
                                    foreach($dimensionalImagesArray as $galImages) {
                                        $image = Image::getByPath(self::IMAGES_FOLDER . $galImages);
                                        $advancedImage = new \Pimcore\Model\DataObject\Data\Hotspotimage();
                                        $advancedImage->setImage($image);
                                        $dimensionalImages[] = $advancedImage;
                                    }

                                    $productObject->setDimensionalImage(new \Pimcore\Model\DataObject\Data\ImageGallery($dimensionalImages));


                                    $productObject->getAttribute()->setActiveGroups([
                                        $this->activeGroup => true
                                    ]);

                                    $specificAttributes = $this->getSpecificAttributesArray($data);

                                    foreach ($specificAttributes as $attributes) {
                                        $key = $this->getClassificationKey(
                                            $attributes[self::ATTRIBUTE_MAPPING['ATTRIBUTE_NAME']]
                                        );
                                        if (!empty($key)) {
                                            $type = $this->getClassificationKeyType($attributes[self::ATTRIBUTE_MAPPING['ATTRIBUTE_NAME']]);
                                            if ($type != 'quantityValue') {
                                                $definition = $attributes[self::ATTRIBUTE_MAPPING['ATTRIBUTE_VALUE']];

                                            } else {
                                                $definitionClassTypeName = '\\Pimcore\\Model\\DataObject\\Data\\' . 'QuantityValue';
                                                $unit = null;
                                                if (!empty($attributes[self::ATTRIBUTE_MAPPING['ATTRIBUTE_UOM']])) {
                                                    $unit = \Pimcore\Model\DataObject\QuantityValue\Unit::getByAbbreviation($attributes[self::ATTRIBUTE_MAPPING['ATTRIBUTE_UOM']]);
                                                }
                                                $definition = new $definitionClassTypeName($attributes[self::ATTRIBUTE_MAPPING['ATTRIBUTE_VALUE']], $unit);
                                            }

                                            $productObject->getAttribute()->setLocalizedKeyValue(
                                                $this->activeGroup,
                                                $key,
                                                $definition
                                            );


                                        }

                                    }

                                    $productObject->save();
                                    unset($productObject);
                                }
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

        } else {
            $this->setLogger('error', "No Products Data found");
        }


    }

    /**
     * @param string $endNode
     * @return mixed
     */
    protected function getFolderPath($endNode = '')
    {
        return \Pimcore\Model\DataObject\Service::createFolderByPath(self::OBJECT_FOLDER_NAME);
    }

    /**
     * @param $key
     * @return bool
     * @throws \Exception
     */
    protected function isProductExist($key): bool
    {

        $list = new DataObject\Product\Listing();

        $list->filterByKey($key);

        $list->setLimit(1);

        $productList = $list->load();

        return (!empty($productList)) ? 1 : 0;
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
     * @param array $header
     * @param array $data
     * @return string
     */
    protected function getEndNode($header = [], $data = [])
    {

        $endNode = null;
        $productData = array_combine(array_keys($header), $data);
        foreach ($productData as $key => $value) {
            if (str_contains($key, self::ATTRIBUTE_MAPPING['TAXONOMY_LEVELS_FIRST'])) {
                if (!empty($value)) {
                    $endNode = $value;
                }
            }
        }
        return $endNode;
    }

    /**
     * @param $endNode
     * @throws \Exception
     */
    protected function getClassificationGroup($endNode)
    {
        $config = \Pimcore\Model\DataObject\Classificationstore\GroupConfig::getByName($endNode);
        $this->activeGroup = $config->getId();
    }

    /**
     * @param $name
     * @return int|null
     * @throws \Exception
     */
    protected function getClassificationKey($name)
    {
        $config = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName($name);
        return !empty($config) ? $config->getId() : null;
    }

    /**
     * @param $data
     * @return array
     */
    protected function getSpecificAttributesArray($data)
    {
        $specificAttributeData = [];
        $productData = array_combine(array_keys($this->headers), $data);
        foreach ($productData as $key => $value) {
            if (str_contains($key, self::ATTRIBUTE_MAPPING['ATTRIBUTE_NAME'])) {
                if (!empty($value)) {
                    $index = explode(' ', $key)[2];
                    $dataArray = [
                        self::ATTRIBUTE_MAPPING['ATTRIBUTE_NAME'] => $value,
                        self::ATTRIBUTE_MAPPING['ATTRIBUTE_VALUE'] => $productData[self::ATTRIBUTE_MAPPING['ATTRIBUTE_VALUE'] . " " . $index],
                        self::ATTRIBUTE_MAPPING['ATTRIBUTE_UOM'] => $productData[self::ATTRIBUTE_MAPPING['ATTRIBUTE_UOM'] . " " . $index]
                    ];
                    array_push($specificAttributeData, $dataArray);
                }
            }
        }

        return $specificAttributeData;
    }

    protected function getImageArray($data, $attributeToFound)
    {
        $images = [];
        $productData = array_combine(array_keys($this->headers), $data);
        foreach ($productData as $key => $value) {
            if (str_contains($key, $attributeToFound)) {
                if (!empty($value)) {
                    array_push($images, $value);
                }
            }
        }

        return $images;
    }

    protected function getClassificationKeyType($name)
    {
        $config = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName($name);
        return !empty($config) ? $config->getType() : null;
    }

    protected function getTaxonomy($key)
    {
        $list = new DataObject\Taxonomy\Listing();
        $list->filterByKey($key);
        $list->setLimit(1);
        $taxonomy = $list->load();
        return (!empty($taxonomy)) ? $taxonomy[0] : null;
    }

    /**
     * @return int|string|null
     * @throws \Exception
     */
    protected function getUniqueSSINNumber()
    {
        return strtoupper(substr(md5(microtime()), 0, 8));
    }

}

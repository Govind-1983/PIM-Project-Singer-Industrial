<?php

namespace App\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Model\Asset\Document;
use Pimcore\Model\Asset\Video;
use Pimcore\Model\DataObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \Pimcore\Model\DataObject\ClassDefinition\Data\InputQuantityValue;
use \Pimcore\Model\Asset\Image;
use \Pimcore\Model\Asset;

class ProductImportCommand extends AbstractCommand
{
    const IS_MIGRATION = true;
    const CHANNEL_IS_MIGRATION = 5773;
    const FILE_TYPE = 'Xlsx';
    const IMAGES_FOLDER = "/Images/";
    const DOCUMENTS_FOLDER = "/Documents/";
    const VIDEOS_FOLDER = "/Videos/";
    const BATCHES_LIMIT = 20;
    const PROCESSED_FOLDER = "/ProcessedFolder/";
    const LOGS_FOLDER = "/ErrorLogs/";

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
        'CERTIFICATIONS' => 'Certifications',
        'SERIES' => 'Series',
        'APPLICATIONS' => 'Applications',
        'PRODUCT_TYPE' => 'Product Type',
        'PRODUCT_WEIGHT_POUNDS' => 'Product Weight (pounds)',
        'PRODUCT_WEIGHT_KG' => 'Product Weight (Kg)',
        'WARRANTY' => 'warranty',
        'NET_PACK_QUANTITY_UNITS' => 'Net Pack Quantity Units',
    ];

    const DATA_TYPES_MAPPING = [
        'Text' => 'Input',
        'Alphanumeric' => 'Input',
        'Number' => 'Numeric',
    ];

    const FILE_PATH = "ProductData.xlsx";

    private $endNode;

    private $headers;

    private $activeGroup = null;

    private $uniqueIdentifier = '';

    private $vendorName = '';

    private $logData = [];

    protected function configure(): void
    {
        $this
            ->setName('product-data:import')
            ->setDescription('Run a Product Import.')
            ->addArgument('product_asset_id', InputOption::VALUE_REQUIRED, 'Product Asset Excel ID')
            ->addArgument('vendor_name', InputOption::VALUE_OPTIONAL, 'Vendor Name');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // $inputAssetId = $input->getArgument('product_asset_id');
            // $this->vendorName = "/" . $input->getArgument('vendor_name')[0];

            $fileData = $this->getVendorImportFile();

            if (!isset($fileData['vendorFolderName']) || !isset($fileData['vendorImportFileId'])) {
                return 1;
            }

            $this->vendorName = "/" . $fileData['vendorFolderName'];
            $inputAssetId = $fileData['vendorImportFileId'];

            if (!empty($inputAssetId)) {
                $productFileObj = \Pimcore\Model\Asset::getById($inputAssetId);
                $fileName = $productFileObj->getFilename();
            }

            if (!is_object($productFileObj)) {
                $this->setLogger('error', "No File found with this asset ID" . $inputAssetId);
                return 0;
            }
            $productImportFile = PIMCORE_WEB_ROOT . '/var/assets' . $productFileObj->getFullPath();

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(self::FILE_TYPE);

            $reader->setReadDataOnly(true);

            $worksheetData = $reader->listWorksheetInfo($productImportFile);

            if (!empty($worksheetData[0])) {

                $reader->setLoadSheetsOnly($worksheetData[0]);

                $spreadsheet = $reader->load($productImportFile);

                $productData = $spreadsheet->getActiveSheet()->toArray();

                $this->importProducts($productData);

                $processedFolder = \Pimcore\Model\Asset\Service::createFolderByPath(($this->vendorName . self::PROCESSED_FOLDER));

                if (!empty($this->logData)) {
                    $this->createLogFile($fileName);
                }

                $productFileObj->setParent($processedFolder);
                $productFileObj->save();
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

                                    $productObject->setManufacturerName(isset($this->headers[self::ATTRIBUTE_MAPPING['MANUFACTURER_NAME']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['MANUFACTURER_NAME']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['MANUFACTURER_NAME']]] : '');

                                    $productObject->setBrand(isset($this->headers[self::ATTRIBUTE_MAPPING['BRAND']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['BRAND']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['BRAND']]] : '');

                                    $productObject->setProductType(isset($this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_TYPE']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_TYPE']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_TYPE']]] : '');

                                    $productObject->setSeries(isset($this->headers[self::ATTRIBUTE_MAPPING['SERIES']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['SERIES']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['SERIES']]] : '');

                                    $productObject->setApplications(isset($this->headers[self::ATTRIBUTE_MAPPING['APPLICATIONS']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['APPLICATIONS']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['APPLICATIONS']]] : '');

                                    $productObject->setProductWeightPounds(isset($this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_WEIGHT_POUNDS']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_WEIGHT_POUNDS']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_WEIGHT_POUNDS']]] : '');

                                    $productObject->setProductWeightKg(isset($this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_WEIGHT_KG']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_WEIGHT_KG']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_WEIGHT_KG']]] : '');

                                    $productObject->setManufacturerPartNumber(isset($this->headers[self::ATTRIBUTE_MAPPING['MANUFACTURER_PART_NUMBER']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['MANUFACTURER_PART_NUMBER']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['MANUFACTURER_PART_NUMBER']]] : '');

                                    $this->uniqueIdentifier = $productObject->getManufacturerPartNumber();

                                    $productObject->setShortDescriptions(isset($this->headers[self::ATTRIBUTE_MAPPING['SHORT_DESCRIPTION']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['SHORT_DESCRIPTION']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['SHORT_DESCRIPTION']]] : '');

                                    $productObject->setLongDescriptions(isset($this->headers[self::ATTRIBUTE_MAPPING['LONG_DESCRIPTION']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['LONG_DESCRIPTION']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['LONG_DESCRIPTION']]] : '');

                                    $productObject->setFeaturesAndBenefit(isset($this->headers[self::ATTRIBUTE_MAPPING['FEATURES']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['FEATURES']]]) ? implode("\n", explode("|", $data[$this->headers[self::ATTRIBUTE_MAPPING['FEATURES']]])) : '');

                                    $productObject->setUpc(isset($this->headers[self::ATTRIBUTE_MAPPING['UPC']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['UPC']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['UPC']]] : '');

                                    $productObject->setUnspsc(isset($this->headers[self::ATTRIBUTE_MAPPING['UNSPSC']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['UNSPSC']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['UNSPSC']]] : '');

                                    $productObject->setUsTariffCode(isset($this->headers[self::ATTRIBUTE_MAPPING['US_TARRIF_CODE']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['US_TARRIF_CODE']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['US_TARRIF_CODE']]] : '');

                                    $productObject->setVendorCageCode(isset($this->headers[self::ATTRIBUTE_MAPPING['VENDOR_CAGE_CODE']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['VENDOR_CAGE_CODE']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['VENDOR_CAGE_CODE']]] : '');

                                    $productObject->setNmfc(isset($this->headers[self::ATTRIBUTE_MAPPING['NMFC']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['NMFC']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['NMFC']]] : '');

                                    $productObject->setPackage_Height(isset($this->headers[self::ATTRIBUTE_MAPPING['PACKAGE_HEIGHT']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['PACKAGE_HEIGHT']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['PACKAGE_HEIGHT']]] : '');

                                    $productObject->setPackageWidth(isset($this->headers[self::ATTRIBUTE_MAPPING['PACKAGE_WIDTH']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['PACKAGE_WIDTH']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['PACKAGE_WIDTH']]] : '');

                                    $productObject->setPackageLength(isset($this->headers[self::ATTRIBUTE_MAPPING['PACKAGE_LENGTH']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['PACKAGE_LENGTH']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['PACKAGE_LENGTH']]] : '');

                                    $productObject->setPackageWeight(isset($this->headers[self::ATTRIBUTE_MAPPING['PACKAGE_WEIGHT']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['PACKAGE_WEIGHT']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['PACKAGE_WEIGHT']]] : '');

                                    $productObject->setCountryofOrigin(isset($this->headers[self::ATTRIBUTE_MAPPING['COUNTRY_OF_ORIGIN']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['COUNTRY_OF_ORIGIN']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['COUNTRY_OF_ORIGIN']]] : '');

                                    $productObject->setRegularPrice(isset($this->headers[self::ATTRIBUTE_MAPPING['REGULAR_PRICE']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['REGULAR_PRICE']]]) ? (float)$data[$this->headers[self::ATTRIBUTE_MAPPING['REGULAR_PRICE']]] : null);

                                    $productObject->setSalePrice(isset($this->headers[self::ATTRIBUTE_MAPPING['SALE_PRICE']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['SALE_PRICE']]]) ? (float)$data[$this->headers[self::ATTRIBUTE_MAPPING['SALE_PRICE']]] : null);

                                    $productObject->setNetPackQuantity(isset($this->headers[self::ATTRIBUTE_MAPPING['NET_PACK_QUANTITY']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['NET_PACK_QUANTITY']]]) ? (float)$data[$this->headers[self::ATTRIBUTE_MAPPING['NET_PACK_QUANTITY']]] : null);

                                    $productObject->setLeadTime(isset($this->headers[self::ATTRIBUTE_MAPPING['LEAD_TIME']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['LEAD_TIME']]]) ? (float)$data[$this->headers[self::ATTRIBUTE_MAPPING['LEAD_TIME']]] : null);

                                    $productObject->setMinOrderQuantity(isset($this->headers[self::ATTRIBUTE_MAPPING['MIN_ORDER_QUANTITY']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['MIN_ORDER_QUANTITY']]]) ? (float)$data[$this->headers[self::ATTRIBUTE_MAPPING['MIN_ORDER_QUANTITY']]] : null);

                                    $productObject->setNetPackQuantityUnits(isset($this->headers[self::ATTRIBUTE_MAPPING['NET_PACK_QUANTITY_UNITS']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['NET_PACK_QUANTITY_UNITS']]]) ? (float)$data[$this->headers[self::ATTRIBUTE_MAPPING['NET_PACK_QUANTITY_UNITS']]] : null);

                                    $productObject->setPartStatus(isset($this->headers[self::ATTRIBUTE_MAPPING['PART_STATUS']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['PART_STATUS']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['PART_STATUS']]] : '');

                                    $productObject->setStockstatus(isset($this->headers[self::ATTRIBUTE_MAPPING['STOCK_STATUS']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['STOCK_STATUS']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['STOCK_STATUS']]] : '');

                                    $productObject->setProposition65(isset($this->headers[self::ATTRIBUTE_MAPPING['PROPISITION']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['PROPISITION']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['PROPISITION']]] : '');

                                    $productObject->setKeywords(isset($this->headers[self::ATTRIBUTE_MAPPING['KEYWORDS']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['KEYWORDS']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['KEYWORDS']]] : '');

                                    $productObject->setMetaDescription(isset($this->headers[self::ATTRIBUTE_MAPPING['META_DESCRIPTION']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['META_DESCRIPTION']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['META_DESCRIPTION']]] : '');

                                    $productObject->setMetaTitle(isset($this->headers[self::ATTRIBUTE_MAPPING['META_TITLE']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['META_TITLE']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['META_TITLE']]] : '');

                                    $productObject->setStandards(isset($this->headers[self::ATTRIBUTE_MAPPING['STANDARDS']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['STANDARDS']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['STANDARDS']]] : '');

                                    $productObject->setCertifications(isset($this->headers[self::ATTRIBUTE_MAPPING['CERTIFICATIONS']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['CERTIFICATIONS']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['CERTIFICATIONS']]] : '');

                                    $productObject->setApprovals(isset($this->headers[self::ATTRIBUTE_MAPPING['APPROVALS']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['APPROVALS']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['APPROVALS']]] : '');

                                    $productObject->setWarranty(isset($this->headers[self::ATTRIBUTE_MAPPING['WARRANTY']]) && isset($this->headers[self::ATTRIBUTE_MAPPING['WARRANTY']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['WARRANTY']]]) ? $data[$this->headers[self::ATTRIBUTE_MAPPING['WARRANTY']]] : '');

                                    $productObject->setParent($this->getFolderPath($this->endNode));

                                    if (!empty($data[$this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_NAME']]])) {
                                        $productObject->setProductName($data[$this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_NAME']]]);
                                    } else {
                                        if (!empty($data[$this->headers[self::ATTRIBUTE_MAPPING['SHORT_DESCRIPTION']]])) {
                                            $productObject->setProductName($data[$this->headers[self::ATTRIBUTE_MAPPING['SHORT_DESCRIPTION']]]);
                                        }
                                    }

                                    $urlSlug = $productObject->getBrand() . " " . $productObject->getProductName();

                                    if (str_contains($productObject->getProductName(), $productObject->getBrand())) {
                                        $urlSlug = $productObject->getProductName();
                                    }

                                    $urlSlug = str_replace(' ', '-', $urlSlug);
                                    $urlSlug = preg_replace('/[^A-Za-z0-9\-]/', '', $urlSlug);
                                    $urlSlug = preg_replace('/-+/', '-', $urlSlug);

                                    $productObject->setUrlSlug($urlSlug);

                                    $taxonomyRelation = $this->getTaxonomy($this->endNode);

                                    if (!empty($taxonomyRelation)) {
                                        $productObject->setTaxonomy([$taxonomyRelation]);
                                    }

                                    if (isset($this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_VIDEO']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_VIDEO']]]) && !empty($data[$this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_VIDEO']]])) {
                                        $video = $this->assetFindOrCreate($data[$this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_VIDEO']]], 'Video', $this->vendorName . self::VIDEOS_FOLDER);
                                        if ($video) {
                                            $videoData = new \Pimcore\Model\DataObject\Data\Video();
                                            $videoData->setData($video);
                                            $videoData->setType("asset");
                                            $videoData->setTitle("My Title");
                                            $videoData->setDescription("My Description");
                                            $productObject->setProductVideo($videoData);
                                        }
                                    }

                                    //For PRODUCT_BROCHURE
                                    if (isset($this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_CATALOG']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_CATALOG']]]) && $data[$this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_CATALOG']]]) {
                                        $productCatalogUrlOrPath = $data[$this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_CATALOG']]];
                                        $productCatalogDoc = $this->assetFindOrCreate($productCatalogUrlOrPath, 'Document', $this->vendorName . self::DOCUMENTS_FOLDER);
                                        if (is_object($productCatalogDoc)) {
                                            $productObject->setProductCatalog($productCatalogDoc);
                                        }
                                    }

                                    //For PRODUCT_BROCHURE
                                    if (isset($this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_BROCHURE']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_BROCHURE']]]) && $data[$this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_BROCHURE']]]) {
                                        $productCatalogUrlOrPath = $data[$this->headers[self::ATTRIBUTE_MAPPING['PRODUCT_BROCHURE']]];
                                        $productCatalogDoc = $this->assetFindOrCreate($productCatalogUrlOrPath, 'Document', $this->vendorName . self::DOCUMENTS_FOLDER);
                                        if (is_object($productCatalogDoc)) {
                                            $productObject->setProductBrochure($productCatalogDoc);
                                        }
                                    }

                                    //For COMPATIBILITY_CHART
                                    if (isset($this->headers[self::ATTRIBUTE_MAPPING['COMPATIBILITY_CHART']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['COMPATIBILITY_CHART']]]) && $data[$this->headers[self::ATTRIBUTE_MAPPING['COMPATIBILITY_CHART']]]) {
                                        $productCatalogUrlOrPath = $data[$this->headers[self::ATTRIBUTE_MAPPING['COMPATIBILITY_CHART']]];
                                        $productCatalogDoc = $this->assetFindOrCreate($productCatalogUrlOrPath, 'Document', $this->vendorName . self::DOCUMENTS_FOLDER);
                                        if (is_object($productCatalogDoc)) {
                                            $productObject->setCompatibilityChart($productCatalogDoc);
                                        }
                                    }

                                    //For feature image
                                    if (isset($this->headers[self::ATTRIBUTE_MAPPING['FEATURED_IMAGE']]) && isset($data[$this->headers[self::ATTRIBUTE_MAPPING['FEATURED_IMAGE']]]) && $data[$this->headers[self::ATTRIBUTE_MAPPING['FEATURED_IMAGE']]]) {
                                        $featureImageurlOrPath = $data[$this->headers[self::ATTRIBUTE_MAPPING['FEATURED_IMAGE']]];
                                        $featureImage = $this->assetFindOrCreate($featureImageurlOrPath, 'Image', $this->vendorName . self::IMAGES_FOLDER);
                                        if (is_object($featureImage)) {
                                            $productObject->setFeaturedImage($featureImage);
                                        }
                                    }

                                    //For GALLERY_IMAGE
                                    $galleryImagesArray = $this->getImageArray($data, self::ATTRIBUTE_MAPPING['GALLERY_IMAGE']);
                                    $galleryImages = [];
                                    foreach ($galleryImagesArray as $galImages) {
                                        $image = $this->assetFindOrCreate($galImages, 'Image', $this->vendorName . self::IMAGES_FOLDER);
                                        if ($image) {
                                            $advancedImage = new \Pimcore\Model\DataObject\Data\Hotspotimage();
                                            $advancedImage->setImage($image);
                                            $galleryImages[] = $advancedImage;
                                        }
                                    }
                                    $productObject->setGalleryImage(new \Pimcore\Model\DataObject\Data\ImageGallery($galleryImages));

                                    // For ISOMETRIC_IMAGE
                                    $isometricImagesArray = $this->getImageArray($data, self::ATTRIBUTE_MAPPING['ISOMETRIC_IMAGE']);
                                    $isometricImages = [];
                                    foreach ($isometricImagesArray as $galImages) {
                                        $image = $this->assetFindOrCreate($galImages, 'Image', $this->vendorName . self::IMAGES_FOLDER);
                                        if ($image) {
                                            $advancedImage = new \Pimcore\Model\DataObject\Data\Hotspotimage();
                                            $advancedImage->setImage($image);
                                            $isometricImages[] = $advancedImage;
                                        }
                                    }
                                    $productObject->setIsometricImage(new \Pimcore\Model\DataObject\Data\ImageGallery($isometricImages));

                                    // For WARNING_IMAGE
                                    $warningImagesArray = $this->getImageArray($data, self::ATTRIBUTE_MAPPING['WARNING_IMAGE']);
                                    $warningImages = [];
                                    foreach ($warningImagesArray as $galImages) {
                                        $image = $this->assetFindOrCreate($galImages, 'Image', $this->vendorName . self::IMAGES_FOLDER);
                                        if ($image) {
                                            $advancedImage = new \Pimcore\Model\DataObject\Data\Hotspotimage();
                                            $advancedImage->setImage($image);
                                            $warningImages[] = $advancedImage;
                                        }
                                    }
                                    $productObject->setWarningImage(new \Pimcore\Model\DataObject\Data\ImageGallery($warningImages));

                                    //For CAD_MODEL
                                    $cadModelImagesArray = $this->getImageArray($data, self::ATTRIBUTE_MAPPING['CAD_MODEL']);
                                    $cadModelImages = [];
                                    foreach ($cadModelImagesArray as $galImages) {
                                        $image = $this->assetFindOrCreate($galImages, 'Image', $this->vendorName . self::IMAGES_FOLDER);
                                        if ($image) {
                                            $advancedImage = new \Pimcore\Model\DataObject\Data\Hotspotimage();
                                            $advancedImage->setImage($image);
                                            $cadModelImages[] = $advancedImage;
                                        }
                                    }

                                    $productObject->setCadModel(new \Pimcore\Model\DataObject\Data\ImageGallery($cadModelImages));

                                    // For DIMENSIONAL_IMAGE
                                    $dimensionalImagesArray = $this->getImageArray($data, self::ATTRIBUTE_MAPPING['DIMENSIONAL_IMAGE']);
                                    $dimensionalImages = [];
                                    foreach ($dimensionalImagesArray as $galImages) {
                                        $image = $this->assetFindOrCreate($galImages, 'Image', $this->vendorName . self::IMAGES_FOLDER);
                                        if ($image) {
                                            $advancedImage = new \Pimcore\Model\DataObject\Data\Hotspotimage();
                                            $advancedImage->setImage($image);
                                            $dimensionalImages[] = $advancedImage;
                                        }
                                    }

                                    $productObject->setDimensionalImage(new \Pimcore\Model\DataObject\Data\ImageGallery($dimensionalImages));

                                    if (!empty($this->activeGroup)) {
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
                                                if ($type != 'inputQuantityValue') {
                                                    $definition = $attributes[self::ATTRIBUTE_MAPPING['ATTRIBUTE_VALUE']];
                                                } else {
                                                    $definitionClassTypeName = '\\Pimcore\\Model\\DataObject\\Data\\' . 'InputQuantityValue';
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
                                    } else {
                                        $this->setLogger('warning', 'Taxonomy ' . $this->endNode . ' not found in Taxonomy Schema.');
                                    }

                                    if (self::IS_MIGRATION) {
                                        $fieldCollection = new \Pimcore\Model\DataObject\Fieldcollection();
                                        for ($i = 0 ; $i <1; $i++) {
                                            $item =  new DataObject\Fieldcollection\Data\Channels();
                                            $item->setChannel($productFileObj = \Pimcore\Model\DataObject\Channels::getById(self::CHANNEL_IS_MIGRATION));
                                            $fieldCollection->add($item);
                                        }
                                        $productObject->setChannelDetails($fieldCollection);
                                        $productObject->setPublished(true);

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
    protected function getFolderPath($folderName = '')
    {
        return \Pimcore\Model\DataObject\Service::createFolderByPath($this->vendorName);
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
        if ($level != 'info') {
            $this->logData[] = [
                'id' => $this->uniqueIdentifier,
                'level' => $level,
                'message' => $message
            ];
        }

        p_r($message);
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
        $this->activeGroup = !empty($config) ? $config->getId() : null;
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

    // Try to find an existing asset with the filename otherwise create asset
    protected function assetFindOrCreate($assetPathOrUrl, $assetType, $assetFolder)
    {
        if (strpos($assetPathOrUrl, 'http://') === 0 || strpos($assetPathOrUrl, 'https://') === 0) {
            $headers = @get_headers($assetPathOrUrl);

            // Extract the filename from the URL
            $filename = basename(parse_url($assetPathOrUrl, PHP_URL_PATH));
            // Try to find an existing asset with the filename
            $asset = $this->getAsset($assetFolder . $filename);

            if (is_object($asset)) {
                return $asset;
            } elseif ($headers && strpos($headers[0], '200')) {
                $contents = file_get_contents($assetPathOrUrl);
                $fqcn = "\\Pimcore\\Model\\Asset\\{$assetType}";
                $imageAsset = new $fqcn();
                $imageAsset->setFilename($filename);
                $imageAsset->setData($contents);
                $imageAsset->setParent(\Pimcore\Model\Asset\Service::createFolderByPath($assetFolder));
                $imageAsset->save();
                return $imageAsset;
            }
        } else {
            return $this->getAsset($assetFolder . $assetPathOrUrl);
        }
        return null;
    }

    //For getting asset according to ImagePath
    protected function getAsset($filePath)
    {
        return Asset::getByPath($filePath);
    }

    protected function createLogFile($fileName)
    {

        $fileName = preg_replace('/\.\w+$/', '', $fileName) . '_' . date('YmdHis') . '_log.csv';

        $importFile = PIMCORE_WEB_ROOT . '/var/assets/' . $fileName;

        $fp = fopen($importFile, 'a+');
        fputcsv($fp, ['id', 'level', 'message'], ',');
        foreach ($this->logData as $line) {
            fputcsv($fp, array_values($line), ',');
        }
        fclose($fp);

        $contents = file_get_contents($importFile);
        unlink($importFile);
        $fqcn = "\\Pimcore\\Model\\Asset\\Document";
        $imageAsset = new $fqcn();
        $imageAsset->setFilename($fileName);
        $imageAsset->setData($contents);
        $imageAsset->setParent(\Pimcore\Model\Asset\Service::createFolderByPath($this->vendorName . self::LOGS_FOLDER));
        $imageAsset->save();
    }

    //For getting vendor import file from respective vendor OutputFolder folder
    protected function getVendorImportFile()
    {
        $data = [];
        // Get the home directory
        $homeDirectory = Asset::getByPath('/');
        if ($homeDirectory instanceof Asset\Folder && $homeDirectory->hasChildren()) {
            $assets = $homeDirectory->getChildren();
            foreach ($assets as $asset) {
                if ($asset instanceof Asset\Folder && $asset->hasChildren()) {
                    foreach ($asset->getChildren() as $assetChild) {
                        if ($assetChild instanceof Asset\Folder && $assetChild->hasChildren() && $assetChild->getFilename() === 'OutputFolder') {
                            foreach ($assetChild->getChildren() as $assetFile) {
                                $data['vendorFolderName'] = $asset->getFilename();
                                $data['vendorImportFileId'] = $assetFile->getId();
                                return $data;
                            }
                        }
                    }
                }
            }
        }
        return $data;
    }
}

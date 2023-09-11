<?php

namespace App\EventListener;

use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Event\Model\DocumentEvent;
use Pimcore\Model\DataObject;
use Symfony\Component\Process\Process;


class ProductPreUpdateListener
{

    const WORKFLOW_STATE_TO_MASTER_CATALOG = 'SentForDataPublish';

    const OBJECT_FOLDER_NAME = 'MasterCatalog';

    public $consumerKey = '';

    public $consumerSecret = '';

    public $url = '';

    public $isMappingAvailable = false;

    public $mapping = [];

    public function onProductPreUpdate(ElementEventInterface $e): void
    {
        if ($e instanceof DataObjectEvent) {
            $object = $e->getObject();
            if ($object instanceof DataObject\Product && empty($object->getSsin())) {
                $ssin = strtoupper(substr(md5(microtime()), 0, 8));
                $object->setSsin($ssin);
                $object->setKey($ssin);
            }

            if ($object instanceof DataObject\Product && $object->getWorkflowState() == self::WORKFLOW_STATE_TO_MASTER_CATALOG
                && !str_contains($object->getCurrentFullPath(), self::WORKFLOW_STATE_TO_MASTER_CATALOG)
            ) {
                $object->setParent($this->getFolderPath());
            }

            if ($object instanceof DataObject\Product && $object->getPublished() == true) {
                foreach ($object->getChannelDetails()->getItems() as $channel) {
                    if (!empty($channel->getChannel())) {
                        $channelObj = DataObject\Channels::getById($channel->getChannel()->getId());
                        $this->url = (string) trim($channelObj->getUrl());
                        $this->consumerKey = (string) trim($channelObj->getConsumerKey());
                        $this->consumerSecret = (string) trim($channelObj->getConsumerSecret());
                        $price = $channel->getSalesPrice();
                        $stock = $channel->getStockQuantity();

                        $stockStatus = 'instock';

                        if (empty($stock) && $stock <= 0) {
                            $stockStatus = 'outofstock';
                        }

                        $productId = $channel->getProductId();


                        $taxonomyPathArray = explode("/", $object->getTaxonomy()[0]->getPath());

                        if (!empty($channel->getChannelTaxonomy())) {
                            $taxonomyPathArray = explode("/", $channel->getChannelTaxonomy()->getPath());
                        }


                        $categoriesArray = array_slice(array_filter($taxonomyPathArray), 2);

                        if (!empty($channel->getChannelTaxonomy())) {
                            $taxonomyID = $channel->getChannelTaxonomy()->getId();
                            $taxonomyName = $channel->getChannelTaxonomy()->getName();
                        } else {
                            $taxonomyID = $object->getTaxonomy()[0]->getId();
                            $taxonomyName = $object->getTaxonomy()[0]->getName();
                        }

                        array_push($categoriesArray, $taxonomyName);
                        $parentId = 0;

                        foreach ($categoriesArray as $value) {

                            $value1 = str_replace(' ', '-', $value);
                            $value1 = strtolower(preg_replace('/[^A-Za-z0-9\-]/', '', $value1));
                            $slug = preg_replace('/-+/', '-', $value1);
                            $response = json_decode($this->getCategory($slug));
                            if (empty($response)) {
                                $parentId = $this->createCategory($value, $parentId);
                            } else {
                                $parentId = $response[0]->id;
                            }
                        }

                        if (!empty($taxonomyName)) {
                            $mapper  = $this->getMapper($taxonomyID, $channelObj->getId());
                            if (!empty($mapper)) {
                                $this->isMappingAvailable = true;
                                $mappingJson = $mapper->getMappingValue();
                                $this->mapping  = json_decode($mappingJson,1 );
                            }
                        }

                        $attributesArray = [
                            [
                                'name' => 'SSIN (Unique ID)',
                                'position' => 1,
                                'visible' => false,
                                'variation' => false,
                                'options' => [
                                    (string) $object->getSsin()
                                ]
                            ],
                            [
                                'name' => 'Brand',
                                'position' => 2,
                                'visible' => !empty($object->getBrand()) ? true : false,
                                'variation' => false,
                                'options' => [
                                    (string) $object->getBrand()
                                ]
                            ],
                            [
                                'name' => 'Manufacturer Part Number',
                                'position' => 3,
                                'visible' => !empty($object->getManufacturerPartNumber()) ? true : false,
                                'variation' => false,
                                'options' => [
                                    (string) $object->getManufacturerPartNumber()
                                ]
                            ],
                            [
                                'name' => 'Standards',
                                'position' => 4,
                                'visible' => !empty($object->getStandards()) ? true : false,
                                'variation' => false,
                                'options' => [
                                    (string) $object->getStandards()
                                ]
                            ],
                            [
                                'name' => 'UNSPSC',
                                'position' => 5,
                                'visible' => !empty($object->getUnspsc()) ? true : false,
                                'variation' => false,
                                'options' => [
                                    (string) $object->getUnspsc()
                                ]
                            ],
                            [
                                'name' => 'Country of Origin',
                                'position' => 6,
                                'visible' => !empty($object->getCountryofOrigin()) ? true : false,
                                'variation' => false,
                                'options' => [
                                    (string) $object->getCountryofOrigin()
                                ]
                            ],
                            [
                                'name' => 'Product Type',
                                'position' => 7,
                                'visible' => !empty($object->getProductType()) ? true : false,
                                'variation' => false,
                                'options' => [
                                    (string) $object->getProductType()
                                ]
                            ],
                            [
                                'name' => 'Series',
                                'position' => 8,
                                'visible' => !empty($object->getSeries()) ? true : false,
                                'variation' => false,
                                'options' => [
                                    (string) $object->getSeries()
                                ]
                            ],
                            [
                                'name' => 'Applications',
                                'position' => 9,
                                'visible' => !empty($object->getApplications()) ? true : false,
                                'variation' => false,
                                'options' => [
                                    (string) $object->getApplications()
                                ]
                            ],

                        ];
                        $count = 10;
                        foreach ($object->getAttribute()->getItems() as $groupId => $group) {
                            foreach ($group as $keyId => $key) {

                                if ($object->getAttribute()->getLocalizedKeyValue($groupId, $keyId) instanceof \Pimcore\Model\DataObject\Data\QuantityValue) {
                                    $value = (string)$object->getAttribute()->getLocalizedKeyValue($groupId, $keyId)->getValue();
                                } else {
                                    $value = (string)$object->getAttribute()->getLocalizedKeyValue($groupId, $keyId);
                                }
                                $attribute = [
                                    'name' => \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getById($keyId)->getName(),
                                    'position' => $count,
                                    'visible' => !empty($value) ? true : false,
                                    'variation' => false,
                                    'options' => [
                                        $value
                                    ]
                                ];
                                $count++;
                                $attributesArray[] = $attribute;
                            }
                        }

                        if ($this->isMappingAvailable && !empty($this->mapping)) {
                            foreach($attributesArray as $key =>  $attribute) {
                                if (!in_array($attribute['name'], $this->mapping['classAttribute'])
                                && !in_array($attribute['name'], $this->mapping['taxonomyAttribute'])) {
                                    unset($attributesArray[$key]);
                                }
                            }
                        }

                        $metaData = [
                            [
                                "key" => '_yoast_wpseo_focuskw',
                                "value" => $object->getKeywords()
                            ],
                            [
                                "key" => '_yoast_wpseo_title',
                                "value" => $object->getMetaTitle()
                            ],
                            [
                                "key" => '_yoast_wpseo_metadesc',
                                "value" => $object->getMetaDescription()
                            ]
                        ];

                        $featuredImages = $object->getFeaturedImage();
                        $images = [];
                        if (!empty($featuredImages)) {
                            $imageUrl = strtolower(substr($_SERVER["SERVER_PROTOCOL"], 0, 5)) == 'https' ? 'https' : 'http' . "://" . $_SERVER['SERVER_NAME'] . $featuredImages->getFullPath();
                            array_push($images, ['src' => $imageUrl]);
                        }

                        $productGallery = $object->getGalleryImage();
                        foreach ($productGallery as $galImages) {
                            $imageUrl = strtolower(substr($_SERVER["SERVER_PROTOCOL"], 0, 5)) == 'https' ? 'https' : 'http' . "://" . $_SERVER['SERVER_NAME'] . $galImages->getImage()->getFullPath();
                            array_push($images, ['src' => $imageUrl]);
                        }

                        $data = [
                            'name' => $object->getShortDescriptions(),
                            'regular_price' => (string)$object->getRegularPrice(),
                            'sale_price' => (string)$price,
                            'description' => $object->getLongDescriptions() . "\n" . $object->getFeaturesAndBenefit(),
                            'stock_status' => (string)$stockStatus,
                            'weight' => $object->getPackageWeight(),
                            'slug' => $object->getUrlSlug(),
                            'dimensions' => [
                                'length' => (string)$object->getPackageLength(),
                                'width' => (string)$object->getPackageWidth(),
                                'height' => (string)$object->getPackage_Height()
                            ],
                            "categories" => [
                                [
                                    "id" => $parentId
                                ]
                            ],
                            "meta_data" => $metaData,
                            "attributes" => $attributesArray
                        ];

                        if (!empty($images)) {
                            $data['images'] = $images;
                        }

                        if (!empty($productId)) {
                            $curl = curl_init();
                            $oauth = new \OAuth($this->consumerKey, $this->consumerSecret);

                            $url = $this->url . '/wp-json/wc/v3/products/' . $productId;
                            $nonce = mt_rand();
                            $timestamp = time();

                            $oauth->setTimestamp($timestamp);
                            $oauth->setNonce($nonce);

                            $sig = $oauth->generateSignature('PUT', $url);

                            $header = array
                            (
                                'Content-Type: application/json',
                                'Connection: keep-alive',
                                'Keep-Alive: 800000',
                                'Expect:'
                            );
                            $header[] = 'Authorization: OAuth ' .
                                'oauth_consumer_key="' . $this->consumerKey . '"' .
                                ',oauth_signature_method="HMAC-SHA1"' .
                                ',oauth_nonce="' . $nonce . '"' .
                                ',oauth_timestamp="' . $timestamp . '"' .
                                ',oauth_version="1.0"' .
                                ',oauth_signature="' . $sig . '"';

                            curl_setopt_array($curl, array(
                                CURLOPT_URL => $this->url . '/wp-json/wc/v3/products/' . $productId,
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_ENCODING => '',
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 0,
                                CURLOPT_FOLLOWLOCATION => true,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => 'PUT',
                                CURLOPT_POSTFIELDS => json_encode($data),
                                CURLOPT_HTTPHEADER => $header,
                            ));

                            $response = curl_exec($curl);
                            curl_close($curl);
                        } else {
                            $curl = curl_init();
                            $oauth = new \OAuth($this->consumerKey, $this->consumerSecret);

                            $url = $this->url . '/wp-json/wc/v3/products';
                            $nonce = mt_rand();
                            $timestamp = time();

                            $oauth->setTimestamp($timestamp);
                            $oauth->setNonce($nonce);

                            $sig = $oauth->generateSignature('POST', $url);

                            $header = array
                            (
                                'Content-Type: application/json',
                                'Connection: keep-alive',
                                'Keep-Alive: 800000',
                                'Expect:'
                            );
                            $header[] = 'Authorization: OAuth ' .
                                'oauth_consumer_key="' . $this->consumerKey . '"' .
                                ',oauth_signature_method="HMAC-SHA1"' .
                                ',oauth_nonce="' . $nonce . '"' .
                                ',oauth_timestamp="' . $timestamp . '"' .
                                ',oauth_version="1.0"' .
                                ',oauth_signature="' . $sig . '"';

                            curl_setopt_array($curl, array(
                                CURLOPT_URL => $this->url . '/wp-json/wc/v3/products',
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_ENCODING => '',
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 0,
                                CURLOPT_FOLLOWLOCATION => true,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => 'POST',
                                CURLOPT_POSTFIELDS => json_encode($data),
                                CURLOPT_HTTPHEADER => $header,
                            ));

                            $response = curl_exec($curl);
                            $response = json_decode($response);
                            if (!empty($response)) {
                                $channel->setProductId($response->id);
                            }

                            curl_close($curl);
                        }

                    }


                }
            }

        }
    }

    protected function getFolderPath()
    {
        return \Pimcore\Model\DataObject\Service::createFolderByPath(self::OBJECT_FOLDER_NAME);
    }

    protected function getCategory($slug)
    {
        $curl = curl_init();
        $oauth = new \OAuth($this->consumerKey, $this->consumerSecret);

        $url = $this->url . '/wp-json/wc/v3/products/categories?slug=' . $slug;
        $nonce = mt_rand();
        $timestamp = time();

        $oauth->setTimestamp($timestamp);
        $oauth->setNonce($nonce);
        $sig = $oauth->generateSignature('GET', $url);

        $header = array
        (
            'Content-Type: application/json',
            'Connection: keep-alive',
            'Keep-Alive: 800000',
            'Expect:'
        );
        $header[] = 'Authorization: OAuth ' .
            'oauth_consumer_key="' . $this->consumerKey . '"' .
            ',oauth_signature_method="HMAC-SHA1"' .
            ',oauth_nonce="' . $nonce . '"' .
            ',oauth_timestamp="' . $timestamp . '"' .
            ',oauth_version="1.0"' .
            ',oauth_signature="' . $sig . '"';

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $header,
        ));

        $response = curl_exec($curl);
        return $response;
    }

    protected function createCategory($name, $parentId)
    {
        $curl = curl_init();
        $oauth = new \OAuth($this->consumerKey, $this->consumerSecret);

        $url = $this->url . '/wp-json/wc/v3/products/categories';
        $nonce = mt_rand();
        $timestamp = time();

        $oauth->setTimestamp($timestamp);
        $oauth->setNonce($nonce);

        $sig = $oauth->generateSignature('POST', $url);

        $header = array
        (
            'Content-Type: application/json',
            'Connection: keep-alive',
            'Keep-Alive: 800000',
            'Expect:'
        );
        $header[] = 'Authorization: OAuth ' .
            'oauth_consumer_key="' . $this->consumerKey . '"' .
            ',oauth_signature_method="HMAC-SHA1"' .
            ',oauth_nonce="' . $nonce . '"' .
            ',oauth_timestamp="' . $timestamp . '"' .
            ',oauth_version="1.0"' .
            ',oauth_signature="' . $sig . '"';

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_POSTFIELDS => json_encode([
                'name' => $name,
                'parent' => $parentId
            ]),
        ));

        $response = curl_exec($curl);
        $response = json_decode($response);
        if (!empty($response)) {
            return $response->id;
        }
        return 0;
    }


    protected function getMapper($taxonomyName, $channelName) {
        $listing = new DataObject\TaxonomyChannelMapper\Listing();
        $listing->filterByChannelName(['id' => $channelName, 'type' => 'object']);
        $listing->filterByTaxonomyName(['id' => $taxonomyName, 'type' => 'object']);
        $listing->setUnpublished(true);
        $listing->setLimit(1);
        $mapperObj = $listing->load();
        return (!empty($mapperObj)) ? $mapperObj[0] : null;


    }
}
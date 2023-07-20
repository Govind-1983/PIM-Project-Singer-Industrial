<?php

namespace App\EventListener;

use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Event\Model\DocumentEvent;
use Pimcore\Model\DataObject;
use Symfony\Component\Process\Process;



class ProductPublishedListener
{

    public $consumerKey = 'ck_5dde4dfcfc20a7cc9acfd443dfa0f28bd7041c20';

    public $consumerSecret = 'cs_f5bdfb116b07071e177c80532274ff2d322d4275';

    public function onProductPostUpdate(ElementEventInterface $e): void
    {
        if ($e instanceof DataObjectEvent) {
            $object  = $e->getObject();
            if ($object instanceof DataObject\Product && $object->getPublished() == true) {
                $data = [
                    'name' => $object->getShortDescriptions(),
                    'regular_price' => (string) $object->getRegularPrice(),
                    'sale_price' => (string) $object->getSalePrice(),
                    'description' => $object->getLongDescriptions() . "\n" . $object->getFeaturesAndBenefit(),
                    'stock_status' => $object->getStockstatus(),
                    'weight' => $object->getPackageWeight(),
                    'dimensions' => [
                        'length' => $object->getPackageLength(),
                        'width' => $object->getPackageWidth(),
                        'height' => $object->getPackage_Height()
                    ],
                    'images' => [
                        [
                            'src' => 'http://www.altiusnxt.local.com/Continental/Images/SSIN001/ssin01_chemicalhoses_3d_.jpg'
                        ],
                    ]
                ];


                $curl = curl_init();
                $oauth = new \OAuth($this->consumerKey, $this->consumerSecret);

                p_r($oauth);
                die;
                $url = 'http://woocomrz.altiussolution.com/index.php/wp-json/wc/v3/products';
                $nonce = mt_rand();
                $timestamp = time();

                $oauth->setTimestamp($timestamp);
                $oauth->setNonce($nonce);

                $sig = $oauth->generateSignature('POST', $url);

                $header = array(
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
                    CURLOPT_URL => 'http://woocomrz.altiussolution.com/index.php/wp-json/wc/v3/products',
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

                print_r($response);

                curl_close($curl);
            }
        }
    }
}

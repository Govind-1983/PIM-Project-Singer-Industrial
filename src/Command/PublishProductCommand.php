<?php

namespace App\Command;

use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Model\DataObject;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use \Pimcore\Model\WebsiteSetting;


class PublishProductCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('product:data:publish')
            ->setDescription('Using this command you can publish  the imported data.')
            ->addArgument('offset', InputArgument::OPTIONAL, 'Set offset  ')
            ->addArgument('limit', InputArgument::OPTIONAL, 'Set limit ');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        // $offset =  $input->getArgument('offset') ?? 0;
        // $limit =  $input->getArgument('limit') ?? 0;
        // $list = new DataObject\Product\Listing();
        // $list->setUnPublished(true);
        // $list->setCondition("published=0");

        // $list->setOffset($offset);
        // $list->setLimit($limit);
        // $a = $list->loadIdList();

        // foreach ($list as $key => $product) {
        //     try {
        //         p_r("Index " . $key . " - Product ID - " . $product->getId());
        //         $product->setPublished(true);
        //         $product->save();
        //     } catch (\Exception $e) {
        //         p_r($e->getMessage());
        //     }
        // }

        $data = [
            50273, 50274, 50275, 50276, 50277, 50278, 50279, 50280, 50281, 50282,
            50283, 50284, 50285, 50287, 50288, 50289, 50290, 50291, 50292, 50294,
            50295, 50296, 50297, 50298, 50299, 50300, 50301, 50302, 50305, 50306,
            50307, 50308, 50309, 50310, 50311, 50312, 50313, 50316, 50317, 50318,
            50319, 50320, 50321, 50322, 53433, 53775, 55614, 56551, 62524, 64939,
            67649, 73045, 77063, 106730, 106778, 106827, 106837, 106842, 107077,
            107179, 107383, 107440, 107450, 107485, 107671, 107680, 107707, 107727,
            107798, 107805, 107883, 107942, 108439, 108444, 108445, 108468, 108481,
            108522, 108608, 108927, 108940, 108942, 109212, 109222, 109224, 109230,
            110775, 110853, 110861, 110862, 110867, 110884, 110900, 110908, 110964,
            110970, 111078, 111171, 111196, 111198, 111208, 111275, 111447, 111459,
            111528, 111616, 111624, 111644, 111667, 111736, 111808, 111861, 111872,
            112078, 112307, 112308, 112336, 112343, 112346, 112362, 112443, 112465,
            112467, 112485, 112773, 113043, 113202, 113266, 113298, 113592, 114738,
            114739, 114839, 114952, 115065, 115077, 115152, 115197, 115470, 115493,
            115560, 115574, 115750, 115897, 116118, 116122, 116123, 116149, 116155,
            116157, 116158, 116174, 116195, 116253, 116264, 116273, 116279, 116401,
            116695, 116709, 116739, 127248, 127318, 127382, 127580, 127896, 128004,
            128096, 128236, 128288, 128408, 128450, 128797, 128815, 128923, 129159,
            131368, 131444, 131457, 131458, 131459, 131505, 131533, 131637, 132317,
            132507, 132762, 132831, 132843, 132940, 132951, 133401, 135393, 135394,
            135395, 135450, 135460, 135551, 135900, 136226, 136285, 136304, 136404,
            136525, 136553, 136677, 136694, 136702, 136861, 136954, 138787, 139018,
            139071, 139148, 139149, 139190, 139203, 139213, 139303, 139312, 139453,
            139565, 139574, 139692, 139894, 139962, 140104, 140508, 140513, 140515,
            140527, 140589, 140593, 140701, 140796, 141024, 141032, 141251, 141967,
            142295, 142356, 142426, 142433, 142456, 142736, 142760, 142841, 143103,
            143216, 143446, 143860, 143861, 143897, 143908, 143931, 144073, 144535,
            144547, 144590, 144825, 145318, 145998, 146180, 146554, 146596, 146640,
            146666, 146678, 146679, 146719, 146792, 147035, 147063, 147175, 147307,
            147349, 147493, 147545, 147569, 147671, 147693, 148177, 148379, 148510,
            148684, 149002, 149181, 149399, 149406, 149899, 149906, 149930, 150133,
            150135, 150143, 150401, 151183, 151211
        ];

        foreach ($data as $key => $productId) {

            try {
                $productObj = DataObject\Product::getById($productId);
                p_r("Index " . $key . " - Product ID - " . $productObj->getId());
                $productObj->setPublished(true);
                $productObj->save();
            } catch (\Exception $e) {
                p_r($e->getMessage());
            }
        }
        return 1;
    }
}

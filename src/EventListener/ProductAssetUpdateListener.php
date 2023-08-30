<?php

namespace App\EventListener;

use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Event\Model\DocumentEvent;
use Symfony\Component\Process\Process;


class ProductAssetUpdateListener
{
    const FOLDER_CHECK = "OutputFolder";
    const EXTENSION = ".xlsx";

    const MASTER_FOLDER_TAXONOMY_SCHEMAS = "TaxonomySchemas";
    const MASTER_FOLDER_TAXONOMY = "Taxonomies";

    public function onAssetPostUpdate(ElementEventInterface $e): void
    {
        if ($e instanceof AssetEvent) {
            $asset  = $e->getAsset();
            $php = \Pimcore\Tool\Console::getExecutable('php');
            $fullPath = $asset->getFullPath();
//            if (str_contains($fullPath, self::FOLDER_CHECK) && str_contains($fullPath, self::EXTENSION) && !empty($asset->getId())) {
//                $assetId = $asset->getId();
//                $cmd = $php . ' ' . PIMCORE_PROJECT_ROOT . '/bin/console product-data:import ' . $assetId;
//                $process = Process::fromShellCommandline($cmd);
//                $process->run(null, []);
//            }

            if (str_contains($fullPath, self::MASTER_FOLDER_TAXONOMY_SCHEMAS) && str_contains($fullPath, self::EXTENSION) && !empty($asset->getId())) {
                $db = \Pimcore\Db::get();
                $assetId = $asset->getId();
                $sql = "SELECT `place` FROM `element_workflow_state` WHERE cid = " . $assetId;
                $fieldsArray = $db->fetchOne($sql);
                if ($fieldsArray == 'TaxonomyReviewCompleted') {
                    $cmd = $php . ' ' . PIMCORE_PROJECT_ROOT . '/bin/console taxonomy-attributes:import ' . $assetId;
                    $process = Process::fromShellCommandline($cmd);
                    $process->run(null, []);
                }
            }

            if (str_contains($fullPath, self::MASTER_FOLDER_TAXONOMY) && str_contains($fullPath, self::EXTENSION) && !empty($asset->getId())) {
                $db = \Pimcore\Db::get();
                $assetId = $asset->getId();
                $sql = "SELECT `place` FROM `element_workflow_state` WHERE cid = " . $assetId;
                $fieldsArray = $db->fetchOne($sql);
                if ($fieldsArray == 'TaxonomyReviewCompleted') {
                    $cmd = $php . ' ' . PIMCORE_PROJECT_ROOT . '/bin/console Import:Taxonomy:import ' . $assetId;
                    $process = Process::fromShellCommandline($cmd);
                    $process->run(null, []);
                }
            }
        }
    }
}

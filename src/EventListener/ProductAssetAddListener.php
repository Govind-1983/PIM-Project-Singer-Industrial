<?php

namespace App\EventListener;

use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Event\Model\DocumentEvent;
use Symfony\Component\Process\Process;


class ProductAssetAddListener
{
    const FOLDER_CHECK = "OutputFolder";
    const EXTENSION = ".xlsx";

    public function onAssetPostAdd(ElementEventInterface $e): void
    {
//        if ($e instanceof AssetEvent) {
//            $asset  = $e->getAsset();
//            $php = \Pimcore\Tool\Console::getExecutable('php');
//            $fullPath = $asset->getFullPath();
//            if (str_contains($fullPath, self::FOLDER_CHECK) && str_contains($fullPath, self::EXTENSION) && !empty($asset->getId())) {
//                $assetId = $asset->getId();
//                $cmd = $php . ' ' . PIMCORE_PROJECT_ROOT . '/bin/console product-data:import '. $assetId;
//                $process = Process::fromShellCommandline($cmd);
//                $process->run(null, []);
//            }
//
//        }
    }
}
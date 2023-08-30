<?php
namespace App\Workflow;

use Symfony\Component\Workflow\SupportStrategy\WorkflowSupportStrategyInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class SupportStrategy implements WorkflowSupportStrategyInterface
{
    public function supports(WorkflowInterface $workflow, object $subject): bool
    {
        if ($subject instanceof \Pimcore\Model\Asset && ($subject->getPath() == '/MasterDataFolder/Taxonomies/' || $subject->getPath() == '/MasterDataFolder/TaxonomySchemas/')) {
            return true;
        }

        return false;
    }
}
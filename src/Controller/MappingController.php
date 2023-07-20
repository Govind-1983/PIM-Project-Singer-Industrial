<?php

namespace App\Controller;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Model\DataObject\Classificationstore\Group;
use Pimcore\Model\Classificationstore\Group\Listing;


class MappingController extends FrontendController
{
    /**
     * @Route("/mapping")
     * @param Request $request
     * @return Response
     */
    public function mappingConfigure(Request $request)
    {
        //$classsDefinition = \Pimcore\Model\DataObject\ClassDefinition::getByName('Product');

        $attributeList = self::getAttributeList('Product');
        // p_r($request->get('mappingAttribute'));
        //self::getListOfGroups();

        return $this->render('mappingPreview.html.twig', [
            'attributeList' => $attributeList
        ]);
    }



    protected function getAttributeList($className)
    {
        //$className =  $request->get('className') ? $request->get('className') : '';

        $attributeList = [];

        if ($className) {
            $classsDefinition = \Pimcore\Model\DataObject\ClassDefinition::getByName($className);
            $fieldDefinition = $classsDefinition->getFieldDefinitions();

            $localizedfields = [];
            foreach ($fieldDefinition as $key => $field) {
                if ($key != 'localizedfields') {
                    if ($field->getFieldtype() === 'calculatedValue' || $field->getFieldtype() === 'video') {
                        $attributeList[] = $key;
                    } else {
                        $attributeList[] = $key;
                    }
                } else {
                    /** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields $field */
                    foreach ($field->getChildren() as $value) {
                        $localizedfields[] = $value->getName();
                    }

                    foreach ($field->getReferencedFields() as $referencedField) {
                        /** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields $referencedField */
                        foreach ($referencedField->getChildren() as $value) {
                            if ($value->getFieldtype() === 'calculatedValue') {
                            } else {
                                $localizedfields[] = $value->getName();
                            }
                        }
                    }
                }
            }

            $attributeList = array_merge($attributeList, $localizedfields);
        }

        //sort($attributeList);
        return $attributeList;
    }
}

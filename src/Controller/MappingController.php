<?php

namespace App\Controller;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Symfony\Component\HttpFoundation\JsonResponse;
use Pimcore\Model\DataObject;

class MappingController extends FrontendController
{

    const CLASS_NAME = "Product";

    /**
     * @Route("/mapping")
     * @param Request $request
     * @return Response
     */
    public function mappingConfigure(Request $request)
    {

        //Attribute list of product class
        $attributeList = self::getAttributeList(self::CLASS_NAME);

        $taxonomyGroupId = $request->get('taxonomy') ? $request->get('taxonomy') : '';
        $mappingAttribute = $request->get('mappingAttribute') ? $request->get('mappingAttribute') : [];
        $selectedAttributes = $request->get('selectedAttributes') ? $request->get('selectedAttributes') : [];

        $mappingAttributeValues = array_values($mappingAttribute);
        $selectedAttributeValues = array_values($selectedAttributes);

        // Create the final array structure with values
        $data = [
            "taxonomyId" => $taxonomyGroupId,
            "classAttribute" =>  $mappingAttributeValues,
            "taxonomyAttribute" => $selectedAttributeValues
        ];

        // Encode the final array into JSON format
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
        $array = json_decode($request->get('context'), true);

        $id = isset($array['objectId']) ? $array['objectId'] : $request->get('objectId');

        $object = DataObject::getById($id);
        if ($mappingAttributeValues || $selectedAttributeValues && $object) {
            $object->setMappingValue($jsonData);
            $object->save();
        }
        $mappingJson = $object->getMappingValue();

        return $this->render('mappingPreview.html.twig', [
            'attributeList' => $attributeList,
            'objectId' => $id,
            'mapping'  => $mappingJson ? json_decode($mappingJson) : [
                "taxonomyId" => '',
                "classAttribute" =>  [],
                "taxonomyAttribute" => []
            ]
        ]);
    }

    protected function getAttributeList($className)
    {
        $attributeList = [];
        if ($className) {
            $classsDefinition = \Pimcore\Model\DataObject\ClassDefinition::getByName($className);
            $fieldDefinition = $classsDefinition->getFieldDefinitions();

            foreach ($fieldDefinition as $key => $field) {
                if ($key !== 'channels' && $key !== 'workflowState' && $key !== 'Attribute') {
                    $attributeList['field'][] = [$key, $field->getTitle()];
                }
            }
        }

        $groupList = new Classificationstore\GroupConfig\Listing();
        $groups = $groupList->load();
        foreach ($groups as $group) {
            $attributeList['attribute'][] = [$group->getId(), $group->getName()];
        }

        return $attributeList;
    }

    /**
     * @Route("/attribute/list")
     *
     */
    public function getClassificationAttributeList(Request $request)
    {
        $groupId =  $request->get('attributeName') ? $request->get('attributeName') : '';
        $group = GroupConfig::getById($groupId);
        $keys = [];
        foreach ($group->getRelations() as $key) {
            array_push($keys, json_decode($key->getDefinition(), true)['name']);
        }
        return new JsonResponse($keys);
    }
}

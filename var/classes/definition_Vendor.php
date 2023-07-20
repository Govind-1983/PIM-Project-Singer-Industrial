<?php

/**
 * Inheritance: no
 * Variants: no
 *
 * Fields Summary:
 * - firstname [firstname]
 * - lastname [lastname]
 * - gender [select]
 * - email [email]
 * - phoneNumber [numeric]
 * - gstnumber [input]
 * - address [textarea]
 * - username [input]
 * - password [password]
 */

return Pimcore\Model\DataObject\ClassDefinition::__set_state(array(
   'dao' => NULL,
   'id' => 'vendor',
   'name' => 'Vendor',
   'title' => '',
   'description' => '',
   'creationDate' => NULL,
   'modificationDate' => 1687525446,
   'userOwner' => 2,
   'userModification' => 2,
   'parentClass' => '',
   'implementsInterfaces' => '',
   'listingParentClass' => '',
   'useTraits' => '',
   'listingUseTraits' => '',
   'encryption' => false,
   'encryptedTables' => 
  array (
  ),
   'allowInherit' => false,
   'allowVariants' => false,
   'showVariants' => false,
   'layoutDefinitions' => 
  Pimcore\Model\DataObject\ClassDefinition\Layout\Panel::__set_state(array(
     'name' => 'pimcore_root',
     'type' => NULL,
     'region' => NULL,
     'title' => NULL,
     'width' => 0,
     'height' => 0,
     'collapsible' => false,
     'collapsed' => false,
     'bodyStyle' => NULL,
     'datatype' => 'layout',
     'children' => 
    array (
      0 => 
      Pimcore\Model\DataObject\ClassDefinition\Layout\Tabpanel::__set_state(array(
         'name' => 'Layout',
         'type' => NULL,
         'region' => NULL,
         'title' => '',
         'width' => '',
         'height' => '',
         'collapsible' => false,
         'collapsed' => false,
         'bodyStyle' => '',
         'datatype' => 'layout',
         'children' => 
        array (
          0 => 
          Pimcore\Model\DataObject\ClassDefinition\Layout\Panel::__set_state(array(
             'name' => 'Basic info',
             'type' => NULL,
             'region' => NULL,
             'title' => 'Basic info',
             'width' => '',
             'height' => '',
             'collapsible' => false,
             'collapsed' => false,
             'bodyStyle' => '',
             'datatype' => 'layout',
             'children' => 
            array (
              0 => 
              Pimcore\Model\DataObject\ClassDefinition\Layout\Fieldset::__set_state(array(
                 'name' => 'Layout',
                 'type' => NULL,
                 'region' => NULL,
                 'title' => 'Basic Details',
                 'width' => 1000,
                 'height' => '',
                 'collapsible' => true,
                 'collapsed' => false,
                 'bodyStyle' => '',
                 'datatype' => 'layout',
                 'children' => 
                array (
                  0 => 
                  Pimcore\Model\DataObject\ClassDefinition\Layout\Fieldcontainer::__set_state(array(
                     'name' => 'Field Container',
                     'type' => NULL,
                     'region' => NULL,
                     'title' => NULL,
                     'width' => '',
                     'height' => '',
                     'collapsible' => false,
                     'collapsed' => false,
                     'bodyStyle' => '',
                     'datatype' => 'layout',
                     'children' => 
                    array (
                      0 => 
                      Pimcore\Model\DataObject\ClassDefinition\Data\Firstname::__set_state(array(
                         'name' => 'firstname',
                         'title' => 'First name',
                         'tooltip' => '',
                         'mandatory' => false,
                         'noteditable' => false,
                         'index' => 0,
                         'locked' => false,
                         'style' => '',
                         'permissions' => NULL,
                         'fieldtype' => '',
                         'relationType' => false,
                         'invisible' => false,
                         'visibleGridView' => false,
                         'visibleSearch' => false,
                         'blockedVarsForExport' => 
                        array (
                        ),
                         'defaultValue' => NULL,
                         'columnLength' => 30,
                         'regex' => '',
                         'regexFlags' => 
                        array (
                        ),
                         'unique' => false,
                         'showCharCount' => false,
                         'width' => 290,
                         'defaultValueGenerator' => '',
                      )),
                      1 => 
                      Pimcore\Model\DataObject\ClassDefinition\Data\Lastname::__set_state(array(
                         'name' => 'lastname',
                         'title' => 'Lastname',
                         'tooltip' => '',
                         'mandatory' => false,
                         'noteditable' => false,
                         'index' => 0,
                         'locked' => false,
                         'style' => '',
                         'permissions' => NULL,
                         'fieldtype' => '',
                         'relationType' => false,
                         'invisible' => false,
                         'visibleGridView' => false,
                         'visibleSearch' => false,
                         'blockedVarsForExport' => 
                        array (
                        ),
                         'defaultValue' => NULL,
                         'columnLength' => 40,
                         'regex' => '',
                         'regexFlags' => 
                        array (
                        ),
                         'unique' => false,
                         'showCharCount' => false,
                         'width' => 290,
                         'defaultValueGenerator' => '',
                      )),
                    ),
                     'locked' => false,
                     'blockedVarsForExport' => 
                    array (
                    ),
                     'fieldtype' => 'fieldcontainer',
                     'layout' => 'hbox',
                     'fieldLabel' => '',
                     'labelWidth' => 180,
                     'labelAlign' => 'left',
                  )),
                  1 => 
                  Pimcore\Model\DataObject\ClassDefinition\Layout\Fieldcontainer::__set_state(array(
                     'name' => 'Field Container',
                     'type' => NULL,
                     'region' => NULL,
                     'title' => NULL,
                     'width' => '',
                     'height' => '',
                     'collapsible' => false,
                     'collapsed' => false,
                     'bodyStyle' => '',
                     'datatype' => 'layout',
                     'children' => 
                    array (
                      0 => 
                      Pimcore\Model\DataObject\ClassDefinition\Data\Select::__set_state(array(
                         'name' => 'gender',
                         'title' => 'Gender',
                         'tooltip' => '',
                         'mandatory' => false,
                         'noteditable' => false,
                         'index' => 0,
                         'locked' => false,
                         'style' => '',
                         'permissions' => NULL,
                         'fieldtype' => '',
                         'relationType' => false,
                         'invisible' => false,
                         'visibleGridView' => false,
                         'visibleSearch' => false,
                         'blockedVarsForExport' => 
                        array (
                        ),
                         'options' => 
                        array (
                          0 => 
                          array (
                            'key' => 'male',
                            'value' => 'male',
                          ),
                          1 => 
                          array (
                            'key' => 'female',
                            'value' => 'female',
                          ),
                        ),
                         'defaultValue' => '',
                         'optionsProviderClass' => '',
                         'optionsProviderData' => '',
                         'columnLength' => 40,
                         'dynamicOptions' => false,
                         'defaultValueGenerator' => '',
                         'width' => 290,
                      )),
                      1 => 
                      Pimcore\Model\DataObject\ClassDefinition\Data\Email::__set_state(array(
                         'name' => 'email',
                         'title' => 'Email',
                         'tooltip' => '',
                         'mandatory' => false,
                         'noteditable' => false,
                         'index' => 0,
                         'locked' => false,
                         'style' => '',
                         'permissions' => NULL,
                         'fieldtype' => '',
                         'relationType' => false,
                         'invisible' => false,
                         'visibleGridView' => false,
                         'visibleSearch' => false,
                         'blockedVarsForExport' => 
                        array (
                        ),
                         'defaultValue' => NULL,
                         'columnLength' => 40,
                         'regex' => '',
                         'regexFlags' => 
                        array (
                        ),
                         'unique' => false,
                         'showCharCount' => false,
                         'width' => 290,
                         'defaultValueGenerator' => '',
                      )),
                    ),
                     'locked' => false,
                     'blockedVarsForExport' => 
                    array (
                    ),
                     'fieldtype' => 'fieldcontainer',
                     'layout' => 'hbox',
                     'fieldLabel' => '',
                     'labelWidth' => 180,
                     'labelAlign' => 'left',
                  )),
                  2 => 
                  Pimcore\Model\DataObject\ClassDefinition\Layout\Fieldcontainer::__set_state(array(
                     'name' => 'Field Container',
                     'type' => NULL,
                     'region' => NULL,
                     'title' => NULL,
                     'width' => '',
                     'height' => '',
                     'collapsible' => false,
                     'collapsed' => false,
                     'bodyStyle' => '',
                     'datatype' => 'layout',
                     'children' => 
                    array (
                      0 => 
                      Pimcore\Model\DataObject\ClassDefinition\Data\Numeric::__set_state(array(
                         'name' => 'phoneNumber',
                         'title' => 'phone Number',
                         'tooltip' => '',
                         'mandatory' => false,
                         'noteditable' => false,
                         'index' => 0,
                         'locked' => false,
                         'style' => '',
                         'permissions' => NULL,
                         'fieldtype' => '',
                         'relationType' => false,
                         'invisible' => false,
                         'visibleGridView' => false,
                         'visibleSearch' => false,
                         'blockedVarsForExport' => 
                        array (
                        ),
                         'defaultValue' => NULL,
                         'integer' => false,
                         'unsigned' => false,
                         'minValue' => NULL,
                         'maxValue' => NULL,
                         'unique' => false,
                         'decimalSize' => NULL,
                         'decimalPrecision' => NULL,
                         'width' => 290,
                         'defaultValueGenerator' => '',
                      )),
                      1 => 
                      Pimcore\Model\DataObject\ClassDefinition\Data\Input::__set_state(array(
                         'name' => 'gstnumber',
                         'title' => 'GST',
                         'tooltip' => '',
                         'mandatory' => false,
                         'noteditable' => false,
                         'index' => 0,
                         'locked' => false,
                         'style' => '',
                         'permissions' => NULL,
                         'fieldtype' => '',
                         'relationType' => false,
                         'invisible' => false,
                         'visibleGridView' => false,
                         'visibleSearch' => false,
                         'blockedVarsForExport' => 
                        array (
                        ),
                         'defaultValue' => NULL,
                         'columnLength' => 40,
                         'regex' => '',
                         'regexFlags' => 
                        array (
                        ),
                         'unique' => false,
                         'showCharCount' => false,
                         'width' => 290,
                         'defaultValueGenerator' => '',
                      )),
                    ),
                     'locked' => false,
                     'blockedVarsForExport' => 
                    array (
                    ),
                     'fieldtype' => 'fieldcontainer',
                     'layout' => 'hbox',
                     'fieldLabel' => '',
                     'labelWidth' => 180,
                     'labelAlign' => 'left',
                  )),
                  3 => 
                  Pimcore\Model\DataObject\ClassDefinition\Layout\Fieldcontainer::__set_state(array(
                     'name' => 'Field Container',
                     'type' => NULL,
                     'region' => NULL,
                     'title' => NULL,
                     'width' => '',
                     'height' => '',
                     'collapsible' => false,
                     'collapsed' => false,
                     'bodyStyle' => '',
                     'datatype' => 'layout',
                     'children' => 
                    array (
                      0 => 
                      Pimcore\Model\DataObject\ClassDefinition\Data\Textarea::__set_state(array(
                         'name' => 'address',
                         'title' => 'Address',
                         'tooltip' => '',
                         'mandatory' => false,
                         'noteditable' => false,
                         'index' => 0,
                         'locked' => false,
                         'style' => '',
                         'permissions' => NULL,
                         'fieldtype' => '',
                         'relationType' => false,
                         'invisible' => false,
                         'visibleGridView' => false,
                         'visibleSearch' => false,
                         'blockedVarsForExport' => 
                        array (
                        ),
                         'maxLength' => NULL,
                         'showCharCount' => false,
                         'excludeFromSearchIndex' => false,
                         'height' => 100,
                         'width' => 770,
                      )),
                    ),
                     'locked' => false,
                     'blockedVarsForExport' => 
                    array (
                    ),
                     'fieldtype' => 'fieldcontainer',
                     'layout' => 'hbox',
                     'fieldLabel' => '',
                     'labelWidth' => 180,
                     'labelAlign' => 'left',
                  )),
                ),
                 'locked' => false,
                 'blockedVarsForExport' => 
                array (
                ),
                 'fieldtype' => 'fieldset',
                 'labelWidth' => 0,
                 'labelAlign' => 'left',
              )),
            ),
             'locked' => false,
             'blockedVarsForExport' => 
            array (
            ),
             'fieldtype' => 'panel',
             'layout' => NULL,
             'border' => false,
             'icon' => '',
             'labelWidth' => 180,
             'labelAlign' => 'left',
          )),
          1 => 
          Pimcore\Model\DataObject\ClassDefinition\Layout\Panel::__set_state(array(
             'name' => 'Credential',
             'type' => NULL,
             'region' => NULL,
             'title' => 'Credential',
             'width' => '',
             'height' => '',
             'collapsible' => false,
             'collapsed' => false,
             'bodyStyle' => '',
             'datatype' => 'layout',
             'children' => 
            array (
              0 => 
              Pimcore\Model\DataObject\ClassDefinition\Layout\Fieldset::__set_state(array(
                 'name' => 'Layout',
                 'type' => NULL,
                 'region' => NULL,
                 'title' => 'Woocommerce details',
                 'width' => 1000,
                 'height' => '',
                 'collapsible' => true,
                 'collapsed' => false,
                 'bodyStyle' => '',
                 'datatype' => 'layout',
                 'children' => 
                array (
                  0 => 
                  Pimcore\Model\DataObject\ClassDefinition\Layout\Fieldcontainer::__set_state(array(
                     'name' => 'Field Container',
                     'type' => NULL,
                     'region' => NULL,
                     'title' => NULL,
                     'width' => '',
                     'height' => '',
                     'collapsible' => false,
                     'collapsed' => false,
                     'bodyStyle' => '',
                     'datatype' => 'layout',
                     'children' => 
                    array (
                      0 => 
                      Pimcore\Model\DataObject\ClassDefinition\Data\Input::__set_state(array(
                         'name' => 'username',
                         'title' => 'Username',
                         'tooltip' => '',
                         'mandatory' => false,
                         'noteditable' => false,
                         'index' => 0,
                         'locked' => false,
                         'style' => '',
                         'permissions' => NULL,
                         'fieldtype' => '',
                         'relationType' => false,
                         'invisible' => false,
                         'visibleGridView' => false,
                         'visibleSearch' => false,
                         'blockedVarsForExport' => 
                        array (
                        ),
                         'defaultValue' => NULL,
                         'columnLength' => 50,
                         'regex' => '',
                         'regexFlags' => 
                        array (
                        ),
                         'unique' => false,
                         'showCharCount' => false,
                         'width' => 290,
                         'defaultValueGenerator' => '',
                      )),
                      1 => 
                      Pimcore\Model\DataObject\ClassDefinition\Data\Password::__set_state(array(
                         'name' => 'password',
                         'title' => 'Password',
                         'tooltip' => '',
                         'mandatory' => false,
                         'noteditable' => false,
                         'index' => 0,
                         'locked' => false,
                         'style' => '',
                         'permissions' => NULL,
                         'fieldtype' => '',
                         'relationType' => false,
                         'invisible' => false,
                         'visibleGridView' => false,
                         'visibleSearch' => false,
                         'blockedVarsForExport' => 
                        array (
                        ),
                         'algorithm' => 'password_hash',
                         'salt' => '',
                         'saltlocation' => 'back',
                         'minimumLength' => 8,
                         'width' => 290,
                      )),
                    ),
                     'locked' => false,
                     'blockedVarsForExport' => 
                    array (
                    ),
                     'fieldtype' => 'fieldcontainer',
                     'layout' => 'hbox',
                     'fieldLabel' => '',
                     'labelWidth' => 180,
                     'labelAlign' => 'left',
                  )),
                ),
                 'locked' => false,
                 'blockedVarsForExport' => 
                array (
                ),
                 'fieldtype' => 'fieldset',
                 'labelWidth' => 0,
                 'labelAlign' => 'left',
              )),
            ),
             'locked' => false,
             'blockedVarsForExport' => 
            array (
            ),
             'fieldtype' => 'panel',
             'layout' => NULL,
             'border' => false,
             'icon' => '',
             'labelWidth' => 180,
             'labelAlign' => 'left',
          )),
        ),
         'locked' => false,
         'blockedVarsForExport' => 
        array (
        ),
         'fieldtype' => 'tabpanel',
         'border' => false,
         'tabPosition' => 'top',
      )),
    ),
     'locked' => false,
     'blockedVarsForExport' => 
    array (
    ),
     'fieldtype' => 'panel',
     'layout' => NULL,
     'border' => false,
     'icon' => NULL,
     'labelWidth' => 100,
     'labelAlign' => 'left',
  )),
   'icon' => '',
   'group' => '',
   'showAppLoggerTab' => false,
   'linkGeneratorReference' => '',
   'previewGeneratorReference' => '',
   'compositeIndices' => 
  array (
  ),
   'showFieldLookup' => false,
   'propertyVisibility' => 
  array (
    'grid' => 
    array (
      'id' => true,
      'key' => false,
      'path' => true,
      'published' => true,
      'modificationDate' => true,
      'creationDate' => true,
    ),
    'search' => 
    array (
      'id' => true,
      'key' => false,
      'path' => true,
      'published' => true,
      'modificationDate' => true,
      'creationDate' => true,
    ),
  ),
   'enableGridLocking' => false,
   'deletedDataComponents' => 
  array (
  ),
   'blockedVarsForExport' => 
  array (
  ),
   'fieldDefinitionsCache' => 
  array (
  ),
   'activeDispatchingEvents' => 
  array (
  ),
));

pimcore.registerNS("pimcore.plugin.processmanager.executor.callback.customExportCallback");
pimcore.plugin.processmanager.executor.callback.customExportCallback = Class.create(pimcore.plugin.processmanager.executor.callback.abstractCallback, {

    name: "customExportCallback",

    getFormItems: function () {
        var items = [];
        var config = {
            //mandatory: true, 
        }
        
        //For export family setting of select box
        var selectConfig = config;
        selectConfig.store = [
            ['parent', 'parent'],
            ['model-color', 'model-color'],
            ['model-season', 'model-season']
        ];
        items.push(this.getSelectField('exportFamilySetting', selectConfig));

        //For channel mapping
        var itemChannel = {
            itemSelectorConfig: {
                type: ["object"],
                subtype: {
                    object: ["object"]
                },
                specific: {
                    classes: ['Channel']
                },
                allowObjectFolder: false
            },
            mandatory: true, 
        };
        items.push(this.getHref('channel',itemChannel));

        var itemSelectorConfig = {
            itemSelectorConfig: {
                type: ["object"],
                subtype: {
                    object: ["object", "folder", "variant"]
                }, 
                // specific: {
                //     classes: ['Shoes','Bags','Textilebottom','Textilecomplete','Textiletop']
                // },
                allowObjectFolder: true
            },
            
            mandatory: true, 
        };
        items.push(this.getItemSelector('object', itemSelectorConfig));

        return items;
    },

    getConfigSelection : function () {
        var configStore = new Ext.data.Store({
            autoLoad : true,
            proxy: {
                url: '/admin/elementsprocessmanager/callback-settings/list?type=' + this.name,
                type: 'ajax',
                reader: {
                    type: 'json',
                    rootProperty: "data"
                }
            },
            fields: ["id","name","description","settings","type"]
        });



        if(!this.predefinedConfig){
            this.predefinedConfig = new Ext.form.ComboBox({
                fieldLabel: t('plugin_pm_predefined_callback_settings'),
                name: 'docType',
                labelWidth: 120,
                xtype: "combo",
                displayField:'name',
                valueField: "id",
                store: configStore,
                editable: false,
                hidden:true,
                width : 400,
                triggerAction: 'all',
                value: '',
                listeners: {
                    "select": function(a,record){
                        var data = record.getData();
                        Ext.getCmp(this.getIdKey("plugin_pm_executor_callback_form_")).getForm().reset();
                        this.applyCallbackSettings(data.extJsSettings);
                    }.bind(this)
                }
            });
        }
        return this.predefinedConfig;
    },

    execute: function () {
        this.openConfigWindow();
    }
});
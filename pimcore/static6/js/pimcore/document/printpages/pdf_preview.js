
pimcore.registerNS("pimcore.document.printpages.pdfpreview");
pimcore.document.printpages.pdfpreview = Class.create({

    initialize: function(page) {
        this.page = page;
    },

    getLayout: function () {

        if (this.layout == null) {

            var iframeOnLoad = "pimcore.globalmanager.get('document_" + this.page.id + "').pdfpreview.iFrameLoaded()";

            var details = [];

            this.generateButton = new Ext.Button({
                text: t("web2print_generate_pdf"),
                iconCls: "pimcore_icon_pdf",
                handler: this.generatePdf.bind(this)
            });

            var checkoxPrinterMarks = new Ext.form.Checkbox({
                fieldLabel: t("web2print_include_printermarks"),
                name: "printermarks",
                itemCls: "object_field"
            });

            this.generateForm = new Ext.form.FormPanel({
                // bodyStyle: "padding: 10px;",
                autoHeight: true,
                border: false,
                // layout: "pimcoreform",
                items: [this.getProcessingOptionsGrid()],
                buttons: [this.generateButton]
            });


            var generateBox = new Ext.Panel({
                title: t("web2print_generate_pdf"),
                expandable: true
            });

            generateBox.add(this.generateForm);

            details.push(generateBox);

            this.downloadButton = new Ext.Button({
                text: t("web2print_download_pdf"),
                iconCls: "pimcore_icon_download",
                handler: function () {
                    var date = new Date();
                    pimcore.helpers.download("/admin/printpage/pdf-download/download/1/id/" + this.page.id + "?time=" + date.getTime());
                }.bind(this)
            });

            var downloadBox = new Ext.Panel({
                title: t("web2print_download_pdf")
            });

            this.generatedDateField = new Ext.form.TextField({
                readOnly: true,
                width: "100%",
                name: "last-generated",
                fieldLabel: t("web2print_last-generated"),
                value: ""
            });
            this.generateMessageField = new Ext.form.TextArea({
                readOnly: true,
                height: 100,
                width: "100%",
                name: "last-generate-message",
                fieldLabel: t("web2print_last-generate-message"),
                value: ""
            });

            this.dirtyLabel = new Ext.form.Label({
                text: "Documents changed since last pdf generation.",
                style: "color: red",
                hidden: true
            });
            downloadBox.add(new Ext.form.FormPanel({
                bodyStyle: "padding: 10px;",
                border: false,
                // layout: "pimcoreform",
                items: [this.generatedDateField, this.generateMessageField, this.dirtyLabel],
                buttons: [this.downloadButton]
            }));
            details.push(downloadBox);

            this.detailsPanel = new Ext.Panel({
                title: t("web2print_pdf-details"),
                region: "west",
                width: 350,
                items: details,
                autoScroll: true,
                tbar: ['->',{
                        xtype: 'button',
                        text: t("web2print_cancel_pdf_creation"),
                        iconCls: "pimcore_icon_cancel",
                        handler: function() {
                            Ext.Ajax.request({
                                url: "/admin/printpage/cancel-generation/",
                                params: {id: this.page.id},
                                success: function(response) {
                                    result = Ext.decode(response.responseText);
                                    if(!result.success) {
                                        pimcore.helpers.showNotification(t('web2print_cancel_generation'), t('web2print_cancel_generation_error'), "error");
                                    }
                                }.bind(this)
                            });
                        }.bind(this)
                    }
                ]
            });
            // this.detailsPanel.getTopToolbar().hide();

            this.detailsPanel.on("afterrender", function () {
                // this.loadMaskDetails = new Ext.LoadMask(generateBox.getEl(), {msg: t("please_wait")});
                // this.loadMaskDetails.enable();
            }.bind(this));

            this.iframeName = 'document_pdfpreview_iframe_' + this.page.id;

            this.layout = new Ext.Panel({
                title: t('web2print_preview_pdf'),
                layout: "border",
                autoScroll: false,
                iconCls: "pimcore_icon_pdf",
                items: [{
                    region: "center",
                    hideMode: "offsets",
                    bodyCls: "pimcore_overflow_scrolling",
                    forceLayout: true,
                    autoScroll: true,
                    border: false,
                    scrollable: false,
                    html: '<iframe src="about:blank" width="100%" frameborder="0" id="' + this.iframeName + '" name="' + this.iframeName + '"></iframe>'
                },this.detailsPanel]
            });

            this.layout.on("resize", this.onLayoutResize.bind(this));
            this.layout.on("activate", this.refresh.bind(this));
            this.layout.on("afterrender", function () {

                // unfortunately we have to do this in jQuery, because Ext doesn'T offer this functionality
                $("#" + this.iframeName).load(function () {
                    // this is to hide the mask if edit/startup.js isn't executed (eg. in case an error is shown)
                    // otherwise edit/startup.js will disable the loading mask
                    if(!this["frame"]) {
                        this.loadMask.hide();
                    }
                }.bind(this));

                this.loadMask = new Ext.LoadMask({
                    target: this.layout,
                    msg: t("please_wait")
                });

                this.loadMask.show();
            }.bind(this));
        }

        return this.layout;
    },

    getProcessingOptionsGrid: function() {

        this.processingOptionsStore = new Ext.data.JsonStore({
            proxy: {
                url: '/admin/printpage/get-processing-options',
                type: 'ajax',
                reader: {
                    type: 'json',
                    rootProperty: "options",
                    idProperty: 'name'
                }
            },
            fields: ['name','label','type','value','default','values'],
            autoDestroy: true,
            autoLoad: true,
            baseParams: { id: this.page.id },

            sortInfo:{field: 'name', direction: "ASC"}
        });

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1,
            listeners: {
                beforeedit: function(editor, context, eOpts) {
                    //need to clear cached editors of cell-editing editor in order to
                    //enable different editors per row
                    editor.editors.each(Ext.destroy, Ext);
                    editor.editors.clear();
                }
            }
        });


        this.processingOptionsGrid = Ext.create('Ext.grid.Panel', {
            autoScroll: true,
            autoHeight: true,
            trackMouseOver: true,
            bodyCls: "pimcore_editable_grid",
            store: this.processingOptionsStore,
            clicksToEdit: 1,
            viewConfig: {
                markDirty: false
            },
            plugins: [
                this.cellEditing
            ],
            columnLines: true,
            stripeRows: true,
            columns: [
                {
                    header: t("name"),
                    dataIndex: 'label',
                    editable: false,
                    width: 120,
                    sortable: true
                },
                {
                    flex: 1,
                    header: t("value"),
                    dataIndex: 'value',
                    getEditor: this.getCellEditor.bind(this),
                    editable: true,
                    renderer: this.getCellRenderer.bind(this),
                    listeners: {
                        "mousedown": this.cellMousedown.bind(this)
                    }
                }
            ]
        });

        return this.processingOptionsGrid;
    },

    getCellRenderer: function (value, metaData, record, rowIndex, colIndex, store) {
        var data = record.data;
        var type = data.type;

        if (type == "bool") {
            if (value) {
                return '<div style="text-align: left"><div role="button" class="x-grid-checkcolumn x-grid-checkcolumn-checked" style=""></div></div>';
            } else {
                return '<div style="text-align: left"><div role="button" class="x-grid-checkcolumn" style=""></div></div>';
            }
        }

        if (type == "select") {
            return t("web2print_" + value);
        }

        return value;
    },

    cellMousedown: function (grid, cell, rowIndex, cellIndex, e) {
        var store = grid.getStore();
        var record = store.getAt(rowIndex);

        var data = record.data;
        var type = data.type;

        if (type == "bool") {
            record.set("data", !data.data);
            record.set("value", !data.value);
        }
    },

    getCellEditor: function (record) {

        var data = record.data;

        var type = data.type;
        var property;

        if (type == "text") {
            property = new Ext.form.TextField();
        }
        else if (type == "bool") {
            //nothing needed there
        }
        else if (type == "select") {
            var values = data.values;
            var storeValues = [];
            for(var i=0; i < values.length; i++) {
                storeValues.push([values[i], t("web2print_" + values[i])]);
            }

            property = new Ext.form.ComboBox({
                triggerAction: 'all',
                editable: false,
                mode: 'local',
                // typeAhead: true,
                lazyRender: true,
                store: new Ext.data.ArrayStore({
                    fields: ["id", "value"],
                    data: storeValues
                }),
                valueField: "id",
                displayField: "value"
            });
        }


        return property;
    },

    generatePdf: function() {

        var params = this.generateForm.getForm().getFieldValues();

        this.processingOptionsStore.each(function(rec) {
            params[rec.data.name] = rec.data.value;
        });
        params.id = this.page.id;

        Ext.Ajax.request({
            url: "/admin/printpage/start-pdf-generation/",
            params: params,
            success: function(response) {
                result = Ext.decode(response.responseText);
                if(result.success) {
                    this.checkForActiveGenerateProcess();
                }
            }.bind(this)
        });
    },


    onLayoutResize: function (el, width, height, rWidth, rHeight) {
        this.setLayoutFrameDimensions(width, height);
    },

    setLayoutFrameDimensions: function (width, height) {
        Ext.get(this.iframeName).setStyle({
            height: (height) + "px"
        });
    },

    iFrameLoaded: function () {
        if(this.loadMask){
            this.loadMask.hide();
        }
    },

    loadCurrentPreview: function () {
        var date = new Date();
        var path;
        path = "/admin/printpage/pdf-download/id/" + this.page.id + "?time=" + date.getTime();

        try {
            Ext.get(this.iframeName).dom.src = path;
        }
        catch (e) {
            console.log(e);
        }
    },

    refresh: function () {
        if(!this.loaded)  {
//            this.loadMask.show();
            this.checkPdfDirtyState();
            this.checkForActiveGenerateProcess();
            this.loaded = true;
        }
    },

    checkForActiveGenerateProcess: function() {
        Ext.Ajax.request({
            url: "/admin/printpage/active-generate-process/",
            params: {id: this.page.id},
            success: function(response) {
                result = Ext.decode(response.responseText);
                if(result.activeGenerateProcess) {
                    // this.detailsPanel.getTopToolbar().show();
                    // this.loadMaskDetails.show();
                    window.setTimeout(function() {
                        this.checkForActiveGenerateProcess();
                    }.bind(this), 2000);
                } else {

                    this.downloadButton.setDisabled(!result.downloadAvailable);

                    // this.detailsPanel.getTopToolbar().hide();
                    // this.loadMaskDetails.hide();
                    this.generatedDateField.setValue(result.date);
                    this.generateMessageField.setValue(result.message);

                    if(result.downloadAvailable) {
                        this.loadCurrentPreview();
                    }
                    this.iFrameLoaded();
                    this.checkPdfDirtyState();
                }
            }.bind(this)
        });
    },

    checkPdfDirtyState: function() {
        Ext.Ajax.request({
            url: "/admin/printpage/check-pdf-dirty/",
            params: {id: this.page.id},
            success: function(response) {
                result = Ext.decode(response.responseText);
                if(result.pdfDirty) {
                    this.dirtyLabel.setVisible(true);
                } else {
                    this.dirtyLabel.setVisible(false);
                }
            }.bind(this)
        });
    }

});
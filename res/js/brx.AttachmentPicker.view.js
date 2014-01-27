(function($, _, Backbone){
    
    _.declare('brx.AttachmentPicker', $.brx.FormView, {
        options:{
            title: 'Выберите файл',
            attachments: null,
            attachmentId: 0,
            initialId: 0,
            views: {},
            validExtensions:[]
        },
        
        postCreate: function(){
            console.dir({'brx.AttachmentPicker': this});
            this.listenTo(this.get('attachments'), 'add', $.proxy(this.renderAttachment, this));
            this.listenTo(this.get('attachments'), 'remove', $.proxy(this.removeAttachmentView, this));
            this.listenTo(this.get('attachments'), 'reset', $.proxy(this.renderAttachments, this));
            this.listenTo(Backbone.Events, 'brx.AttachmentPicker.attachmentSelected', $.proxy(this.onAttachmentSelected, this));
//            this.listenTo(this.get('attachments'), 'all', $.proxy(this.render, this));
            this.setupUploadForm();
            this.render();
        },
                
        getSelectedModel: function(){
            return this.getInt('attachmentId')?this.get('attachments').get(this.getInt('attachmentId')):null;
        },
                
        selectById: function(id){
            this.setInt('attachmentId', id);
            var model = this.getSelectedModel();
            this.onAttachmentSelected(model);
        },
          
        onAttachmentSelected: function(model){
            this.setInt('attachmentId', model?model.id:0);
            this.get('boxAttachments')
                    .find('.attachment_picker-attachment-selected')
                    .removeClass('attachment_picker-attachment-selected');
            if(model){
                this.getAttachmentView(model).$el.addClass('attachment_picker-attachment-selected');
            }
            this.renderInfo();
        },
                
        render: function(){
            this.renderAttachments();
        },
                
        getAttachmentView: function(attachment) {
            var view = _.has(this.get('views'), attachment.id)?
                this.get('views')[attachment.id]:
                (this.get('views')[attachment.id] = new $.brx.AttachmentPicker.AttachmentView({model: attachment}));
            return view;
        },

        removeAttachmentView: function(attachment) {
            if(_.has(this.get('views'), attachment.id)){
                this.get('views')[attachment.id].remove();
                delete(this.get('views')[attachment.id]);
            }
        },

        renderAttachment: function(attachment) {
          var view = this.getAttachmentView(attachment);
          this.get('boxAttachments').prepend(view.render().el);
        },

        // Add all items in the **Todos** collection at once.
        renderAttachments: function() {
          this.get('attachments').each(this.renderAttachment, this);
        },
                
        buttonSelectClicked: function(){
            this.trigger('attachmentSelected', this.getSelectedModel());
            this.hide();
        },
                
        buttonCancelClicked: function(){
            this.hide();
        },
                
        renderInfo: function(){
            var model = this.getSelectedModel();
            if(model){
                var full = model.getImageData_Full();

                this.$el.addClass('show_info');
                this.get('viewer.thumb').attr('src', (full||model.getImageData_Thumbnail()).url);

                var src = '';
                if(full){
                    src = full.url;
                    var re = /[^\\\/]+$/;
                    var m = src.match(re);
                    if(m){
                        src = m.shift();
                    }
                }
                this.get('viewer.filename').text(src?src:model.getTitle());
                this.get('viewer.dimensions').text(full?_.template('<%= width %>x<%= height %>', full):'');
                console.dir({'model':model});
            }else{
                this.$el.removeClass('show_info');
            }
        },
                
        deleteAttachment: function(){
            $.brx.modalConfirm(_.template('Действительно удалить файл <%= post_title %> ?', this.getSelectedModel().attributes), $.proxy(function(){
                this.getSelectedModel().destroy();
                if(this.getInt('attachmentId')===this.getInt('initialId')){
                    this.trigger('attachmentSelected', null);
                }
                this.selectById(0);
            },this));
        },
    
        show: function(id){
            var width = 805;
            if(this.get('attachments').length){
                this.$el.removeClass('empty_library');
            }else{
                this.$el.addClass('empty_library');
                width = 600;
            }
            this.setInt('initialId', id);
            this.selectById(id);
            this.$el.dialog({
                title: this.get('title'),
                width: width,
                modal: this.get('modal')
            });
        },
                
        hide: function(){
            this.$el.dialog('close');
        },
                
        fileSelected: function(){
            var txt = this.inputs('fileUpload').val().toString();
            var re = /[^\\\/]+$/;
            var m = txt.match(re);
            if(m){
                txt = m.shift();
            }
            this.get('viewer.uploadFilename').text(txt);  
            this.checkUploadForm();
        },

        checkUploadForm: function(event){
            var value = this.inputs('fileUpload').val().toString();
            var nonempty = value?true:false;
            var validFormats = true;
            var validExtensions = this.get('validExtensions', []);
            var msg = '';
            if(validExtensions.length){
                var re = new RegExp(_.template('\.(<%= exts %>)$', {exts: validExtensions.join('|')}), 'i');
                var validFormats = value.match(re);//(/\.(png|jpg|jpeg|gif)$/i);
            }
            this.checkField('fileUpload');
//            if(!nonempty){
//                msg = 'Необходимо выбрать файл для закачки';
//            }else 
            if(nonempty && !validFormats){
                msg = _.template('Допустимые форматы: <%= exts %>', {exts: validExtensions.join(', ')});
//                msg = 'Допустимые форматы: png, jpg, gif';
            }
            var msgBox = this.get('boxUpload').find('.field_file_upload .form_field-tips');
            msgBox.text(msg);
            if(msg){
                msgBox.addClass('form_field-tips_error');
                this.get('viewer.uploadFilename').addClass('ui-state-error');
            }else{
                msgBox.removeClass('form_field-tips_error');
                this.get('viewer.uploadFilename').removeClass('ui-state-error');
            }
            
            return nonempty && validFormats;
        },

        processErrors: function(errors){
            console.dir({'processErrors': errors});
            for(key in errors){
                var errorMessage = errors[key];
                if('invalid_format' === key){
                    errorMessage = "Загружаемый файл должен именть один из следующих форматов:<br/>"
                    + "png, jpg, gif";
                }
                var field = 'messageBox';
                if(!_.empty(this.fields('key'))){
                    field = key;
                }
                if(field!=='messageBox'){
                    this.setFormFieldStateError(field, errorMessage );
                }else{
                    this.setMessage(errorMessage, true);
                }
            }
        },
                
        setupUploadForm: function(){
            
            this.prepareAjaxForm('boxUpload', {
                spinner: false,
                errorMessage: 'Ошибка загрузки файла',
                send: $.proxy(function(){
                    if(this.checkUploadForm()){
                        this.showUploadSpinner('Загрузка файла...');
                        return true;
                        
                    }
                    return false;
                }, this),
                success: $.proxy(function(data, xhr){
                    var short = false;
                    if(!this.get('attachments').length){
                        short = true;
                        this.hide();
                    }
                    this.get('attachments').add(data.payload);  
                    this.get('boxUpload')[0].reset();
                    this.fileSelected();
                    this.selectById(data.payload.id);
                    if(short){
                        this.buttonSelectClicked();
                    }
                }, this),
                error: $.proxy(function(){
                }, this),
                complete: $.proxy(function(){
                    this.hideUploadSpinner();
                }, this)
            });
            
//            this.get('boxUpload').iframePostForm({
//                iframeID : 'iframe-post-form',
//                json: 'true',
//                post: $.proxy(function(form){
//                    if(this.checkUploadForm()){
//                        this.showUploadSpinner('Загрузка файла...');
//                        return true;
//                        
//                    }
//                    return false;
//                }, this),
//                complete: $.proxy(function (data, status){
//                    this.hideUploadSpinner();
//                    console.dir({'uploadFile.success':{args: arguments}});
//                    if(data && 0 === data.code){
//                        var short = false;
//                        if(!this.get('attachments').length){
//                            short = true;
//                            this.hide();
//                        }
//                        this.get('attachments').add(data.payload);  
//                        this.get('boxUpload')[0].reset();
//                        this.fileSelected();
//                        this.selectById(data.payload.id);
//                        if(short){
//                            this.buttonSelectClicked();
//                        }
//                    }else{
//                        this.handleAjaxErrors(data);
//                        this.showMessage();
//                    }
//                }, this)
//            });
            
        },
        
        showUploadSpinner: function(text){
            this.fields('fileUpload').hide('fade', {}, 200, $.proxy(function(){
                this.get('uploadSpinner').show(text);
            }, this));
            this.buttons('uploadFile').hide('fade', {}, 200);
        },
                
        hideUploadSpinner: function(){
            this.get('uploadSpinner').hide($.proxy(function(){
                this.fields('fileUpload').show('fade', {}, 200);                
                this.buttons('uploadFile').show('fade', {}, 200);
            }, this));
        }
    });
    
    _.declare('brx.AttachmentPicker.AttachmentView', $.brx.View, {

        options:{
            templateSelector: '#attachment-template'
        },

        postCreate: function() {
          this.listenTo(this.model, 'change', this.render);
          this.listenTo(this.model, 'destroy', this.remove);
        },

        // Re-render the titles of the todo item.
        render: function() {
            this.get('attachmentId').text(this.model.id);
            this.get('attachmentThumbnail').attr('src', this.model.getImageData_Thumbnail().url);
          return this;
        },

        onClick: function(){
            Backbone.Events.trigger('brx.AttachmentPicker.attachmentSelected', this.model);
        }

    });
    
}(jQuery, _, Backbone));
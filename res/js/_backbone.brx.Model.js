(function($) {
    $.brx = $.brx||{};
    
    $.brx.Model = Backbone.Model.extend({
        
        collectionFields: [],
        
        dateFields: [],
        
        parse: function(response, options){
            response = response || {payload: null, code: 1, message: 'empty response'};
            return response.payload ? response.payload : response;
        },
        
        set: function(key, val, options){
            var attr, attrs, unset, changes, silent, changing, prev, current;
            if (key == null) return this;

            // Handle both `"key", value` and `{key: value}` -style arguments.
            if (typeof key === 'object') {
                attrs = key;
                options = val;
            } else {
                (attrs = {})[key] = val;
            }

            options || (options = {});
            
            for(var i in this.collectionFields){
                var field = this.collectionFields[i];
                if(_.has(attrs, field)){
                    this[field].set(attrs[field])
                    delete attrs[field];
                }
                if(_.has(attrs, this.idAttribute)){
                    this[field].parentId = attrs[this.idAttribute];
                }
            }
            
            for(i in this.dateFields){
                field = this.dateFields[i];
                if(_.has(attrs, field) && !_.isDate(attrs[field])){
                    if(_.isString(attrs[field])){
                        attrs[field] = Date.parse(attrs[field]);
                    }
                    if(_.isNumber(attrs[field])){
                        attrs[field] = new Date(attrs[field]);
                    }
                }
            }
            
            if(!_.isEmpty(attrs)){
                return Backbone.Model.prototype.set.apply(this, [attrs, options]);
            }
            
            return this;
        }
        
    });
    
}(jQuery));



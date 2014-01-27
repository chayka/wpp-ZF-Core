(function($,_) {

    _.declare = $.declare = function(classname, parent, implementation){
        var parts = classname.split('.');
        var root = $;
        var part = '';
        for(var i = 0; i < parts.length; i++){
            part = parts[i];
            if(i === parts.length - 1){
                break;
            }
            root[part] = root[part] || {};
            root = root[part];
        }
        
        if(_.isUndefined(implementation)){
            implementation = parent;
            parent = null;
        }
        
        var options = null;
        
        if(parent){
            options = $.extend(true, {}, _.getItem(parent, 'options', {}));
            if(parent.__super__){
                options = $.extend(true, {}, options, _.getItem(parent.__super__, 'options', {}));
            }
            if(parent.prototype){
//                options = $.extend( {}, options, _.getItem(parent.prototype, 'options', {}));
            }
            options = $.extend(true, {}, options, _.getItem(implementation, 'options', {}));
            implementation.options = options;
            if(_.has(parent, 'extend') && _.isFunction(parent.extend)){
                root[part] = parent.extend(implementation);
            }else{
                root[part] = _.extend(parent, implementation);
            }
        }else{
            root[part] = implementation;
        }
        
        return;// root[part];
    };
    
    _.empty = function(value){
        return 	!value
        ||	value === ""
        ||	value === undefined
        ||	value === null
        ||	value === "NaN"
        ||	value === 0
        ||	value === "0"
        ||	value === {}
        ||	value === []
        ;
    };
    
    _.getItem = function(obj, key, defaultValue){
        if(defaultValue === undefined){ 
            defaultValue = null; 
        }
        var parts = (key+'').split('.');
        if(obj && (_.isObject(obj)||_.isArray(obj))){
            var root = obj;
            for(var i in parts){
                var part = parts[i];
                if((_.isObject(root)||_.isArray(root)) && root[part]!==undefined){
                    root = root[part];
                }else{
                    return defaultValue;
                }
            }
            return root;
        }
        
        return defaultValue;
//        return _.empty(obj[key])?defaultValue:obj[key];
    };
    
    _.getVar = function(path, root){
        root = root || window;
        var parts = path.split('.');
        for(var x in parts){
            var part = parts[x];
            if(!parseInt(x)  && part === '$'){
                root = $;
                continue;
            }
            if(root[part]!==undefined){
                root = root[part];
            }else{
                return null;
            }
        }
        return root;
    };
    
    _.setVar = function(path, val, root){
        var parts = path.split('.');
        root = root || window;
        var part = '';
        for(var i = 0; i < parts.length; i++){
            part = parts[i];
            if(i === parts.length - 1){
                break;
            }
            root[part] = root[part] || {};
            root = root[part];
        }
        
        return root[part] = val;
    };
}(jQuery, _));

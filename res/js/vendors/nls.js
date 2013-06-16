(function(){

    window.nls = {
        
        vocabulary: {},
        
        get: function(key){
            var parts = key.split('.');
            var value = this.vocabulary;
            for(var i = 0; i < parts.length; i++){
                var part = parts[i];
                if(!_.has(value, part)){
                    return key;
                }
                value = value[part];
            }
            return value;
        },
        
        set: function(key, value){
            var parts = key.split('.');
            var root = this.vocabulary;
            for(var i = 0; i < parts.length - 1; i++){
                var part = parts[i];
                if(!_.has(root, part)){
                    root[part] = {};
                }
                root = root[part];
            }
            root[_.last(parts)] = value;
            return this;
        },
        
        _: function(key){
            return this.get(key);
        }
    };
    
}).call(this);


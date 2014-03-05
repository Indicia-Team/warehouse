/** Implementation of an array of hashes in javascript.
 *  Will support access through array type methods and also through
 *  hashtable style put, get, etc.
 *  Critically, order is important, so there will also be methods to move
 *  within the list.
 *  Note that currently only string, integers and others that may be
 *  compared with the == operator may be used as keys.
 *  N. Clarke 2008-11-07
 */

var HashArray = function() {
    this._keys = [];
    this._values = [];
};

// Clear the HashArray
HashArray.prototype.clear = function() {
    this._keys = [];
    this._values = [];
};

HashArray.prototype._indexOf = function(item, list){
    for (var i = 0, l = list.length; i<l; i++) {
	if (this._equals(list[i],item)) {
	    return i;
	}
    }
    return -1;
};

HashArray.prototype._equals = function(a,b){
    if (a == b){
	return true;
    }
    return false;
};

HashArray.prototype.size = function(){
    return this._keys.length;
};

HashArray.prototype.hasKey = function(key){
    return (this._indexOf(key, this._keys) != -1);
};

HashArray.prototype.hasValue = function(value){
    return (this._indexOf(value, this._values) != 1);
};

HashArray.prototype.put = function(key,value){
    var index = this._indexOf(key, this._keys);
    if (index == -1){
	index = this._keys.length;
    }
    this._keys[index] = key;
    this._values[index] = value;
};

HashArray.prototype.unshift = function(key,value){
    var index = this._indexOf(key, this._keys);
    if (index != -1){
	this.remove(key);
    }
    this._keys.unshift(key);
    this._values.unshift(value);
};

HashArray.prototype.push = function(key,value){
    var index = this._indexOf(key, this._keys);
    if (index != -1){
	this.remove(key);
    }
    this._keys.push(key);
    this._values.push(value);
};

HashArray.prototype.get = function(key){
    var index = this._indexOf(key, this._keys);
    if (index != -1) {
	return this._values[index];
    }
    return undefined;
};

HashArray.prototype.getKeyAtIndex = function(i){
    if (i >= this._keys.length){
	return undefined;
    }
    return this._keys[i];
};

HashArray.prototype.getKeys = function(){
  return this._keys;
};

HashArray.prototype.getValueAtIndex = function(i){
    if (i >= this._values.length){
	return undefined;
    }
    return this._values[i];
};

HashArray.prototype.getValues = function(){
  return this._values;
};

HashArray.prototype.remove = function(key){
    var index = this._indexOf(key, this._keys);
    if (index != -1){
	this._keys.splice(index,1);
	this._values.splice(index,1);
    }
};

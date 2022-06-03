// Adds an element to the array if it does not already exist
Array.prototype.pushIfNotExist = function(item) {
    if (this.indexOf(item) === -1) this.push(item);
};
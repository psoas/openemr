//Included file 

//Requires Jquery
//from - http://stackoverflow.com/questions/4465244/compare-2-json-objects; http://threebit.net/mail-archive/rails-spinoffs/msg06156.html
//Object.extend(Object, {
//   deepEquals: function(o1, o2) {
//     var k1 = Object.keys(o1).sort();
//     var k2 = Object.keys(o2).sort();
//     if (k1.length != k2.length) return false;
//     return k1.zip(k2, function(keyPair) {
//       if(typeof o1[keyPair[0]] == typeof o2[keyPair[1]] == "object"){
//         return deepEquals(o1[keyPair[0]], o2[keyPair[1]])
//       } else {
//         return o1[keyPair[0]] == o2[keyPair[1]];
//       }
//     }).all();
//   }
//});
//
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const source=fs.readFileSync(__dirname+'/preferences.js','utf8');
const prefix=source.slice(0,source.indexOf('const originals='));
const sandbox={localStorage:{getItem(){return null;}}};
vm.runInNewContext(prefix+'globalThis.test={rows,translate,setLanguage:v=>language=v,language,theme,depth};})();',sandbox);
assert.equal(sandbox.test.language,'hu');assert.equal(sandbox.test.theme,'follow');assert.equal(sandbox.test.depth,'3d');
for(const [lang,index]of [['en',1],['de',2],['fr',3]]){
 sandbox.test.setLanguage(lang);
 for(const row of sandbox.test.rows)assert.equal(sandbox.test.translate(row[0]),row[index],lang+': '+row[0]);
}
sandbox.test.setLanguage('en');
assert.equal(sandbox.test.translate('Mentés és elkülönített visszaállítás sikeres. · 73 fájl.'),'Backup and isolated restore succeeded. · 73 files.');
assert.equal(sandbox.test.translate('CH704 · Aktív'),'CH704 · Active');
sandbox.test.setLanguage('hu');assert.equal(sandbox.test.translate('Mentés most'),'Mentés most');
const followed=source.slice(source.indexOf('function followed()'),source.indexOf('function apply()'));
for(const theme of ['white','black','gray','azure']){
 const ctx={parent:{document:{documentElement:{className:'Theme--'+theme+' Theme--nav-top'}}},matchMedia:()=>({matches:false})};
 vm.runInNewContext(followed+'globalThis.actual=followed();',ctx);assert.equal(ctx.actual,theme);
}
const ctx={get parent(){throw Error('cross-origin');},matchMedia:()=>({matches:true})};
vm.runInNewContext(followed+'globalThis.actual=followed();',ctx);assert.equal(ctx.actual,'black');
console.log('PASS: all translation entries, dynamic results, defaults and Unraid theme detection');

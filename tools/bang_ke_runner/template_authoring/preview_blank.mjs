import fs from 'node:fs/promises';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {FileBlob,SpreadsheetFile} from '@oai/artifact-tool';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
const workbook=await SpreadsheetFile.importXlsx(await FileBlob.load(path.join(root,'validation/blank_preview.xlsx')));
const dest=path.join(root,'validation/blank_previews');
await fs.mkdir(dest,{recursive:true});
for(const [sheetName,range,name] of [
 ['KHAI THUE','B3:P11','khai'],
 ['tong hop hoa don','K1:AC5','tong_hop'],
 ['chi tiet hoa don','K1:AE5','chi_tiet'],
]){
 const preview=await workbook.render({sheetName,range,scale:1,format:'png'});
 await fs.writeFile(path.join(dest,name+'.png'),new Uint8Array(await preview.arrayBuffer()));
}
console.log('Rendered all three blank template views');

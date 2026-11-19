import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { Workbook, SpreadsheetFile } from '@oai/artifact-tool';

const here = path.dirname(fileURLToPath(import.meta.url));
const target = path.resolve(here, '../assets/BangKe_Template.xlsx');
const previews = path.resolve(here, '../validation/template_previews');
await fs.mkdir(path.dirname(target), { recursive: true });
await fs.mkdir(previews, { recursive: true });
const workbook = Workbook.create();
const columns = n => { let s=''; while(n) { n--; s=String.fromCharCode(65+n%26)+s; n=Math.floor(n/26); } return s; };
const color = '#244875';
const detailHeaders = ['STT','Tên công ty','MST công ty','Tên chi nhánh','MST chi nhánh','Tên phòng ban','Tên người mua','Họ tên người mua','Địa chỉ người mua','Email nhận hóa đơn','Mẫu số','Ký hiệu','Số hóa đơn','Ngày hóa đơn','Ngày ký','Ngày cấp mã','Ngày hạch toán','Mã người bán','Tên người bán','Địa chỉ người bán','MST người bán','Tính chất','Diễn giải','Đơn vị tính','Số lượng','Đơn giá','Tổng tiền hàng','Tiền chiết khấu','Doanh số bán chưa thuế','Thuế suất','Thuế GTGT','Loại HH đặc trưng','Số khung','Số máy','Biển kiểm soát phương tiện vận chuyển','Tên người gửi hàng','Địa chỉ người gửi hàng','MST người gửi hàng','Số định danh người gửi hàng','Tổng giảm trừ khác','Tổng tiền thanh toán','Loại tiền','Tỷ giá','Tổng tiền quy đổi','Số lô','Hạn sử dụng','Nhãn dán','Số chứng từ','Người hạch toán','Ghi chú','Kết quả kiểm tra','Loại chi phí','Trạng thái hóa đơn','Biển số xe','Link tra cứu','Mã tra cứu'];
const summaryHeaders = ['STT','Tên công ty','MST công ty','Tên chi nhánh','MST chi nhánh','Tên phòng ban','Tên đơn vị mua hàng','Người mua hàng','Địa chỉ','Email nhận hóa đơn','Mẫu số','Ký hiệu','Số hóa đơn','Ngày hóa đơn','Ngày vào hệ thống','Ngày ký','Ngày cấp mã','Ngày hạch toán','Mã người bán','Tên người bán','Địa chỉ bên bán','Mã số thuế','Diễn giải','Tổng tiền hàng','Tiền chiết khấu','Doanh số bán chưa thuế','Tổng giảm trừ không chịu thuế','Thuế suất','Thuế GTGT','Tổng tiền phí','Tổng giảm trừ khác','Tổng tiền thanh toán','Loại tiền','Tỷ giá','Tổng tiền quy đổi','Nhãn dán','Số chứng từ','Người hạch toán','Kết quả kiểm tra','Loại chi phí','Trạng thái hóa đơn','Hóa đơn liên quan','Thông báo sai sót','Ngày thay đổi trạng thái trên CQT','Ngày phát hiện đổi trạng thái','Biển số xe','Ghi chú','Định mức chi phí','Vượt định mức','Bản thể hiện hóa đơn','Xml hóa đơn','Link tra cứu','Mã tra cứu'];
const khai = workbook.worksheets.add('KHAI THUE');
const detail = workbook.worksheets.add('chi tiet hoa don');
const summary = workbook.worksheets.add('tong hop hoa don');

function base(sheet, end, headRow, endRow=55) {
  sheet.showGridLines = false;
  const area = sheet.getRange(`A1:${end}${endRow}`);
  area.format.font = {name:'Arial',size:10,color:'#22314A'};
  area.format.verticalAlignment = 'center';
  area.format.columnWidth = 18;
  area.format.rowHeight = 21;
  const head = sheet.getRange(`A${headRow}:${end}${headRow}`);
  head.format = {fill:color,font:{name:'Arial',size:10,bold:true,color:'#FFFFFF'},wrapText:true,horizontalAlignment:'center',verticalAlignment:'center'};
  head.format.rowHeight = 72;
  const data = sheet.getRange(`A${headRow+1}:${end}${endRow}`);
  data.format.borders = {insideHorizontal:{style:'thin',color:'#EBEEF3'}};
  sheet.freezePanes.freezeRows(headRow);
}
base(khai, 'P', 5);
khai.getRange('B2:P2').merge();
khai.getRange('B2').values = [['BẢNG KÊ HÓA ĐƠN MUA VÀO']];
khai.getRange('B2:P2').format.font = {name:'Arial',size:14,bold:true,color};
khai.getRange('B2:P2').format.rowHeight = 28;
khai.getRange('B3:P3').merge();
khai.getRange('B3').values = [['Nhập dữ liệu từ dòng 6. Giữ tiêu đề cột; số hóa đơn và MST nên để dạng văn bản.']];
khai.getRange('B3:P3').format.font = {name:'Arial',size:10,italic:true,color:'#66758B'};
khai.getRange('B5:P5').values = [['STT','Mẫu số','Ký hiệu','Số hóa đơn','Ngày hóa đơn','Tên người bán','Mã số thuế người bán','Tên hàng hóa, dịch vụ','Đơn vị tính','Số lượng','Đơn giá','Giá trị HHDV mua vào chưa có thuế GTGT','Thuế suất (%)','Tiền thuế GTGT','Ghi chú']];
khai.getRange('A1:A55').format.columnWidth = 3;
khai.getRange('A1:A55').format.fill = '#FFFFFF';
khai.getRange('A1:A55').format.borders = {preset:'none'};
khai.getRange('B1:B55').format.columnWidth = 7;
khai.getRange('C1:D55').format.columnWidth = 12;
khai.getRange('E1:F55').format.columnWidth = 17;
khai.getRange('G1:G55').format.columnWidth = 31;
khai.getRange('H1:H55').format.columnWidth = 20;
khai.getRange('I1:I55').format.columnWidth = 38;
khai.getRange('J1:K55').format.columnWidth = 12;
khai.getRange('L1:L55').format.columnWidth = 18;
khai.getRange('M1:M55').format.columnWidth = 26;
khai.getRange('N1:N55').format.columnWidth = 12;
khai.getRange('O1:P55').format.columnWidth = 20;
for (const col of ['C','D','E','H']) khai.getRange(`${col}6:${col}55`).setNumberFormat('@');
khai.getRange('F6:F55').setNumberFormat('dd/mm/yyyy');
khai.getRange('K6:K55').setNumberFormat('#,##0.00');
for (const col of ['L','M','O']) khai.getRange(`${col}6:${col}55`).setNumberFormat('#,##0');
khai.getRange('N6:N55').dataValidation = {rule:{type:'list',values:['0','5','8','10','KCT','Khác']}};

for (const [sheet,headers] of [[detail,detailHeaders],[summary,summaryHeaders]]) {
  const end = columns(headers.length);
  base(sheet,end,1,51);
  sheet.getRange(`A1:${end}1`).values = [headers];
  sheet.getRange('A1:A51').format.columnWidth = 7;
  for (const col of ['B','D','G','H','I','J','S','T','U','W']) sheet.getRange(`${col}1:${col}51`).format.columnWidth = 26;
  sheet.getRange('W1:W51').format.columnWidth = 38;
  for (const col of ['C','E','K','L','M']) sheet.getRange(`${col}2:${col}51`).setNumberFormat('@');
  sheet.getRange('N2:R51').setNumberFormat('dd/mm/yyyy');
}
detail.getRange('U2:U51').setNumberFormat('@');
detail.getRange('Y2:Y51').setNumberFormat('#,##0.00');
detail.getRange('Z2:AC51').setNumberFormat('#,##0');
detail.getRange('AE2:AE51').setNumberFormat('#,##0');
// R is the seller code, rather than a date, in the detail layout.
detail.getRange('R2:R51').setNumberFormat('@');
summary.getRange('V2:V51').setNumberFormat('@');
summary.getRange('X2:AC51').setNumberFormat('#,##0');
summary.getRange('AB2:AB51').setNumberFormat('General');
workbook.recalculate();
const check = await workbook.inspect({kind:'sheet',include:'id,name',maxChars:2000});
console.log(check.ndjson);
for (const [sheetName,range,name] of [
  ['KHAI THUE','B2:P9','khai_thue'],
  ['chi tiet hoa don','K1:AE5','chi_tiet'],
  ['tong hop hoa don','K1:AC5','tong_hop'],
]) {
  const preview = await workbook.render({sheetName,range,scale:1,format:'png'});
  await fs.writeFile(path.join(previews,name+'.png'),new Uint8Array(await preview.arrayBuffer()));
}
await (await SpreadsheetFile.exportXlsx(workbook)).save(target);
console.log('Saved '+target);

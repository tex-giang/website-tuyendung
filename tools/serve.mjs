import { createServer } from 'node:http';
import { createReadStream } from 'node:fs';
import { stat } from 'node:fs/promises';
import path from 'node:path';

const root=process.cwd(),port=Number(process.env.PORT||4173);
const mime={'.html':'text/html;charset=utf-8','.css':'text/css;charset=utf-8','.js':'text/javascript;charset=utf-8','.svg':'image/svg+xml','.png':'image/png','.jpg':'image/jpeg','.webp':'image/webp','.pdf':'application/pdf'};
const server=createServer(async(req,res)=>{
  try {
    const parts=decodeURIComponent(new URL(req.url,'http://local').pathname).split('/');
    if(parts.some(p=>p.startsWith('.')||p==='node_modules'||p==='tools')||!['GET','HEAD'].includes(req.method)) {res.writeHead(403);res.end();return;}
    let filename=path.resolve(root,'.'+parts.join('/'));
    if(filename!==root&&!filename.startsWith(root+path.sep)){res.writeHead(403);res.end();return;}
    if((await stat(filename)).isDirectory())filename=path.join(filename,'index.html');
    const type=mime[path.extname(filename)];if(!type){res.writeHead(403);res.end();return;}
    res.writeHead(200,{'Content-Type':type,'Cache-Control':'no-store','X-Content-Type-Options':'nosniff'});
    if(req.method==='HEAD'){res.end();return;}createReadStream(filename).pipe(res);
  } catch(_){res.writeHead(404,{'Content-Type':'text/plain;charset=utf-8'});res.end('Không tìm thấy trang.');}
});
server.listen(port,'127.0.0.1',()=>console.log(`Preview: http://127.0.0.1:${port}/admin/`));

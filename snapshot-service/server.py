"""Read-only FejesNAS AutoPilot UI. No Docker socket, keys or command execution."""
import argparse
from http.server import BaseHTTPRequestHandler,ThreadingHTTPServer
import json
from pathlib import Path
import os

APP=Path(__file__).resolve().parent/'app'
STATE=Path(os.environ.get('FAP_STATE',str(Path(__file__).resolve().parent/'state/status.json')))

def public_status(value):
    if not isinstance(value,dict) or type(value.get('schema_version')) is not int or value['schema_version']!=1:
        raise ValueError('Unknown state schema')
    def fields(item,names):
        if not isinstance(item,dict):return {}
        return {k:item[k] for k in names if k in item and isinstance(item[k],(str,int,float,bool,type(None)))}
    profiles=[fields(p,('id','label','country','server','certificate','expires','approved','tested_at','nat_pmp_verified'))
              for p in value.get('profiles',[]) if isinstance(p,dict)]
    return {'schema_version':1,'read_only':True,'snapshot':True,'updated_at':value.get('updated_at'),
        'host':fields(value.get('host'),('name','version','compatibility')),
        'vpn':fields(value.get('vpn'),('active_profile','healthy','handshake_age_seconds','https_ok','firewall_drop','route_wg0','mapped_port','listen_port','port_matches','bound_wg0','torrent_count')),
        'profiles':profiles,'warnings':[x for x in value.get('warnings',[]) if isinstance(x,str)]}

class Handler(BaseHTTPRequestHandler):
    def log_message(self,*args):pass
    def respond(self,status,payload,content_type='application/json; charset=utf-8'):
        raw=payload if isinstance(payload,bytes) else json.dumps(payload,ensure_ascii=False).encode()
        self.send_response(status);self.send_header('Content-Type',content_type)
        self.send_header('Cache-Control','no-store');self.send_header('X-Content-Type-Options','nosniff')
        self.send_header('Content-Security-Policy',"default-src 'self'; script-src 'self'; style-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'none'")
        self.send_header('Content-Length',str(len(raw)));self.end_headers();self.wfile.write(raw)
    def do_GET(self):
        route=self.path.split('?',1)[0]
        if route=='/api/status':
            try:result=public_status(json.loads(STATE.read_text(encoding='utf-8')))
            except (OSError,ValueError,TypeError):
                self.respond(503,{'error':'Az állapot nem olvasható vagy még nem igazolt.','read_only':True});return
            self.respond(200,result);return
        files={'/':('index.html','text/html; charset=utf-8'),'/app.js':('app.js','text/javascript; charset=utf-8'),'/style.css':('style.css','text/css; charset=utf-8'),
               '/shared/fejes-ui.css':('shared/fejes-ui.css','text/css; charset=utf-8'),'/shared/fejes-ui.js':('shared/fejes-ui.js','text/javascript; charset=utf-8')}
        if route not in files:self.respond(404,{'error':'Nincs ilyen oldal.'});return
        name,kind=files[route];self.respond(200,(APP/name).read_bytes(),kind)
    def do_POST(self):self.respond(405,{'error':'Az előnézet nem indít NAS-műveletet.','read_only':True})

if __name__=='__main__':
    parser=argparse.ArgumentParser();parser.add_argument('--host',default='127.0.0.1');parser.add_argument('--port',type=int,default=8864)
    args=parser.parse_args();server=ThreadingHTTPServer((args.host,args.port),Handler)
    print(f'FejesNAS AutoPilot preview: http://{args.host}:{args.port}',flush=True)
    server.serve_forever()

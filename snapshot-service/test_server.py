import http.client,json,tempfile,threading,unittest
from pathlib import Path
import server

class StatusBoundary(unittest.TestCase):
    def test_secrets_and_unknown_fields_are_not_returned(self):
        raw={'schema_version':1,'private_key':'secret','docker_socket':'secret',
             'host':{'version':'7.3.4','token':'secret'},'vpn':{'healthy':True,'password':'secret'},
             'profiles':[{'id':'AT49','label':'Ausztria','PrivateKey':'secret'}]}
        result=server.public_status(raw)
        self.assertNotIn('secret',json.dumps(result));self.assertTrue(result['read_only'])
    def test_unknown_schema_does_not_enable_operations(self):
        with self.assertRaises(ValueError):server.public_status({'schema_version':99})
    def test_profile_requests_and_private_paths_are_never_executed(self):
        httpd=server.ThreadingHTTPServer(('127.0.0.1',0),server.Handler)
        worker=threading.Thread(target=httpd.serve_forever,daemon=True);worker.start()
        try:
            for method,path in [('POST','/api/profile-request'),('GET','/server.py'),('GET','/../state/status.json')]:
                client=http.client.HTTPConnection(*httpd.server_address)
                client.request(method,path);response=client.getresponse();response.read();client.close()
                self.assertEqual(response.status,405 if method=='POST' else 404)
        finally:httpd.shutdown();httpd.server_close();worker.join()

if __name__=='__main__':unittest.main()

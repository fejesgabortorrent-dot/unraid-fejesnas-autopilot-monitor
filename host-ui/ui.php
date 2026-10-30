<?php
declare(strict_types=1);
if (isset($_COOKIE[session_name()])) session_start();
if (!isset($_SESSION['unraid_login'])) { http_response_code(401); exit('Bejelentkezés szükséges.'); }
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; connect-src 'self'; frame-ancestors 'self'; base-uri 'none'; form-action 'self'");
require_once __DIR__.'/compatibility.php';
const ROOT='/mnt/disk5/biztonsagi_mentesek/FejesNAS_AutoPilot/automatic-v1';
const BACKUP='/boot/config/plugins/user.scripts/scripts/FejesNAS_AutoPilot_Daily_Backup/script';
function reply(int $code,array $data): never {http_response_code($code);header('Content-Type: application/json; charset=utf-8');echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
$asset=$_GET['asset']??'home';
if (!is_string($asset)) reply(400,['error'=>'Hibás kérés.']);
if (!isset($_SESSION['fap_control_nonce'])) $_SESSION['fap_control_nonce']=bin2hex(random_bytes(32));
$nonce=$_SESSION['fap_control_nonce'];
if ($_SERVER['REQUEST_METHOD']==='POST') {
 $origin=$_SERVER['HTTP_ORIGIN']??'';
 $host=$_SERVER['HTTP_HOST']??'';
 if (!is_string($origin)||parse_url($origin,PHP_URL_SCHEME)!=='https'||parse_url($origin,PHP_URL_HOST)!==explode(':',$host)[0]||isset(parse_url($origin)['port'])) reply(403,['error'=>'Eltérő eredetű kérés.']);
 if (!hash_equals($nonce,$_SERVER['HTTP_X_FAP_NONCE']??'')) reply(403,['error'=>'Lejárt munkamenet. Frissítsd az oldalt.']);
 if ($asset!=='backup'||(int)($_SERVER['CONTENT_LENGTH']??0)>1024) reply(400,['error'=>'Nem engedélyezett művelet.']);
 session_write_close();
 if (!fap_host_compatibility()['control_verified']) reply(409,['error'=>'A rendszerverzió vezérlése még nincs igazolva.']);
 if (!is_file(BACKUP)||is_link(BACKUP)) reply(503,['error'=>'A mentési program nem érhető el.']);
 $lock=fopen('/var/lock/fejes-autopilot-web.lock','c');
 if (!$lock||!flock($lock,LOCK_EX|LOCK_NB)) reply(409,['error'=>'Már folyamatban van egy kérés.']);
 $pipes=[];
 $process=proc_open(['timeout','150','bash',BACKUP,'daily'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
 if (!is_resource($process)) reply(503,['error'=>'A mentés nem indítható.']);
 fclose($pipes[0]);$output=stream_get_contents($pipes[1]);fclose($pipes[1]);$errors=stream_get_contents($pipes[2]);fclose($pipes[2]);$rc=proc_close($process);
 $result=null;
 foreach(explode("\n",$output) as $line){$v=json_decode($line,true);if(is_array($v)&&($v['status']??'')==='VERIFIED')$result=$v;}
 if ($rc!==0||!$result) reply(500,['error'=>'A mentés nem igazolt. A korábbi verziók megmaradtak. Részletek a User Scripts naplóban.']);
 reply(200,['status'=>'VERIFIED','files'=>$result['files']??null,'bytes'=>$result['bytes']??null,'archive_sha256'=>$result['archive_sha256']??null,'message'=>'Mentés és elkülönített visszaállítás sikeres.']);
}
if ($_SERVER['REQUEST_METHOD']!=='GET') reply(405,['error'=>'Nem engedélyezett kérés.']);
if ($asset==='control') {
 session_write_close();$rows=[];$warnings=[];
 if(is_dir(ROOT)&&!is_link(ROOT))foreach(glob(ROOT.'/*/manifest.json')?:[] as $file){
  if(count($rows)>=1000){$warnings[]='A lista legfeljebb 1000 mentést mutat.';break;}
  if(is_link($file)||is_link(dirname($file))||filesize($file)>2097152)continue;
  $v=json_decode(file_get_contents($file),true);if(!is_array($v))continue;
  $rows[]=['name'=>basename(dirname($file)),'created'=>$v['created']??null,'kind'=>$v['kind']??null,'files'=>count($v['files']??[]),'verified'=>($v['restore_verified']??false)===true,'sha256'=>$v['archive_sha256']??null];
 }
 usort($rows,fn($a,$b)=>strcmp($b['name'],$a['name']));
 $cron=@file_get_contents('/etc/cron.d/root')?:'';
 $scheduled=str_contains($cron,'40 5 * * * /usr/local/emhttp/plugins/user.scripts/startCustom.php '.BACKUP.' ');
 $config=json_decode(@file_get_contents('/boot/config/plugins/fejesnas.autopilot/vpn_repair.json')?:'{}',true);
 $auto=$config['auto_profile_switch']??null;
 $platform=parse_ini_file('/var/local/emhttp/var.ini');
 reply(200,['compatibility'=>fap_host_compatibility(),'nonce'=>$nonce,'csrf'=>$platform['csrf_token']??null,'backups'=>$rows,'scheduled'=>$scheduled,'schedule'=>'05:40','timezone'=>date_default_timezone_get(),'automatic_profile_switch'=>$auto,'storage'=>'HDD-array · disk5','policy'=>'14 napi + 4 heti kijelölés; nincs automatikus törlés','warnings'=>$warnings,'checked_at'=>gmdate('c')]);
}
session_write_close();
if($asset==='status'){
 $ctx=stream_context_create(['http'=>['timeout'=>8,'follow_location'=>0,'ignore_errors'=>true]]);
 $body=@file_get_contents('http://127.0.0.1:8864/api/status',false,$ctx);$data=json_decode($body?:'',true);
 if(!is_array($data)||($data['schema_version']??null)!==1)reply(503,['error'=>'Az állapotforrás nem érhető el.']);
 // Only public, allowlisted fields cross into the browser.
 $pick=static fn($x,$keys)=>is_array($x)?array_intersect_key($x,array_flip($keys)):[];
 $profiles=[];foreach($data['profiles']??[] as $p)$profiles[]=$pick($p,['id','label','country','server','approved','tested_at','nat_pmp_verified']);
 reply(200,['updated_at'=>$data['updated_at']??null,'host'=>$pick($data['host']??[],['name','version']),'vpn'=>$pick($data['vpn']??[],['active_profile','healthy','handshake_age_seconds','https_ok','firewall_drop','route_wg0','mapped_port','port_matches','bound_wg0','torrent_count']),'profiles'=>$profiles]);
}
if(in_array($asset,['js','css','preferencesjs','preferencescss','icon','favicon','iconpng'],true)){
 $assets=['js'=>['control.js','text/javascript'],'css'=>['control.css','text/css'],'preferencesjs'=>['preferences.js','text/javascript'],'preferencescss'=>['preferences.css','text/css'],'icon'=>['icon.svg','image/svg+xml'],'favicon'=>['favicon.svg','image/svg+xml'],'iconpng'=>['icon.png','image/png']];
 [$file,$type]=$assets[$asset];header('Content-Type: '.$type.'; charset=utf-8');readfile(__DIR__.'/'.$file);exit;
}
if($asset!=='home')reply(404,['error'=>'Nincs ilyen oldal.']);
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html><html lang="hu"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>FejesNAS AutoPilot Monitor</title><link rel="stylesheet" href="?asset=css"><link rel="stylesheet" href="?asset=preferencescss"><link rel="icon" type="image/svg+xml" href="?asset=favicon"><script src="?asset=preferencesjs" defer></script><script src="?asset=js" defer></script></head><body>
<header><div><small>FEJESNAS · VEZÉRLŐKÖZPONT</small><h1><img src="?asset=icon" alt="">FejesNAS AutoPilot Monitor</h1><p>A kapcsolatod és a mentéseid egy helyen.</p></div><button id="refresh">Állapot frissítése</button></header>
<nav><a href="#overview">Áttekintés</a><a href="#profiles">VPN-profilok</a><a href="#backups">Mentések</a><a href="#protection">Védelmek</a></nav>
<main><p id="message" role="status" aria-live="polite">Állapot lekérése…</p>
<section id="overview"><h2>Kapcsolat</h2><div class="grid"><article><small>AKTÍV VPN</small><h3 id="active">Ellenőrzés…</h3><p id="health">Még nincs igazolt állapot</p><p id="checks"></p><small id="updated"></small></article><article><small>TORRENTEK</small><h3 id="torrents">—</h3><p id="ports">Portegyezés: nincs adat</p><p id="handshake"></p></article></div></section>
<section id="profiles"><h2>VPN-profilok</h2><p>A profil részletei a kártyára kattintva láthatók.</p><div id="profile-list" class="grid"></div><article id="profile-detail" hidden></article><p class="note">Profilváltás és új profilok tesztelése még nincs bekötve. Az éles kapcsolat változatlan marad.</p></section>
<section id="backups"><div class="sectionhead"><h2>Biztonsági mentések</h2><button id="backup" disabled>Mentés most</button></div><div class="grid"><article><small>IDŐZÍTÉS</small><h3 id="schedule">Ellenőrzés…</h3><p id="storage"></p><p id="policy"></p></article><article><small>LEGUTÓBBI MENTÉS</small><h3 id="latest">—</h3><p id="restore">Még nincs igazolt állapot</p></article></div><p>Az új mentés minden fájlját elkülönítve visszaolvassa és ellenőrzi. A privát kulcsok nem jelennek meg a felületen.</p><div class="tablewrap"><table><thead><tr><th>Időpont</th><th>Típus</th><th>Fájlok</th><th>Visszaállításpróba</th></tr></thead><tbody id="backup-list"></tbody></table></div></section>
<section id="protection"><h2>Védelmek</h2><article><p id="auto">Automatikus profilváltás: állapot nem igazolt</p><p>Mentés: HDD-array, csak root hozzáférés, ellenőrzőösszegek és elkülönített visszaállításpróba.</p><p>A régebbi mentések megmaradnak. Törlés és éles visszaállítás nem indítható erről az oldalról.</p></article></section>
<section id="compatibility"><h2>Rendszerkompatibilitás</h2><article><p id="compat-state">Verzió ellenőrzése…</p><p>Unraid 8-ra külön illesztési határral előkészítve. A 8-as működés még nincs azon a rendszeren igazolva. Ismeretlen verzión a vezérlés tiltott.</p></article></section><aside id="support"><span>Tetszik az alkalmazás? Támogasd a fejlesztést.</span><details><summary>Támogatás</summary><a href="https://paypal.me/FejesG/2EUR" target="_blank" rel="noopener">Kávé · 2 €</a> · <a href="https://paypal.me/FejesG/5EUR" target="_blank" rel="noopener">Sör · 5 €</a> · <a href="https://paypal.me/FejesG" target="_blank" rel="noopener">Más összeg</a></details><button id="dismiss" aria-label="Támogatói sáv bezárása">×</button></aside>
<footer>FejesNAS AutoPilot Monitor · <span id="host">Unraid</span></footer></main></body></html>

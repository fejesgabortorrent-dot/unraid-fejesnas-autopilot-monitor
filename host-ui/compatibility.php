<?php
function fap_compatibility(string $version): array {
 $verified=$version==='7.3.4';
 return ['schema_version'=>1,'version'=>$version,'control_verified'=>$verified,'unraid8_prepared'=>true,'unraid8_verified'=>false,'message'=>$verified?'Unraid 7.3.4: jelenlegi környezetben ellenőrizve.':'Ez a rendszerverzió még nincs igazolva; a vezérlés tiltott.'];
}
function fap_host_compatibility(): array {
 $info=@parse_ini_file('/etc/unraid-version');
 return fap_compatibility(is_array($info)?(string)($info['version']??'ismeretlen'):'ismeretlen');
}

<?php
require __DIR__.'/../shared/configuration.php';
function check(bool $condition): void {if(!$condition)throw new RuntimeException('Test failed');}
function rejects(array $value): void {try{fap_validate_settings($value);}catch(InvalidArgumentException $e){return;}throw new RuntimeException('Unsafe settings accepted');}
$defaults=fap_validate_settings([]);check($defaults['language']==='hu');check($defaults['backup']['enabled']===false);check(fap_physical_destination($defaults)===null);check(fap_cron_expression($defaults)===null);
$custom=['backup'=>['enabled'=>true,'array_disk'=>'disk2','share'=>'My Backups','hour'=>3,'minute'=>15,'daily'=>21,'weekly'=>6],'services'=>['qbittorrent_container'=>'my_qbit-vpn']];
$actual=fap_validate_settings($custom);check(fap_physical_destination($actual)==='/mnt/disk2/My Backups/FejesNAS_AutoPilot_Monitor');check(fap_cron_expression($actual)==='15 3 * * *');check($actual['backup']['daily']===21);
foreach([['backup'=>['enabled'=>true]],['backup'=>['array_disk'=>'cache']],['backup'=>['array_disk'=>'../disk1']],['backup'=>['share'=>'../appdata']],['backup'=>['share'=>'backups/other']],['backup'=>['share'=>'.']],['backup'=>['hour'=>'5']],['backup'=>['minute'=>60]],['backup'=>['automatic_delete'=>true]],['auto_profile_switch'=>true],['schema_version'=>2],['services'=>['qbittorrent_container'=>'qbit; reboot']],['command'=>'reboot'],['backup'=>['command'=>'reboot']],['language'=>'invalid']] as $bad)rejects($bad);
foreach(['hu','en','de','fr'] as $language)check(fap_validate_settings(['language'=>$language])['language']===$language);
echo "PASS: first-run defaults, custom disk/share/schedule/retention, four languages and unsafe settings rejected\n";

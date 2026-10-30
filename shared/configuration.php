<?php
/* Generic first-run settings. No maintainer NAS hostname, disk or VPN profile. */
declare(strict_types=1);
function fap_defaults(): array {
 return ['schema_version'=>1,'display_name'=>'FejesNAS AutoPilot Monitor','language'=>'hu','theme'=>'follow','depth'=>'3d','border'=>'running','backup'=>['enabled'=>false,'array_disk'=>null,'share'=>null,'hour'=>5,'minute'=>40,'daily'=>14,'weekly'=>4,'automatic_delete'=>false],'services'=>['qbittorrent_container'=>null],'auto_profile_switch'=>false];
}
function fap_validate_settings(array $value): array {
 $default=fap_defaults();
 if(array_diff(array_keys($value),array_keys($default)))throw new InvalidArgumentException('Unknown settings field');
 $value=array_replace($default,$value);
 if($value['schema_version']!==1)throw new InvalidArgumentException('Unknown settings schema');
 if(!is_string($value['display_name'])||strlen($value['display_name'])>120||trim($value['display_name'])===''||preg_match('/[\x00-\x1f<>]/',$value['display_name']))throw new InvalidArgumentException('Invalid display name');
 foreach(['language'=>['hu','en','de','fr'],'theme'=>['follow','white','black','gray','azure'],'depth'=>['3d','flat'],'border'=>['running','still']] as $key=>$choices)if(!in_array($value[$key],$choices,true))throw new InvalidArgumentException('Invalid preference');
 if($value['auto_profile_switch']!==false)throw new InvalidArgumentException('Automatic profile switching is unavailable');
 if(!is_array($value['backup'])||array_diff(array_keys($value['backup']),array_keys($default['backup'])))throw new InvalidArgumentException('Invalid backup settings');
 $backup=array_replace($default['backup'],$value['backup']);
 if(!is_bool($backup['enabled'])||$backup['automatic_delete']!==false)throw new InvalidArgumentException('Automatic deletion is unavailable');
 foreach(['hour'=>[0,23],'minute'=>[0,59],'daily'=>[1,90],'weekly'=>[1,52]] as $key=>[$min,$max])if(!is_int($backup[$key])||$backup[$key]<$min||$backup[$key]>$max)throw new InvalidArgumentException('Invalid schedule or retention');
 if($backup['array_disk']!==null&&(!is_string($backup['array_disk'])||!preg_match('/\Adisk[1-9][0-9]*\z/',$backup['array_disk'])))throw new InvalidArgumentException('Select a physical array disk');
 if($backup['share']!==null&&(!is_string($backup['share'])||!preg_match('/\A[\pL\pN][\pL\pN _.-]{0,79}\z/u',$backup['share'])||str_contains($backup['share'],'..')))throw new InvalidArgumentException('Invalid share name');
 if($backup['enabled']&&($backup['array_disk']===null||$backup['share']===null))throw new InvalidArgumentException('Backup destination must be selected first');
 $value['backup']=$backup;
 if(!is_array($value['services'])||array_diff(array_keys($value['services']),array_keys($default['services'])))throw new InvalidArgumentException('Unknown service settings');
 $services=array_replace($default['services'],$value['services']);$container=$services['qbittorrent_container'];
 if($container!==null&&(!is_string($container)||!preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9_.-]{0,127}\z/',$container)))throw new InvalidArgumentException('Invalid container name');
 $value['services']=$services;return $value;
}
function fap_physical_destination(array $settings,string $mounts='/mnt'): ?string {
 $settings=fap_validate_settings($settings);$b=$settings['backup'];
 if($b['array_disk']===null||$b['share']===null)return null;
 return rtrim($mounts,'/').'/'.$b['array_disk'].'/'.$b['share'].'/FejesNAS_AutoPilot_Monitor';
}
function fap_cron_expression(array $settings): ?string {
 $b=fap_validate_settings($settings)['backup'];return $b['enabled']?$b['minute'].' '.$b['hour'].' * * *':null;
}

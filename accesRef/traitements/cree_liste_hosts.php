<?php
/*
 * Create the ../hosts_domains/liste_hosts file used to rewrite URL with or without 
 * ezproxy suffix. It needs all your config file to be in ../hosts_domains directory.
 * Every one must have .txt extension to be parsed.   
 *
 * Crée le fichier ../hosts_domains/liste_hosts à partir de vos fichier de configuration 
 * mis dans le même répertoire et dont le nom DOIT se terminer par .txt.  
 * Le fichier liste_hosts est celui utilisé la librairie accesRef.lib.php pour renvoyer 
 * une URL vers le reverse proxy ou non.
 */

$ce_repertoire = dirname(__FILE__);
include_once ($ce_repertoire."/../conf/conf.inc.php");


if (!($fd = opendir($dir_sources)))
	exit ("Unknown directory repertoire inexistant $dir_sources / repertoire inexistant $dir_sources .\n");
$tab_hosts=array();
while ($un_fic= readdir($fd)){
	if (!strpos($un_fic,'.txt')) continue;
	$lignes  = file($dir_sources.'/'.$un_fic);	
	foreach ($lignes as $une_ligne){
		if (!preg_match("/^(U|URL|H|HJ|HOST|D|DJ|DOMAIN)\\b/i", $une_ligne,$matches)){
			continue;
		}
		$une_ligne = trim(substr($une_ligne,strlen($matches[0])));
		$une_ligne = preg_replace("!https?://!", '', $une_ligne);
		$une_ligne = trim($une_ligne);
		if (!preg_match("!^([a-zA-Z0-9][a-zA-Z0-9.\\-_]*)!", $une_ligne,$matches))
			continue;
		$une_ligne=$matches[1];
		if (strpos($une_ligne,'/')!==false) {
			echo "Erreur sur : $une_ligne \n";
			continue;
		}
		$enr_ref=array('lg'=>strlen($une_ligne),'url'=>$une_ligne);
		$a_ajouter=true;
		for ($i=0;$i<count($tab_hosts);$i++) {
			if ($tab_hosts[$i]['lg']>$enr_ref['lg']) {
				if (strpos($tab_hosts[$i]['url'], $une_ligne)){
					$tab_hosts[$i]=$enr_ref;
					$a_ajouter=false;
					break;
				}
			} else {
				if (strpos($une_ligne,$tab_hosts[$i]['url'])!==false){
					$a_ajouter=false;
					break;		
				}		
			}
		}
		if ($a_ajouter) $tab_hosts[]=$enr_ref;
	}
}
closedir($fd);
$str_liste = "";
foreach ($tab_hosts as $un_host){
	$str_liste.="\n".$un_host['url'];
}
$str_liste=substr($str_liste,1);
$path_liste = $dir_sources.'/'.$fic_liste;
$r = file_put_contents($path_liste, $str_liste);
?>

#!/usr/bin/php
<?php
/*
 * V1.2 create conflit and general files . conflits contains the conflicts between 
 * 	database definition and general reflects the links SPU -> Hosts and Domains. 
 * 
 * Create the ../hosts_domains/liste_hosts file used to rewrite URL with or without 
 * ezproxy suffix. It needs all your config file to be in ../hosts_domains directory.
 * Every one must have .txt extension to be parsed.   
 * V1.0 Append file with host domain conflicts detected
 * V1.1 Same parsing order as ezproxy to affect good index / name SPU to doubled definitions   
 *
 * Crée le fichier ../hosts_domains/liste_hosts à partir de vos fichier de configuration 
 * mis dans le même répertoire et dont le nom DOIT se terminer par .txt.  
 * Le fichier liste_hosts est celui utilisé la librairie accesRef.lib.php pour renvoyer 
 * une URL vers le reverse proxy ou non.
 * V1.0 crée le fichier des conflits de définitions multiples notamment entre Host et domaines
 * V1.1 analyse des fichiers identique à celle de ezproxy pour indiquer LE bon SPU avec son
 * 		n° à chaque	conflit trouvé.
 * V1.2 création du fichier des conflits et du fichier general (liste des hosts par SPU)
 */

$ce_repertoire = dirname(__FILE__);
include_once ($ce_repertoire."/../conf/conf.inc.php");
$fic_liste.=".test";



$tab_hosts=array(); $tab_conflits = array();
$tab_titre=array();$tab_spu=array(); $tab_HD=array();
$spu_cou = "";  $titre_cou = "";
$no_spu=-1;

if (file_exists("$dir_sources/config.txt")) $fic_boot="config.txt";
elseif (file_exists("$dir_sources/ezproxy.cfg")) $fic_boot="ezproxy.cfg";
else exit ("Not found / inexistant $dir_sources/ config.txt | ezproxy.cfg");
$fs = fopen ("$dir_sources/$fic_boot",'r');
$fpoint=array($fs); 
$fichiers=array($fic_boot);
$ipoint = 0;
$un_fic=$fic_boot;

$liste_clauses_valides = array	('includefile'
								,'u','url'
								,'h','hj','host'
								,'d','dj','domain'
								,'t','title'
								);

while (($une_ligne=lit_ligne ())!==false){
	$une_ligne=trim($une_ligne);
	$type = strtoupper(substr($une_ligne,0,1));
	if (!(in_array($type, array('I','U','H','D','T')))) continue;
	$tligne = preg_split(":\\s+:", $une_ligne);
	$clause = strtolower($tligne[0]);
	if (!in_array($clause, $liste_clauses_valides)) {
			continue;
	}
	if ($clause=="includefile") {
		inclure_fichier($tligne[1]);
		continue;
	}
	$ss_type = (substr($clause,1,1)=='j')?'J':'';

	$une_ligne = trim(substr($une_ligne,strlen($clause)));
	
	if ($type =='T') {
		$titre_cou = $une_ligne;
		continue;
	}
	if ($type =='U') {
		$no_spu = count($tab_spu); 
		$tab_spu[]  = $une_ligne;
		$tab_titre[]= $titre_cou;
		$tab_HD[]   = array();
		$liste_conflits=array();
	}
	if ($no_spu<0) {
		fprintf(STDERR, "ERR: Host/Domain before any SPU / avant tout SPU.\n");
		$no_spu = 0; 
		$tab_spu[]  = "BF/AV 1. SPU";
		$tab_titre[]= "No Title / Avant tout SPU.";
		$tab_HD[]   = array();
	}
	$une_ligne = preg_replace("!https?://!", '', $une_ligne);
	$une_ligne = trim($une_ligne);
	if (!preg_match("!^([a-zA-Z0-9][a-zA-Z0-9.\\-_]*)!", $une_ligne,$matches)){
		fprintf(STDERR, "Err $un_fic : $une_ligne \n");
		continue;
	}
	$une_ligne=$matches[1];

	$no_url = count($tab_HD[$no_spu]);
	$tab_HD[$no_spu][] = $une_ligne;
	
	$enr_ref = array	('lg'=>strlen($une_ligne)
						,'url'=>$une_ligne
						,'no_url'=>$no_url
						,'no_spu'=>$no_spu
						,'fic'=>$un_fic
						,'type'=>$type
						,'ss_type'=>$ss_type
						);
	$a_ajouter=true;
	for ($i=0;$i<count($tab_hosts);$i++) {
		$no_spu_prec = $tab_hosts[$i]['no_spu'];
		$no_url_prec = $tab_hosts[$i]['no_url'];
		$conflit_potentiel = ($no_spu_prec!=$no_spu && !in_array($no_spu_prec, $liste_conflits));
		$pref_old=$pref=""; 
		if ($tab_hosts[$i]['lg']>$enr_ref['lg']) {
			if (strpos($tab_hosts[$i]['url'], $une_ligne)){
				$pref_old='E'.$no_spu;$pref='';
				if ($conflit_potentiel){
						if (!isset($tab_conflits[$no_spu_prec])) 
							$tab_conflits[$no_spu_prec]=array(); 
						$tab_conflits[$no_spu_prec][] =  
							ecrit_enr($tab_hosts[$i]). ' -&- '.ecrit_enr($enr_ref);
						$pref_old.='C'; $pref.='C';
						$liste_conflits[]=$no_spu_prec;
				} 
				$tab_HD[$no_spu_prec][$no_url_prec]=
					$pref_old.$tab_HD[$no_spu_prec][$no_url_prec];
				$tab_HD[$no_spu][$no_url] =	$pref.$tab_HD[$no_spu][$no_url];
				$tab_hosts[$i]=$enr_ref;
				$a_ajouter=false;
				break;
			}
		} else {
			if (strpos($une_ligne,$tab_hosts[$i]['url'])!==false){
				if ($une_ligne==$tab_hosts[$i]['url']) $pref='DB'.$no_spu_prec;
				else $pref='E'.$no_spu_prec;
				if ($conflit_potentiel){
					if (!isset($tab_conflits[$no_spu_prec])) $tab_conflits[$no_spu_prec]=array(); 
					$tab_conflits[$no_spu_prec][] = 
						ecrit_enr($tab_hosts[$i]) . ' -&- '.ecrit_enr($enr_ref);
					$pref_old.='C'; $pref.='C';
					$liste_conflits[]=$no_spu_prec;
				}
				$tab_HD[$no_spu_prec][$no_url_prec]=
					$pref_old.$tab_HD[$no_spu_prec][$no_url_prec];
				$tab_HD[$no_spu][$no_url] =	$pref.$tab_HD[$no_spu][$no_url];
				$a_ajouter=false;
				break;		
			}		
		}
	}
	if ($a_ajouter) $tab_hosts[]=$enr_ref;
}
$urls = array();
foreach ($tab_hosts as $un_host){
	$urls[]=$un_host['url'];
}
$vrai = sort($urls);
$r = file_put_contents($dir_sources.'/'.$fic_liste, implode("\n", $urls));

$fconflits = "$dir_sources/conflits";
if (file_exists($fconflits)) unlink($fconflits);
$fd = fopen($fconflits, 'w');

for ($no_spu=0;$no_spu<count($tab_spu);$no_spu++){
	if (!isset($tab_conflits[$no_spu])) continue;
	foreach ($tab_conflits[$no_spu] as $l){
		fwrite($fd, "$l\n");
	}
}
fclose($fd);

$fgen = "$dir_sources/general";
if (file_exists($fgen)) unlink($fgen);
$fd = fopen($fgen, 'w');
for ($no_spu=0;$no_spu<count($tab_spu);$no_spu++){
	$l = substr('    '.$no_spu,-5).' '.$tab_titre[$no_spu]."\n     ".$tab_spu[$no_spu];
	fwrite($fd, "$l\n");
	foreach ($tab_HD[$no_spu] as $url){
		fwrite($fd, "        + $url\n");
	}
}
fclose($fd);


function ecrit_enr($enr){
	global $tab_spu,$tab_titre;
	$debut = ' '.$enr['no_spu'].' T='.$tab_titre[$enr['no_spu']];
	return ($debut." (".$enr['fic'].") ".$enr['url'].'['.$enr['type'].$enr['ss_type']."]");
}

function lit_ligne (){
	global $ipoint,$fpoint,$fichiers,$fs,$un_fic;
	if (($ligne=fgets($fs))!== false) return ($ligne);
	while ($ipoint>0){
		fclose($fs);
		$ipoint--;
		$fs = $fpoint[$ipoint];
		$un_fic=$fichiers[$ipoint];
		if (($ligne=fgets($fs))!== false) return ($ligne);
	}
	fclose($fs);
	return (false);
}
function inclure_fichier($nom_fichier){
	global $dir_sources,$ipoint,$fpoint,$fichiers,$fs,$un_fic;
	if (! ($fessai = fopen("$dir_sources/$nom_fichier",'r'))){
		fprintf(STDERR, "!! Fail open file / Fichier inaccessible $nom_fichier.\n");
	} else {
		$ipoint++;
		$fichiers[$ipoint]=$un_fic=$nom_fichier;
		$fpoint[$ipoint]=$fs=$fessai;
	}
}

?>

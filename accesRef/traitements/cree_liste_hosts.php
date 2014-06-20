#!/usr/bin/php
<?php
/*
 * V1.3 
 * 
 * Create the ../hosts_domains/liste_hosts file used to rewrite URL with or without 
 * ezproxy suffix. It needs all your config file to be in ../hosts_domains directory.
 * Every one must have .txt extension to be parsed.   
 * V1.0 Append file with host domain conflicts detected
 * V1.1 Same parsing order as ezproxy to affect good index / name SPU to doubled definitions
 * V1.2  create conflits and general files . conflits contains the conflicts between 
 * 	database definition and general reflects the links SPU -> Hosts and Domains.  
 * V1.3 quickness upgrade  
 *
 * Crée le fichier ../hosts_domains/liste_hosts à partir de vos fichier de configuration 
 * mis dans le même répertoire et dont le nom DOIT se terminer par .txt.  
 * Le fichier liste_hosts est celui utilisé la librairie accesRef.lib.php pour renvoyer 
 * une URL vers le reverse proxy ou non.
 * V1.0 crée le fichier des conflits de définitions multiples notamment entre Host et domaines
 * V1.1 analyse des fichiers identique à celle de ezproxy pour indiquer LE bon SPU avec son
 * 		n° à chaque	conflit trouvé.
 * V1.2 création du fichier des conflits et du fichier general (liste des hosts par SPU)
 * V1.3 accélération traitement
 */

$ce_repertoire = dirname(__FILE__);
include_once ($ce_repertoire."/../conf/conf.inc.php");
<<<<<<< HEAD
=======
$fic_liste.=".test";
>>>>>>> ee89e4d44c2ffe383bb7c5ba0726dd87b67865e9



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
$tab_gen = array();
$tab_domain = array();
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
	$cle = cunu_cle($une_ligne);
	
	$no_url = count($tab_HD[$no_spu]);
	$tab_HD[$no_spu][] = $cle;
	
	if ($type=="D"){
		if (!isset($tab_domain[$cle]))$tab_domain[$cle]=array();
		$tab_domain[$cle][]=$no_spu; 
	}
	$enr_ref = array	('no_url'=>$no_url
						,'no_spu'=>$no_spu
						,'fic'=>$un_fic
						,'type'=>$type
						,'ss_type'=>$ss_type
						);
	if (!isset ($tab_gen [$cle])) 
		$tab_gen [$cle]=array('orig'=>$une_ligne,'defs'=>array());
	$tab_gen[$cle]['defs'][] = $enr_ref;
}
$v1 = ksort($tab_gen );
$tab_hosts=array(); $tab_conflits = array();

$domaine_cou = ""; $spu_domaine=-1;
foreach ($tab_gen  as $cle=>$struct){
	$lurl = $struct['orig'];
	$enrs = $struct['defs']; $enr = $enrs[0];
	$no_spu_cou = $enr['no_spu'];
	$est_host=($enr['type']=='H' || $enr['type']=='U');
	$i = 1; 
	while ($i<count($struct['defs']) ){
		$enr = $enrs[$i];
		if ($no_spu_cou != $enr['no_spu']){
			ajout_conflit ($no_spu_cou,$enr['no_spu'],$lurl);
		} 
		$est_host=($enr['type']=='H' || $enr['type']=='U');
		$i++;
	}
	if ($est_host){
		$tab_hosts[]=$lurl;
		$ipos = 0;
		while (($ipos=strpos($cle, '|',$ipos))!==false){
			if (isset($tab_domain[substr($cle,0,$ipos)])){
				foreach ($tab_domain[substr($cle,0,$ipos)] as $un_spu) {
					if ($no_spu_cou!=$un_spu ){
						ajout_conflit ($no_spu_cou,$un_spu,$lurl);
					}
				}
			break;
			}
			$ipos++;
		}
	}
}
$vrai = sort($tab_hosts);
$r = file_put_contents($dir_sources.'/'.$fic_liste, implode("\n", $tab_hosts));

$fconflits = "$dir_sources/conflits";
if (file_exists($fconflits)) unlink($fconflits);
$fd = fopen($fconflits, 'w');

for ($no_spu=0;$no_spu<count($tab_spu);$no_spu++){
	if (!isset($tab_conflits[$no_spu])) continue;
	for ($lautre=0;$lautre<count($tab_spu);$lautre++){
		if (!isset($tab_conflits[$no_spu][$lautre])) continue;
		$les_urls = $tab_conflits[$no_spu][$lautre];
		$l = " $no_spu  ".$tab_titre[$no_spu]. ' -&- '.
				" $lautre  ".$tab_titre[$lautre]."\n\t...".
				implode ("\n\t...",$les_urls)."\n";
		fwrite($fd, "$l\n");
	}
}
fclose($fd);

$fgen = "$dir_sources/general";
if (file_exists($fgen)) unlink($fgen);
$fd = fopen($fgen, 'w');
for ($no_spu=0;$no_spu<count($tab_spu);$no_spu++){
	$l = substr('    '.$no_spu,-5).' '.$tab_spu[$no_spu]. 'T='.$tab_titre[$no_spu];
	fwrite($fd, "$l\n");
	foreach ($tab_HD[$no_spu] as $cle){
		$v=ecrit_enr($cle ,$no_spu);
		fwrite($fd, "        + $v\n");
	}
}
fclose($fd);

function cunu_cle ($l){
	$revl = strrev($l);
	$tcomp = explode ('.',$revl);
	for ($i =0;$i<count($tcomp);$i++){
		$tcomp[$i] = strrev($tcomp[$i]);
	}
	return (implode('|', $tcomp));
}

function ecrit_enr($cle ,$no_spu_cou){
	global $tab_gen;
	$debut = $tab_gen[$cle]['orig'];
	$milieu = ' In '; $milieu = "";
	$suite = ' V. '; $suite = "";
	foreach ($tab_gen[$cle]['defs'] as $enr){
		$no_spu = $enr['no_spu'];
		$un_fic = $enr['fic'];
		$type=$enr['type'].$enr['ss_type'];
		if ($no_spu==$no_spu_cou)
			$milieu .= ", ($un_fic) [$type]";
		else 
			$suite .= ", $no_spu ($un_fic) [$type]";	
	}
	$res = $debut." In ".substr($milieu,1);
	if ($suite) $res.= " ++ ".substr($suite,1);
	return ($res);
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
function ajout_conflit ($spu1,$spu2,$lurl){
	global $tab_conflits;
	if ($spu1>$spu2) {$s = $spu2; $spu2=$spu1;$spu1=$s;}
	if (!isset($tab_conflits[$spu1])) $tab_conflits[$spu1]=array();
	if (!isset($tab_conflits[$spu1][$spu2])) $tab_conflits[$spu1][$spu2]=array();
	if (!in_array($lurl, $tab_conflits[$spu1][$spu2])) $tab_conflits[$spu1][$spu2][]= $lurl;
}
?>

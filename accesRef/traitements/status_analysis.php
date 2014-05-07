#!/usr/bin/php
<?php
/*
 * V0  
 * 
 * Create the ../hosts_domains/status_report which lists SPU and their Host with anotation 
 * of redefinition, javasrcipt rewrite option. 
 * It needs all your config file to be in ../hosts_domains directory.
 * Every one must have .txt extension to be parsed.   
 *
 * Crée le fichier ../hosts_domains/status_report à partir de vos fichier de configuration 
 * mis dans le même répertoire et dont le nom DOIT se terminer par .txt.  
 * 
 */

$ce_repertoire = dirname(__FILE__);
include_once ($ce_repertoire."/../conf/conf.inc.php");



$tab_hosts=array(); 
$tab_titre=array();$tab_spu=array();
$spu_cou = "";  $titre_cou = "";
$no_spu=-1;

if (file_exists("$dir_sources/config.txt")) $fic_boot="config.txt";
elseif (file_exists("$dir_sources/ezproxy.cfg")) $fic_boot="ezproxy.cfg";
else exit ("Not found / inexistant $dir_sources/ config.txt | ezproxy.cfg");
$fs = fopen ("$dir_sources/$fic_boot",'r');
$fpoint=array($fs);
$ficpile = array(0); 
$fichiers=array($fic_boot);
$ipoint = $no_fic = 0;
$no_fic=0;

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
	}
	if ($no_spu<0) {
		fprintf(STDERR, "ERR: Host/Domain before any SPU / avant tout SPU.\n");
		$no_spu = 0; 
		$tab_spu[]  = "BF/AV 1. SPU";
		$tab_titre[]= "No Title / Avant tout SPU.";
	}
	$une_ligne = preg_replace("!https?://!", '', $une_ligne);
	$une_ligne = trim($une_ligne);
	$un_fic = $fichiers[$no_fic];
	if (!preg_match("!^([a-zA-Z0-9][a-zA-Z0-9.\\-_]*)!", $une_ligne,$matches)){
		fprintf(STDERR, "Err $un_fic : $une_ligne \n");
		continue;
	}
	$une_ligne=$matches[1];


	$enr_ref = array	('no_spu'=>$no_spu
						,'no_fic'=>$no_fic
						,'type'=>$type
						,'ss_type'=>$ss_type
						);
	$cle = cunu_cle ($une_ligne);
	if (!isset ($tab_hosts[$cle])) 
		$tab_hosts[$cle]=array('orig'=>$une_ligne,'defs'=>array());
	$tab_hosts[$cle]['defs'][] = $enr_ref; 
}
$vrai = ksort ($tab_hosts); $membre_de=array();
/*
 * 
foreach ($tab_hosts as $cle=>$tdefs){
	$tcomp = explode('.', $cle);
	$vrech  = ''; 
	$membre_de[$cle] = array();
	foreach ($tcomp as $member){
			$vrech.=$member;
			if (isset($tab_hosts[$vrech])) {
				$membre_de[$cle][]=$vrech;
			}
			$vrech.='.';
	} 

}
*/


$fconflits = "$dir_sources/status";
if (file_exists($fconflits)) unlink($fconflits);
$fd = fopen($fconflits, 'w');
for ($i=0;$i<count($tab_spu);$i++){
	$debut = substr('     '.$i,-5);
	fwrite($fd, "$debut = ".$tab_spu[$i]." : ".$tab_titre[$i]."\n");	
}
fwrite($fd, "\t\t=========\n\t\t Hosts + Domains\n\t\t=========\n\n");	

foreach ($tab_hosts as $cle=>$infos){
	$complement = "";
	if (count ($infos['defs'])>1) {
		foreach ($infos['defs'] as $enr){
			$complement.= "\n\t...\t".ecrit_enr($enr);
		}
	} else {
		$enr = $infos['defs'][0];
		$complement=ecrit_enr($enr);
/*		
		$no_spu = $enr['no_spu'];
		$fic = $fichiers[$enr['no_fic']];
		$type=$enr['type'].$enr['ss_type'];
		$complement = " SPU=$no_spu ($fic) [$type]";
*/		
	}
	fwrite($fd, "$cle = ".$infos['orig']."$complement\n");
	
}
fclose($fd);


function ecrit_enr($enr){
	global $tab_spu,$tab_titre,$fichiers;
	$no_spu = $enr['no_spu'];
	$debut = " SPU $no_spu";// = ".$tab_spu[$no_spu].' T='.$tab_titre[$no_spu];
	return ($debut." (".$fichiers[$enr['no_fic']].") ".'['.$enr['type'].$enr['ss_type']."]");
}

function cunu_cle ($l){
	$revl = strrev($l);
	$tcomp = explode ('.',$revl);
	for ($i =0;$i<count($tcomp);$i++){
		$tcomp[$i] = strrev($tcomp[$i]);
	}
	return (implode('|', $tcomp));
}
function lit_ligne (){
	global $ipoint,$fpoint,$fichiers,$fs,$ficpile  ,$no_fic;
	if (($ligne=fgets($fs))!== false) return ($ligne);
	while ($ipoint>0){
		fclose($fs);
		$ipoint--;
		$fs = $fpoint[$ipoint];
		$no_fic=$ficpile[$ipoint];
		if (($ligne=fgets($fs))!== false) return ($ligne);
	}
	fclose($fs);
	return (false);
}
function inclure_fichier($nom_fichier){
	global $dir_sources,$ipoint,$fpoint,$fichiers,$fs,$ficpile  ,$no_fic;
	if (! ($fessai = fopen("$dir_sources/$nom_fichier",'r'))){
		fprintf(STDERR, "!! Fail open file / Fichier inaccessible $nom_fichier.\n");
	} else {
		$no_fic = count($fichiers);
		$fichiers[]=$nom_fichier;
		$ipoint++;
		$ficpile[$ipoint]=$no_fic;
		$fpoint[$ipoint]=$fs=$fessai;
	}
}

?>

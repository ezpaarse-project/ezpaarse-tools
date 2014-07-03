#!/usr/bin/php
<?php
/**
 *
 * gcsv_injecteRef.php V 0.1
 * Script qui sert à injecter certaines colonnes d'un fichier de référence sur les lignes du flux
 * d'entrée ou des fichiers traités.
 * La détermination de la ligne de référence à utiliser se base sur l'identité de contenu d'une
 * ou plusieurs colonnes désignées comme formant  la clé.
Si aucun fichier n'est indiqué en source (-src), c'est l'entrée standard qui est utilisée.
Si aucun fichier n'est indiqué en résultat (-res), c'est la sortie standard qui est utilisée.
 *
 *
 *  V0.1 :
 *  	- utilisation de l'entrée standard pour un usage en pipe possible.
 * performance : 2s pour 17970 lignes triees donnant 3613 clés formees de deux de ses colonnes.
 */
$parametres_possibles=array('-aide','-help','-h',"-test"
							,'-max','-tmax'
							,'-hd','-hd1','-hdr','-hdr1','+hd','+hd1','-xtrt'
							,'-sep','-sepr','-glu','-colt','-colh','-col'
							,'-src','-ref','-refv','-par'
							,'-res','-resr','-rej','-forcedef'
							);


$ce_repertoire = dirname(__FILE__);
$racinePhys  = preg_replace (":/traitements\$:","",$ce_repertoire);
$libs = $racinePhys."/libs";
require_once "$libs/logs.lib.2.php";

set_parentheses(array(array('"','"'),array('[',']')));


/* Tableau  de couple de caractères utilisés en début et fin de valeur unitaire
 * notamment afin que les valeurs puissent contenir des caratères séparateur
 * ex. cuple [] pour le séparateur ,
 * [Auteur, Marc],[Titre,à virgule],Flammarion,1957
 */
$limites_valeurs = array(array('"','"'),array('[',']'));
/**
 * Traitement de la ligne de heaedr qui consiste à établir un lien entre nom de colonne et sa position.
 * Le tableau $conv_colnum est construit lors de l'analyse le commande avec toutes les colonnes désignées.
 * @param string $ligne : chaîne (ligne d'entête ) contenant le format des lignes
 * @return boolean : vrai si OK. sinon étblit la chaîne globale $echo_err.
 */
function  traite_ligne_header ($tab_ligne,&$etat_header,&$t_conv){
	global $echo_err;
	  /*
	   * Si désignation symbolique, conversion des noms de colonnes en n°
	   */
	$mess_err = "";
	if ($etat_header == 'i') return (true);
	for ($i=1;$i<=count($tab_ligne);$i++){
		$ipos=$i-1; $nom = $tab_ligne[$ipos];
		if (!isset($t_conv[$nom])) continue;
		if ($t_conv[$nom]>0) {
			$mess_err .= traite_ligne_header_mess('2defCol',$nom);
		} else
			$t_conv[$nom]=$i;
	}
	foreach ($t_conv as $un_nom=>$nocol){
		if ($nocol<=0) {
			$mess_err.=traite_ligne_header_mess('colNonTrouvee',$un_nom);
		}
	}
	if ($mess_err){
		$echo_err.=$mess_err;
		return (false);
	}
	/*
	 * Ajustement de $etat_header pour la suite du traitement :
	 * Non réplication et présence ou non de cette ligne en début des fichiers suivants à analyser.
	 */
	if (substr($etat_header,-1)=='1') $etat_header=false;
	else $etat_header='i';
	return(true);
}


/*
 * Gestion des temps :
 */
/**
 * Fonction de normalisation sur deux caracteres :
 */
function cal2car ($num){
	if ($num<10) return ("0".$num);
	else return ($num."");
}
/**
 * Fonction de normalisation sans tabulation :
 */
function normalise_cle ($chaine){
		$chaine = str_replace("\t",  " ",$chaine);
	return ($chaine);
}

/**
 *
 * projete_temps : Conversion d'une chaîne tampon en temps timestamp
 * @param string $chaine_tampon = la chaîne
 * @param string $format = format comme décrit dans strftime (http://fr2.php.net/manual/en/function.strftime.php)
 * @param boolean $tampon = vrai si un tampon (timestamp) est desiré au lieu d'une chaîne normalisée.
 * @return mixed  = si $tampon int = timestamp correspondant sinon string = date AAAA/MM/JJ:hh:mm:ss
 *                 false si erreur
 */
function projete_temps ($chaine_tampon,$format,$tampon=FALSE){
	$tdh = strptime($chaine_tampon,$format);
	if (!is_array($tdh)) return(false);
	if ($tampon){
		$temps = mktime($tdh["tm_hour"]
							,$tdh["tm_min"]
							,$tdh["tm_sec"]
							,$tdh["tm_mon"]+1
							,$tdh["tm_mday"]
							,$tdh["tm_year"]+1900
							);
		return($temps);
	} else {
		$annee = ($tdh["tm_year"]+1900)."";
		$mois = cal2car ($tdh["tm_mon"]+1);
		$jour = cal2car($tdh["tm_mday"]);
		$heure = cal2car($tdh["tm_hour"]);
		$minute = cal2car($tdh["tm_min"]);
		$seconde = cal2car($tdh["tm_sec"]);
		return ("[$annee/$mois/$jour:$heure:$minute:$seconde]");
	}

}

/**
 * ajoute_val
 * Enter description here ...
 * @param unknown_type $tab_val
 * @param unknown_type $vals
 */

function ajoute_val (&$tab_val,$vals){
	foreach ($vals as $une_val)
  		{$tab_val[]=normalise($une_val);}
  	return (true);
}
/**
 *
 * Fait d'une valeur une RegExp adaptée/normalisée
 * @param string $v = valeur à traitée
 * @return string = valeur normalisée
 */
function normalise($v){
	$v = preg_replace(":(\\W):", '\\\\$0', $v);
	/*
	if (preg_match("/^\\w/", $v)) $v = "\\b".$v;
	if (preg_match("/\\w\$/", $v)) $v = $v."\\b";
*/
	return ("/$v/");
}

function ecrit_ligne_res($cle,$val) {
	global $fres,$cptecrites;
	$l = $cle.$val."\n";
	if ($fres) gzwrite($fres, $l);
	else  print $l;
	$cptecrites++;
}



function nom_ext_mem ($cle){
	return ("tmpirl_".$cle);
}
function termine (){
	global $tab_tri,$ext_mem;
	if ($ext_mem) {
		foreach ($tab_tri as $cle=>$handle){
			unlink (nom_ext_mem($cle));
		}
	}
}

function test_double_definition_col ($col_source,$col_ref){
	$echo_test="";
	global $col_a_tester;
	if ($col_source && in_array($col_source,$col_a_tester) ) {
		$echo_test.= test_double_definition_col_mess('colCle2',$col_source);
	}
	global $col_cle_referentiel;
	if ( $col_ref && in_array($col_ref,$col_cle_referentiel) ) {
		$echo_test.= test_double_definition_col_mess('colCleR2',$col_ref);
	}
	global $col_a_injecter;
	if ($col_ref && in_array($col_ref, $col_a_injecter)) {
		$echo_test.= test_double_definition_col_mess('colInj2',$col_ref);
	}
	global $col_horaire;
	if ($col_source && $col_horaire && $col_source==$col_horaire) {
		$echo_test.= test_double_definition_col_mess('colH2',$col_source);
	}
	global  $col_horaire_ref;
	if  ($col_ref && $col_horaire_ref && $col_ref==$col_horaire_ref) {
		$echo_test.= test_double_definition_col_mess('colHR2',$col_ref);
	}

	return ($echo_test);
}

$err = false;$echo_test = "";
// suppression du propre nom :
$moi = array_shift($argv);
if (count($argv)<1) {
	$echo_test = message('argInv',implode(' ',$argv));
	$err=true;
}

// ... et du fichier source nécessaire
$sources=array(); $references=array(); $fic_res="";$fic_resr="";$fic_rej="";$fic_par="";

// liste des colonnes des fichiers traites, contenant la clé avec le referentiel.
$col_a_tester = array();
// colonnes du referentiel testees (contenant la cle commune)
$col_cle_referentiel = array();
// colonnes du referentiel a injecter dans les fichier traites
$col_a_injecter = array(); $defaut_col_injectee = array();
// colonne d'horadatage des fichiers a traiter
$col_horaire = ""; $format_horaire="";
// idem dans le referentiel.
$col_horaire_ref=""; $format_horaire_ref = "";

$ligne_header=''; $mod_col = 'num'; $conv_colnum = array();

$ligne_header_referentiel='';$mod_col_referentiel = 'num'; $conv_colnum_referentiel = array();

$maxval=$tmax= -1; $en_col ='';$sep=$sepr="";$glu="";
$test=$emploi=false;

$forcedef = false;

// Analyse de la ligne de commande :

$prochain= ""; $est_vrai_ref=false;


foreach ($argv as $v_arg){

	$un_arg=$v_arg;
	$fut_signe = substr($v_arg,0,1);
	if ( $fut_signe=='-' || $fut_signe=='+') {
		$un_arg = substr($v_arg, 1);
		if (!in_array($v_arg, $parametres_possibles)) {
			$err=true;
			$echo_test .= message ('ArgINC',$v_arg);
			continue;
		}
		if ($test) echo (message('par=',$un_arg));
		switch ($un_arg) {
			case 'aide':
			case 'help' :
			case 'h' :
				$emploi = true;
				$prochain = "";
				break;
			case "test" :
				$test=true;
				$prochain = "";
				break;
			case 'forcedef' :
				if ($fic_rej) {
					$echo_test .= message('forcedefRej',$fic_rej);
					$err=true;
				}
				$forcedef=true;
				$prochain = "";
				break;
			case 'max':
			case 'tmax':
			case 'sep':
			case 'sepr' :
			case 'glu':
			case 'colt' :
			case 'col' :
			case 'colh' :
			case 'src':
			case 'res':
			case 'resr':
			case 'rej' :
			case 'ref':
			case 'refv':
			case 'par' :
			case 'xtrt' :
				$prochain=$un_arg;
				break;
			case 'hd' :
			case 'hd1':
	  			$ligne_header=$fut_signe;
	  			if ($v_arg=='hd1') $ligne_header.='1';
	  			$mod_col='nom';
	  			$prochain="";
	  			break;
			case 'hdr' :
			case 'hdr1':
	  			$ligne_header_referentiel='-';
	  			if ($v_arg=='hdr1') $ligne_header_referentiel.='1';
	  			$mod_col_referentiel='nom';
	  			$prochain="";
	  			break;
	  		default :
				$err= true;
				$echo_test .= message ('ArgINC',$un_arg);
		}
		continue;
	} else {
		if ($prochain!=""){
			if ($test) echo " $v_arg  pour $prochain\n";
			switch ($prochain) {
					case 'max':
						if (! preg_match("/^\\d+\$/", $un_arg)) {
							$echo_test .= message('maxLigNnEnt',$un_arg);
							$err=true;
						} else {
							$maxval=$un_arg*1;
							$echo_test.=message('maxLig',$maxval);
						}
						$prochain="";
						break;
					case 'tmax':
						if (! preg_match("/^\\d+\$/", $un_arg)) {
							$echo_test .= message('maxTpsNnEnt',$un_arg);
							$err=true;
						} else {
							$tmax=$un_arg*1;
							$echo_test.=message('maxTps',$tmax);
						}
						$prochain="";
						break;
					case 'sep':
						$sep = trim($un_arg,'"');
						$echo_test.=message('sep=',$sep);
						$prochain="";
						break;
					case 'sepr':
						$sepr = trim($un_arg,'"');
						$echo_test.=message('sepr=',$sepr);
						$prochain="";
						break;
					case 'glu':
						$glu = trim($un_arg,'"');
						$echo_test.=message('glu=',$glu);
						$prochain="";
						break;
					case 'res':
					case 'resr':
					case 'rej' :
						$casef = message ('casef');
						$cas = $casef[$prochain];
						if (! (substr($un_arg,-3)==".gz")) $un_arg.='.gz';
						if (in_array($un_arg,$sources)|| in_array($un_arg,$references)
							|| $fic_par==$un_arg
							|| $un_arg==$fic_res || $un_arg==$fic_resr|| $un_arg==$fic_rej ) {
							$echo_test .= message ('FicDesigne2',$cas);
							$err=true;
						} elseif ( ($fic_res && $prochain=='res')
								 ||($fic_resr && $prochain=='resr')
								 ||($fic_rej && $prochain=='rej')
								 ) {
							$echo_test .= message('2FicCas',$cas);
							$err=true;
						} elseif (file_exists($un_arg)){
							$echo_test .= message('FicExist',$un_arg);
							$err=true;
						} else {
							if ($prochain=='res'){	$fic_res = $un_arg;}
							elseif  ($prochain=='resr'){	$fic_resr = $un_arg;}
							else {	$fic_rej = $un_arg;}
							$echo_test.= message('EtatFicRes',array($cas,$un_arg));
						}
						$prochain="";
						break;
					case 'src':
					case 'ref':
					case 'refv':
						if ($un_arg==$fic_res ||  $un_arg==$fic_resr || $un_arg==$fic_par
							|| $un_arg==$fic_rej
							|| in_array($un_arg, $sources) || in_array($un_arg, $references)
							) {
							$echo_test .= message ('FicDesigne2f',$un_arg);
							$err=true;
						} elseif (!file_exists($un_arg)) {
							$echo_test .= message ('FicInex',$un_arg);
							$err=true;
						} else {
							if ($prochain=='src'){
								$sources[] = $un_arg;
								$echo_test.= message ('FicSrc',$un_arg);
							} else {
								if ($prochain=='ref') {
									if ($est_vrai_ref){
										$echo_test .= message('ref+refv');
										$err=true;
									} else {
										$est_vrai_ref=false;
										$echo_test.= message ('FicRef',$un_arg);
									}
								} else {
									if ($references){
										$echo_test .=  (!$est_vrai_ref) ?
													 message('ref+refv'):
													 message ('refvSeul');
										$err=true;
									} else {
										$est_vrai_ref=true;
										$echo_test.= message ('FicRef',$un_arg);
									}
								}
								if (!$err) $references[] = $un_arg;
							}
						}
						break;
					case 'xtrt':
						if ($un_arg==$fic_res ||  $un_arg==$fic_resr || $un_arg==$fic_par
							|| in_array($un_arg, $sources) || in_array($un_arg, $references)
							) {
							$echo_test .= message ('2FicTrait',$un_arg);
							$err=true;
						} else {
							include_once ($un_arg);
							//$includes[] = $un_arg;
							$echo_test.= message ('FicTrait',$un_arg);
						}
						break;
					case 'par':
						if ($un_arg==$fic_res ||  $un_arg==$fic_resr || $un_arg==$fic_par
							|| in_array($un_arg, $sources) || in_array($un_arg, $references)
							) {
							$echo_test .= message ('2FicPar',$un_arg);
							$err=true;
						} elseif (!traite_fichier_parentheses ($un_arg)) {
							$echo_test .= message ('FicParInv',$un_arg);
							$err=true;
						} else {
							$echo_test.= message ('FicPar',$un_arg);
						}
						break;
					case 'colt' :
						$t_un_arg = explode(',', $un_arg);
						if (count($t_un_arg)!=2) {
							if ($references && !$est_vrai_ref || $col_cle_referentiel){
								$echo_test.= message ('ColAssocinv',$un_arg);
								$err=true;
								break;
							}
							$col_source = $t_un_arg[0];
							$col_ref="";
							$est_vrai_ref=true;
						} else {
							if  ($est_vrai_ref) {
								$echo_test.= message ('ColAssocinv',$un_arg);
								$err=true;
								break;
							}
							$col_source = $t_un_arg[0];
							$col_ref=$t_un_arg[1];
						}

						if ($mess= test_double_definition_col ($col_source,$col_ref)){
							$echo_test.=$mess;$err=true;break;
						}
						$col_a_tester[]=$col_source; $conv_colnum[$col_source]=-1;
						if ($col_ref) {
							$col_cle_referentiel[]=$col_ref;
							$conv_colnum_referentiel[$col_ref]=-1;
							$echo_test.= message ('ColAssoc',array($col_source,$col_ref));
						}
						break;
					case 'colh' :
						if ($col_horaire || $col_horaire_ref) {
							$echo_test.= message('2ColH',$un_arg);
							$err=true;
							break;
						}
						$colhr=$un_arg;
						$pref = $colhs= "";
						while ($colhr){
							$t_un_arg= explode(',', $colhr);
							$colhs.= $pref.$t_un_arg[0];
							$colhr=(count($t_un_arg)>1)?$t_un_arg[1]:"";
							$pref=',';
							if (substr($colhs, -1)!='%') break;
						}
						if (! preg_match("/^([^:]+):(.+)\$/", $colhs,$matches_s)){
							$echo_test.= message('FormColHInv',$colhs);
							$err=true;
							break;
						}
						if (!$colhr){
							if  ($est_vrai_ref) {
								$echo_test.= message ('ColAssocinv',$un_arg);
								$err=true;
								break;
							}
							if ($references && !$est_vrai_ref || $col_cle_referentiel){
								$echo_test.= message ('ColAssocinv',$un_arg);
								$err=true;
								break;
							}
							$col_ref="";

						} else {
							if (! preg_match("/^([^:]+):(.+)\$/", $colhr,$matches_r)) {
								$echo_test.= message('FormColHInv',$colhr);
								$err=true;
								break;
							}
							$col_ref=$matches_r[1];
							$format_horaire_ref = $matches_r[2];
						}
						$col_source=$matches_s[1];
						$format_horaire=$matches_s[2];
						if ($mess= test_double_definition_col ($col_source,$col_ref)){
							$echo_test.=$mess; $col_horaire=$col_horaire_ref="";
							$err=true;break;
						}
						$col_horaire = $col_source;
						$conv_colnum[$col_horaire]=-1;
						if ($col_ref){
							$col_horaire_ref = $col_ref;
							$conv_colnum_referentiel[$col_horaire_ref]=-1;
							$echo_test.= message('ColHAssoc',array($col_horaire ,$col_horaire_ref));
						}
						break;
					case 'col' :
						$t_arg = explode (":",$un_arg);
						$n = count($t_arg);
						if ($n==1){
							$defaut=NULL;
						} elseif ($n==2){
							$defaut=$t_arg[1]; $un_arg=$t_arg[0];
						} else {
							$err=true;
							$echo_test.= message ('PlsrsDefauts',$un_arg);
							break;
						}
						if (preg_match("/[^0-9]/", $un_arg)) { //le nom de col comporte un car. non numérique
							$mod_col='nom';
							$col=$un_arg;
						} else {
							$col=$un_arg*1;
						}
						if ($mess= test_double_definition_col ('',$col)){
							$echo_test.=$mess; $err=true; break;
						}
						$conv_colnum_referentiel[$col]=-1;
						$col_a_injecter[]=$col;
						$defaut_col_injectee[$col]=$defaut;
						$echo_test.= message ('ColInj',$un_arg);
						break;
					default :
						$err = true;
						$echo_test .= message ('ParamInv',array($un_arg,$prochain));

				}
			} else {
				$err = true;
				$echo_test .= message ('ParamInc',array($un_arg));
			}

		}
} // fin analyse ligne de commande

if ($emploi) {
	montre_usage() ; exit();
}


if ($test) error_reporting(15);


// séparateurs par défaut au besoin, en entrée et en sortie
$sepaff=$sep;$gluaff=$glucom=$sep;
if (!$sep) $sep='s';
if (!$glu)$glu= $sep;
if (preg_match ("/\\w/",$glu)) {
	$echo_test .= message ('NvGlu',$glu);
	switch ($glu){
		case 't': $glu="\t";break;
		case 'r': $glu="\r";break;
		case 'n': $glu="\n";break;
		default : $glu=" ";break;
	}
}
$sepraff = $sepr;
if (!$sepr) $sepr='s';

if (!$col_a_tester || !$col_cle_referentiel || !$col_a_injecter) {
	$err=true;
	$echo_test .= message ('Def_colt+col');
}
if ($forcedef && in_array(NULL, $defaut_col_injectee)) {
	$err=TRUE;
	$echo_test .= message ('DefautOblige');
}

if (!$references) {
	$err=true;
	$echo_test .= message ('refOblige');
}

if ($mod_col_referentiel=='nom') {
		/* Il faut un format soit dans la commande ... */
	if ( !$ligne_header_referentiel) {
		$echo_test .= message('hdrOblige');
		$err=true;
	}
		/* Homogénéïsation de la forme du tableau des colonnes particulierement utiles. */
} else {
	foreach ($conv_colnum_referentiel as $nom=>$nocol){
		$conv_colnum_referentiel[$nom]=$nom;
	}
	$ligne_header_referentiel=false;
}

if ($mod_col=='nom') {
		/* Il faut un format soit dans la commande ... */
	if ( !$ligne_header) {
		$echo_test .= message ('hdOblige');
		$err=true;
	}
		/* Homogénéïsation de la forme du tableau des colonnes particulierement utiles. */
} else {
	foreach ($conv_colnum as $nom=>$nocol){
		$conv_colnum[$nom]=$nom;
	}
	$ligne_header=false;
}

if ($err) {
	montre_usage("ERR: ".$echo_test); exit();
}
/*
 *  Affichage du mode de fonctionnement si test demandé.
 */
$echo_etat = "";
if ($test || $err) {
		if ($sepaff!='') $echo_etat.= message ('sepEst',$sepaff);
		if ($sepraff!='') $echo_etat.= message ('seprEst',$sepraff);
		if ($maxval>0) $echo_etat.= message ('maxLigEst',$maxval);
		$echo_etat .= message ('nbColsCle',count($col_a_tester));
		if ($fic_res)
			$echo_etat.= message ('FicResEst',$fic_res);
		$echo_etat .= message ('nbColInj',count($col_a_injecter));
}
if ($test)
	fprintf(STDERR, "%s",$echo_etat."\n") ;

if ($tmax>0) {
	$tmax+=time();
}

$total_memo = 0;
$max_atteint=false;
if (!$est_vrai_ref){
	/*
	 * Construction du referentiel.
	 */

	$interm1 = nom_ext_mem('1');
	/*
	 * ===============================================================================
	 * Lecture des fichiers référence - création d'un fichier intermédiaire a trier
	 * ===============================================================================
	 */
	$f_interm = fopen($interm1, 'w');
	$a_ajouter_header="";
	if ($ligne_header=='+') {
		if ($ligne_header_referentiel)
			$a_ajouter_header=$glu.implode($glu,$col_a_injecter);
		else {
			foreach ($col_a_injecter as $num_col){
				$a_ajouter_header.=$glu."refCol".$num_col;
			}
		}
		$a_ajouter_header .="\n";
	}
	$cptligref=0;
	foreach ($references as $source) {

	// Ouverture des fichiers source et résultat :
		if (!($ps = gzopen($source, 'r'))) die (message ('ErrOuvRef',$source));
		$cptlignefic = 0;
		$err = false;
		while (($ligne = gzgets($ps))!==false) {
			if ($tmax>0 && time()>$tmax) {
				$max_atteint=true;
				fprintf(STDERR, "%s",message('>TpsMax'));
				break;
			}
			$cptlignefic++;
			$cptligref++;
			if (function_exists('a_lecture_ligne_ref')){
				$ligne = a_lecture_ligne_ref($ligne);
				if (!$ligne) continue;
			}

			$tab_ligne = explode_lig ($ligne,$sepr,$limites_valeurs);
			if ($cptlignefic==1 && $ligne_header_referentiel){
				if ($ligne_header_referentiel=='i') continue;
				$echo_err="";
				if (!traite_ligne_header ($tab_ligne,$ligne_header_referentiel,$conv_colnum_referentiel)) {
					fprintf(STDERR, message ('ERRHdRef',array($source,$echo_err)));
					$err=true;break;
				}
				$echo_err .= converti_col_val ($col_cle_referentiel,$conv_colnum_referentiel);
				$echo_err .= converti_col_val ($col_a_injecter,$conv_colnum_referentiel);
				$echo_err .= converti_col_index($defaut_col_injectee, $conv_colnum_referentiel);
				if ($col_horaire_ref)
					$echo_err .= converti_col_val ($col_horaire_ref,$conv_colnum_referentiel);
				if ($echo_err) {
					fprintf(STDERR, message ('ERRHdRef',array($source,$echo_err)));
					$err=true;break;
				}
				continue;
			}
			$a_tester = "";
			foreach ($col_cle_referentiel as $col) {
				if ($col > count($tab_ligne)){
					$echo_test= message ('ColrAbs',array($source,$cptligref,$col));
					fprintf(STDERR, "%s",$echo_test) ;
					$err = true;
					continue;
				} else
					$a_tester.="\t".normalise_cle($tab_ligne[$col-1]);
			}
			$a_tester = substr($a_tester,1);
			if ($col_horaire_ref>0) {
				if ($col_horaire_ref > count($tab_ligne)){
						$echo_test=message ('ColrAbs',array($source,$cptligref,$col_horaire_ref));
						fprintf(STDERR, "%s",$echo_test) ;
						$err = true;
						continue;
					} else {
						$vcol = $tab_ligne[$col_horaire_ref-1];
						if (($h = projete_temps($vcol, $format_horaire_ref))){
							$a_tester.=":#:".$h;
						} else {
							$echo_test=message ('DtHeurInv',array($source,$cptligref,$vcol));
							fprintf(STDERR, "%s",$echo_test) ;
							$err = true;
						}
					}
			}
			if ($err) continue;
			$a_memo = "";
			foreach ($col_a_injecter as $col) {
				$a_memo .= "\t";
				if (($col > count($tab_ligne))
					|| $tab_ligne[$col-1]==""
					){
					if ($defaut_col_injectee[$col]!=NULL) {
						$a_memo .= $defaut_col_injectee[$col];
					} elseif ($test) {
						$echo_test=message('ATTColInjAbs',array($source,$cptligref, $col));
						fprintf(STDERR, "%s",$echo_test) ;
					}
				} else {
					$a_memo .= $tab_ligne[$col-1];
				}
			}
			$a_memo=substr($a_memo,1);
			fwrite($f_interm, "$a_tester:#:$a_memo\n");
		}
		gzclose($ps);
		if ($max_atteint||$err) break;
	}
	fclose($f_interm);

	if ($err){
		unlink($interm1);
		exit();
	}
	if ($test){
		fprintf(STDERR, message ('FinRef',$cptligref));
	}


	/*
	 * ==========================================
	 *       Tri du fichier intermédiaire
	 * ==========================================
	 */
	$interm2 = nom_ext_mem('2');
	exec ("sort $interm1 >$interm2");
	unlink ($interm1);
	/*
	 * ===========================================================================
	 * Relecture du fichier trie et création du référentiel en mémoire / en fichier
	 * ===========================================================================
	 */


	if ($fic_resr && !$est_vrai_ref) {
		if (!($f_resr = gzopen($fic_resr, 'w'))){
			unlink($interm2);
			die (message ('ImpRefRes',$fic_resr));
		}
		if ($test) fprintf(STDERR, message('RefResOuv',$fic_resr));
	} else {
		$f_resr = NULL;
	}
	if ($test){
		fprintf(STDERR, message('CreeRefRes'));
	}
} else {
	$interm2 = $references[0];
}
$f_interm2=fopen ($interm2,'r');
$referentiel=array();
$cptecrites=0;
$cle_cou="";$horaire_cou="";$valeur_cou="";
while (($ligne = fgets($f_interm2))!==false) {
//		$tab_ligne=explode("\t", $ligne);
//		$taille_cle = count($col_cle_referentiel);
//		$tab_cle = array_splice($tab_ligne, 0,$taille_cle);
//		$cle = implode("\t",$tab_cle);
//		$horaire = ($col_horaire_ref>0)?array_shift($tab_ligne):"";
//		$valeur = implode ("\t",$tab_ligne);

	$tab_ligne=explode(":#:", $ligne);
	$cle = $tab_ligne[0];
	if (count ($tab_ligne)>2){
		$horaire = $tab_ligne[1];
		$valeur = $tab_ligne[2];
	} else {
		$horaire = "";
		$valeur = $tab_ligne[1];
	}

	$diff = false;
	if ($cle!=$cle_cou) {
			$cle_cou=$cle; $horaire_cou=$horaire;$valeur_cou=$valeur;
			$referentiel[$cle_cou]=($col_horaire_ref>0)?
									array($horaire_cou=>$valeur_cou):
									$valeur_cou;
			$diff=true;
	} elseif ($col_horaire_ref>0 && $horaire!=$horaire_cou && $valeur!=$valeur_cou) {
			$horaire_cou=$horaire; $valeur_cou=$valeur;
			$referentiel[$cle_cou][$horaire_cou]=$valeur;
			$diff=true;
	}
	if ($diff) {
			if ($f_resr) {
				gzwrite($f_resr, $ligne);
				$cptecrites++;
			}
	} elseif ($valeur!=$valeur_cou) {
			$mess = message ('2ValInj',$cle_cou);
			if ($horaire_cou) $mess.=" a $horaire_cou";
			$mess.=".\n";
			fprintf(STDERR, $mess);
	}
}
fclose($f_interm2);
if (!$est_vrai_ref)	{
	unlink ($interm2);
	if ( $f_resr ){
		gzclose($f_resr);
		if ($test) fprintf(STDERR, message ('nbLigRefRes',array($fic_resr,$cptecrites)));
	}
}
if ($test) {
		fprintf(STDERR, message ('nbCles',count($referentiel))) ;
}

$defaut_absolu = "";
if ($forcedef){
		foreach ($col_a_injecter as $col) {
			$defaut_absolu .= "\t";
			if ($defaut_col_injectee[$col]!=NULL) {
				$defaut_absolu .= $defaut_col_injectee[$col];
			}
		}
		$defaut_absolu=substr($defaut_absolu,1)."\n";
}

/*
 * ===============================================================
 *             Traitement de l'injection :
 * ===============================================================
 */

if (!($sources)) {
	$sources=array('php://stdin');
}

if ($fic_res!="") {
	if ( !$fres=gzopen($fic_res, 'wb')) die (message ('ImpRes',$fic_res));
} else $fres=NULL;
if ($fic_rej) {
	if (!($f_rej = gzopen($fic_rej, 'w'))){
		die (message('ImpRej', $fic_resr));
	}
} else {
	$f_rej = NULL;
}

$cptlues= 0; $cptecrites=0;
$max_atteint=false;
foreach ($sources as $source) {

// Ouverture des fichiers source et résultat :
	if (!($ps = gzopen($source, 'r'))) die (message('ImpOuvSrc',$source));
	$cptlignefic = 0;
	$err = false;
	while (($ligne_or = gzgets($ps))!==false) {
		if ($maxval>0 && $cptlues>=$maxval) {$max_atteint=true; break;}
		if ($tmax>0 && time()>$tmax) {
			$max_atteint=true;
			fprintf(STDERR, "%s",message('>TpsMax')) ;
			break;
		}
		$cptlues++;
		$cptlignefic++;
		$ligne=trim($ligne_or);
		if (function_exists('a_lecture_ligne')){
			$ligne = a_lecture_ligne($ligne);
			if (!$ligne) continue;
		}

		$tab_ligne = explode_lig ($ligne,$sep,$limites_valeurs);

		if ($cptlignefic==1 && $ligne_header){
			if ($ligne_header=='i') continue;
			$echo_err="";
			if (!traite_ligne_header ($tab_ligne,$ligne_header,$conv_colnum)) {
				fprintf(STDERR, message('hdInv',array($source,$echo_err)));
				$err=true;break;
			}
			$echo_err .= converti_col_val ($col_a_tester,$conv_colnum);
			if ($col_horaire)
				$echo_err .= converti_col_val ($col_horaire,$conv_colnum);
			if ($echo_err) {
				fprintf(STDERR, message('hdInv',array($source,$echo_err)));
				$err=true;break;
			}
			if ($a_ajouter_header){
				$ligne=implode($glu, $tab_ligne).$a_ajouter_header;
				if ($fres) gzwrite($fres, $ligne);
				else  print $ligne;
				$cptecrites++;
			}
			continue;
		}
		$a_tester = "";
		foreach ($col_a_tester as $col) {
			if ($col > count($tab_ligne)){
				if ($test) {
					fprintf	(STDERR, "%s"
							,message('ColCleAbs'
									,array($source,$cptlignefic,$col)
									)
							);
				}
				$a_tester = "";
				break;
			}
			$a_tester.="\t".$tab_ligne[$col-1];
		}
		if (!$a_tester) {
			if ($f_rej) {
				gzwrite($f_rej, $ligne_or);
			}
			continue;
		}
		$a_tester = substr($a_tester,1);
		if (!isset($referentiel[$a_tester])) {
			if ($defaut_absolu) $valeur_cou=$defaut_absolu;
			else {
				if ($test) {
					$echo_test=message('CleAbsRef'
									,array($source,$cptlignefic,$a_tester)
									);
					fprintf(STDERR, "%s",$echo_test) ;
				}
				if ($f_rej) {
					gzwrite($f_rej, $ligne_or);
				}
				continue;
			}
		} elseif ($col_horaire) {
			if ($col_horaire > count($tab_ligne)){
				if ($test) {
					$echo_test=message ('ColHAbs',array($source,$cptlignefic));
					fprintf(STDERR, "%s",$echo_test) ;
				}
				continue;
			}
			$heure = projete_temps($tab_ligne[$col_horaire-1], $format_horaire);
			$valeur_cou = "";
			foreach ($referentiel[$a_tester] as $horaire=>$valeur){
				if (!$valeur_cou) {
					$valeur_cou=$valeur;
				}
				else {
					if ($horaire>$heure) break;
					$valeur_cou=$valeur;
				}
			}
		} else $valeur_cou=$referentiel[$a_tester];
		$ligneres=implode($glu, $tab_ligne).$glu.str_replace("\t", $glu, $valeur_cou);
		if ($fres) gzwrite($fres, $ligneres);
		else  print $ligneres;
		$cptecrites++;
	}
	gzclose($ps);
	if ($err||$max_atteint) break;
}

if ($fres) {
	gzclose($fres);
}
if ($f_rej){
	gzclose($f_rej);
}
if ($test) {
	fprintf(STDERR, message ('Conc',array($cptlues,$cptecrites)));
}

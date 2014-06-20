<<<<<<< HEAD
=======
#!/usr/bin/php
>>>>>>> ee89e4d44c2ffe383bb7c5ba0726dd87b67865e9
<?php
/*
 * Implantation :
error_reporting(E_ALL);
$ce_repertoire = dirname(__FILE__);
$racinePhys  = preg_replace (":/traitements\$:","",$ce_repertoire);
$libs = $racinePhys."/libs";
 */

/**
 * 
 * tri_ezp_logs.php V 0.1
 * Script qui sert à trier les ligne de plusieurs fichiers de logs (CSV) ou de l'entrée standard (utilisable
 * en pipe). Le tri s'effectue sur une ou plusieurs colonne des données source et cette clé est présente 
 * en tête de chaque ligne du résultat. 
 * Les lignes peuvent être complétées par :
<<<<<<< HEAD
- des colonnes indiquée (-col). Il y aura autant de ligne de même clé que de valeurs pour ces colonnes.
- la liste des couples fichier_source:numero_ligne où se trouve la clé (option -pos).  
- le nombre de lignes de l'original contenant la clé en tête (option -cpt)
- en l'absence de -col, -pos et -cpt, l'ensembles des colonnes inutilisées pour le tri.  
=======
- des colonnes indiquée (-col). Il y aura autant de ligne de même clé que de valeurs pour ces colonnes. 
- le nombre de lignes de l'original contenant la clé en tête (-cpt)
- en l'absence de -col et -cpt, la liste des couples fichier_source:numero_ligne où se trouve la clé.  
>>>>>>> ee89e4d44c2ffe383bb7c5ba0726dd87b67865e9
Si aucun fichier n'est indiqué en source (-src), c'est l'entrée standard qui est utilisée.
Si aucun fichier n'est indiqué en résultat (-res), c'est la sortie standard qui est utilisée.
Si aucun fichier n'est indiqué pour les lignes non intéressante (-rej), elles sont oubliées.
 * 
 *
 *  V0.1 : 
 *  	- utilisation de l'entrée standard pour un usage en pipe possible.
 * performance : 2s pour 17970 lignes triees donnant 3613 clés formees de deux de ses colonnes.
 * V0.2 :
 *      - possibilité d'utiliser des noms de colonnes comme dans les autres outils. 
 */
$parametres_possibles=array('-aide','-help','-h','-xtrt','-max','-tmax'
							,'-hd','+hd','-hd1','+hd1'
							,'-sep','-glu','-res','-colt','-col','-src','-par'
<<<<<<< HEAD
							,"-test" ,"+test" ,"-cpt","-pos","-multi",'-u','-uk'); 
=======
							,"-test" ,"+test" ,"-cpt","-multi",'-u','-uk'); 
>>>>>>> ee89e4d44c2ffe383bb7c5ba0726dd87b67865e9
/* Tableau  de couple de caractères utilisés en début et fin de valeur unitaire 
 * notamment afin que les valeurs puissent contenir des caratères séparateur
 * ex. cuple [] pour le séparateur , 
 * [Auteur, Marc],[Titre,à virgule],Flammarion,1957 
 */
require_once "$libs/parentheses.lib.php";
set_default_parentheses();

require_once "$libs/logs.lib.2.php";

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
	return ("/$v/");
}

/**
 * Traitement de la ligne de heaedr qui consiste à établir un lien entre nom de colonne et sa position. 
 * Le tableau $conv_colnum est construit lors de l'analyse le commande avec toutes les colonnes désignées.
 * @param string $ligne : chaîne (ligne d'entête ) contenant le format des lignes
 * @return boolean : vrai si OK. sinon étblit la chaîne globale $echo_err.  
 */
function  traite_ligne_header ($ligne){
	global $glu,$sep, $mod_col,$ligne_header,$ligne_header_res,$echo_err,$cptecrites;
	global $conv_colnum,$tri_col,$cols,$ecrit_par_col;
	global $cptlues; 
	  /*
	   * Si désignation symbolique, conversion des noms de colonnes en n°
	   */
	if ($ligne_header == 'i') return (true);
	$tab_ligne = explode_lig ($ligne, $sep,get_parentheses());
	for ($i=1;$i<=count($tab_ligne);$i++){
		$ipos=$i-1; $nom = $tab_ligne[$ipos];
		if (!isset($conv_colnum[$nom])) continue;
		if ($conv_colnum[$nom]>0) {
			$echo_err = traite_ligne_header_mess ('2DefCol',$nom);
		} else 
			$conv_colnum[$nom]=$i;
	}
	foreach ($conv_colnum as $un_nom=>$nocol){
		if ($nocol<=0) {
			$echo_err.=traite_ligne_header_mess("NomInv",$nom);
		}
	}
	$ligne_header_res = implode($glu, $tri_col).$glu.implode($glu, $cols);
	if (!$echo_err){		
		$echo_err .= converti_col_val ($tri_col,$conv_colnum);
		$echo_err .= converti_col_val ($cols,$conv_colnum);
	}
	if ($echo_err) {
		$echo_err .= traite_ligne_header_mess('EntAna',$ligne);
			return(false);
	}
    
	/*
	 *  S'il s'agit d'une entete, écriture au besoin de la ligne en resultat 
	 *  (sans la traiter comme les suivantes) 
	 */			

	if (! ($ligne_header=='+' || $ligne_header=='+1')) $ligne_header_res="";
	/*
	 * Ajustement de $ligne_header pour la suite du traitement : 
	 * Non réplication et présence ou non de cette ligne en début des fichiers suivants à analyser.
	 */
	if (substr($ligne_header,-1)=='1') $ligne_header=false;
	else $ligne_header='i'; 
	return(true);				
				
}

function nom_ext_mem ($cle){
	return ("tmptel_".$cle);
}

$err = false;$echo_test = ""; 
// suppression du propre nom :
$moi = array_shift($argv);
if (count($argv)<1) {
	$echo_test = message ('ComInv',implode(' ',$argv));
	$err=true;
}

// ... et du fichier source nécessaire 
$sources=array(); 
$tri_col = $cols=array(); 
 $en_col ='';$sep=$glu="";$maxval=$tmax=$mmax=0;
<<<<<<< HEAD
$test=$bavard=$emploi=false; $mem_cpt_pos='';
=======
$test=$bavard=$emploi=false; $mem_cpt=false;
>>>>>>> ee89e4d44c2ffe383bb7c5ba0726dd87b67865e9
$fic_res=$fic_par=""; // $includes=array();

// traitement du header :
$mod_col = 'num'; $conv_colnum = array(); $ligne_header=''; 

$mode='mono'; $mode_u = '';
// Analyse de la ligne de commande :

$prochain= "";  


foreach ($argv as $v_arg){
	$un_arg=$v_arg;
	$fut_signe = substr($v_arg,0,1); 
	if ( $fut_signe=='-' || $fut_signe=='+' ) {
		$un_arg = substr($v_arg, 1);
		if (!in_array($v_arg, $parametres_possibles)) {
			$err=true;
<<<<<<< HEAD
			$echo_test .= message ('ArgInc',$v_arg);
=======
			$echo_test .= message ('ArgInc',array($v_arg,implode(',',$parametres_possibles)));
>>>>>>> ee89e4d44c2ffe383bb7c5ba0726dd87b67865e9
			continue;
		}
		if ($bavard) echo message('par=',$un_arg);
		switch ($un_arg) {
			case 'aide':
			case 'help' :
			case 'h' :
				$emploi = true;
				$prochain = "";
				break;
			case "test" : 
				$test=true;
				if ($fut_signe=='+') $bavard=true; 
				$prochain = "";
				break;
			case "cpt" :
<<<<<<< HEAD
				if ($mem_cpt_pos=='p'){
					$err=true;
					$echo_test .= message ('ArgIncomp',array('-cpt','-pos'));
					break;
				} else {
					$mem_cpt_pos='c'; 
					$echo_test.= message ('par_cpt');					
				}
				$prochain = "";
				break;
			case "pos" :
				if ($mem_cpt_pos=='c'){
					$err=true;
					$echo_test .= message ('Inc-cpt+pos');
					break;
				} else {
					$mem_cpt_pos='p'; 
					$echo_test.= message ('par_pos');					
				}
=======
				$mem_cpt=true; 
				$echo_test.= message ('par_cpt');
>>>>>>> ee89e4d44c2ffe383bb7c5ba0726dd87b67865e9
				$prochain = "";
				break;
			case "multi" :
				$mode='multi'; 
				$prochain = "";
				break;
			case 'u' :
				if ($mode_u=='uk') {
					$echo_test .= message ('-u+-uk');
					$err=true;
				}
				$mode_u = 'u';
				$prochain = "";
				break;
			case 'uk' :
				if ($mode_u=='u') {
					$echo_test .= message ('-u+-uk');
					$err=true;
				}
				$mode_u = 'uk';
				$prochain = "";
				break;
			case 'max':  
			case 'tmax':  
			case 'sep': 
			case 'glu' :
			case 'res':
			case 'colt' :
			case 'col' :
			case 'src':
			case 'par' :
			case 'xtrt' :
				$prochain=$un_arg; 
				break;
			case 'hd' : 
			case 'hd1': 
	  			$ligne_header=$fut_signe;
	  			if ($v_arg=='hd1') $ligne_header.='1';
	  			$mod_col='nom';
	  			$prochain=$signe="";
	  			break;
			default :
				$err= true;
				$echo_test .= message ('ArgInc',$un_arg);
		}
		continue;
	} else {
		if ($prochain!=""){
			if ($bavard) echo (message('VProc',array($v_arg,$prochain)));
			switch ($prochain) {
					case 'max':
						if (! preg_match("/^\\d+\$/", $un_arg)) {
							$echo_test .= message ('maxInv',$un_arg);
							$err=true;
						} else {
							$maxval=$un_arg*1;
							$echo_test.= message('max=',$maxval); 					
						}
						$prochain="";
						break;
					case 'tmax':
						if (! preg_match("/^\\d+\$/", $un_arg)) {
							$echo_test .= message ('tmaxInv',$un_arg);;
							$err=true;
						} else {
							$tmax=$un_arg*1;
							$echo_test.= message('max=',$tmax); 					
						}
						$prochain="";
						break;
					case 'sep': 
						$sep = trim($un_arg,'"'); 
						$echo_test.=message ('sep=',$sep); 
						$prochain="";
						break;
					case 'glu': 
						$glu = trim($un_arg,'"'); 
						$echo_test.=message ('glu=',$glu); 
						$prochain="";
						break;
					case 'res': 
						$cas = 'resultat';
						if (! (substr($un_arg,-3)==".gz")) $un_arg.='.gz';
						if (in_array($un_arg,$sources)|| $fic_par==$un_arg) {
							$echo_test .= message('res#autres');
							$err=true;
						} elseif ($fic_res) {
							$echo_test .= message ('1Res',$un_arg);
						} else {
								$fic_res = $un_arg;
							$echo_test.= message ('res=',$un_arg);
						} 
						$prochain="";
						break;
					case 'src':
						if ($un_arg==$fic_res ||  $un_arg==$fic_par  
							|| in_array($un_arg, $sources)
							) {
							$echo_test .= message ('src#autres',$un_arg);
							$err=true;
						} else {
							$sources[] = $un_arg; 
							$echo_test.= message ('src=',$un_arg);
						} 
						break;
					case 'xtrt':
						if ($un_arg==$fic_res ||  $un_arg==$fic_par  
							|| in_array($un_arg, $sources)
							) {
							$echo_test .= message ('xtrt#autres',$un_arg);
							$err=true;
						} else {
							include_once ($un_arg);
							$echo_test.= message ('xtrt=',$un_arg);
						} 
						break;
					case 'par':
						if ($un_arg==$fic_res || $un_arg==$fic_par  
							|| in_array($un_arg, $sources)
							) {
							$echo_test .= message('par#autres',$un_arg);
							$err=true;
						} else {
							$fic_par = $un_arg; 
							$echo_test.=message('par=',$un_arg);
						} 
						break;
					case 'colt' :
					case 'col' :
						if (preg_match("/[^0-9]/", $un_arg)) { //le nom de col comporte un car. non numérique
							$mod_col='nom';
						}
						$conv_colnum[$un_arg]=-1;
/*
 * 
						if (! preg_match("/^\\d+\$/", $un_arg)) {
							$echo_test.= "No colonne invalide $un_arg\n";
							$err=true;
							break;
						}						
						$un_arg=$un_arg*1;
*/						
						if (in_array($un_arg, $tri_col) || in_array($un_arg, $cols) ) {
							$echo_test.= message('DbleCol',$un_arg);
							$err=true;
							break;
						}
						if ($prochain=='colt') {
							$tri_col[] = $un_arg;
							$echo_test.= message('ColTri',$un_arg);
						}
						else { 
							$cols[]=$un_arg;
							$echo_test.= message('ColRes',$un_arg);
						}
						break;
					default :
						$err = true;
						$echo_test .= message ('ParInc',array($prochain,$v_arg));
						
				}  				
			} else {
				$err = true;
				$echo_test .= message('ValSsPar',$un_arg);
			} 
			
		}
} // fin analyse ligne de commande
<<<<<<< HEAD
$allcol=false;
if ($mem_cpt_pos) { 
	if ($mem_cpt_pos=='c' && ($mode=='multi' || $mode_u)) {
		$err=true;
		$echo_test .= message ('-cpt+-multiOU-uk');
	} 
	if ($mode_u=='u'){
		$err=true;
		$echo_test .= message('-uSs-col');
	}
=======

if ($mem_cpt) { 
	if ($mode=='multi' || $mode_u) {
			$err=true;
			$echo_test .= message ('-cpt+-multiOU-uk');
	} 
>>>>>>> ee89e4d44c2ffe383bb7c5ba0726dd87b67865e9
}
if ($mode=='multi'){
	if ($mode_u=='uk') {
			$err=true;
			$echo_test .= message('-multi+-uk');
	} 
<<<<<<< HEAD
} else {
	if ($cols) {
		if ($mem_cpt_pos){
				$err=true;
				$echo_test .= message ('-cpt+-col');
		}
	} else {
		$allcol=true;
	}
=======
}
if ($cols) {
	if ($mem_cpt){
			$err=true;
			$echo_test .= message ('-cpt+-col');
	}
} elseif ($mode_u=='u'){
	$err=true;
	$echo_test .= message('-uSs-col');
>>>>>>> ee89e4d44c2ffe383bb7c5ba0726dd87b67865e9
}
/*
 * Lecture du fichier des parenthèseur
 */
if ($fic_par) {
	$ok = traite_fichier_parentheses($un_arg);
	if (!$ok){
		$echo_test .= message ('FicParInv',$fic_par);
		$fic_par="";
	} else {
			$limites_valeurs= get_parentheses();
	}	
}

// Ouvrage du résultat :

// Affichage du mode de fonctionnement si test demandé.
$echo_etat = "";
if ($test || $err) {
		if ($fic_par) 
			$echo_etat.= message('FicPar=',$fic_par);
		if ($sep!='') message('sep=',$sep);
		else $echo_etat .= message('sepDef');
		if ($maxval>0) 
			$echo_etat.= message('max=',$maxval);
		if ($tmax>0) 
			$echo_etat.= message('tmax=',$tmax);
		if (count($tri_col)) 
			$echo_etat .= message ('ColTri',implode(', ',$tri_col));
		if (count($cols)>0) 
			$echo_etat .= message ('ColRes',implode(', ',$cols));
		if ($fic_res)
			$echo_etat.= message ('res=',$fic_res);
		if ($mode=='multi') 
			$echo_etat.= message ('estMulti');
		if ($mode_u) 
			$echo_etat.= message('est-u');
<<<<<<< HEAD
		if ($mem_cpt_pos=='c')
=======
		if ($mem_cpt)
>>>>>>> ee89e4d44c2ffe383bb7c5ba0726dd87b67865e9
			$echo_etat.= message('est-cpt');
}
if ($emploi) {
	montre_usage() ; exit();
}
if ($err) {
	montre_usage($echo_etat); 
	exit(message ('StopErr',$echo_test));
}
if ($test) echo  "$echo_etat";

// séparateurs par défaut au besoin, en entrée et en sortie
$sepaff=$sep;$gluaff=$glu; 
if (!$sep) $sep='s';
if (!$glu) $glu= $sep;
if (preg_match ("/\\w/",$glu)) {
	switch ($glu){
		case 't': $glu="\t";break;
		case 'r': $glu="\r";break;
		case 'n': $glu="\n";break;
		default : $glu=" ";break;				
	}
}


$cptlues = 0; 
if ($tmax>0) {
	$tmax+=time();
}
$tab_tri = array();

if (!($sources)) {
	$sources[]='stdin';
}

$max_atteint=false;
$ligne_header_res='';

/*
 * Création d'un fichier intermédiaire pour le tri 
 */
$interm1 = nom_ext_mem('1');
$f_interm = fopen($interm1, 'w');
$cpt_fic=0;
foreach ($sources as $source) {
	
// Ouverture des fichiers source et résultat :
//	if (!($ps = gzopen($source, 'r'))) die ("pb ouverture de la source $source");
	if (!ouvre_source($source)) die (message('ImpOuvSrc',$source));
	
	$cptlignefic = 0;
	
	$err = false;	
//	while (($ligne = gzgets($ps))!==false) {
	while (($ligne = lit_source())!==false) {
		$cptlignefic++;
		$marque = $source.':'.$cptlignefic;
		if ($maxval>0 && $cptlues>=$maxval) {$max_atteint=true; break;}
		if ($tmax>0 && time()>$tmax) {
			$max_atteint=true;
			fprintf(STDERR, "%s",message ('TMaxFait',$tmax));
			break;			
		}
		if ($cptlignefic==1 && $ligne_header){
			if (!traite_ligne_header ($ligne)) {
				fprintf(STDERR, "%s",$echo_err) ;
				$echo_err="";
				$err=true;
				break;
			}
			continue;
		} 
		
		$cptlues++;

		if (function_exists('a_lecture_ligne')) 
			$ligne = a_lecture_ligne($ligne);
		
		$tab_ligne = explode_lig($ligne,$sep,get_parentheses());
		if ($bavard && $cptlignefic<10) print $marque."==".implode('|', $tab_ligne)."\n";
		if (count($tri_col)>0) {
			$a_tester = "";
			foreach ($tri_col as $col) {
				$a_tester.=$glu;
				if ($col > count($tab_ligne)){
						$echo_test.= message ('ColCleAbs',array($source,$cptlues,$col)).
							":\n = $ligne\n";
						fprintf(STDERR, "%s",$echo_test) ;
						$echo_test="";
						$err = true;
					continue;
				} else 
					$a_tester.=$tab_ligne[$col-1];
			}
			$a_tester = substr($a_tester,strlen($glu));
		} else $a_tester=$ligne;
<<<<<<< HEAD
		$a_memo = "";
		if (count($cols)>0) {
=======
		if (count($cols)>0) {
			$a_memo = "";
>>>>>>> ee89e4d44c2ffe383bb7c5ba0726dd87b67865e9
			foreach ($cols as $col) {
				$a_memo.= $glu;
				if ($col > count($tab_ligne)){
						$echo_test.= message('ColResAbs',array($source,$cptlues,$col)).
							":\n = $ligne\n";
						fprintf(STDERR, "%s",$echo_test) ;
						$echo_test="";
					//$err = true;
					continue;
				} else {
					$a_memo .=  $tab_ligne[$col-1];
				}
			}
			$a_memo = substr($a_memo,strlen($glu));
<<<<<<< HEAD
		} elseif ($mem_cpt_pos!='') $a_memo=$marque;
		else {
			for ($icol=0;$icol<count($tab_ligne);$icol++){
				$place = $icol+1;
				if (in_array($place, $tri_col)) continue;
				$cols[]=$place;
				$a_memo .= $glu. $tab_ligne[$icol];
			}
			$a_memo = substr($a_memo,strlen($glu));
		}
=======
		} else  $a_memo=$marque;
>>>>>>> ee89e4d44c2ffe383bb7c5ba0726dd87b67865e9
		
		// Revoir pour rejet
		if ($a_tester) {
			fwrite($f_interm, "$a_tester:#:$a_memo\n");
		}
	}
	ferme_source();
	$cpt_fic++;
	if ($err||$max_atteint) break;
} 
fclose($f_interm);
if ($err||$test) {
	fprintf(STDERR, "%s",message('Conc1',array($cpt_fic,$cptlues))) ;
}
$interm2 = nom_ext_mem('2');
exec ("sort $interm1 >$interm2");
unlink ($interm1);
if (!$fic_res) $fic_res='stdout';
if (!$fres = open_result('res', $fic_res)) die (message ('ImpRes',$fic_res));

$f_interm2=fopen ($interm2,'r');
$cptecrites=0;
if ($ligne_header_res){
	puts_result('res', $ligne_header_res);
}
if ($mode=='multi'){
	$ligne_cou = "";$cle_cou = "";
	while (($ligne = fgets($f_interm2))!==false) {
		if ($mode_u && $ligne==$ligne_cou) continue;
		$ligne_cou = $ligne;
		$p = strpos($ligne,":#:");
		$cle=substr($ligne, 0,$p);
		if ($mode_u=='uk' && $cle==$cle_cou) continue;
		$cle_cou=$cle;
		$val = substr($ligne, $p+3);
		$l = $cle.$glu.$val;
		puts_result('res', $l);
		$cptecrites++;
	}
} else {
	$cle_cou = ""; $cpt_val=0; $cumul="";$ligne_cou = "";
	while (($ligne = fgets($f_interm2))!==false) {
		if ($mode_u && $ligne==$ligne_cou) continue;
		$ligne_cou = $ligne;
		$p = strpos($ligne,":#:");
		$cle=substr($ligne,0,$p); 
		if ($mode_u=='uk' && $cle==$cle_cou) continue;
		$val = trim(substr($ligne, $p+3));
		if ($cle_cou!=$cle && $cle_cou!="") {
<<<<<<< HEAD
			if ($mem_cpt_pos=='c') {$cumul =$glu.$cpt_val;}
=======
			if ($mem_cpt) {$cumul =$glu.$cpt_val;}
>>>>>>> ee89e4d44c2ffe383bb7c5ba0726dd87b67865e9
			$l = $cle_cou.$cumul;
			puts_result('res', $l);
			$cptecrites++;
			$cpt_val=0;	$cumul="";
		}
		$cle_cou=$cle;
		$cumul .= $glu.$val; $cpt_val++;
	}
	if ($cumul || $cpt_val) {
<<<<<<< HEAD
		if ($mem_cpt_pos=='c') {$cumul =$glu.$cpt_val;}
=======
		if ($mem_cpt) {$cumul =$glu.$cpt_val;}
>>>>>>> ee89e4d44c2ffe383bb7c5ba0726dd87b67865e9
		$l = $cle_cou.$cumul;
		puts_result('res', $l);
		$cptecrites++;
	}
}
	
close_result('res');
unlink ($interm2);
if ($test) {
	echo $echo_etat;
	echo (message('Conc', array($cptlues ,$cptecrites)));
}


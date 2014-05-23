<?php
$ce_repertoire = dirname(__FILE__);

require_once ($ce_repertoire."/parentheses.lib.php");

/**
 * 
 * Explode GCSV record line to an array of its columns. Using sep and perentheses to parse.
 *  
 * Fonction qui tout en eclatant la ligne sur le séparateur principal, tout en tenant
 * compte des caractères parenthèsants.  
 * Les parenthèses permettent aux valeurs de comporter LE caractère séparateur de colonne. 
 * Ex : si le séparateur est le plus générique : l'espace, on peut avoir comme parenthèses
 * les crochets pour la date soit [AAAA/MM/JJ à HH:mm:ss]
 *   
 * @param string $ligne_entree : ligne à découper 
 * @param char $sep : séparateur de colonnes
 * @param array $col_testees : liste (en clé) des colonnes devant être testées. La colonne C porte un critère
 * 			 si $col_testees[C] existe.
 * @uses global $limites_valeurs : tableau 
 * @return mixed array = tableau des valeurs contenues dans la ligne 
 * 				 string = message d'erreur
 */

function explode_lig ($ligne_entree,$sep,$limites_valeurs=array(),$ote_parentheses=false){
	$ligne_entree=trim($ligne_entree);
	$prem_tab = preg_split('/(\\'.$sep.')/', $ligne_entree,-1,PREG_SPLIT_DELIM_CAPTURE);
	$tab_res = array(); 
	$limit_val=$val_retenue="";
	// analyse de chaque morceau 
	for ($ipos=0;$ipos<count($prem_tab);$ipos+=2) {
		// si on est en cours de valeur parenthésée 
		$morceau=$prem_tab[$ipos];
		if ($limit_val) {
			// si fin de parenthèse mémorisation et init pour la suite
			if (substr($morceau,-$l_limit_val) ==$limit_val) {
				if ($ote_parentheses) $morceau = substr($morceau,0,-$l_limit_val);
				$limit_val="";
			} elseif ($ipos == count($prem_tab)-1){
				$limit_val="";
			}
			if ($ipos>0) $val_retenue.= $prem_tab[$ipos-1];
			$val_retenue.=$morceau;			
			if (!$limit_val){
				$tab_res[]=$val_retenue;
				$val_retenue="";
			} 				
			continue;
		} 
		// on N'est pas dans une valeur parenthésée. On recherche si c'en est le début 
		foreach ($limites_valeurs as $def){
			if (strpos($morceau, $def[0])===0){
				$fin = substr($morceau,-strlen($def[1]));
				if ($ote_parentheses){
					$morceau = substr($morceau,strlen($def[0]));
				}
				if ($fin!=$def[1]) {
					$limit_val=$def[1];
					$l_limit_val=strlen($limit_val);
				} else {
					if ($ote_parentheses) $morceau = substr($morceau,0,-strlen($fin));
				}
				break;
			} 
		}
		if ($limit_val) {
			$val_retenue=$morceau; 
		} else {
			$tab_res[]=$morceau;$val_retenue="";
		}		
	}
	return ($tab_res);
}

/* 
 * ------------------------------------------------------------------------------
 * Header and named columns 
 * Traitement des colonnes nommées et des headers
 *  ------------------------------------------------------------------------------ 
 */

/**
 * Returns an indexed table from a hash table $converted by conversion of
 *  the names used to hash into the column position integer
 *    
 * Pour toutes les tables indexée par un identifiant de colonne, transforme un nom 
 * identifiant en un numéro de colonne
 *  @param array $converted:  Table to convert indexing mode - 
 *  	$tab_indexee  tableau dont on convertit les index qui sont des noms par le n° de la colonne
 *  @param array $conversion:  conversion hash table (name=>column number) -
 *  	$tab_conv  tableau qui associe à un nom de colonne son numéro
 *  @return string = error message - message d erreur.
 */
function indexName2Number (&$converted,$conversion){
	return (converti_col_index ($converted,$conversion));
}
function converti_col_index (&$tab_indexee,$tab_conv) {
	$err = '';
	if (!$tab_indexee) return($err);
	$res = array();
	foreach ($tab_indexee as $index=>$valeur){
		if (isset($tab_conv[$index])) $res[$tab_conv[$index]]=$valeur;
		elseif (strpos($index, 'URL:')=== 0 || strpos($index, 'FIX:')=== 0 || 
				$index=='*') $res[$index]=$valeur;
		else $err .= converti_col_val_mess('COLINV', $index);
	}
	if (!$err) $tab_indexee=$res;
	return ($err);
}
/**
 * colName2Number - converti_col_val
 * 
 * Converts a column name into its position number. If input $converted is an array,
 * converts all its values.
 * 
 * Pour tous les identifiants nominatifs de colonne, transforme le nom en numéro de colonne.
 * Si l'argument est un tableau, traite toutes les valeurs du tableau
 *  @param mixed $converted: value or array of values to convert -
 *  	$col_p_nom  valeur ou tableau de valeurs a convertir 
 *  @param array $conversion: conversion hash table (name=>column number) -
 * 		$tab_conv  tableau les index sont ceux de l'autre et les valeurs doivent remplacer 
 *      ces index.
 *  @return string = error message - message d erreur.
 */
function colName2Number (&$converted,$conversion){
	return (converti_col_val($converted,$conversion) );
}
function converti_col_val (&$col_p_nom,$tab_conv) {
	$err = '';
	if (!$col_p_nom) return($err);
	$tabu = (is_array($col_p_nom))?$col_p_nom:array($col_p_nom);
	$res = array();
	foreach ($tabu as $index=>$valeur){
		if (isset($tab_conv[$valeur])) $res[$index]=$tab_conv[$valeur];
		elseif (strpos($valeur, "URL:")===0 || strpos($valeur, "FIX:")===0 || $valeur=='*') $res[$index]=$valeur;
		else $err .= converti_col_val_mess('COLINV', $valeur);
	}
	if (!$err) {
		$col_p_nom= (is_array($col_p_nom))? $res:$res[0];
	}
	return ($err);
}



/*
 * ================================================================================ 
 * Use of StdIn / StdOut or Input / Output file 
 * 
 * Bloc de gestion des sources entrée standard ou fichier compressé.  
 * ================================================================================ 
*/
/**
 * @var resource $source_u = surtout utile pour les fichier
 * @var char $standard_utilise='s' si on utilise l'entrée standard, vide sinon.
 * 
 */
$unite_source_utilisee=''; $standard_utilise = '';

/**
 * 
 * Permet d'ouvrir la source dans le cas d'un fichier. D'établir le mode standard dans le cas de l'entrée 
 * standard.
 * @param string $unite = 'stdin' ou chemin du fichier 
 * @return resource 
 */
function ouvre_source ($unite){
	global $unite_source_utilisee,$standard_utilise;
	if ($unite_source_utilisee || $standard_utilise){
		if (!ferme_source()) return (false);
	} 
	if ($unite=='stdin') {
		$standard_utilise='s';
		$unite_source_utilisee = STDIN;
	}else {
		$unite_source_utilisee =gzopen($unite, 'r'); $standard_utilise='';
	} 
	return ($unite_source_utilisee);
}
/**
 * 
 * Rend une ligne lue à la source ...
 * @return string = ligne lue.
 */
function lit_source (){
	global $unite_source_utilisee,$standard_utilise;
	if ($standard_utilise=='s') return (fgets(STDIN));
	else return gzgets($unite_source_utilisee); 
}
/**
 * 
 * Find'usage de la source...
 */
function ferme_source(){
	global $unite_source_utilisee,$standard_utilise;
	if ($standard_utilise!='s') {$r = gzclose($unite_source_utilisee); }
	else {$r=true;}
	$unite_source_utilisee='';$standard_utilise=='';
	return ($r);
}
/*
 * ================================================================================ 
 * Bloc de gestion de plusieurs fichiers d'entrees ou de resultats : 
 * sortie/entree standard ou fichier compressé.  
 * ================================================================================ 
*/
/**
 * @var string array $result_files =  liste des fichiers écrits par étiquette logique
 * @var resource array $result_pointers =  liste des pointeurs de ressource par étiquette logique
 * 
 */
$result_files=array(); $result_pointers=array(); 
/**
 * 
 * Permet d'ouvrir un fichier resultat etiqueté $label. Pour ouvrir la sortie standard, le 
 * nom de fichier 'stdout' sera donné, sinon c'est son adresse qui le sera.
 * Si l'étiquette est déjà utilisée pour un fichier différent, elle sera fermée sur ce fichier et
 * ouverte sur celui de l'appel à la finction.
 * Si le fichier désigné est déjà ouvert sur une autre étiquette, les deux étiquettes pointeront sur 
 * le même fichier sans altération du flux de sortie déjà produit.
 * Bien que la ressource soit inutile, elle est quand même fournie en résultat. En cas d'échec, 
 * retourne false.
 * @param string $label = etiquette de sortie 
 * @param string $unite = 'stdout' ou chemin du fichier 
 * @return resource 
 */
function open_result ($label,$unite){
	global $result_files,$result_pointers;
	if (isset($result_files[$label])){
		if ($result_files[$label]==$unite) return ($result_pointers[$label]);
		if (!close_result($label)) return (false);
	}
	$p = array_search($unite,$result_files);
	$result_files[$label]=$unite;
	if ($p !== false) {
		$result_pointers[$label]=$result_pointers[$p];
	} else {
		if ($unite=='stdout') {
			$result_pointers[$label]=STDOUT;
		}else {
			$result_pointers[$label] = gzopen($unite, 'w'); 
		} 
	}
	return ($result_pointers[$label]);
}
/**
 * 
 * Ecrit une ligne lue en résultat...
 * @param string $label = etiquette de sortie 
 * @param string $str = chaîne à écrire 
 * 
 * @return boolean.
 */
function puts_result ($label,$str){
	global $result_files,$result_pointers;
	if (!isset($result_pointers[$label])) return (false);
	$d = substr($str, -1);
	if ($d!="\n" && $d!="\r") $str.="\n";
	if ($result_files[$label]=='stdin') return(fputs(STDOUT, $str));
	return (gzputs($result_pointers[$label],$str)); 
}
/**
 * 
 * Find'usage du résultat...
 */
function close_result($label){
	global $result_files,$result_pointers;
	if (!isset($result_pointers[$label])) return (false);
	if ($result_files[$label]!='stdout') {
		$r = gzclose($result_pointers[$label]); 
	} else {
		$r = true;
	}
	if ($r) {
		unset($result_files[$label]);
		unset($result_pointers[$label]);
	}	
	return ($r);
}
/**
 * @var resource array $source_files =  liste des ressource (fichier) par étiquette logique
 * @var char $stdin_lab='' non vide si on utilise l'entree standard, pour l'étiquette logique.
 * 
 */
$source_files=array(); $source_pointers=array();
/**
 * 
 * Permet d'ouvrir le fichier source ou l'entrée standard (nommée 'stdin') sur l'étiquette
 * indiquée. 
 * Si l'étiquette est déjà utilisée pour un fichier différent, elle sera fermée sur ce fichier et
 * ouverte sur celui de l'appel à la fonction.
 * Si le fichier désigné est déjà ouvert sur une autre étiquette, les deux étiquettes pointeront sur 
 * le même fichier sans altération de la position de lecture de cette entrée.
 * Bien que la ressource soit inutile, elle est quand même fournie en résultat. En cas d'échec, 
 * retourne false.
 * @param string $label = etiquette d'entrée 
 * @param string $unite = 'stdin' ou chemin du fichier 
 * @return resource / boolean = false 
 */
function open_source ($label,$unite){
	global $source_files,$source_pointers;
	if (isset($source_files[$label])){
		if ($source_files[$label]==$unite) return ($source_pointers[$label]);
		if (!close_source($label)) return (false);
	}
	$p = array_search($unite,$source_files);
	$source_files[$label]=$unite;
	if ($p !== false) {
		$source_pointers[$label]=$source_pointers[$p];
	} else {
		if ($unite=='stdin') {
			$source_pointers[$label]=STDIN;
		}else {
			$source_pointers[$label] = gzopen($unite, 'r'); 
		} 
	}
	return ($source_pointers[$label]);
}
/**
 * 
 * Rend une ligne lue à la source ...
 * @param string $label = etiquette de sortie 
 * 
 * @return string = ligne lue.
 */
function gets_source ($label){
	global $source_files,$source_pointers;
	if (!isset($source_pointers[$label])) return (false);
	if ($source_files[$label]=='stdin') return (fgets(STDIN));
	else return (gzgets($source_pointers[$label])); 
}
/**
 * 
 * Fin d'usage de la source...
 */
function close_source($label){
	global $source_files,$source_pointers;
	if (!isset($source_pointers[$label])) return (false);
	if ($source_files[$label]!='stdin') {
		$r = gzclose($source_pointers[$label]); 
	} else {
		$r = true;
	}
	if ($r) {
		unset($source_files[$label]);
		unset($source_pointers[$label]);
	}	
	return ($r);
}

/**
 * Rotation de version d'un fichier
 * Calcule le nom d'un fichier à créer en veillant à ne pas écraser des version antérieures
 * dudit fichier. A partir de son adresse complète, $path du suffixe avant lequel sera inséré 
 * un n° de version, cette fonction retourne selon le mode : l'adresse complète d'une nouvelle 
 * version ($mode == 'dns' ou ''), le simple nom de fichier suffixé ($mode=='ns'), 
 * ou non sans ($mode=='n') ou avec le répertoire ($mode='dn').
 * ... ou simplement le nombre de versions existantes ($mode=='c')
 * @param string $path : adresse complète du fichier avec son nom de base
 * @param string $suffix : suffixe du fichier devant lequel le N° de version doit être inséré.
 * @param string $mode : forme du résultat souhaité
 */

function tourne_fichier ($path,$suffix="",$mode=''){
	$elements = pathinfo($path);
	$nom_simple = $elements['basename'];
	if ($suffix){
	    if (substr($suffix,0,1)=='.') $extension = ltrim($suffix,'.');
		else {	$extension=$suffix; $suffix='.'.$suffix;}
		if ($elements['extension'] == $extension) 
			$nom_simple = $elements['filename'];
	}
	$dir = $elements['dirname'];
	$no=0;
	$nom=$nom_simple;
	$path = "$dir/$nom".$suffix;
	while (file_exists($path)){
		$nom = $nom_simple."_$no";
		$path = "$dir/$nom".$suffix;
		$no++; 
	}
	switch ($mode){
		case 'ns' : return ($nom.$suffix);
		case 'n' : return($nom);
		case 'dn': return("$dir/$nom");
		case 'c': return($no);
		case '' :
		case 'dns': 
		default : return($path);
	}
}

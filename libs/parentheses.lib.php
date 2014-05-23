<?php
/*
 * ====================================================================================================
 * 			Traitement des parenthèseurs 
 * ====================================================================================================
 * 
 */

/** Fourniture d'un tableau  de couple de caractères utilisés en début et fin de valeur unitaire 
 * notamment afin que les valeurs puissent contenir des caratères séparateur
 * ex. couple [] pour le séparateur , 
 * [NOMdAuteur, Marc],[Titre,à virgule],Flammarion,1957
 * var array() $limites_valeurs 
 */
$limites_valeurs = array();

function set_default_parentheses (){
	set_parentheses(array(array('"','"'),array('[',']')));
}
function get_parentheses (){
	global $limites_valeurs;
	return ($limites_valeurs);
}

function add_parentheses ($val){
	global $limites_valeurs;
	if (is_array($val)) {
		if (count($val)>=2) $limites_valeurs[]=array($val[0],$val[1]);
		elseif (count($val)==1)  $limites_valeurs[]=array($val[0],$val[0]);
		else return(false);
	} else {
		$limites_valeurs[]=array($val,$val);
	}	
	return (true);
}

function set_parentheses($l=''){
	global $limites_valeurs;
	if (!is_array($l)) {
		$limites_valeurs = array();
		return (true);		
	}
	foreach ($l as $elem) {
		if (!add_parentheses($elem)) return (false);
	}
	return (true);
}


/**
 * Lecture d'un defichier de parenthèseurs.
 * @param $fic string = nom du fichier
 * @return boolean = lecture correcte ou non.
 */
function traite_fichier_parentheses ($fic_par){
	if (!($fp = fopen($fic_par, 'r'))) {
		$echo_err .= traite_fichier_parentheses_mess('FINC',$fic_par);
		$err=true;
		return (false);
	} 
	$nouv = array();	
	$ok =true;
	while (($ligne = fgets($fp))!==false) {
		$ligne=trim($ligne);
		$tchaine = explode(' ', $ligne);
		if (count($tchaine)==2) {
			$nouv[]=$tchaine;
			continue;
		}
		if (strlen($ligne)==2) {
			$nouv[]=array($ligne[0],$ligne[1]); 
			continue;
		}
		if (strlen($ligne)==1) {
			$nouv[]=array($ligne,$ligne); 
			continue;
		}
		$ok=false;
	}
	fclose($fp);
	if ($ok){
			$r = set_parentheses($nouv); unset($nouv);
	} 
	return ($ok);	
}

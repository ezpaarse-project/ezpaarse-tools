<?php
/**
 * 
 * Fonction qui tout en eclatant la ligne sur le séparateur principal, tient compte de caractères 
 * parenthèsants.  
 * Les parenthèses permettent aux valeurs de comporter LE caractère séparateur de colonne. 
 * Ex : si le séparateur est le plus générique espace, on peut avoir comme parenthèses
 * les crochets pour la date soit [AAAA/MM/JJ à HH:mm:ss]  
 * @param string $ligne_entree : ligne à découper 
 * @param char $sep : séparateur de colonnes
 * @param array $col_testees : liste (en clé) des colonnes devant être testées. 
 * 		La colonne C porte un critère	 si $col_testees[C] existe.
 * @uses global $limites_valeurs : tableau 
 * @return mixed array = tableau des valeurs contenues dans la ligne 
 * 				 string = message d'erreur
 */
$ce_repertoire = dirname(__FILE__);

require_once ($ce_repertoire."/parentheses.lib.php");
function explose_ligne ($ligne_entree,$sep,$col_testees=array(),$ote_parentheses=false){
	global $limites_valeurs;
	if (!$limites_valeurs){
		set_parentheses(array(array('"','"'),array('[',']')));
	}
	$a_test = count($col_testees);
	$ligne_entree=trim($ligne_entree);
	if (isset($col_testees["*"])) {
		$r = valeur_col_valide($ligne_entree, "*");
		if ($r) return ($r);
	}
	$prem_tab = preg_split('/(\\'.$sep.')/', $ligne_entree,-1,PREG_SPLIT_DELIM_CAPTURE);
	$tab_res = array(); 
	$limit_val=$val_retenue="";
	/* analyse de chaque morceau */
	for ($ipos=0;$ipos<count($prem_tab);$ipos+=2) {
		/* si on est en cours de valeur parenthésée */
		$morceau=$prem_tab[$ipos];
		if ($limit_val) {
			// si fin de parenthèse mémorisation et init pour la suite
			if (substr($morceau,-$l_limit_val) ==$limit_val) {
				if ($ote_parentheses) $morceau = substr($morceau,0,-$l_limit_val);
				$limit_val="";
			} elseif ($ipos == count($prem_tab)-1){
				$limit_val="";
			}
			$val_retenue.=$dersep.$morceau;
			if ($ipos<count($prem_tab)-1) $dersep = $prem_tab[$ipos+1];
			if (!$limit_val){
				$tab_res[]=$val_retenue;
				if (isset($col_testees[count($tab_res)])) {
					$r = valeur_col_valide($val_retenue, count($tab_res));
					if ($r) return ($r);
				} 
				$val_retenue=$dersep="";
			} 				
			continue;
		} 
		/* on N'est pas dans une valeur parenthésée. On recherche si c'en est le début */
		foreach ($limites_valeurs as $def){
			$deb = substr($morceau,0,strlen($def[0]));
			$fin = substr($morceau,-strlen($def[1]));
			if ($deb==$def[0]) {				
				if ($fin!=$def[1]) {
					$limit_val=$def[1];
					$l_limit_val=strlen($limit_val);
					if ($ote_parentheses) $morceau = substr($morceau,strlen($def[0]));
				} else {
					$morceau = substr($morceau,strlen($deb),-strlen($fin));
				}
				break;
			} 
		}
		if ($limit_val) {
			$val_retenue=$morceau; if ($ipos<count($prem_tab)-1) $dersep = $prem_tab[$ipos+1];
		} else {
			$tab_res[]=$morceau;$val_retenue=$dersep="";
			if ($a_test && isset($col_testees[count($tab_res)])) {
				$r = valeur_col_valide($morceau, count($tab_res));
				if ($r) return ($r);
			}
		}		
	}
	return ($tab_res);
}

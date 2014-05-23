<?php
/**
 * @version du 5/03/2014 avec séparation code / configuration dans regles_groupage.php.
 * Seuls les traitements demeurent ici
 *  	Ensemble de la configuration  dans regles_groupage_test.php
 */

/**
 * @version au 16/09/2013 
 * Functions to give access groups / statistical qualification / localisation groups for users
 * Configuration in regles_groupage.php
 * Uses global definitions   
 *  $regles_groupage_LDAP = rules for setting groups using extracted informations (out of b.e. LDAP)
 *  $regles_resultats_LDAP = rules for setting statistical categories using extracted informations (out of b.e. LDAP)
 *  $regles_groupage_IP = rules for setting groups using host IP. 
 *                 Special Possibilité de postes publics à usage anonyme (sans identification d'usager). 

 * fonctions qui utilisent les informations glanées par 
 *  principal.php pour établir les droits et le faciès de l'usager.
 *  Configuration voir regles_groupage.php dans
 *  $regles_groupage_LDAP = définit les groupes en fonction des informations (LDAP par ex)
 *  $regles_resultats_LDAP = définit les catégories statistique en fonction des infos (LDAP par ex)
 *  $regles_groupage_IP = définit les groupes en fonction de l'IP du poste utilisé. 
 *                 Possibilité de postes publics à usage anonyme (sans identification d'usager). 
 */
/**
 * Log file name / Journal propre au filtre
 */
$nom_journal = "journal.txt";

/**
 * function groupes_LDAP = 
 * 		returns ezproxy group string deduced out of LDAP user properties. 
 * 		
 * 		calcule la chaîne des groupes à renvoyer à ezproxy d'après les attributs LDAP.
 * 		 
 * @param array $tous_les_att_LDAP = hash table in wich each entry name is a user
 * 		attribute name and contains its value list.
 *       			= Tableau assoc. des valeurs des attributs lus plus ceux fournis 
 *       				par défaut par valid_acces 
 * @return string = + separated list of users group names.
 * 			Liste des noms de groupes séparés par le signe + .          
 * 
 */
function groupes_LDAP (&$tous_les_att_LDAP){
	global $regles_groupage_LDAP;//,$login_master;
	$test_OK = peut_tester();
	$groupes_regles = ""; 
	$ttes_valeurs=array();
	if ($test_OK){	
		if ($_GET['forcequi'] && $_GET['forcede']) {
			$d = __VAcces_simul_identif ($att_result,$_GET['forcede'],$_GET['forcequi']);
			$tous_les_att_LDAP=$att_result; 
			$tous_les_att_LDAP ['identifieur']=$_GET['forcede'];
			if ($d===false || substr($d,0,4)=="ERR:" || !$att_result['valide']) {
				$cas= ($d===false || !$att_result['valide'])?
						trad_message('realise_regles','no_user'):
						trad_message('realise_regles','unvalidated_user',$d);
				en_trace("<li>$cas ".$_GET['forcequi']." </li>\n");
				return ('');
			} else {
				if (simul_peut_tester($d, $tous_les_att_LDAP)){
					$groupes_regles = "admDB";$ttes_valeurs=array("admDB");
				}
				en_trace("<li>".
						trad_message('realise_regles','chg_user',array($_GET['forcequi'],$_GET['forcede'])).
						"</li>\n");
				if (function_exists("LDAP_cache_att")) 
					$tous_les_att_LDAP = LDAP_cache_att($tous_les_att_LDAP);
			}			
		} else {
			$groupes_regles = "admDB";$ttes_valeurs=array("admDB");
		}
	}
	$paragraphe_trace = "";
	foreach ($regles_groupage_LDAP as $att=>$def_regles) {
		if (!is_array($def_regles)) {
			$regles=$regles_groupage_LDAP[$def_regles];
		}
		else $regles = $def_regles;
		if (!isset($tous_les_att_LDAP[$att])) {
			if (function_exists("LDAP_att_est_cache") && LDAP_att_est_cache($att)) {
				$paragraphe_trace.= "<br />\n".trad_message('realise_regles','hidden_att',$att);
				continue;
			}
			$paragraphe_trace.= "<br />\n".trad_message('realise_regles','invalid_att',$att);
			continue;
		}
		$ses_val = $tous_les_att_LDAP[$att];
		if (!is_array($ses_val)) $ses_val=array($ses_val);
		$res=deduire_de_regles($ses_val, $regles, $ttes_valeurs);
		if (!$res) continue;
		$groupes_regles .= '+'.implode('+',$res);
		$ttes_valeurs = array_merge ($res,$ttes_valeurs);
		
	}
	if ($paragraphe_trace) {
		en_trace("<li>\n".trad_message('realise_regles','grant_access',$paragraphe_trace)."</li>\n") ;
	}
	return ($groupes_regles);
}


/**
 * function groupes_finaux 
 * 		permet de définir des groupes par corellation entre les groupes LDAP et les groupes IP:
 * @param $groupe string  : liste des groupes déjà établis séparés par des signe +
 * @return string : liste résultante des groupes. 
*/
function groupes_finaux ($groupesLDAP,$groupesIP){
	global $OnOnlyPass,$OnOffPass;
	//global  $login_master;
	if (peut_tester()) {
		$g = $groupesIP;
		if ($_GET['forceIPgroupe']!="" ) $g.='+'.$_GET['forceIPgroupe'];
		if ($_GET['forcegroupe']) $g.='+'.implode('+',$_GET['forcegroupe']);
//		if ($g) return ($g);
		$groupesIP=ltrim($g,'+');
	}
	if (!$groupesLDAP) {
		if (est_groupe_public($groupesIP)===false) return ('');
		return ($groupesIP);
	} 
	$tab_groupesLDAP = explode('+',$groupesLDAP); 
	$groupesIP = trim($groupesIP,'+ ');
	$tab_groupes = array(); $timbre=false; 
	foreach ($tab_groupesLDAP as $gr) {
		$gru = $gr;
		if ($gru == $OnOnlyPass ) {
			if ($groupesIP) $gru =  $OnOffPass;
			else $gru = "";
		} 
		if ($gru=="") continue;
		if ($gru==$OnOffPass) { if ($timbre) continue; $timbre = true; }
		if (!in_array($gru, $tab_groupes)) $tab_groupes[]=$gru;
	}
	if (!$timbre) return ('');
	$groupes = '+'.implode('+', $tab_groupes);
	if ($groupesIP) $groupes.='+'.$groupesIP;
	return ($groupes);
}


/**
 * function est_groupe_public (string $str_gps)
 * 
 * returns true if name of anonymous access group is found in $str_gps 
 *    
 * test la présence du nom désignant le groupe des postes publics dans la chaîne $str_gps 
 * @param string $str_gps = string groups list - liste littérale de groupes
 */
function est_groupe_public ($str_gps){
	global $GroupAnonyme;
	return (strpos($str_gps, $GroupAnonyme)!==false) ;
}
/**
 * function a_groupe_public (array $array_gps)
 * 
 * returns true if anonymous group name has is in  $array_gps array
 *    
 * test la présence du nom du groupe attribué aux postes publics le tableau  $array_gps
 * @param array  $array_gps = array of group names - tableau de noms de groupes.  
 */
function a_groupe_public ($array_gps){
	global $GroupAnonyme;
	return (in_array($GroupAnonyme, $array_gps)!==false) ;
}
/**
 * liste_IP_pour_public
 * Rend la liste des blocs IP qui correspondent à un groupe public.
 */
function liste_IP_pour_public(){
	global $regles_groupage_IP;
	$res = array();
	for ($i=0; $i<count($regles_groupage_IP);$i+=2 ){
		$bloc = $regles_groupage_IP[$i];
		$grps = $regles_groupage_IP[$i+1];
		if (est_groupe_public($grps)) $res[$bloc]=$grps;
	}
	return ($res);
} 

function test_groupes_IP ($son_IP){
	global $regles_groupage_IP;
	$public_declaree=NULL;
	$res="";
	for  ($ipos=0;$ipos<count($regles_groupage_IP);$ipos+=2){
		$mini_max=$regles_groupage_IP[$ipos];
		if (compare_IP($son_IP,$mini_max)!=0) { 
			continue;			
		}
		return(explode('+', $regles_groupage_IP[$ipos+1]));
	}
	return (false);
}

/*
 * Affectation de la variable  $groupes avec les 
 * qui correspondent à l'établissement du poste informatique 
 * utilisé par l'usager. 
 * + retour d'un login signifiant un usager banalisé situé dans cet établissemnt.
 */
function groupes_de_l_IP(&$groupes) {
	global $regles_groupage_IP,$mess_IP,$LoginAnonyme,$OnOnlyPass; //,$IP_master;
	$son_IP = $_SERVER['REMOTE_ADDR'];
	$nomme_IP = $son_IP;
	if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$son_IP_bck = $_SERVER['HTTP_X_FORWARDED_FOR'];
		$nomme_IP = " $son_IP_bck derriere $son_IP";
	} else {
		$son_IP_bck = "" ;
		$nomme_IP = " $son_IP";
	}
	if (isset($_GET['forceIP']) && $_GET['forceIP']!=''){
		if (peut_tester()) {
			$s = ($son_IP_bck)?trad_message('realise_regles','ss_proxy',$son_IP):"";
			$nomme_IP .= trad_message('realise_regles','chged_to_IP',array($_GET['forceIP'],$s));
			$son_IP=$_GET['forceIP']; $son_IP_bck="";// faut-il mettre les 2 ?			
		}
	}
	$login = ""; 
	$groupes_corres =test_groupes_IP($son_IP); 
	if ($groupes_corres!==false && $son_IP_bck){
	  	$groupes_corres_bck = test_groupes_IP($son_IP_bck);
	  	if ($groupes_corres_bck) {
	  		foreach ($groupes_corres_bck as $un_grp){
	  			if (!in_array($un_grp, $groupes_corres)) 
	  				$groupes_corres[]=$un_grp;
	  		}
	  	}
	}
	
	if ($groupes_corres===false) {
		$mess_IP = trad_message('realise_regles','unknown IP',$nomme_IP);
		return (false);
	} 
	if (!$groupes_corres || !in_array($OnOnlyPass,$groupes_corres)) {
		$mess_IP = trad_message('realise_regles','no access IP',$nomme_IP);
		return (false);
	} 
	if (!in_array($OnOnlyPass,$groupes_corres)){
		$mess_IP = trad_message('realise_regles','no access IP',$nomme_IP);
		return (false);
	}
	if (a_groupe_public ($groupes_corres) {
		$login= $LoginAnonyme;
		$mess_IP= trad_message('realise_regles','pub host',$nomme_IP);
	} else {
		$mess_IP = trad_message('realise_regles','on_campus',$nomme_IP);
	}
	$groupes = implode('+',$groupes_corres);
	return($login);
}

/**
 * 
 * function peut_tester()
 *   returns true if actually user is an admin as mentionned if status returned by valid_acces.php is in list 
 *   of admin status
 *   Indique si le statut de l'usager issu de valid_acces.php est celui d'un administrateur ou
 *   non du CGI.
 * 
 * @return boolean : true if user is admin - vrai si l'usager a le droit d'utiliser le programme de vérification 
 * */

function peut_tester (){
	global $AdminList;
	if (function_exists("__VA_droits") && ($gps = __VA_droits())!=''){
		foreach ($AdminList as $un_grp){
			if (strpos($gps,$un_grp)!==false) return (true);
		}
	}
	return (false);
}
/**
 * function function simul_peut_tester($gps, $tous_les_att_LDAP)
 * 	idem peut_tester but use string parameter $gps in place of valid_acces status
 *  idem peut_tester mais utilise $gps à la place la fonction __VA_droits de valid_acces
 *  
 *   @param string $gps = valid_acces status string 
 *   					chaîne contenant des statuts possible de valid_acces
 *   @param string $tous_les_att_LDAP = unused - obsolète
 *   @return bool = is admin - vrai pour un admin
 */
function simul_peut_tester($gps, $tous_les_att_LDAP){

	if (strpos($gps,'docelec')!==false) return (true);
	if (strpos($gps,'admin_VA')!==false) return (true);
	return (false);
}
/*
 * User statistic categories evaluation.
 * Calcul des catégories statistiques. 
 */
/**
 * 
 * Coupling rules user properties of $tous_les_att_LDAP  rules of $regles_resultats_LDAP 
 * for each entry of this array wich will be also an entry of the result.
 * See the "how to" with deduire_des_attributs
 *  
 * Couplage des règles de la table $regles_resultats_LDAP avec les valeurs des attributs lus
 * dans  attributs  $tous_les_att_LDAP.  Le principe est celui décrit dans deduire_des_attributs
 *  
 * @param array $tous_les_att_LDAP =
 * 		hash table of user properties 
 * 		liste de valeurs par attribut (LDAP ou BdD)
 * @global array  $regles_resultats_LDAP =
 * 		array of rules associated with each result entry  
 *      liste des règle par valeur cible à calculer
 *  @return array = 
 *  		association array for each entry of $regles_profiles_LDAP
 *  		tableau des valeurs calculées.
 */			
function profiles_LDAP ($tous_les_att_LDAP){
	
	global $regles_profiles_LDAP;//$ordre_evaluation_profiles,,$login_master;
	$profiles	= deduire_des_attributs($tous_les_att_LDAP, $regles_profiles_LDAP);
	return ($profiles);	
}
/**
 * Gluing in a one string value of multicategory in order  of $ordre_formation_profiles
 * Assemblage en une chaîne "csv" des valeurs des 
 * catégories statistiques dans l'ordre donne par $ordre_formation_profiles 
 */
function forme_profiles ($profiles,$glu){
	global $ordre_formation_profiles;
	$res = "";
	foreach ($ordre_formation_profiles as $cle){
		$res.=$glu;
		if (! isset($profiles[$cle])) continue; 
		$res.= (is_array($profiles[$cle]))?implode(',',$profiles[$cle]):$profiles[$cle];
	}
	$res = substr($res,strlen($glu));
	return ($res);
}

/**
 * function deduire_des_attributs
 * 		List of target-values to create. Every one is associated with its source users properties i.e.
 * every of its values is the result of a unique value of a unique property as :
 * a result of a function  :: 
 * 		'property_name'=>array('function'=>'function_name')
 * a set of "deduire_de_regles function" rules :: 
 * 		'property_name'=>array('rules'=>array(...))
 * simply the values of property  ::
 * 		'property_name'=>array()
 * 
 * Interprète les attributs LDAP pour construire des données dites cibles. 
 * Chacune utilise pour être définies, des attributs mais de manière individuelle i.e.
 * les donnée extraites (forgées) sont chacune déduite d'un seul attribut par
 * application d'une fonction  :: 
 * 		'attribut1'=>array('fonction'=>'nom_de_fonction')
 * usage de règles de réécriture/remplacement (V. fonction deduire_de_regles) :: 
 * 		'attribut2'=>array('regles'=>array(...))
 * simple copie des valeurs  ::
 * 		'attribut3'=>array()
 * 
 * @param array $tous_les_att_LDAP 
 * 					= association array of attribute name and their values.
 * 					= tableau associatif des valeur de chaque attribut  
 * @param array $ttes_les_regles 
 * 				= association array of targets (as entries) and their rules (entry's values) va
 * 				= tableau associant à chaque cible les règle de création.  
 */
function deduire_des_attributs ($tous_les_att_LDAP,$ttes_les_regles){
	$resultats = array();  
	foreach ($ttes_les_regles as $cible=>$regles_cible) {
		$resultats[$cible]=array();
		foreach ($regles_cible as $att=>$def_regles){
			if (!isset($tous_les_att_LDAP[$att])) {
				continue;
			}
			$ses_val = $tous_les_att_LDAP[$att]; 
			if (!is_array($ses_val)) $ses_val=array($ses_val);
			$func_name = $regles = ""; 
			if (isset($def_regles['function'])) {
				$func_name = $def_regles['function'];
			} elseif (isset($def_regles['fonction'])) {
				$func_name = $def_regles['fonction'];
			}
			if (isset($def_regles['regles'])) {
				$regles = $def_regles['regles'];
			} elseif (isset($def_regles['rules'])) {
				$regles = $def_regles['rules'];
			}
			if ($func_name){
				if (function_exists($func_name))
					$res=call_user_func($func_name,$ses_val);
				else 
					continue;
			} elseif ($regles) {
				$res=deduire_de_regles($ses_val, $regles);
			} else {
				$res=$ses_val;
			}
			foreach ($res as $v){
				if (!in_array($v,$resultats[$cible])){
					$resultats[$cible][]=$v;
				}
			}
		}
	}
	return ($resultats);
}


/**
 * 
 * Coupling of values ($valeurs) with rules ($regles) to get a list of group 
 * names. Each rule is a match value and a replacement string or RegExp. 
 * If the left match value is found, then the right value is 
 *  - if it begins with ==, the value to add to result.
 *  - else it's used as RegExp replacement string (i.e. preg_replace(match,right,att_value);  
 *  
 * Couplage d'une liste de $valeurs et de $regles pour retourner une autre liste de valeurs, 
 * filtées / réécrites. Chaque valeur d'origine est sensée fournir au plus une valeur en 
 * résultat grâce aux règles, la première déduite des dègles étant retenue. 
 * Une règle consiste en une expression régulière recherchée à laquelle on fait correspondre 
 * - soit d'une valeur cible (cette valeur est précédée de ==) 
 * - soit d'une expression régulière de remplacement.
 *  
 * @param array() $valeurs = liste de valeurs à traiter
 * @param array() $regles = tableau associatif de valeur recherchée => valeur résultat ou remplacement
 * @param array() $exclues = liste de valeurs à exclure du résultat.
 */
function deduire_de_regles ($valeurs,$regles,$exclues=array()){
	$res = array();
	foreach ($valeurs as $une_val) {
			$v="";
			foreach ($regles as $recherche=>$remplace) {
				// test de validité de la règle par rapport à la valeur 
				if (! preg_match($recherche, $une_val)) continue;  
				// cas d'affectation simple ou de substitution
				if (strpos($remplace, '==')===0) $v = substr($remplace, 2);
				else $v = preg_replace ($recherche,$remplace,$une_val);
				break;				
			}
			if (!$v) continue;
			if (in_array($v, $res))  continue;
			if ($exclues && in_array($v, $exclues))  continue;
			$res[]=$v;
	}
	return ($res);
}


/*
 * To hide properties - Cachage des attributs
 */

// hidden property ? Attribut cache ?
function LDAP_att_est_cache($att){
	return (isset($_GET['inibit_'.$att]) || isset($_SESSION['sv_CGI_verif']['inibit_'.$att]));	
}			
// array of not hidden property values - Valeur des attributs non caches
function LDAP_cache_att($val_atts){
	$res = array();
	foreach ($val_atts as $att=>$valeurs){
		if (LDAP_att_est_cache($att))
			continue;
		$res[$att]=$valeurs;
	}
	return ($res);	
}			
// array of is_hidden of properties - table des attributs avec pour chacun s'il est caché.
function LDAP_att_liste_statuts(){
	global $regles_groupage_LDAP ;
	$res=array(); 
	foreach ($regles_groupage_LDAP as $att=>$regle){
		$res[$att]=LDAP_att_est_cache($att);
	}
	return ($res);
} 				

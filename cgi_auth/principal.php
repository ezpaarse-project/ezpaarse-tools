<?php
/*
 * EZPROXY : CGI authentication.
 * Main module. 
 */

/**
 * @version au 16/09/2013 Version test
 * 
 * How does it work :
 * 1/ EzProxy send a request to this module (via index.php). This request contains target
 *  startUp URL and if new identity is required to grant user to get resource access (or not for first login) 
 * 2/ The CGI follows these steps :
 *   - test host IP of user and give first groups for him. If there is 'GdPublic' among the 
 *    groups then let the user access and return back the ticket to Ezproxy with user login 
 *     set to anonyme except it's a new login request by Ezproxy.
 * 3/ 
 *   3.1 )Use valid_acces.php filter to get login and LDAP or DB source properties of the user.
 *   3.2 ) Use this informations to gives other groups (rights) 
 * 4/ Merge 2/ and 3/ groups
 * 5/ If Debug write informations as diagnostic for user or verification report for admin 
 *    else request your ezproxy zand let it know login and groups of the user.
 *       
 * 
  * FONCTIONNEMENT : 
 * 	- EzProxy requère ce cgi en lui fournissant deux paramètre 
 * 		. l'URL en clair demandée par l'utilisateur (url)
 * 		. le fait qu'il y ait demande de changement d'identité pour cause de non auto-
 * 		risation d'accès à une ressource (logup)
 * Ce programme :
 *  - établit la localisation de l'usager par son IP de requête et lui attribue un ou
 *  	plusieurs groupe la reflétant (regles_groupage.php)
 *  - si l'IP n'est pas d'un poste public , 
 *  	Le préfiltre valid_acces.php, configuré par acces_conf.php, demande à l'usager de s'identifier 
 *  	pour lui attribuer des "droits" et fournir le contenu d'attributs (extraits de LDAP)
 *  	Pour la plupart des usagers il rend la valeur default, mais positionne des valeurs 
 *  	d'administration pour quelques usagers désignés individuellement.
 *  	Depuis septembre 2013, il n'utilise plus qu'un seul couple CAS/LDAP pour la remontées des 
 *  	infos concernant l'usager)
 *  	regles_groupage.php : utilise les attributs LDAP fournis pour
 *  	- établit les groupes transmis à ezproxy. Ces groupe servent limiter l'accès de l'usager 
 *  	à certaines ressources ;
 *  	- conserve dans ses log (journal.txt), en plus des infos précitée, le son profil o faciès
 *  	 statistique de l'usager. (pour utilisation avec ezpaarse : 
 *  	V. sur serveur erzproxy mise_en_archives_longues.php et calcul_ecs.php)
 * - utilisé hors mode Debug, il renvoie une requête au reverse proxy émetteur avec un ticket contenant l'URL 
 * 		demandée à l'origine et surtout le login et les groupes auxquels appartient l'usager .
 * - utilisé en mode Debug/diagnostic il affiche un diagnostic (via diagnostic.php) d'accès pour 
 * 		l'utilisateur effectif,	en mode Debug/verif (via verif.php) tous les éléments
 * 		d'informations sut lesquels il s'est basé pour attribuer groupe et faciès à un usgager qui
 * 		n'est pas l'utilisateur effectif sur un poste d'IP qui n'est pas l'effective.
 * 
 * 
 */
/*
 * Choice your language file in locale 
 */
if (!function_exists('trad_message')){
	include_once ("./locale/fr.php");
}
include_once ("./regles_groupage.php");	
include_once ("./realise_regles.php");	

		
$trace_tab=array(); $trace="";

function en_trace ($message){
	global $trace;
	$trace .= $message;	
}		
function affi_trace() {
	global $trace;
	echo $trace;	
}

function affiche_LDAP_tab ($tab,$non_imprim){
	if (!is_array($tab)){
		if (is_bool($tab)){
			if ($tab===true) $tab=trad_message('gen', 'vrai');
			else $tab=trad_message('gen', 'faux');
		}
		if (!$non_imprim) echo $tab;
		return ($tab);
	}
	$tableau = "<table>\n";
	foreach ($tab as $cle=>$val){
		$tableau .= "<tr><td>$cle</td><th>".affiche_LDAP_tab($val,$non_imprim)."</th></tr>\n";
	}
	$tableau .= "</table>\n";
	if (!$non_imprim) echo $tableau;
	return($tableau);
}

function affiche_att_LDAP ($tab,$non_imprim,$presente=true){
	global $regles_affichage_LDAP;
	$tab1 = array();
	foreach ($tab as $att=>$vals){
		if (!$vals) continue;
		if (!is_array($vals)) {
			if (is_bool($vals))
				$v_util= ($vals===true) ? 
						array(trad_message('gen', 'vrai')): 
						array(trad_message('gen', 'vrai'));
			else $v_util=array($vals);
			$tab1[$att]=$v_util;
		}
		else $tab1[$att]=$vals;
	}
	$tableau="";
	if ($presente){
	foreach ($regles_affichage_LDAP['reecrire'] as $att=>$att_cible){
		if (isset($tab1[$att]) && isset($tab1[$att_cible])){
			foreach ($tab1[$att] as $une_val){
				$pos = array_search($une_val, $tab1[$att_cible]);
				if ($pos !== false) {
					$tab1[$att_cible][$pos]= "<b>".$tab1[$att_cible][$pos]."</b>";
				}
			}
			unset ($tab1[$att]);
		}
	}
	foreach ($regles_affichage_LDAP['ligne'] as $ligne){
		$tableau.="  <tr><td>\n"; $sep = "";
		foreach ($ligne as $att){
			$tit = (isset($regles_affichage_LDAP['titre'][$att]))?
					$regles_affichage_LDAP['titre'][$att] : $att;
			$tableau .= "$sep<i>$tit :</i> ".implode(' - ', $tab1[$att])."\n";
			$sep = " ; ";		
		}
		$tableau .= "  </td></tr>\n";
	}
	} else {	
		foreach ($tab1 as $att=>$vals){
			if (count($vals)>1) $ligs=" rowspan=".count($vals);
			else $ligs="";
			$tableau.="  <tr><th$ligs>$att&nbsp;= </th>\n";
			$nvlig = "";
			foreach ($vals as $une_val){
				$tableau.=$nvlig."\n<td>$une_val</td></tr>\n";
				$nvlig="<tr>";
			}
		}
	}
	$tableau = "<table>\n".$tableau."</table>\n"; 
	if (!$non_imprim) echo $tableau;
	return($tableau);	
}

function affiche_profiles ($tab,$non_imprim){
	$tableau = "";
	$affi_cle = (count($tab)>1); 
	foreach ($tab as $att=>$vals){
		if (!$vals) $v_util=array('-');
		elseif (!is_array($vals)) {
			if (is_bool($vals))
				$v_util= ($vals===true) ? array(trad_message('gen', 'oui')): array(trad_message('gen', 'faux'));
			else $v_util=array($vals);
		} else $v_util=$vals;
		if ($affi_cle) $tableau .= " ; <b>$att : </b>"; 
		$tableau .= implode(',', $v_util)."\n";		
	}
	if ($affi_cle) $tableau=substr($tableau, 3);
	if (!$non_imprim) echo $tableau;
	return($tableau);	
}




en_trace("<hr /> <h2>".trad_message('principal','diagnostic').":</h2>\n<hr />\n <ul>\n") ;
$groupes='';
/*
 * Take out of URL, CGI parameters
 * Retire de l'URL fournie les éléments de paramétrage du CGI lui-même
 */
if (isset($_GET['url'])) {
	$url=$_GET['url'];
	if (preg_match("/(&|\\?)identifieur=([^&]*)/",$url,$res)) {
		$__VA_identifieur=$res[2];
		$url = str_replace($res[0], '', $url);
	}
	if (preg_match("/(&|\\?)VA_init=([^&]*)/",$url,$res)) {
		$__VAcces_init=true;
		$url = str_replace($res[0], '', $url);
	}	
	if ($Debug==='verif' && $_GET['url']) 
		en_trace(trad_message('principal','URLrecue', $_GET['url'])) ;
	$_SESSION['EzpURL']=$url;
}

if (isset($_GET['logup'])) {
	if ($_GET['logup']=='true') {$_SESSION['EzpF']=true;}
	else {$_SESSION['EzpF']=false;}
}

// Must login again ? - Cas de réidentification nécessaire
$recommence=$_SESSION['EzpF'];
if ($Debug && $recommence) 
	en_trace("<li>".trad_message('principal','Req_identifieur')."</li>\n") ;



include_once ("./acces.conf.php");
include_once ("./acces.lib.php");



$__VAcces_inipag=2;
/* */    
if ($recommence) {
	en_trace("<li><b>" . trad_message('principal','2ndLogin')."</b></li>\n");
	if (count($__VA_identifieurs)<=1) {
		if ($Debug) { exit ($trace.trad_message('principal','1SeulIdP'));}
		header("Location:$reverse");
		exit();
	} 	
}

/*
 *  Détermination des droits d'accès généraux
 */

$groupes_IP=""; $ajout_trace = ""; $profiles=$profiles_defaut;
/*
 * Special groups set by the admin with verif.php
 * Cas particulier des groupes forcés pour test 
 *  peut_tester ne peut être vrai que si l'usager réel est déjà identifié 
 */
  
  /*
   * Host grouping =  Association of groups for some hosts  
   * Groupes lié aux poste utilisé : détermination de poste 
   */	
	if (isset($regles_groupage_IP) && count($regles_groupage_IP)>0) {
		include_once $dirLibs.'/compare_IP.php';
		$mess_IP="";
	    $login = groupes_de_l_IP($groupes_IP);
		if ($Debug) {
	    	if ($Debug==='verif' && $groupes_IP)
	    		$mess_IP.= ($groupes_IP)?trad_message('principal', 'grpsIP', $groupes_IP):"";
	    	en_trace("<li>$mess_IP</li>\n");
		    	}
		}

/*
 * Groups issued of LDAP / DB properties of authenticated user if the host is not for 
 * anonymous people. 
 * Groupes lié à l'usager par son identification et les infos extraites de LDAP ou en BdD
 * Si le poste est public ET qu'il n'y a pas demande de réidentification par
 * EzProxy, on se contente du grouppe lié aux postes publics : GdPublic
 */ 
	
	if (est_groupe_public($groupes_IP)!==false && !$recommence) {
		$groupes = groupes_finaux("", $groupes_IP);
		$profiles = $profiles_defaut;  //"-----";
	} else {
		// Idt filter and set user's properties
		// Passage par le filtre d'identification et récup des attribut LDAP
		include_once ("./valid_acces.php");
		$tous_les_att_LDAP = $_SESSION['_VAcces_utilisateur'];
		$groupes_regles = groupes_LDAP ($tous_les_att_LDAP);
		$login = $tous_les_att_LDAP ['login'];
		$groupes = groupes_finaux($groupes_regles, $groupes_IP);	
		$tab_profiles = profiles_LDAP ($tous_les_att_LDAP);
		// message for (pour) verif.php :	
		if ($Debug==='verif') {
			$presente = ($_GET['ldap'])?false:true;
			en_trace(trad_message
						('principal', 'val_attrs'
						,array	(affiche_att_LDAP($tous_les_att_LDAP,true,$presente)
								,$groupes_regles
								)
						)
					) ;
		}
		// Access off campus 
		// Accès hors les murs estimé   
		if ($Debug && trim($groupes_IP) && !$_GET['forcegroupe']) {
			$groupes_ext = groupes_finaux($groupes_regles, "");
			if (trim($groupes_ext)=="") {
					$ajout_trace .= trad_message('principal','OnlyOnCampus');
			} else {
				if ($Debug=='verif') {
						$ajout_trace .= trad_message('principal','OffCampusAccessTo',$groupes_ext);
				} else {
						$ajout_trace .= trad_message('principal','OnOffCampusAccess');
				}
			}
		}
		$profiles = forme_profiles($tab_profiles, "|");
		if ($Debug==='verif'){
			en_trace(trad_message('principal','vstats',affiche_profiles($tab_profiles,true)));
		}
	} // fin du cas où il faut identifier l'usager

/* 
 * Logging result
 * Journalisation du résultat 
 */
	if ($Debug) {
	$nom_journal="$Debug.$nom_journal";
}


$aller = false;
$groupes = trim($groupes," +"); 
/*
 * No group = no access.
 * Aucun groupe=aucun droit d'accès
 */
if ($groupes=="" ){
	include_once ("./journalisation.php");
	$le_journal = new journal("refuses_".$nom_journal) ;
	$enr_journal =  "$login\tIP:$groupes_IP\tLDAP:$groupes_regles\t".$_SESSION['EzpURL'];
	if ($Debug) {
		en_trace(trad_message('principal','SsAcces'));
	} else {
		$le_journal->enregistre($enr_journal);
		include ("page_sans_acces.php");
		exit();
	}
} else {
/*
 * Avoid too long group list to send back to ezproxy
 * Test liste de groupes trop longue pour la réponse à ezproxy.
 */
	include_once ("./journalisation.php");
	$le_journal = new journal($nom_journal) ; 
	$enr_journal = "$login\t$groupes\t$profiles\t".$_SESSION['EzpURL'];
	
	$groupes='default+'.$groupes;
	$lg_groupes = strlen($groupes);
	if ($lg_groupes>430) {
		$le_journal->enregistre("Trop de groupes pour $login : $groupes");
		$p = strpos($groupes,'+');
		$groupes=substr($groupes,$p+1);
		$p = strrpos($groupes,'+',430);
		if ($Debug) {
			en_trace(trad_message('principal','GrpTrpLg',array($lg_groupes,$p)));
		}
		$groupes = substr($groupes,0,$p);
	}	
		
	if ($Debug) {
		if ($Debug==='verif'){
			en_trace(trad_message('principal','aAccesA',array($login,$groupes,$ajout_trace)));
			if ($_SESSION['EzpURL']){
				en_trace(trad_message('principal','URLcible',$_SESSION['EzpURL']));
				$aller = true;
			}
		} else {
			en_trace(trad_message('principal','accesOK'));
		}
	} else {
		$le_journal->enregistre($enr_journal);
	}
}

	
include_once ("./ezproxyticket.php");

// ticketed request URL ... - Formation de l'URL à ticket pour ezproxy
$son_url = new EZproxyTicket ($reverse
							 ,$cle_ticket
							 ,$login
							 ,$groupes
							 );

							 
$retour = $son_url->URL($_SESSION['EzpURL']);

if ($Debug==='verif' && $_SESSION['EzpURL'] && strpos($retour, $_SESSION['EzpURL'])===false){
	$complement_affichage.= trad_message('principal','URLincomplete');
}
// Session cleaning / Nettoyage de la session.			
    __VA_nettoie_session();				
    unset ($_SESSION['EzpURL'])	;			

    // Conclusion Debug = Display / Normal = ticketed request to ezproxy  
    
if ($Debug){
	en_trace("</ul><hr>\n");
	if ($aller) 
		en_trace("<a href=\"$retour\">".trad_message('principal','allerURL')."</a> <br />\n");
	en_trace($complement_affichage);
	affi_trace(); 
	exit();
}

  header('Location: '.$retour);
?>
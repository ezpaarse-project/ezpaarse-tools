<?
/*
 * EZPROXY : CGI authentication tools.
 * Multi CAS/LDAP authentication Filter. 
 * Include as soon as you need filtering 
 */
/**
 * 
      Vérification de droits d accès.
      Rights Filter module

  !!!!
	Requirements before include it at the begining of your owns modules :
    ========================================================
  Set the $__VAcces_inipag value to :
       0 = (default) for pages where you consider user has been identified. If
           not, it's a violation access to the page.
       1 = anonymous accessed page = don't filter. 
       2 = page for authentication if needed. It allways require new authentication if   
           $__VAcces_init is set to true.

      Configure its behaviour with acces.conf.php and modify below.

	Requis à mettre avant le include de ce module 
    ========================================================
  Fixer $__VAcces_inipag avant d'inclure ce module de filtrage d'accès à :
       0 = défaut = l utilisateur doit DEJA être identifié pour passer.
       1 = page passante, pas de vérification d accès. 
       2 = page d entrée, on demande à l usager de s identifier SI ce n est fait.
           Si de plus $__VAcces_init est à TRUE, on force la demande.

      La configuration de son fonctionnement se trouve dans acces.conf.php qui DOIT être
      inclu AVANT l inclusion de valid_acces.php

	Customisation of the two process formulars through two functions  :

	function my_IdP_selector ($error_message) : to customize the choice of the identity provider
			( i.e. Shibboleth "WAYF" or "Discovery Service" ).
	function my_login_page : to customize login page for LDAP IdP 


    ========================================================
      Mémorise dans $_SESSION['_VAcces_droit']  et fournit dans $droits_etablis 
      		la liste des droits de l usager séparés par des virgules
      Mémorise dans $_SESSION['_VAcces_utilisateur'] et fournit dans $utilisateur 
      		un tableau associatif donnant les attributs suivants de l usager :
        'Nom' = nom de l usager
        'Prenom' = son prénom
        'Adresse'= son adresse postale
        'Adrel'  = son adresse électronique
        'Tel'    = son téléphone
        'Fax'    = son num. télex
        'identifieur' = l'organisme qui l'a identifié. 
        +....    = tous les attributs désignés en conf dans atts_lus
 
	Fonctions d adaptation/personnalisation de fonctionnement :

	ma_demande_identifieur : permet d ecrire son propre "WAYF" ou "Discovery Service" au sens Shibboleth.
	ma_demande_idt : permet d écrire son propre formulaire en cas d identification grâce à la base d Encycloped.
	__VAcces_corr_utilisateur = si une fonction de ce nom existe, elle est appeler pour finaliser l identification.

    Paramétrage dynamique a l appel HTTP :
	anonyme : 1 = accès anonyme. Permet de mettre un lien pour un accès anonyme.



    ========================================================
    Configuration in (see) acces.conf.php
    ========================================================

    Configuration (paramétrage) à l inclusion :
	$__VAcces_init : réinitialisation de l identification. On oublie tout de l'usager, même l organisme identifieur.  
	$__VAcces_inipag = indicateur qui précise à 0 (ou non défini) que l'usager doit déjà être identifié ; à 1 que la page est publique (identité non requise) ; 
				à 2 page d'entrée possible i.e. si l'usager n'est pas identifié, on le renvoie sur l'identification.
	$__VAcces_pdefaut : accès par défaut utilisé pour l'accès anonyme et utilisateur dont l'identifieur ne fournit pas de données d'accès spécifique. 
			La valeur de type string dépend des accès définis dans le projet.
	$__VAcces_delai_saisie = délai au delà duquel on considère que la saisie d'un login/MdP
			a été réalisé. Permet de jauger la validité d'un retour suite à l'intervention 
			d'un identifieur externe (CAS, Shibboleth, AD, ...)  
	$__VAcces_mode : quasi obsolète, permet d'indiquer une identification par protection http
			 			s'il vaut 'serveur'
	$__VAcces_Anonyme : indique si l accès anonyme au projet est autorisé.
	$__VAcces_connect_xxquidam : à vrai : forçe la simulation d un accès anonyme si l usager n est pas identifié.
	$__VAcces_PageAppelee = Permet de renvoyer systématiquement vers une page à l'issue de l'identification.
	$__VA_identifieurs = tableau des identifieurs possibles (V. conf)

	$__VAcces_serveurs_CAS = configuration d'accès aux serveurs CAS (identifieurs de type CAS)
	$__VAcces_serveurs_LDAP =  configuration d'accès et aux données utilisées de serveurs LDAP.
		(identifieurs ou source de données LDAP)
	$__VAcces_serveurs_BDD =  configuration d'accès et aux données utilisées de serveurs de Base de données
		comme MySql. 	
	
	 $__VAImageFond = image de fond des formulaires standards
	 $__VA_Err_ViolationPage et $__VA_Err_AccesRefuse = messages d'erreur pour page inaccessible sans être 
	 		identifié ou aucun accès à l'application 
	
	=========================================================
	Fonction d adaptation/personnalisation de fonctionnement :
	
	ma_demande_identifieur : permet d ecrire son propre "WAYF" ou "Discovery Service" au sens Shibboleth.
	ma_demande_idt : permet d écrire son propre formulaire en cas d identification grâce à la base d Encycloped.
	__VAcces_corr_utilisateur = si une fonction de ce nom existe, elle est appeler pour finaliser l identification.

    Paramétrage dynamique a l appel HTTP :
	anonyme : 1 = accès anonyme. Permet de mettre un lien pour un accès anonyme.

*/


if (!isset($__VAcces_inipag) || $__VAcces_inipag<=0 || $__VAcces_inipag>2) {
	$__VAcces_inipag=0;
}

if (!isset($__VAcces_delai_saisie)){
	$__VAcces_delai_saisie=30;
}

if (!function_exists("__VAcces_identif")) {
	if ($test) 
		include_once (dirname(__FILE__)."/acces.lib_test.php");
	else 
		include_once (dirname(__FILE__)."/acces.lib.php");
}

switch ($__VAcces_inipag){
	case 1: 
	    $droits_etablis = '';
	    $utilisateur = array();
	    $srv_util = '';
	    break;
	case 0:
		$droits_etablis = __VA_droits() ;
		$utilisateur = __VA_utilisateur();
		$srv_util = __VA_identifieur();
		if ($droits_etablis && $utilisateur && $srv_util) {
			break;
		}
    	$mess = "ERR_VA : Cette page n'est pas une page d'acc&egrave;s initial &agrave; l'application."; 
    	if (isset ($__VApage)) $mess.= "<br />\n Page d'entr&eacute;e <a href=\"$__VApage\">ici</a>.";
    	if (isset ($__VA_Err_ViolationPage) && $__VA_Err_ViolationPage!='') $mess=$__VA_Err_ViolationPage ;
    	exit ($mess);    	
	default :
		__VAcces_verifie_usager();
		$droits_etablis = __VA_droits() ;
		$utilisateur = __VA_utilisateur();
		$srv_util = __VA_identifieur();
		break;
}



function __VAcces_verifie_usager(){
	global $__VA_MAP, $__VAcces_init
			,$__VAcces_delai_saisie
			,$__VAcces_connect_xxquidam,$__VAcces_Anonyme
			,$__VA_Err_ContactAdmin,$__VA_Err,$__VA_Err_AccesRefuse;
// Variable de message

	if ($__VAcces_init) {
		if 	(isset($_SESSION['_VAcces_utilisateur'])
			|| (isset($_SESSION['_VAcces_tmps_limite']) &&  mktime()>$_SESSION['_VAcces_tmps_limite'])
			){
			__VA_nettoie_session();		
		} else {
			$__VAcces_init=false;
		}
	} 
	
	if ($__VA_MAP) {
		if ($__VAcces_init) {
			echo ( "Initialisation...<br />\n"); 
		} else {
			echo "Entree sans initialisation : <br />\n";
		}
		__VA_echo_etat ();
	}
	
	if ( __VA_utilisateur()) 
		return (true);

// Entrée anonyme pour tout usager inconnu :
	if ($__VAcces_connect_xxquidam) {
	  __VA_met_xxquidam();
	  return (true);
  	}


    $_SESSION['_VAcces_tmps_limite'] = 
    	mktime (date("H") , date("i") , date("s")+$__VAcces_delai_saisie);
    if ($__VA_MAP) {echo  "<br />a ".$_SESSION['_VAcces_tmps_limite']."<br />\n"; }
    $droits_etablis = __VAcces_identif($utilisateur,$srv_util);
    if ($droits_etablis == '') {
		__VA_nettoie_session();
		$mess = "ERR_VA : vous n'avez aucun droit d'usage de cette application d'apr&egrave;s la configuration de Valid_acces.<br />\n";
		if ($__VA_MAP) $mess.=$srv_util . " connait ". __VA_print_r($utilisateur,true); 
    	if (isset ($__VA_Err_AccesRefuse) && $__VA_Err_AccesRefuse!='') $mess=$__VA_Err_AccesRefuse;
      	exit ($mess);
    }
    elseif (strpos($droits_etablis,'ERR:')===0)
      {
		__VA_nettoie_session();
      	$mess = "ERR_VA : "; $nat_err = substr($droits_etablis,4);
		if (isset($__VA_Err) && isset($__VA_Err[$nat_err])) $mess.=$__VA_Err[$nat_err];
		else $mess.= "Erreur d'&eacute;valuation de vos droits d'usage : $nat_err."; 
    	if (isset ($__VA_Err_ContactAdmin) && $__VA_Err_ContactAdmin!='') $mess.= "Veuillez contacter l'administrateur ".$__VA_Err_ContactAdmin;
		exit ($mess); 
      }
    unset ($_SESSION['_VAcces_tmps_limite']);
    $_SESSION['_VAcces_droit'] = $droits_etablis;
    if (function_exists('__VAcces_corr_utilisateur'))
      { __VAcces_corr_utilisateur($utilisateur,$srv_util);}
    $utilisateur ['identifieur']=$srv_util;
    $_SESSION['_VAcces_utilisateur']=$utilisateur;
	if ($__VA_MAP) { __VA_echo_etat () ;}
}
  
?>
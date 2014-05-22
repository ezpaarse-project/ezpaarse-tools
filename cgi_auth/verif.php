<?
/*
 * EZPROXY : CGI authentication tools.
 * Self information page for Users see their groups. 
 * Can help admin to confirm to user, they can access ( or not ) a resource.   
 */
/**
 * Use principal.php and could be compared with index.php but for debugging.  
 * This utility provide an admin method to see wich groups a user is a member using its host 
 * IP number and its properties returned by valid_acces filter .
 * You can also verify if provider recognize all of your EZPROXY's IPs (not on general on campus IPs)
 * in other if a resource is accessible with X or (and not) with Y IP / ...
 * You can verify all parameters of you CGI filter. 
 *  Can be ignored. if you have other tool to do that. 
 *  Don't request it in ezproxy user.txt file.
 *  

 * Module d'appel de principal.php en mode Debug/verif et permet son test. 
 * Il n'est pas sensé être requis, ni recevoir de ticket, de la part du reverse.
 * Il est réservé aux login ou IP master désignés ci-dessous en plus des quelques droits
 * particuliers déclarés dans acces_conf.php et reproter dans peut_tester de regles_groupage.php
 * Le test permet
 * 1/ de pister tout le fonctionnement de principal.php.
 * 2/ de faire un test d'une nouvelle version du module principal nommé principal_test.php si on
 * 		ajoute le paramètre test
 * Voir commentaire plus complet dans principal.php
 */


/*
 * You can test (or not if empty) as if user is known in a particular IdP 
 * Forçage ou non de l'identifieur  et autorisation pour lecteurs autorisés.
 */
$__VA_identifieur = "IDT_S1";
$__VA_identifieur = "" ;

/*
 * URL of your ezproxy service 
 * 
 * reverse proxy = URL:port 
 */
$reverse = "http://my-proxy.univ-fed.edu";
/*
 * Report Key given in user.txt for this CGI
 *  
 * Clé lié à la méthode d'encodage à reporter dans user.txt de la configuration de EzProxy
 */
$cle_ticket = 'secretKeyForEZProxy';

/*
 * Choice your language in locale
 * 
 * Choisissez votre langue (de locale)
 */
include_once("./locale/fr.php");

session_start();

$__VAcces_inipag=2;
$test = isset($_GET['test']);// || isset($_SESSION['sv_CGI_verif']['test']);
$Debug='verif'; 

if (!($_GET['tester'])){
		include_once ("./acces.conf.php");
		include_once (dirname(__FILE__)."/acces.lib.php");
		include_once "./regles_groupage.php";
		include_once ("./realise_regles.php");
		include_once './compare_IP.php';
	if (peut_tester() && $_GET['info']=='info') {
		phpinfo();
		exit();
	}
	$__VAcces_Anonyme = false;
	include_once ("./valid_acces.php");

	if (!peut_tester()) exit (trad_message('verif','sans_droit', __VA_droits()));
	if ($_GET['init']) unset ($_SESSION['sv_CGI_verif']);
	include_once ("./page_formulaire_verif.php");
	exit();
}

$_SESSION['sv_CGI_verif']=$_GET;
$complement_affichage = '<a href="verif.php">'.trad_message('verif','autre_verif').'</a>';

include ("./principal.php");	
	
exit();


?>
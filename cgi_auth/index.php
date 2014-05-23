<?
/*
 * EZPROXY : CGI authentication.
 *    
 */
/**
 * Environment configuration and root filter 

 * Configuration du CGI d'identification des usagers 
 * de son environnement d'appel
 * Voir commentaire plus complet dans principal.php
 */

session_start();

/*
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
 * Allows or Disables Anonymous access   
 */
$__VAcces_Anonyme = false;
/*
 * Force an IdP -  Forçage de l'identifieur
 * 
 */
//$__VA_identifieur = "IDT_S1";

/*
 * custumized WAYF/login-pass page - module de fonctions de personnalisation    
 */
//include_once 'page_identifieur.php';

include ("./principal.php");


exit();
?>
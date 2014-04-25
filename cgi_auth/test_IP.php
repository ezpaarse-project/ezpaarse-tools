<?
/**
 * Program to list the public hosts declared 
 * 
 * Module de test des postes publics (sans authentification) 
 */
$racine = dirname(__FILE__);
$lng = ($_GET['lng'])?$_GET['lng']:'en';
include_once("$racine/locale/$lng.php");


session_start();


/*
 * Forçage ou non de l'identifieur  et autorisation pour lecteurs autorisés.
 */
$__VA_identifieur = "My_CAS";
$__VAcces_Anonyme = false;
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
 * En test mettre à true
 */
$Debug = isset($_GET['debug']);
$test = isset($_GET['test']);
//$test = true; 

$__VAcces_init=true;
include ("./principal_test_IP.php");	
exit();
?>
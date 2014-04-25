<?php
/*
 *
 *  accesRef confuiguration files. (index.php and traitements/cree_list_hosts.php
 *  
 *  Configuration de tous les modules de l application de accesRef
 *  
 */

// Your ezproxy URL - L'URL de votre reverse proxy
$reverse = "my.library.edu"; $reverse = "ma.bibliotheque.fr";


/* 
 * If you have no link resolver service let these 2 resolveur_vars empty.
 *
 * Si vous n'avez pas de résolveur de lien, laissez ces variables commençant 
 * par $resolveur à vide. 
 */ 

//  id asked for a DOI number - idem avec requete pour un n° DOI
$resolveur_add_doi = 
   "http://link.resolver.com.my.library.edu/?case=doi";

//  id asked for a PMId number - idem avec requete pour un n° PMId
$resolveur_add_pmid =
    "http://resolveur.com.ma.bibliotheque.fr/?case=pmid";

// path to data file liste_hosts - creation du chemin du fichier de donnees liste_hosts 
$ce_repertoire = dirname(__FILE__);
$racinePhys  = preg_replace (":/conf\$:","",$ce_repertoire);
$dir_sources = $racinePhys."/hosts_domains";
$fic_liste = "liste_hosts";


?>
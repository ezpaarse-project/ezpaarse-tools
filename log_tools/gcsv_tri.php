#!/usr/bin/php
<?php
/*
 * Implantation :
 */
error_reporting(E_ALL);
$ce_repertoire = dirname(__FILE__);
$racinePhys  = preg_replace (":/traitements\$:","",$ce_repertoire);
$libs = $racinePhys."/libs";

/**
 * 
 * gcsv_tri.php V 0.1
 * Script qui sert à trier les ligne de plusieurs fichiers de logs (CSV) ou de l'entrée standard (utilisable
 * en pipe). Le tri s'effectue sur une ou plusieurs colonne des données source et cette clé est présente 
 * en tête de chaque ligne du résultat. 
 * Les lignes peuvent être complétées par :
- des colonnes indiquée (-col). Il y aura autant de ligne de même clé que de valeurs pour ces colonnes. 
- le nombre de lignes de l'original contenant la clé en tête (-cpt)
- en l'absence de -col et -cpt, la liste des couples fichier_source:numero_ligne où se trouve la clé.  
Si aucun fichier n'est indiqué en source (-src), c'est l'entrée standard qui est utilisée.
Si aucun fichier n'est indiqué en résultat (-res), c'est la sortie standard qui est utilisée.
Si aucun fichier n'est indiqué pour les lignes non intéressante (-rej), elles sont oubliées.
 * 
 *
 *  V0.1 : 
 *  	- utilisation de l'entrée standard pour un usage en pipe possible.
 * performance : 2s pour 17970 lignes triees donnant 3613 clés formees de deux de ses colonnes.
 * V0.2 :
 *      - possibilité d'utiliser des noms de colonnes comme dans les autres outils. 
 */
function montre_usage ($mess=''){
	echo $mess."\n";
	?>
	
Usage : 
./gcsv_tri.php [-aide|-help|-h] [(+|-)test] [-max int][-tmax int]  
	[-par parentheses_filename] [-sep "split_char"]  [-glu "glu_char"] 
	[(+|-)hd[1]] -colt namenum [namenum ...] [(-col namenum [namenum ...] [-multi] [-u] | -cpt)]       
     [-res result_filename] {-src source_filename [source_filename ...] | source_filename}
<?php 
	if ($mess) return(false);
?>               
Traite le(s) fichier(s) CSV source_filenameS en triant le résultat sur les colonnes désignées par colt. 
Au niveau des fichiers CSV source, il y deux parenthèseurs de valeur complexe ( i.e. colonne dont les valeurs peuvent contenir le séparateur de colonne), ce sont la double quote(") ou le couple crochet ouvrant/fermant ([]). 
Pour changer ces parenthèseurs, utiliser l'argument par suivi du nom d'un fichier contenant sur chaque ligne, deux caractères séparés par un espace, qui marquent le début et la fin d'une valeur complexe. 
Le séparateur de base est l'espace qui peut-être modifié par le paramètre sep suivi d'un caractère quoté. 
Le tri s'effectue sur une ou plusieurs colonne des données source (-colt) et cette clé est présente en tête de chaque ligne du résultat.
 Les lignes peuvent être complétées par :
- des colonnes indiquées (-col). Il y aura autant de ligne de même clé que de valeurs pour ces colonnes. 
- le nombre de lignes de l'original contenant la clé en tête (-cpt)
- en l'absence de -col et -cpt, la liste des couples fichier_source:numero_ligne où se trouve la clé.  
Si aucun fichier n'est indiqué en source (-src), c'est l'entrée standard qui est utilisée.
Si aucun fichier n'est indiqué en résultat (-res), c'est la sortie standard qui est utilisée.
Si aucun fichier n'est indiqué pour les lignes non intéressante (-rej), elles sont oubliées.  
Arguments :
    -aide , -help, -h : simple affichage de l'usage
	(+|-)test : Indique les arguments	reconnus et pris en compte. Avec le signe + mode bavard.
	-max n : nombre n de lignes à traiter. 
		Si non présent, l'intégralité des fichiers source est traitée.
	-tmax n : Durée max du traitement en secondes. 
		Si non présent, l'intégralité des fichiers source est traitée.
   	-sep char : précise le séparateur de colonne char à utiliser.
   		Mettre  t pour tabulations, s pour toutes les caractères invisibles. 
		(ou espaces généraux)
   	-glu char : précise le séparateur de colonne char à utiliser dans le resultat.
   		Mettre  t pour tabulations, n retour a la ligne r  pour retour chariot. 
	-par file : fichier de ligne contenant chacune deux chaines séparées 
		par un espace. La première représente la marque de début
		d'une valeur et la seconde la marque de la fin d'une valeur. 
		Les lignes non conformes sont ignorées.
	-xtrt PHP_filename : contient une fonction a_lecture_ligne($ligne_lue) 
	    qui corrige et retourne la ligne à traiter à partir de la ligne 
	    originale fournie en paramètre. 
    (+|-)hd,(+|-)hd1  : indique la presence de la ligne d'entete a conserver ou non dans le resultat. 
    	Si le chiffre 1 est present, seul le premier fichier source contient cette ligne. 
       Si elle existe sans etre declaree, elle sera traitee comme une autre ligne, elle ne peut être 
       omise si les colonnes sont designees par leur nom (de cette entete).
	-colt c1 c2 ... : les cn désignent les colonnes servant au tri. 
		L'ordre des colonne sera celui utilisé pour créer les clés de tri des lignes. 
	**	Résultat associé à chaque clé
    -col cr1 cr2 ... : colonnes formant autant de valeurs associées à chaque occurrence de
		la clé.  Toutefois, si -u est utilisé, on ne retient qu'un exemplaire de chaque 
		combinaison différente de ces n colonnes. 
    -cpt : pour n'obtenir que le nombre de lignes contenant  la clé dans le résultat. 
        Ne peut etre employe avec -col ou un des multiplexeurs suivants.
    sans -col ni cpt, c'est le couple fichier:ligne qui est associé a chaque fois que la clé
    	est trouvée
    -u : avec -col, une combinaison des colonnes associée à une clé n'est reporté qu'une
    	fois dans le résultat (unicité des valeurs).   
		i.e. : parmi les valeurs associées à chaque clé, certaines sont identiques car les 
		colonnes les formant ont les mêmes valeurs, dans ce cas, une seule occurrence 
		de la clé associée à ces valeurs sera retenue  
    	En d'autre terme , avec -multi, il n'y a pas en résultat deux lignes strictement
    	identiques.
    	
	**	Présentation des clés et valeurs associées
    -uk : pour clé unique = seule la première valeur compte, i.e. 
    	si la clé d'une ligne source a été deja ete rencontrée,  on ignore la ligne.
    	Option incompatible avec -cpt.
    -multi : indique que le resultat doit contenir autant de ligne, pour une clé, 
    	que de valeurs associées à cette clé sinon elles seront toutes ajoutées derrière la 
    	clé sur une même ligne. Cette option est incompatible avec -cpt.
    sans -uk ni -multi, chaque clé est sur une seule ligne, en tête de ligne, suivie de
    	toutes les valeurs qui lui sont associées. Le resultat n'est plus un gcsv. 

    Ainsi, une clé qui apparaît sur N lignes ayant C combinaisons differentes de n 
        colonnes designees par -col aura en resultat, selon le cas
        - ni -multi ni -uk, une ligne avec la clé suivie de 
        	- C*n colonnes avec -u,
        	- N*n colonnes sans -u. 
        - avec -multi, des lignes avec la clé suivie de n colonnes 
        	- au nombre de C avec -u,
        	- au nombre de N sans -u.
        - avec -uk, une ligne avec les n colonnes ayant la valeur trouvée sur la première
        	ligne contenant la clé.   
    	
    -res result_filename : précise le chemein du fichier à créer. Si cet argument
        est absent, c'est la sortie standard qui affiche les lignes triées.
    -src source_filenames : liste des fichiers à traiter. Si on en designe pas, c'est l'entree standard 
         qui fait office de source.
Exemples :
     ./gcsv_tri.php -test -colt 2 3 -res trie -src journal.txt.0
     ./gcsv_tri.php -colt 2 3 -sep t -res trie -src journal.txt.0
<?php
	return(true);
}

function  traite_ligne_header_mess ($no,$p){
	switch ($no){
		case '2DefCol' :
			return ("Double definition de la colonne $p dans l'entete.\n");
		case 'NomInv' : 
			return ("Nom de colonne invalide $p.");
		case 'EntAna' :
			return ("Entete analysee $p.");
		} 
}

function message ($no,$p) {
	if (is_array($p) && count($p)<5){
		$p0=$p[0];
		if (count($p)>1) $p1=$p[1];
		if (count($p)>2) $p2=$p[2];
		if (count($p)>3) $p3=$p[3];
	}
	switch ($no){
		case 'ComInv' :
			return ("invalide : $p\n  fournir pour le moins, clé de tri , source, ... \n ".
					"... pour précision mettre aide");	
		case 'ArgInc' :
			return ("Argument $p0 hors liste $p1.\n");
		case 'par=' :
			return ("Parametre = $p\n");
		case 'par_cpt' :
			return (" Memo du compte d occurrence\n");	
		case '-u+-uk' :
			return ("ERR : -u et -uk exclusif\n");
		case 'ArgInc' :
			return ("Argument inconnu $p.\n");
		case 'VProc' : 
			return (" $p0 pour $p1\n");
		case 'maxInv':
			return ("Le maximum de ligne max n'est pas un entier $p\n");
		case 'max=' :
			return (" Maximum de lignes traitees $p.\n");
		case 'tmaxInv':
			return ("Le maximum temps n'est pas un entier $p\n");
		case 'tmax=' :
			return (" Duree maximum des traitements $p.\n");
		case 'sep=':
			return (" Le separateur est \"$p\".\n");
		case 'sepDef' :
			return ("Separateurs par defaut, espaces, tabulation.\n");
		case 'glu=' :
			return(" Recoller avec \"$p\".\n");
		case 'res#autres':
			return ("Le fichier resultat doit etre different des autres fichiers (source, parentheseurs). \n");
		case '1Res' : 
			return("Ne donner qu'un seul fichier resultat. $p ignore.\n");
		case 'res=' :
			return (" Fichier resultat $p.\n");
		case 'src#autres' :
			return ("le fichier source $p est deja designe par ailleurs.\n");
		case 'src=' :
			return (" Fichier source $p.\n");
		case 'xtrt#autres' :
			return ("le fichier de traitement $p est deja designe par ailleurs.\n");
		case 'xtrt=' :
			return (" Fichier de traitement externe $p\n");
		case 'par#autres' :
			return ("le fichier de parenthèseurs $p est deja designe par ailleurs.\n");
		case 'FicPar=' :
			return (" Fichier de parenthèseurs $p.\n"); 
		case 'DbleCol':
			return ("Double emploi de la colonne $p\n");
		case 'ColTri' :
			return (" Colonne(s) de tri $p.\n");
		case 'ColRes' :
			return (" Colonne(s) resultat $p.\n");
		case 'ParInc' : 
			return ("Valeur inaffectable $p1 car parametre invalide $p0.\n");
		case 'ValSsPar' :
			return ("Valeur inaffectable $p.\n");
		case '-cpt+-multiOU-uk' :
			return ("ERR Emploi de -cpt exclue celui de -multi, -u ou -uk.\n");
		case '-multi+-uk' :
			return ("ERR Emploi de -multi exclue celui de -uk.\n");
		case '-cpt+-col' :
			return ("ERR Emploi de -col et -cpt simultanement.\n");
		case '-uSs-col' :
			return ("ERR Emploi de -u sans colonne.\n");
		case 'StopErr' :
			return ("Arret sur erreur : \n$p");
		case 'FicParInv' :
			return ("Fichier de parenthèseur invalide $p.\n");
		case 'estMulti':
			return ("- Mettre autant de ligne que de valeur associee a une cle.\n");
		case 'est-u':
			return ("- Ne pas repeter les lignes identiques.\n");
		case 'est-cpt' :
			return ("- Associer a chaque cle le nombre de ses occurrences.\n");
		case 'ImpOuvSrc' :
			return ("Pb ouverture de la source $p");
		case 'TMaxFait' :
			return ("Temps maximum de $p secondes atteint. \n");
		case 'ColCleAbs' :
			return ("ERR ligne sans cle $p0:$p1 : colonne $p2 absente. \n");
		case 'ColResAbs' :
			return ("ATT ligne $p0:$p1 sans colonne $p2. \n");
		case 'Conc1' :
			return ("$p0 fichiers lus de $p1 lignes.\n");
		case 'ImpRes' :
			return ("Impossible de creer le fichier resultat $p.\n");			
		case 'Conc' :
			return ("Lignes lue: $p0 ; lignes ecrite: $p1\n");
	}
}
$ce_repertoire = dirname(__FILE__);

include_once ("$ce_repertoire/gcsv_tri.corps.php");
?>
#!/usr/bin/php
<?php
/**
 * 
 * gcsv_injecteRef.php V 0.1
 * Script qui sert à injecter certaines colonnes d'un fichier de référence sur les lignes du flux 
 * d'entrée ou des fichiers traités. 
 * La détermination de la ligne de référence à utiliser se base sur l'identité de contenu d'une
 * ou plusieurs colonnes désignées comme formant  la clé.
Si aucun fichier n'est indiqué en source (-src), c'est l'entrée standard qui est utilisée.
Si aucun fichier n'est indiqué en résultat (-res), c'est la sortie standard qui est utilisée.
 * 
 *
 *  V0.1 : 
 *  	- utilisation de l'entrée standard pour un usage en pipe possible.
 * performance : 2s pour 17970 lignes triees donnant 3613 clés formees de deux de ses colonnes.
 */
function montre_usage ($mess=''){
	?>
Usage : 
./gcsv_injecteRef.php [-aide|-help|-h] [-test] [-max int][-tmax int]  
	[-par parentheses_filename] [-sep "split_char"] [-sepr "split_char"] [-glu "split_char"]
	[(+|-)hd[1]] [-hdr[1]]
	-colt namenum,namenum [namenum,namenum ...] [-colh namenum:format,namenum:format] 
	-col namenum[:default] [namenum[:default] ...] 
     [-src source_filename [source_filename ...]] (-ref filename [filename ...] | |-refv ref_name)
     [-res result_filename] [-resr total_reference_filename] [(-rej reject_filename| -forcedef)] 
     
<?php 
	if ($mess) {
		echo $mess."\n"; return(false);
	}
?>               
Traite le(s) fichier(s) CSV source_filenameS (source) en ajoutant a chaque lignes les colonnes d'une ligne 
d'un des fichiers de reference (reference).
La ligne utilisee en reference est celle dont les contenus des colonnes de test designees dans la source et dans
la reference par le parametre colt sont identiques. i.e. chaque paramètre fourni derrière colt est un couple de
colonne.
Pour lever la part de hasard lorsque plusieurs lignes de la reference verifient cette identite, on designe  
de la meme maniere par -colh une colonne en source et une colonne en reference contenant les dates qui  
permettront de departager les lignes de referentiel liees a la meme cle. -colh est suivi d'un couple
de numero et d'un format de date separe par ":" i.e. "No:fffff,NoR:ffffR". La ligne de reference ayant la date anterieure la plus proche 
de celle de la ligne traitee, sera retenue. 
 
Au niveau des fichiers CSV source ou de reference, le séparateur par defaut est l'espace. Pour modifier l'un ou 
les deux, utiliser -sep ou -sepr pour designer respectivement celui de(s) source(s) et celui des references. 
Pour le séparateur dans le résultat, utiliser -glu.


Pour permettre de traiter des colonnes contenant ces separateurs, on utilise des parentheseurs.
Il y deux parenthèseurs de valeur complexe qui sont la double quote(" ") et le couple de crochets 
ouvrant/fermant ([]). 
Pour changer ces parenthèseurs, utiliser l'argument par suivi du nom d'un fichier qui contient sur chaque ligne, 
deux caractères séparés par un espace. Le premier etant la parenthese ouvrante, le second la fermante. 

 Les colonnes peuvent être désignées par leur numéro (de 1 à N) ou par leur nom indique dans la
premiere ligne de chaque fichier source (-hd) ou uniquement celle du premier fichier source (-hd1). 
Elle est conservee dans le resultat si c'est le signe + qui est utilise (+hd ou +hd1). 
Idem pour les references avec les deux possibilites -hdr et -hdr1. 

 Le complement issu du referentiel sont les colonnes indiquées par (-col). Chacune peut se voir affectee une valeur
par defaut utilisee dans deux cas :
- Soit la cle est presente mais sans cette colonne.
- Soit la cle n'est pas presente dans le referentiel et le parametre -forcedef est utilise.
Dans ce second cas, toutes les colonnes a injecter doivent disposer d'une valeur par defaut.
 
Si aucun fichier n'est indiqué en source (-src), c'est l'entrée standard qui est utilisée.
Si aucun fichier n'est indiqué en résultat (-res), c'est la sortie standard qui est utilisée.
  
Arguments :
    -aide , -help, -h : simple affichage de l'usage
	-test : pour ne faire que compter les lignes retenues. 
		Indique aussi les arguments	reconnus et pris en compte.
	-max n : nombre n de lignes à traiter. 
		Si non présent, l'intégralité des fichiers source est traitée.
	-tmax n : Durée max du traitement en secondes. 
		Si non présent, l'intégralité des fichiers source est traitée.
   	-sep char : précise le séparateur de colonne char à utiliser.
   		Mettre  t pour tabulations, s pour toutes les caractères invisibles. 
		(ou espaces généraux)
   	-sepr char : précise le séparateur de colonne char des references.
   		Mettre  t pour tabulations, s pour toutes les caractères invisibles. 
		(ou espaces généraux)
   	-glu char : précise le séparateur de colonne char à utiliser dans le résultat.
   		Mettre  t pour tabulations ou entre " pour tout autre caractères. 
	-par file : fichier de ligne contenant chacune deux chaines séparées 
		par un espace. La première représente la marque de début
		d'une valeur et la seconde la marque de la fin d'une valeur. 
		Les lignes non conformes sont ignorées.
    -hd|-hd1|+hd|+hd1 : indique la presence de la ligne d'entete qui ne sera conservee dans le resultat que
       si le signe + est utilisé devant hd. 
       Si le chiffre 1 est present, seul le premier fichier source contient cette ligne. 
       Si elle existe sans etre declaree, elle sera traitee comme une autre ligne. Elle ne peut être omise
       si les colonnes sont designees par leurs noms au lieu de leurs numeros.
    -hdr|-hdr1  : indique la presence de la ligne d'entete dans le referentiel.
       Si le chiffre 1 est present, seul le premier fichier reference contient cette ligne. 
       Si elle existe sans etre declaree, elle sera traitee comme une autre ligne. Mais elle ne peut être omise
       si les colonne sont designees par leurs noms au lieu de leurs numeros.
	-xtrt PHP_filename : contient une fonction a_lecture_ligne($ligne_lue) 
	    qui corrige et retourne la ligne à traiter à partir de la ligne 
	    originale fournie en paramètre.   
	-colt c1,cr1 c2,cr2 ... : les couples cn,crn désignent les colonnes de la source et de la
	    reference qui forment la cle, I.e. qui doivent etre identiques pour selectionner la ligne 
	    de reference a utiliser. 
		Rem. L'ordre des de designation des colonnes est utilisé pour trier les cles utilisees et leur
		faire correspondre les colonne a injecter. La performance de l'ensemble implique que la colonne
		de la source ayant le plus grand nombre de valeurs soit indiquee en premier. 
		Mettre autant de couples cn,crn qu'il y a de colonnes qui forme la cle.
	-colh c:format,cr:formatr : meme principe pour LA colonne d'horadatage permettant de prendre en 
	    compte l'evolution du regerentiel. Format decrit en  http://fr2.php.net/manual/en/function.strftime.php
	    Mettre la valeur c:format,cr:formatr entre quote est preferable. 
	    (V.P.B. grandes lignes)
    -col c1[:def1] [c2[:def2] ...] : numero/nom des colonnes de la reference a inserer dans le 
        resultat suivi eventuellement de la valeur par defaut a mettre. Chacune doit en avoir
        si on utilise ...
    -forcedef :  indique que les lignes du source dont la cle est absente du referentiel se voient
        affectees de toutes les valeurs par defaut des colonne a injecter. Elle est incompatible
        avec l'usage de -rej 
    -res result_filename : précise le chemin du fichier à créer. Si cet argument
        est absent, c'est la sortie standard qui affiche les lignes triées.
    -resr reference_filename : précise le chemin d'un fichier de reference complet à créer. Si cet argument
        est absent, le referentiel etabli sera oublie apres traitement.
    -rej reject_filename : nom du fichier contenant les lignes dont la cle n'est pas dans le referentiel.
        Ne pas utiliser avec forcedef.
    -src source_filenames : liste des fichiers à traiter. Si on en designe pas, c'est l'entree standard 
         qui fait office de source.
    -ref as_reference_filenames : liste des fichiers contenant la reference.
    -refv reference_filenames : indique LE fichier de reference a utiliser et qui a été 
    	  produit par gcsv_injecteRef

Format d'horodatage :
     Tout élément d'information est symbolise par le signe % suivi d'une lettre. 
     Tout lettre non precedee de % ou autre caractere du format different de %, est interprete tel que.
     Dans la liste suivante, 
     litt = litteral / num = numerique / (n) fixe sur n lettres/chiffres. 
     Jour de la semaine :  %a = litt(3) %A = litt ; %u = num (1=lun-7=dim.) ; %w = num (0=dim-6=sam)
     Jour du mois : %d = num(2) ; %e = num ; de l'annee : %j = num(3)
     Mois : %b = %h = litt(3) ; %B = litt ; %m = num(2)
     Annee : %y : num(2) ; %Y = num(4)
     Heure : %H : 00-23 ; %k = 0-23 ; %I = 01-12 ; %p = AM/PM ; %P = am/pm
     Minute : %M = 00-59 Secondes %S = 00-59 
     Horaire %r=%I:%M:%S %p ; %T = %H:%M:%S    
     Caracteres echappes  %% = % %, = ,     
Exemples :
     ./gcsv_injecteRef.php -test -colt 4,2 -colh "3:[%d/%b/%Y %H:%M:%S +0100],1:[%Y/%m/%d %H:%M:%S]" -res stat \\
 -src ezproxy.log.* -ref journal.txt.0 -res pour_stat  -col 4 
     ./gcsv_injecteRef.php -colt 4,2 1,3 -sep t -res stable -src ezproxy.log.* -ref journal.txt.0 -ref journal.txt.0\\
 -col 4
<?php
	return(true);
}



/**
 * Message relatif au traitement de la ligne de heaedr qui consiste à établir un lien entre nom de colonne et sa position. 
 * @param string $ligne : chaîne (ligne d'entête ) contenant le format des lignes
 * @return boolean : vrai si OK. sinon étblit la chaîne globale $echo_err.  
 */
function  traite_ligne_header_mess ($no,$p=''){
	switch ($no){
		case '2defCol' :  return("Double definition de la colonne $p.\n");
		case 'colNonTrouvee' :
				return ("Nom de colonne $p non trouve dans l'entete.\n");
	}
}

	

function test_double_definition_col_mess ($no,$p=''){
	switch ($no){
		case 'colCle2': return ("Colonne cle des sources $p est designee une seconde fois.\n");
		case 'colCleR2': return ("Colonne cle de reference $p est designee une seconde fois.\n");
		case 'colInj2': return ("Colonne a injecter $p est designee une seconde fois .\n");
		case 'colH2' : return ("Colonne source contenant l'heure $p est designee une seconde fois.\n");
		case 'colH2' : return ("Colonne de reference contenant l'heure $p est designee une seconde fois .\n"); 
	}
}

function message ($no,$p=''){
	if (is_array($p) && count($p)<5){
		$p0=$p[0];
		if (count($p)>1) $p1=$p[1];
		if (count($p)>2) $p2=$p[2];
		if (count($p)>3) $p3=$p[3];
	}
		
	switch ($no){
		case 'argInv': 
			return ("invalide : $p\n  fournir pour le moins, clé de tri , source, ... \n ".
					"... pour précision mettre aide");
		case 'argINC':
			 return ("Argument $p inconnu.\n");
		case 'par=' : return ("Parametre = $p\n");
		case 'forcedefRej' :
			return ("Arguments incompatibles -forcedef et -rej $p.\n");
		case 'maxLigNnEnt':
			return ("Le maximum de ligne max n'est pas un entier $p\n");
		case 'maxLig' :
			return (" max de lignes traitees $p\n");
		case 'maxTpsNnEnt':
			return ("Le maximum temps n'est pas un entier $p\n");
		case 'maxTps' :
			return (" max duree des traitements $p\n");
		case 'sep=' : 
			return (" separateur=$p\n");
		case 'sepr=':
			return (" separateur referenetiel=$p\n");
		case 'glu=' : 
			return (" separateur en resultat=$p\n");
		case 'casef' :
			return (array	('res'=>'resultat'
							,'resr'=>'referentiel final'
							,'rej'=>'rejet'
							)
					);
		case 'FicDesigne2':  
			return ("Le fichier $p doit etre different des autres fichiers (source, reference, parentheseurs). \n");
		case '2FicCas' : 
			return ("Ne donner qu'un seul fichier $p.\n");
		case 'FicExist' :
			return ("Le fichier $p existe deja.\n");
		case 'EtatFicRes' : 
			return (" Fichier $p0 $p1\n");
		case 'FicDesigne2f' : 
			return ("Le fichier $p est designe plusieurs fois.\n");
		case 'FicInex' :
			return ("Le fichier $p n'existe pas.\n");
		case 'FicSrc' :
			return (" Fichier source $p\n");
		case 'ref+refv':
			return ("-ref et -refv incompatibles.\n");
		case 'refvSeul' :
			return ("-refv n'admet qu'un fichier de reference.\n");
		case 'FicRef' :
			return (" Fichier servant de reference $p\n");
		case '2FicTrait':
			return ("le fichier de traitement $p est deja designe par ailleurs.\n");
		case 'FicTrait' :
			return (" Fichier de traitement externe $p\n");
		case '2FicPar' :
			return ("le fichier de parenthèseurs $p est deja designe par ailleurs.\n");
		case 'FicParInv' :
			return ("le fichier de parenthèseurs $p est invalide.\n");
		case 'FicPar' :
			return (" Fichier de parenthèseurs $p\n");
		case 'ColAssocinv' :
			return ("Association de colonnes invalide $p.\n");	
		case 'ColAssoc' :
			return (" Colonnes cle correspndantes $p0 - $p1.\n");
		case '2ColH' :
			return ("Double definition des colonnes horaires $p.\n");
		case 'FormColHInv':
			return ("Colonnes horaires : formats associes absents $p.\n");
		case 'ColHAssoc' :
			return (" Colonnes horaires correspondantes $p0 - $p1.\n");
		case 'PlsrsDefauts':
			return ("Ne fournir qu'une valeur par defaut pour $p.\n");
		case 'ColInj' :
			return (" Colonne de referentiel a injecter $p\n");
		case 'ParamInv' :
			return ("Valeur inaffectable $p0 car parametre invalide $p1.\n");
		case 'ParamInc' :
			return ("Valeur $p sans paramètre désigné avant.\n");
		case 'NvGlu' :
			return ( "Caractere glu $p special.\n"); 
		case 'Def_colt+col' : 
			return ("Definir des couple de colonnes cle par colt et des colonnes a injecter par col.\n");
		case 'DefautOblige' :
			return ("Avec -forcedef, definir un defaut pour chaque colonne injectee.\n");
		case 'refOblige' : 
			return ("Indiquer au moins un fichier de references.\n");
		case 'hdrOblige' :
			return ("Reference : Mettre un parametre -hdr[1] pour indiquer la place des colonnes nommees.");
		case 'hdOblige' :
			return ("Source : Mettre un parametre -hd[1] pour indiquer la place des colonnes nommees.");
		case 'sepEst' :
			return ("Utiliser comme separateur $p .\n");
		case 'seprEst' :
			return ("Utiliser comme separateur dans le referentiel $p .\n");
		case 'maxLigEst' :
			return ("* Traiter un maximum de $p lignes.\n");
		case 'nbColsCle' : 
			return ("Cle sur $p colonne(s).\n");
		case 'FicResEst' :
			return ("- Mettre le resultat dans $p\n");
		case 'nbColInj' :
			return ("Injecter $p colonne(s) du referentiel.\n");
		case 'ERRHdRef' : 
			return ("Arret sur erreur dans l'entete de reference $p0 : \n$p1.\n");
		case 'ColrAbs' :
			return ("ERR reference $p0:$p1 : colonne cle $p2 absente.\n");
		case 'DtHeurInv' :
			return ("ERR reference $p0:$p1 : date/heure erronnee $p2.\n");
		case 'ATTColInjAbs':
			return ("ATT reference $p0:$p1 : colonne a injecter absente $p2. \n");
		case 'ErrOuvRef' :
			return ("Impossible d'ouvrir le referentiel $p.\n");
		case 'FinRef' :
			return ("Fin de lecture des $p lignes de reference.\n Tri par cle des valeurs ...\n");
		case 'ImpRefRes':
			return ("Impossible de creer le fichier referentiel $p.\n");
		case 'RefResOuv':
			return ("Fichier referentiel ouvert $p.\n");
		case 'FicRefInv':
			return ("Fichier referentiel invalide $p.\n");
		case 'CreeRefRes' :
			return ("Creation du referentiel...\n");
		case '2ValInj':
			return ("Double valeur de colonnes a injecter pour $p");
		case 'nbLigRefRes' :
			return ("Lignes ecrites dans $p0 : $p1.\n");
		case 'nbCles' :
			return ("Nbre de cles $p.\n");
		case 'ImpRes' :
			return ("Impossible de creer le fichier $p.\n");
		case 'ImpRej' :
			return ("Impossible de creer le fichier de rejet $p.\n");
		case 'ImpOuvSrc' :
			return ("Pb ouverture de la source $p");
		case '>TpsMax' :
			return ("Temps alloue depasse");
		case 'hdInv' :
			return ("Arret sur erreur dans l'entete de source $p0 :\n$p1\n");
		case 'ColCleAbs' :
			return ("ERR ligne sans cle $p0:$p1 : colonne $p2 absente. \n");
		case 'CleAbsRef' :
			return ("ERR ligne $p0:$p1 a cle non referencee : $p2.\n");
		case 'ColHAbs' :
			return ("ERR ligne sans horaire $p0:$p1.\n");
		case 'Conc' :
			return ("Lignes lue: $p0 ; lignes ecrite: $p1\n");
	}
}
$ce_repertoire = dirname(__FILE__);

include_once ("$ce_repertoire/gcsv_injecteRef.corps.php");

?>
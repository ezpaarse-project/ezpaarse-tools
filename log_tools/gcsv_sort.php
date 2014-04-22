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
 * gcsv_sort.php V 0.1
 * Sort lines of many CSV (family) files and produce a new zipped file or can used in pipe sequence.
 * Many columns can be used as sort key and put ahead the produced result lines.  
 * Behind key, result lines can be completed with  
 *  - content of other columns of the original line (not all) wich are designed with -col 
 *    parameter. There will be so many lines in result as different result col values.
 *  - the number of lines that have the same key value. Parameter -cpt   
 *  - the locations (file:line_number) list where the key is present.
 * If no input file (-src) is given, the standard input is used as input.
 * If no result (-res) is given, the standard outpput is used as output.
 * If no reject file is given, duplicate lines are forgotten.
 *
 *  V0.1 : 
 *  	- used of standard input/out as source/result file.
 * 		- performance : 2s to sort 17970 lines into 3613 result two columns keyed lines.
 * V0.2 :
 *      - extension to named columns and header line to get their location. 
 */
function montre_usage ($mess=''){
	echo $mess."\n";
	?>
	
Usage : 
./gcsv_sort.php [-aide|-help|-h] [(+|-)test] [-max int][-tmax int]
	[-par parentheses_filename] [-sep "split_char"]  [-glu "glu_char"] 
	[(+|-)hd[1]] -colt namenum [namenum ...] [(-col namenum [namenum ...] [-multi] [-u] | -cpt)]       
     [-res result_filename] {-src source_filename [source_filename ...] | source_filename}
<?php 
	if ($mess) return(false);
?>               
Sort lines of many CSV (family) files and produce a new zipped file or can used in pipe sequence.
  Many columns, following -colt parameter, can be used as sort key and reprot ahead the produced result lines. 
  Input and output file are general CSV files i.e. list of records written each one on a line.
  Each record is a list of ordered attributes separated in the line with a delimiter named 
  separator. As THE separator can eventually used in the attribute values, parentheses couples 
  of chars can be used at begin and end of value to allow parsing of a line. 
  Use -par parameter to design a file of lines of coupled parentheses and change the 2 
  default parentheses couples (" "  and [ ]).
  Use "-sep char" to change tab wich is default input separator and "-glu" to change output separator. 
  Le séparateur de base est l'espace qui peut-être modifié par le paramètre sep suivi d'un caractère quoté. 
  The key columns are written ahead in result records, followed with:
   - content of other columns of the original line (not all) wich are designed with -col 
     parameter. There will be so many lines in result as different result col values.
   - the number of lines that have the same key value. Parameter -cpt   
   - the locations (file:line_number) list where the key is present.
  If no input file (-src) is given, the standard input is used as input.
  If no result (-res) is given, the standard outpput is used as output.
  If no reject file is given, duplicate lines are forgotten.
Parameters :
    -help, -h : display this manual message.
	(+|-)test : to debug the meaning of a gcsv_sort command line. With + prefix 
	-max n : maximum number of lines to sort (default : all lines)
	-tmax n : maximum time in seconds to do the job.
   	-sep char : to change input separator to char. Letter t is used as alias of tab char  
   		and letter s as alias of all space characters (tab, space, Vert tab, ...). 
   	-glu char : to change input separator. Same aliasing as before in -sep. 
	-par file : parentheses couple file (only one). It must contain lines composed 
		with 3 chars : opening parenthese, a white space, closin parenthese. 
		Invalid lines are ignored.  
	-xtrt PHP_filename : PHP program file containing a_lecture_ligne($ligne_lue) and/or
		a_ecriture_ligne(array(key_columns, result columns)) to manage an input line before 
		parsing it or an output column content before writing it .
    (+|-)hd,(+|-)hd1  : say a header line is present (only in the first source file with hd1)
    	in source and if it has to be written in result if + prefix is used (instead of - 
    	prefix). If the file(s) have header line(s) without the presence of this parameter in
    	the command, this (ese) line(s) will be used as other and the result is then faulted. 
	-colt c1 c2 ... : c1 c2 ... are the place number (from 1 to ...) or the name of the columns
		used to form the sort key.
	**	Key associated values in the result:
    -col cr1 cr2 ... : columns number/name list to copy behind the key in the result.
     	Used with -u, only the first combined column value is kept in the result values 
     	for the key. 
    -cpt : the result value is the count of key occurences. Then, there is only one value 
    	that is associated with a key. It cannot be used with -col, nor -u, _uk or _multi.        
    Without -col nor -cpt, the couple filename:line is associated with the key for each line
    	line containing key. 
    	
	**	Présentation des clés et valeurs associées
    -u : (unique value) with -col, only the first combined column value is kept in the result 
    	values for the key. i.e. : identical associated value are kept only once in the 
    	result. So, used with -multi, there will not be to identical lines. 
    -uk : (unique key) say that only the first value will be associated with a key i.e. the
    	 combination of columns -col found, or first filename:linenumber couple formed, 
    	 on the first line containing the key. Don't use with -cpt.
    -multi : to have in result as many lines as there are values associated with a key. 
    	Don't use with -uk option. If not present,  
    Without -uk nor -multi, all associated values will follow the
    	key in a lonely line and result file is no more a gcsv file. 

    Ex: a key wich is found in N lines and having C different combinations of n result columns
    	named with -col parameter, will be written
        - without -multi nor -uk, on one line followed by  
        	- C*n columns if -u is used,
        	- else, N*n columns. 
        - if -multi is used, the key followed with the n columns, is repeated 
        	- in C different lines with -u,
        	- or, without -u, in N not all different lines. 
        - with -uk, on one line followed by the first combination of n columns.

    -res result_filename : to create a gzip file with the output result. If this option is
    	omitted, the standard output will be used for the result. 
    -src source_filenames : imust be followed with the list of the source file names. If  
         omitted, the standard input is used as source.
Examples :
     ./gcsv_sort.php -test -colt 2 3 -res trie -src journal.txt.0
     ./gcsv_sort.php -colt 2 3 -sep t -res trie -src journal.txt.0
<?php
	return(true);
}

function  traite_ligne_header_mess ($no,$p){
	switch ($no){
		case '2DefCol' :
			return ("Twice use of $p column .\n");
		case 'NomInv' : 
			return ("Invalid column name $p.");
		case 'EntAna' :
			return ("Parsed header : $p.");
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
			return ("Invalid command line : $p\n  must contains at least sort columns or -help to have some help.\n ");	
		case 'ArgInc' :
			return ("Unknown parameter $p0: out of $p1.\n");
		case 'par=' :
			return (" Parameter = $p\n");
		case 'par_cpt' :
			return (" Result vaue is count.\n");	
		case '-u+-uk' :
			return ("ERR : -u et -uk exclusif\n");
		case 'ArgInc' :
			return (" Unknown parameter $p.\n");
		case 'VProc' : 
			return ("  Parameter $p1 = $p0 \n");
		case 'maxInv':
			return ("Invalid maximum line number $p\n");
		case 'max=' :
			return (" Maximum lines to sort $p.\n");
		case 'tmaxInv':
			return ("Invalid timeout $p for the sort.\n");
		case 'tmax=' :
			return (" Timeout limit $p seconds.\n");
		case 'sep=':
			return (" Separator \"$p\".\n");
		case 'sepDef' :
			return ("Default separators are white space and tabulation.\n");
		case 'glu=' :
			return(" Result separator \"$p\".\n");
		case 'res#autres':
			return ("Result file must be different of other files. \n");
		case '1Res' : 
			return("Only one result file can be named. $p is ignored.\n");
		case 'res=' :
			return (" Result file $p.\n");
		case 'src#autres' :
			return ("Source file $p is used twice.\n");
		case 'src=' :
			return (" Source file $p.\n");
		case 'xtrt#autres' :
			return ("PHP file must be different of other files.\n");
		case 'xtrt=' :
			return (" PHP file $p\n");
		case 'par#autres' :
			return ("Parentheses file must be different of other files.\n");
		case 'FicPar=' :
			return (" Parentheses file $p.\n"); 
		case 'DbleCol':
			return ("Twice use of $p column\n");
		case 'ColTri' :
			return (" Sort column(s) $p.\n");
		case 'ColRes' :
			return (" Result column(s)  $p.\n");
		case 'ParInc' : 
			return ("Not known parameter  $p0 before $p1.\n");
		case 'ValSsPar' :
			return ("None parameter before $p.\n");
		case '-cpt+-multiOU-uk' :
			return ("ERR Don't use -multi, -u or -uk with -cpt.\n");
		case '-multi+-uk' :
			return ("ERR Don't use -uk with -multi.\n");
		case '-cpt+-col' :
			return ("ERR Don't use -col with -cpt.\n");
		case '-uSs-col' :
			return ("ERR Don't use -u without -col.\n");
		case 'StopErr' :
			return ("Stop on error: \n$p");
		case 'FicParInv' :
			return ("Invalid parentheses file $p.\n");
		case 'estMulti':
			return ("- as many lines as associated values with a key.\n");
		case 'est-u':
			return ("- don't repeat identical lines.\n");
		case 'est-cpt' :
			return ("- count of occurences as result with each key.\n");
		case 'ImpOuvSrc' :
			return ("Cannot open source file $p");
		case 'TMaxFait' :
			return ("Timeout $p secondes is over. \n");
		case 'ColCleAbs' :
			return ("ERR line $p0:$p1 has no key : no $p2 column. \n");
		case 'ColResAbs' :
			return ("WAR line $p0:$p1 without $p2 column. \n");
		case 'Conc1' :
			return ("$p0 source files = $p1 lines.\n");
		case 'ImpRes' :
			return ("Cannot write $p result file.\n");			
		case 'Conc' :
			return ("Lines in: $p0 / out: $p1\n");
	}
}
include_once './gcsv_tri.corps.php';
?>
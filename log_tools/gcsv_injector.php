#!/usr/bin/php
<?php
/**
 * 
 * gcsv_injector.php V 0.1
 * Script to add columns from a referential file, in CSV files. Injection is based on key columns 
 * equality between ref file and injected files.
 * None source file is interpreted as standard input as source.
 * None result file is interpreted as standard output as result. 
 */
function montre_usage ($mess=''){
	?>
Usage : 
./gcsv_injector.php [-aide|-help|-h] [-test] [-max int][-tmax int]  
	[-par parentheses_filename] [-sep "split_char"] [-sepr "split_char"] [-glu "split_char"]
	[(+|-)hd[1]] [-hdr[1]]
	-colt namenum,namenum [namenum,namenum ...] [-colh namenum:format,namenum:format] 
	-col namenum[:default] [namenum[:default] ...] [-forcedef]
     [-src source_filename [source_filename ...]](-ref filename [filename ...] | |-refv ref_name)
     [-res result_filename] [-resr total_reference_filename] [(-rej reject_filename| -forcedef)] 
     
<?php 
	if ($mess) {
		echo $mess."\n"; return(false);
	}
?>               
Inject new data columns in  CSV source files (or standard input data) with columns data found
 in reference CSV files (needed).
Data used as in reference files to add are these found in the unique line which has the same 
 values in key columns than those in source key columns.
But if two reference lines are eligible, the timestamp -colh column is used to select the nearest 
 old  value to be used. -colh is followed as the other key columns by two colums index coupled 
 with time format i.e. "No:fffff,NoR:ffffR". 
 
Default split-character or delimiter used for source or reference parsing is space. 
 To change it, source split-character use -sep parameter and -sepr parameter for reference 
 delimiter parsing.
 For result, use -glu parameter to force another delimiter. 

As columns can contain the CSV column delimiter, it's possible to define characters couple to parenth their
  values. These couples are found in "-par" parameter followed with a file name in the command line. 
  Default is that these column values must be enclosed between double quotes("") or left/right bracket([]).

Every column parameter can be followed by a name or a number (place order) of the column.
  First case, the place of named columns are mapped after the first line has been read 
  (header column_names line).

Columns injected from reference file(s) can be named or ordered after the -col parameter.
Each ref column can have a default value used in two ways:
- key columns are present but not this columns in reference used
- key of source is unknown in reference and -forcedef parameter is used.
If -forcedef is used, every injected column must be default valued. 
 
If the command don't have a -src source_files parameter, standard input is used data to be filtered.
If the command don't have a -res result_file parameter, standard output is used to write out the result.
If the command don't have a -rej cast_file parameter, for casted lines, these are lost.
  
Arguments :
    -aide , -help, -h : display this help.
	-test : show accepted arguments and at end of output, the number of readen/written lines.
	-max n : to limit to n the number of treated lines. The rest of file is simply ignored 
			in result. If none -max given, all source  files shall be treated. 
	-tmax n : limit to s seconds the time of filtering. Not treated lines are ignored. 
		If none -max given, all source  files shall be treated.
   	-sep char : set the input delimiter char to c. Use t for tab character, s for space char 
   	-sepr char : the same for refential files 
   	-glu char : the same for result. 
	-par file : file of parens couple lines. Each line contains 
	     - two strings separated with space (first string represents the left parens, the second the right one) 
	     - deux chars (first the left, second the right)
	     - only one char that is the left and right parens itself.
	-xtrt PHP_filename : PHP with two string functions : 
		string function a_lecture_ligne(string input_line) {} 
	    string a_ecriture_ligne(array result_columns) {}
	    First give a corrected line before filter, second give the line to write to result stream.
    (+|-)hd,(+|-)hd1  : say to keep or not this header line or not on the first line of result. Followed by 1
    	digit says that only the first file as a header line (only with -src parameter followed by many 
    	filenames or wildcarded name). 
       If no hd parameter is present, first lines of file are seen as other lines.
       If column are used in the command, +/-hd / -format MUST be present.
    (+|-)hdr,(+|-)hdr1 : the same for reference files 
    -colt c1,cr1 c2,cr2 ... : Couples of source,reference columns name/num . These columns must be identical
    	to link a reference line with a source line.
		Rem. Order of these columns implies the keystring used to link together source and reference.
		That has matter to performance of matching link between reference and source. Use the most valued 
		column first, even if you would do the contrary.
	-colh c:format,cr:formatr : is as colt but for time columns. The date/hour of this column
	    is used to decide what value to add when two (or more) lines have the same key.
	    The last recent line is retained.
	    A format string is necessary to analyse the content of this column. It has the syntax 
	    describe on http://fr2.php.net/manual/en/function.strftime.php
	    You have quote your c:format,cr:formatr string to avoid problem. 
	    (V.P.B. grandes lignes)
    -col c1[:def1] [c2[:def2] ...] : columns number/name from refernece to inject into source 
        line. Each can be associated with a default value wich is used if column isn't valued in 
        refernce or even (-forcedef) if key isn't found in reference. If -forcedef is used,
        each column must have a default value.
    -forcedef :  force the injection of default values and say that all lines must be present in
        result file. Cannot be used with -rej option.
    -res result_filename : result file path. If the command has no result file, the result 
    	shall be written on standard output.
    -resr result_reference_filename : reference file path. If present, a reference file will 
        be created result.
    -rej reject_filename : result invalid lines file path. If no present, these lines will
        be forgotten. Cannot be used with -forcedef.
    -src source_filenames : source file path list. If none in the command line, standard input 
        will be the source. 
    -ref as_reference_filenames : original reference file path list . Needed if none -refv. Cannot be
        used with -refv
    -refv reference_filenames : THE  reference validate file path obtained by a precedent 
        gcsv_injector.php.  Needed if none -ref but cannot be used WITH -ref. 

Date/hour format :
     Each element begins with the % char followed by a letter. 
     Other letters (not preceded with %), are parsed as letter.
     In the following list  
     litt is a litteral / num a numeric value / (n) for n fixed length. 
     Weekday :  %a = litt(3) %A = litt ; %u = num (1=mon-7=sun.) ; %w = num (0=sun-6=sat)
     Monthday  : %d = num(2) ; %e = num ; Yearday : %j = num(3)=001-366
     Month : %b = %h = litt(3) ; %B = litt ; %m = num(2)
     Year : %y : num(2) ; %Y = num(4)
     Hour : %H : 00-23 ; %k = 0-23 ; %I = 01-12 ; %p = AM/PM ; %P = am/pm
     Minute : %M = 00-59 Seconds %S = 00-59 
     Composed hour %r=%I:%M:%S %p ; %T = %H:%M:%S    
     Escape character % to escape :  
       "%"  i.e. %%r is equivalent %r 
       ","  i.e. b%,a is equivalent to  b,a     
Examples :
     ./gcsv_injector.php -test -colt 4,2 -colh "3:[%d/%b/%Y %H:%M:%S +0100],1:[%Y/%m/%d %H:%M:%S]"\\
 -src ezproxy.log.* -ref ref.txt -res my_result  -col 4 
     ./gcsv_injector.php -colt 4,2 1,3 -sep t -res stable -src ezproxy.log.* -ref journal.txt.0 -ref journal.txt.0\\
 -col 4
<?php
	return(true);
}


/**
 * Messages used .
 * Association of a code and a message. Some parameters can be included in the message    
 * @param string $no: message code
 * @param string $p : parameter value.
 * @return string : the message.  
 */

function  traite_ligne_header_mess ($no,$p=''){
	switch ($no){
		case '2defCol' :  return("Column $p is used twice or more.\n");
		case 'colNonTrouvee' :
				return ("Column name $p not found in the header.\n");
	}
}

	

function test_double_definition_col_mess ($no,$p=''){
	switch ($no){
		case 'colCle2': return ("Source key column $p used twice.\n");
		case 'colCleR2': return ("Reference key column $p used twice.\n");
		case 'colInj2': return ("Added column $p used twice.\n");
		case 'colH2' : return ("Timecolumn  $p used twice.\n");
		case 'colH2' : return ("Reference timecolumn  $p used twice.\n"); 
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
			return ("invalide : $p\n  you must use -colt (association key columns), reference, or use -help.\n");
		case 'argINC':
			 return ("Unknown argument $p.\n");
		case 'par=' : return ("Parameter = $p\n");
		case 'forcedefRej' :
			return ("Cannot use -forcedef and -rej $p together.\n");
		case 'maxLigNnEnt':
			return ("Max lines $p must be a number.\n");
		case 'maxLig' :
			return (" Max lines $p reached before end of source file(s).\n");
		case 'maxTpsNnEnt':
			return ("Max time $p must be a number of seconds.\n");
		case 'maxTps' :
			return (" Max time $p seconds reached before end of source file(s).\n");
		case 'sep=' : 
			return (" separator=$p\n");
		case 'sepr=':
			return (" referencial separator=$p\n");
		case 'glu=' : 
			return (" result separator=$p\n");
		case 'casef' :
			return (array	('res'=>'result'
							,'resr'=>'written reference'
							,'rej'=>'rejected'
							)
					);
		case 'FicDesigne2':  
			return ("The $p file must be different from other source files (sources, references, parentheses). \n");
		case '2FicCas' : 
			return ("You have to name a lone $p file.\n");
		case 'FicExist' :
			return ("$p exists. Delete it before try again.\n");
		case 'EtatFicRes' : 
			return (" File $p0 $p1\n");
		case 'FicDesigne2f' : 
			return (" $p is used more than once.\n");
		case 'FicInex' :
			return ("$p file is unknown.\n");
		case 'FicSrc' :
			return (" Source file $p\n");
		case 'ref+refv':
			return ("-ref cannot be used with -refv.\n");
		case 'refvSeul' :
			return ("-refv can be followed with a lone file name/path.\n");
		case 'FicRef' :
			return (" Reference file $p\n");
		case '2FicTrait':
			return ("PHP library file $p is used more than once.\n");
		case 'FicTrait' :
			return (" PHP library file $p \n");
		case '2FicPar' :
			return ("Parentheses file $p used twice.\n");
		case 'FicParInv' :
			return ("Invalid parentheses file $p.\n");
		case 'FicPar' :
			return (" Parentheses file $p\n");
		case 'ColAssocinv' :
			return ("Invalid columns association $p.\n");	
		case 'ColAssoc' :
			return (" Key associated columns $p0 - $p1.\n");
		case '2ColH' :
			return ("Invalid date/hour columns $p. Only one can be used.\n");
		case 'FormColHInv':
			return ("Unknown format for date/hour column $p.\n");
		case 'ColHAssoc' :
			return (" Date/hour associated columns $p0 - $p1.\n");
		case 'PlsrsDefauts':
			return ("Only one default value can be given for $p.\n");
		case 'ColInj' :
			return (" Reference column to inject $p\n");
		case 'ParamInv' :
			return ("Value $p0 is ignored because follow an invalid parameter $p1.\n");
		case 'ParamInc' :
			return ("Value $p follow none parameter name.\n");
		case 'NvGlu' :
			return ( "Gluing character $p.\n"); 
		case 'Def_colt+col' : 
			return ("You must use at least,  one key column name following -colt \n".
				"and one value column to inject.\n");
		case 'DefautOblige' :
			return ("With -forcedef, you must give default value foreach column to inject.\n");
		case 'refOblige' : 
			return ("You must precise -ref or -refv to have a reference data to use.\n");
		case 'hdrOblige' :
			return ("Reference : -hdr[1] is needed to convert column names to there position order number.");
		case 'hdOblige' :
			return ("Source : -hd[1] is needed to convert column names to there position order number.");
		case 'sepEst' :
			return ("Separator used $p in source.\n");
		case 'seprEst' :
			return ("Separator used $p in reference file.\n");
		case 'maxLigEst' :
			return ("* Max lines to $p treat.\n");
		case 'nbColsCle' : 
			return (" $p column(s) for key.\n");
		case 'FicResEst' :
			return ("- Write $p result file\n");
		case 'nbColInj' :
			return ("Add/inject $p column(s) from refrence lines content.\n");
		case 'ERRHdRef' : 
			return ("ERR: invalid header in ref. file $p0 : \n$p1.\n");
		case 'ColrAbs' :
			return ("ERR reference $p0:$p1 : key column $p2 is empty.\n");
		case 'DtHeurInv' :
			return ("ERR reference $p0:$p1 : invalid date/hour $p2.\n");
		case 'ATTColInjAbs':
			return ("ATT reference $p0:$p1 : value column $p2 is empty. \n");
		case 'ErrOuvRef' :
			return ("ERR cannot open referential file $p.\n");
		case 'FinRef' :
			return ("$p reference records read in reference file.\n Sort the values...\n");
		case 'ImpRefRes':
			return ("Cannot write result referential file $p.\n");
		case 'RefResOuv':
			return ("Referential file $p is opened.\n");
		case 'FicRefInv':
			return ("Invalid referential file $p.\n");
		case 'CreeRefRes' :
			return ("Writing referential file ...\n");
		case '2ValInj':
			return ("2 different values in injected columns for the key $p.\n");
		case 'nbLigRefRes' :
			return ("$p1 lines has been written in $p0.\n");
		case 'nbCles' :
			return ("$p keys found.\n");
		case 'ImpRes' :
			return ("Cannot write/create $p result file.\n");
		case 'ImpRej' :
			return ("Cannot write/create $p reject file.\n");
		case 'ImpOuvSrc' :
			return ("ERR: Cannot open $p.");
		case '>TpsMax' :
			return ("Max time to process exceeded.\n");
		case 'hdInv' :
			return ("ERR: invalid source file header in $p0 :\n$p1\n");
		case 'ColCleAbs' :
			return ("ERR: key unknown record on line $p0:$p1 : $p2 column empty. \n");
		case 'CleAbsRef' :
			return ("ERR unknown key $p2 on line $p0:$p1.\n");
		case 'ColHAbs' :
			return ("ERR: empty date/hour column $p0:$p1.\n");
		case 'Conc' :
			return ("$p0 source read ; $p1 result lines written.\n");
	}
}
$ce_repertoire = dirname(__FILE__);

include_once ("$ce_repertoire/gcsv_injecteRef.corps.php");
?>
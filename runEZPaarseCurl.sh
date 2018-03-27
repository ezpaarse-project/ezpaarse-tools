ids=(./input/*)

outputPath="./EZVis/ezpaarse"
declare -i count=1

ids1="${ids[@]}"
for filepath in $ids1;
do

  if (($count == 1));
  then
  	echo "$filepath"
  	idsFile="-F \"files[]=@$filepath\""
	echo "$idsFile" 
	curlCommand="curl -X POST http://localhost:59599 -H 'Log-Format-ezproxy: %t - %U %m %s %b \"%{user-agent}<.*>\"' -H 'Accept:text/csv' -H 'Traces-Level:info' -H 'COUNTER-Reports:JR1' -H 'COUNTER-Format:tsv' -H 'Job-Report-jr1:/home/hsteele/Desktop/report_output.tsv' $idsFile > $outputPath/EZPaarseReport_$count.csv"
  	eval "$curlCommand"

  else
    	echo "$filepath"
        idsFile="-F \"files[]=@$filepath\""
	echo "$idsFile"
        curlCommand="curl -X POST http://localhost:59599 -H 'Log-Format-ezproxy: %t - %U %m %s %b \"%{user-agent}<.*>\"' -H 'Accept:text/csv' -H 'Traces-Level:info' -H 'COUNTER-Reports:JR1' -H 'COUNTER-Format:tsv' -H 'Job-Report-jr1:/home/hsteele/Desktopreport_output.tsv' $idsFile >> $outputPath/EZPaarseReport_$count.csv"

        eval "$curlCommand"


  fi

  let "count=count+1"


done

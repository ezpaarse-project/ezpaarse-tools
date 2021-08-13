#!/bin/bash

# merge ezproxy config with ezpaarse manifest file

# $1 : ezproxy config file
# $2 : platform manifest file


function usage(){
    printf "Usage : "
    printf $(basename $0)
    printf " <ezproxy config file> <ezpaarse manifest file>\n"
	printf "\nAdd new domains to ezPAARSE manifest file from ezproxy config file\n"
}

while getopts ":h" opt; do
  case $opt in
    h)
      usage
      exit 1
      ;;
    :)
      echo "Option -$OPTARG nécessite un argument." >&2
      exit 1
      ;;
  esac
done

if [ $# != 2 ]
then
    printf "Must have 2 arguments (found $#)\n"
    usage 
    exit 1
fi

# have to test jq is here
CHECK=$(jq -h)
if [ $? != 0 ]
then
  echo "jq not found. You have to install it"
  usage
  exit 1
fi

NEW_DOMAINS=new_domains_file.json
EZPROXY_CONFIG=$1
MANIFEST=$2

if [[ -f ${EZPROXY_CONFIG} ]]; then
    cat ${EZPROXY_CONFIG} | grep '^DJ' | sed -e 's/^#\?DJ //' -e 's/http.?:\/\///'| sort -u | jq -R -n '{new_domains:[inputs]}' > ${NEW_DOMAINS}
    jq -n 'reduce inputs as $i ({}; . * $i)|.domains=.domains+.new_domains|del(.new_domains)' ${MANIFEST} ${NEW_DOMAINS}
fi

rm ${NEW_DOMAINS}





#!/usr/bin/python
# coding: utf8
'''	Richer.py 

		It excepts one arguement : the log to parse.
		It appends to each line some ldap attributes related to the login used.
		The login is the third field of each line.

		The script generated a file called : "rapport.log"
'''

import sys, string, re, ldap  #yum install python-ldap


def attribute_ldap ( uid ):
	''' Fuction attributeLdap
			takes uid and return needed attributes in a hash
			Attributes = ['eduPersonAffiliation','supannEtuCursusAnnee','departmentNumber']
	'''
	YOUR_LDAPSERVER = ""
	YOUR_BASEDN = "" #For instance ou=student,dc=domain,dc=com

	try:
		l = ldap.initialize('ldap://'+YOUR_LDAPSERVER)
		l.simple_bind_s()

		basedn = YOUR_BASEDN
		filter = "(uid=" + uid + ")"
		attributs = ['eduPersonAffiliation','supannEtuCursusAnnee','departmentNumber']
		results = l.search_s(basedn,ldap.SCOPE_SUBTREE,filter,attributs)
	except ldap.LDAPError, e:
		print e
	l.unbind_s()
	return results

def enrich_uid ( uid ):
	
	'''Function enrichUid
			Add in a dict, ldap attributes, key is the uid
			following this rules :
			-if student -> return department and cursus
			-if other than student -> return department and status (employee,..)
			-default returned = - -'''
				
	if uid == "-" :
		dn="-['- -'])"
		return dn 

	results =  attribute_ldap ( uid )

	for dn,entry in results:
		affiliation=str(entry.get('eduPersonAffiliation', "NoAffiliation"))
		cursus=str(entry.get('supannEtuCursusAnnee', "['-']"))
		department=str(entry.get('departmentNumber', "-"))
		attr1=str(department)
		if ( affiliation == "['student']" ):
			attr2=cursus
		else:
			attr2=affiliation
		attr= (attr1, attr2)
		return attr	

def clean_line ( s ):
	'''Just clean the attr tuple for insert in each line of logs'''
	s =  s[3:-3].replace("', '","+").replace("']\", \"['"," ").replace("'","").replace("{SUPANN}","")
	return s

				
readerDictionnary = {}
log = open(sys.argv[1], "r")
lines = log.readlines()
log.close
nb_lines = len(lines)

reg = re.compile('.+\s-\s(?P<id>[0-9a-z-]+) .*')

cpt_lines=0
cpt_requests=0

rapport = open('rapport.log','w')

for line in lines:
		cpt_lines+=1
		print(str(cpt_lines)+"/"+str(len(lines)))
		regMatch = reg.match(line)

		if regMatch:
			linebits=regMatch.groupdict()
			uid= str((linebits['id'])).split(',')[0].replace('uid=','')
			if uid in  readerDictionnary:
				rapport.write(line.replace('\n','') + " " + clean_line(str(readerDictionnary[uid])) + "\n" )
			else:
				cpt_requests+=1
				readerDictionnary[uid]=enrich_uid( uid )
				rapport.write(line.replace('\n','') + " " + clean_line(str(readerDictionnary[uid])) + "\n" )			
		else:
			print("RegNotMatch")

print( "\t\t" + str(cpt_requests) + " requêtes LDAP" )
print( "\t\tRapport généré : rapport.log.")

# Affichage des utilisateurs:
#for i in readerDictionnary:
#	range(1,2)
#	d = range(*readerDictionnary[i])
	
#	print i +" " + str(readerDictionnary[i].iteritems())
#	print d 

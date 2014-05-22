Redirection tool that take effects only for the proxied resources URL.
Can be used through a javascript working on diplayed page and registered as a markup : 
javascript:function%20o(){window.location.replace('http://my.library.edu/accesRef/index.php?url='+location.href,"OffCampusAccess")};o()

This markup redirects the browser to the proxied URL only if the proxy is configured to relay the URL.
It can be also used with a form witch have post or get parameter url set by an text input. On that way, it can also redirect 
DOI Id or PMId to your favorite resolver. 
!!! Customization 
conf /conf.inc.php : 
 - $reverse is URL of your reverse proxy (ezproxy, bibliopam, ...) 
  - $resolveur_add_doi and $resolveur_add_pmid are URLs of your resolver respectivly for DOI and PMId rewriting.
  -  $dir_sources (DHD below) the directory and $fic_liste (liste_hd below) the filename which contains  the domain list of
  the resources you can to proxy
   
DHD/ : copy in this directory all configuration files of your ezproxy . Then use traitements/cree_liste_hosts.php to create :
DHD/liste_hd : filename of hosts and domains to redirect.à créer avec le programme ...
!!! Before first copy and after every configuration file change, you have to run :
traitements/cree_liste_hosts.php : to update liste_hd file and two status debugging  files : general and conflits in DHD.
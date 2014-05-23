<?php


/**
 * Web page which redirects the user to 
 * - link resolver of institution if it receives a resource identifier as DOI or PMId
 * - reverse proxy of library if it receives an URL of a proxied domain 
 * - the received URL if none upper condition is satisfied.
 * To be configured in conf/conf.inc.php. 
 * To use it with you own tools, try to catch 
 *   http://your.library/somewhere/accesRef/?url=ValueOfURL_UserTryToCatch 
 * 
 * Page Web à configurer dans conf/conf.inc.php pour permettre, selon l'url passée en 
 * paramètre, de rediriger la requête vers :
 * - votre résolveur de lien s'il s'agit d'un identifiant DOI ou PMId de ressource
 * - votre reverse proxy si le domaine de l'URL fait partie de vos abonnements
 * - la ressource dans les autres cas 
 * 
 */
require_once './accesRef.lib.php';
$MAP = false;
$url = traite_donnee();
$onload =  ($url)? " onload=\"window.location='$url';\" ":"";
	/* eventuellement ,"menubar=no, status=no, scrollbars=no, menubar=no, width=200, height=100" */
$messages = ($comm || $err)?"$err $comm <br />":"";
?>

<html>
<head>
<link rel="shortcut icon" href="http://bu.univ-lorraine.fr/sites/all/themes/BU/favicon.ico" type="image/vnd.microsoft.icon" />
  <title>Acces ref bibliographique</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  
  <SCRIPT TYPE="text/javascript">
<!--
function submitenter(myfield,e)
{
var keycode;
if (window.event) keycode = window.event.keyCode;
else if (e) keycode = e.which;
else return true;

if (keycode == 13)
   {
   myfield.form.submit();
   return false;
   }
else
   return true;
}
//-->
</SCRIPT>

</head>
  <body topmargin="0" marginwidth="0" marginheight="0"<?=$onload?> >
<form name=donne_url style="font-size:0.8em;font-family: Arial, Helvetica, sans-serif;">
 <?php echo $messages;?>
Coller ici l'URL ou le n&deg; du document : <input type=text name=url  onKeyPress="return submitenter(this,event);"> 
        <input TYPE="image" src="ok.png" onClick="this.form.submit();"  name="ok">
</form>

</body>
</html>
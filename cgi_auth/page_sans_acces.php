<?php
/*
 * EZPROXY : CGI authentication tools.
 * TO CUSTUMISE error page for access denied by the CGI 
 */
header( 'Cache-Control: no-cache' );
  header( 'Cache-Control: must-revalidate' );
  header( 'Pragma: no-cache' );
  header( 'Expires: 0' );
    ?>
  <html>
  <head>

  <title>Access denied</title>
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
  <body>
  
 <div align=center>
  <img src="bandeau_titre.jpg" alt="" />

	<h2>Access denied - Vous ne semblez pas pouvoir acc&eacute;der aux ressources en ligne  !</h2>
	<p>To get precision ...</p>
	<p>Pour avoir des pr&eacute;cisions, ....  
	</p>  
</div>
	
  </body>
  </html>
<?
  exit();


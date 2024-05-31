<?php
// Thomas Schilling 2021
$message = array();
$message_css = "";
function
changePassword($user,$oldPassword,$newPassword,$newPasswordCnf){
  global $message;
  global $message_css;
  $server = "localhost";
  $dn = "dc=bsp,dc=server,dc=de";
  $rootdn = "cn=nsspam,dc=bsp,dc=server,dc=de";
  $rootpwd = "Passwort";
  error_reporting(0);
  ldap_connect($server);
  $con = ldap_connect($server);
  ldap_set_option($con, LDAP_OPT_PROTOCOL_VERSION, 3);
  // bind
  $ldapBind = ldap_bind($con,$user,$oldPassword); 
  $user_search = ldap_search($con,$dn,"(uid=$user)");
  $user_get = ldap_get_entries($con, $user_search);
  $user_entry = ldap_first_entry($con, $user_search);
  $user_dn = ldap_get_dn($con, $user_entry);
  $user_id = $user_get[0]["uid"][0];
  $user_givenName = $user_get[0]["givenName"][0];
  $user_search_arry = array( "*", "ou", "uid", "mail",
"passwordRetryCount", "passwordhistory" );
  $user_search_filter = "(|(uid=$user_id)(mail=$user))";
  $user_search_opt =
ldap_search($con,$user_dn,$user_search_filter,$user_search_arry);
  $user_get_opt = ldap_get_entries($con, $user_search_opt);
  $passwordRetryCount = $user_get_opt[0]["passwordRetryCount"][0];
  $passwordhistory = $user_get_opt[0]["passwordhistory"][0];
  $message[] = "Username: " . $user_id;
  /* Produktiv ausblenden */
  $message[] = "DN: " . $user_dn;
  $message[] = "altes Passwort: " . $oldPassword;
  $message[] = "neues Passwort: " . $newPassword;
  /* Start */
  if ( $passwordRetryCount == 3 ) {
    $message[] = "Error E101 - Userkonto gesperrt!";
    return false;
  }
  if (ldap_bind($con, $user_dn, $oldPassword) === false) {
    $message[] = "Error E999 - User/Passwort falsch.";
    return false;
  }
  if ($newPassword != $newPasswordCnf ) {
    $message[] = "Error E102 - Passwort stimmt nicht ueberein!";
    return false;
  }
  $salt = openssl_random_pseudo_bytes(12);
  $encoded_newPassword = "{SSHA}" . base64_encode( hash('sha1',
$newPassword . $salt, true ) . $salt );
  if (strlen($newPassword) < 8 ) {
    $message[] = "Error E103 - Passwort zu kurz.";
    return false;
  }
  if (!preg_match("/[0-9]/",$newPassword)) {
    $message[] = "Error E104 - Zahlen fehlen.";
    return false;
  }
  if (!preg_match("/[a-zA-Z]/",$newPassword)) {
    $message[] = "Error E105 - Gross und Kleinbuchstaben.";
    return false;
  }
  if (!preg_match("/[A-Z]/",$newPassword)) {
    $message[] = "Error E106 - Kleinbuchstabe.";
    return false;
  }
  if (!preg_match("/[a-z]/",$newPassword)) {
    $message[] = "Error E107 - Grossbuchstaben.";
    return false;
  }
   if (!preg_match("/[^A-Za-z0-9]/",$newPassword)) {
    $message[] = "Error E110 - Sonderzeichen.";
    return false;
  }
   if ($oldPassword == $newPassword) {
    $message[] = "Error E111 - Nicht gleiches Passwort verwenden.";
    return false;
  }
  if (!$user_get) {
    $message[] = "Error E200 - Nichts wurde geaendert.";
    return false;
  }
  $auth_entry = ldap_first_entry($con, $user_search);
  $mail_addresses = ldap_get_values($con, $auth_entry, "mail");
  $given_names = ldap_get_values($con, $auth_entry, "givenName");
  $mail_address = $mail_addresses[0];
  $first_name = $given_names[0];
  /* Aenderung schreiben */
  $entry = array();
  $entry["userPassword"] = "$encoded_newPassword";
$X = ldap_bind($con,$rootdn,$rootpwd);
  if ($X = ldap_modify($con,$user_dn,$entry) === false){
    $error = ldap_error($con);
    $errno = ldap_errno($con);
    $message[] = "E201 - Nichts wurde geaendert.";
    $message[] = "$errno - $error";
  } else {
    $message_css = "yes";
 $message[] = "Passwort erfolgreich geändert";
  }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>Neues Passwort vereinbaren</title>
<style type="text/css">
body { font-family: Verdana,Arial,Courier New; font-size: 0.7em; }
th { text-align: right; padding: 0.8em; }
#container { text-align: center; width: 500px; margin: 5% auto; }
.msg_yes { margin: 0 auto; text-align: center; color: green;
background: #D4EAD4; border: 1px solid green; border-radius: 10px;
margin: 2px; }
.msg_no { margin: 0 auto; text-align: center; color: red; background:
#FFF0F0; border: 1px solid red; border-radius: 10px; margin: 2px; }
</style>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
</head>
<body>
<p style="text-align:center;"><img width='150' src='xxx.PNG' alt='Logo'
/></p>
<div id="container">
<h2>Passwort ändern</h2>
<p>Das neue Passwort muss mindestens 8 Zeichen lang sein!<br/>
Gross- Kleinbuchstaben, Sonderzeichen und Zahlen <b>müssen</b>
verwendet werden.<br/>
<br/></p>
<?php
      if (isset($_POST["submitted"])) {
changePassword($_POST['username'],$_POST['oldPassword'],$_POST['newPassword1'],$_POST['newPassword2']);
        global $message_css;
        if ($message_css == "yes") {
          ?><div class="msg_yes"><?php
         } else {
          ?><div class="msg_no"><?php
          $message[] = "Nicht geändert.";
        }
        foreach ( $message as $one ) { echo "<p>$one</p>"; }
      ?></div><?php
      } ?>
<form action="<?php print $_SERVER['PHP_SELF']; ?>"
name="passwordChange" method="post">
<table style="width: 400px; margin: 0 auto;">
<tr><th>Username:</th><td><input name="username" type="text"
size="20px" autocomplete="off" /></td></tr>
<tr><th>Passwort:</th><td><input name="oldPassword" size="20px"
type="password" /></td></tr>
<tr><th>Neues Passwort:</th><td><input name="newPassword1" size="20px"
type="password" /></td></tr>
<tr><th>Neues Passwort wiederholen:</th><td><input name="newPassword2"
size="20px" type="password" /></td></tr>
<tr><td colspan="2" style="text-align: center;" >
<input name="submitted" type="submit" value="Passwort ändern"/>
<button
onclick="$('frm').action='changepassword.php';$('frm').submit();">Abbruch</button>
</td></tr>
</table>
</form>
</div>
</body>
</html>

<?php

$path = dirname(__FILE__);
$tmp_path = explode("/", $path);

// header('Content-Type: text/html \n\n');
// echo "FOO: $tmp_path[2] and $tmp_path[4] from $path ";

$domain = $tmp_path[4];
$user = $tmp_path[2];

$base = "/home/$user/web/$domain/public_html/autodiscover";
$ssl_base = "/home/$user/conf/web/$domain/ssl";

if (!isset($_POST['name']) || !isset($_POST['email'])) {
	show_home($domain);
	exit;
}

// check the user and domain path exists
if (!file_exists($ssl_base)) {
  echo "Error: $ssl_base does not exist";
  exit;
}


function UUIDv4() {
  $data = PHP_MAJOR_VERSION < 7 ? openssl_random_pseudo_bytes(16) : random_bytes(16);
  $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
  $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

$UUID1 = UUIDv4();
$UUID2 = UUIDv4();

$AccountName = $_POST['name'];
$MailAddress = strtolower($_POST['email']);

// Profilname
$MailServer    = "mail.$domain";
$DisplayProfil = "Mail Server";
$DisplayName   = "Mail for $MailAddress";
$Description   = "Mail: $MailAddress";
$Identifier    = "$MailAddress";
$Organization  = "$domain (iOS)";

$data = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<!DOCTYPE plist PUBLIC \"-//Apple//DTD PLIST 1.0//EN\" \"http://www.apple.com/DTDs/PropertyList-1.0.dtd\">
<plist version=\"1.0\">
<dict>
    <key>PayloadContent</key>
    <array>
	<dict>
	    <key>EmailAccountDescription</key>
	    <string>$Description</string>
	    <key>EmailAccountName</key>
	    <string>$AccountName</string>
	    <key>EmailAccountType</key>
	    <string>EmailTypeIMAP</string>
	    <key>EmailAddress</key>
	    <string>$MailAddress</string>
	    <key>IncomingMailServerAuthentication</key>
	    <string>EmailAuthPassword</string>
	    <key>IncomingMailServerHostName</key>
	    <string>$MailServer</string>
	    <key>IncomingMailServerPortNumber</key>
	    <integer>995</integer>
	    <key>IncomingMailServerUseSSL</key>
	    <true/>
	    <key>IncomingMailServerUsername</key>
	    <string>$MailAddress</string>
	    <key>IncomingPassword</key>
	    <string></string>
	    <key>OutgoingMailServerAuthentication</key>
	    <string>EmailAuthPassword</string>
	    <key>OutgoingMailServerHostName</key>
	    <string>$MailServer</string>
	    <key>OutgoingMailServerPortNumber</key>
	    <integer>465</integer>
	    <key>OutgoingMailServerUseSSL</key>
	    <true/>
	    <key>OutgoingMailServerUsername</key>
	    <string>$MailAddress</string>
	    <key>OutgoingPasswordSameAsIncomingPassword</key>
	    <true/>
	    <key>PayloadDescription</key>
	    <string>$Description</string>
	    <key>PayloadDisplayName</key>
	    <string>$DisplayName</string>
	    <key>PayloadIdentifier</key>
	    <string>E-Mail $Identifier</string>
	    <key>PayloadOrganization</key>
	    <string>$Organization</string>
	    <key>PayloadType</key>
	    <string>com.apple.mail.managed</string>
	    <key>PayloadUUID</key>
	    <string>$UUID1</string>
	    <key>PayloadVersion</key>
	    <integer>1</integer>
	</dict>
    </array>
    <key>PayloadDisplayName</key>
    <string>$DisplayProfil</string>
    <key>PayloadDescription</key>
    <string>$Description</string>
    <key>PayloadIdentifier</key>
    <string>$Identifier</string>
    <key>PayloadOrganization</key>
    <string>$Organization</string>
    <key>PayloadType</key>
    <string>Configuration</string>
    <key>PayloadUUID</key>
    <string>$UUID2</string>
    <key>PayloadVersion</key>
    <integer>1</integer>
</dict>
</plist>";


$filenameOut = "$base/$UUID1.signed";

file_put_contents($filenameOut, $data);

/* give the user the .mobilconfig file */
header('Content-Type: application/x-apple-aspen-config; chatset=utf-8');
header("Content-Disposition: attachment; filename=\"$MailAddress.mobileconfig\"");
readfile($filenameOut);

/* remove temporary files */
unlink($filenameOut);

function show_home($domain) {

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <meta name="author" content="Andy Newby">
  <meta name="google" content="notranslate">

  <title>Configuration of Apple devices</title>

  <!--  jquery + bootstrap -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <script src="https://code.jquery.com/jquery-2.2.4.min.js" integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44=" crossorigin="anonymous"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

  <!-- bootstrap form validator -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/1000hz-bootstrap-validator/0.11.9/validator.min.js"></script>
</head>
<body>

<div class="container-fluid">
<div class="row">
    <div class="col-xs-12 col-sm-8 col-md-6 col-sm-offset-2 col-md-offset-3">
        <form data-toggle="validator" method="POST" action="" novalidate="true">
            <fieldset>
                <h2>Configuration of Apple devices</h2>
                <hr class="colorgraph">
                <div class="form-group has-feedback has-success">
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-user fa-fw"></i></span>
                    <input type="text" name="name" required="" class="form-control input-lg" placeholder="Name">
                  </div>
                  <span class="glyphicon form-control-feedback glyphicon-ok"></span>
                  <div class="help-block with-errors"></div>
                </div>
                <div class="form-group has-feedback has-success">
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-envelope fa-fw"></i></span>
                    <input type="text" name="email" pattern="^[_A-z0-9\-]{1,}$" required="" class="form-control input-lg" placeholder="Your email..." data-error="Please only enter the part before the @ in your email">
                    <span class="input-group-addon" id="basic-addon2">@<?php echo $domain ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                  </div>
                  <span class="glyphicon form-control-feedback glyphicon-ok"></span>
                  <div class="help-block with-errors"></div>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-lg btn-success btn-block" value="Generate configuration file...">
                </div>

            </fieldset>
        </form>
    </div>
</div>
</div>

</body>
</html>

<?php

}

?>

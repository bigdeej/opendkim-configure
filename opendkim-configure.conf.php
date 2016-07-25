<?php

$conf->daemon_path = '/usr/sbin/opendkim';
$conf->install_path = '/etc/opendkim/';
$conf->selector = 'mail';
$conf->path->keys = '/etc/opendkim/keys/';
$conf->path->KeyTable = '/etc/opendkim/KeyTable';
$conf->path->SigningTable = '/etc/opendkim/SigningTable';
$conf->path->TrustedHosts = '/etc/opendkim/TrustedHosts';

function stdin() {
	$varin = trim(fgets(STDIN));
	return $varin;
}




function get_opendkim_dot_conf() {
$con = "# Log to syslog
Syslog                  yes

# Required to use local socket with MTAs that access the socket as a non-
# privileged user (e.g. Postfix)
UMask                   002

# KeyTable, SigningTable, and TrustedHosts
KeyTable                /etc/opendkim/KeyTable
SigningTable            /etc/opendkim/SigningTable
ExternalIgnoreList      /etc/opendkim/TrustedHosts
InternalHosts           /etc/opendkim/TrustedHosts
X-Header                yes";

return $con;
}

function get_default_opendkim() {
$con = 'SOCKET="inet:8891@localhost"';

return $con;
}

?>

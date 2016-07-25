<?php

require_once 'opendkim-configure.conf.php';

echo "OpenDKIM Configuration Tool\n";
echo "\n";


// Check if opendkim is installed (check for daemon binary)
$configure = false;
while (!file_exists($conf->daemon_path)) {
	echo "You do not currently have OpenDKIM Installed.\n";
	echo "This tool can install and configure OpenDKIM for you.\n";
	echo "Install and Configure OpenDKIM now?[y]: ";
	
	$opt = trim(stdin());
	if ($opt == 'y') {
		$configure = true;
		
		// Install OpenDKIM
		$cmd = 'apt-get -y install opendkim';
		echo $cmd;
		stdin();
		
		echo "Installing...\n";
		exec($cmd, $output);
		foreach ($output as $line) { echo $line . "\n"; }
		echo "\n\n";
	}
	else { die("\n"); }
	
	echo "\n";
}


// Configure
if ($configure) {
	// Create /etc/default/opendkim
	$con = get_default_opendkim();
	file_put_contents('/etc/default/opendkim', $con);
	
	
	// Create /etc/opendkim.conf
	$con = get_opendkim_dot_conf();
	file_put_contents('/etc/opendkim.conf', $con);
	
	
	// Create $conf->install_path folder
	mkdir($conf->install_path, 700);
	chown($conf->install_path, 'opendkim');
	chgrp($conf->install_path, 'opendkim');
	
	
	// Create $conf->path->keys folder
	mkdir($conf->path->keys, 700);
	chown($conf->path->keys, 'opendkim');
	chgrp($conf->path->keys, 'opendkim');
	
	// Create files
	file_put_contents($conf->path->KeyTable, "");
	file_put_contents($conf->path->SigningTable, "");
	file_put_contents($conf->path->TrustedHosts, "127.0.0.1\nlocalhost");
}



$running = true;
while ($running) {

echo "Selector:  " . $conf->selector . "\n";
echo "Options:\n";
echo "0 - View All Domains\n";
echo "1 - View KeyTable\n";
echo "2 - View SigningTable\n";
echo "3 - View TrustedHosts\n";
echo "D - Add New Domain\n";
echo "S - Change Selector\n";
echo "Q - Quit\n";
echo "\n";
echo "Option[0]: ";

// get option
$opt = strtolower(stdin());

switch ($opt) {
	
	case 1:
	// View Files
	$con = file_get_contents($conf->path->KeyTable);
	echo $conf->path->KeyTable . "\n$con\n\n";
	break;
	
	
	case 2:
		$con = file_get_contents($conf->path->SigningTable);
	echo $conf->path->SigningTable . "\n$con\n\n";
	break;
	
	
	case 3:
	$con = file_get_contents($conf->path->TrustedHosts);
	echo $conf->path->TrustedHosts . "\n$con\n\n";
	break;
	
	
	// Show/Create domain
	case "d":
	echo "New Domain: ";
	$domain = stdin();
	get_domain($domain);
	break;
	
	
	// Change Selector
	case "s":
		// Choose Selector
		echo "Choose Selector:\n";
		echo "Selector(" . $conf->selector . "): ";
		$s = trim(stdin());
		if (strlen($s) > 0 && $s >= 0) { $conf->selector = $s; }
		echo "New Selector: " . $conf->selector . "\n";
		echo "\n";
	break;
	
	
	// Quit
	case "q":
		// Set running to false
		$running = false;
		
		
		// Set file permissions
		$cmd = 'chmod -R 700 ' . $conf->path->keys;
		echo $cmd . "\n";
		exec($cmd, $output);
		echo implode("\n", $output) . "\n";
		$cmd = 'chown -R opendkim:opendkim ' . $conf->install_path;
		echo $cmd . "\n";
		exec($cmd, $output);
		echo implode("\n", $output) . "\n";
		
		
		// Restart opendkim
		$cmd = '/etc/init.d/opendkim restart';
		echo $cmd . "\n";
		exec($cmd, $output);
		echo implode("\n", $output) . "\n";
		
	break;
	
	
	// Show all domains
	default:
	$running2 = true;
	while ($running2) {
		// View all domains (dir scan)
		$domains = scandir($conf->path->keys);
		$domains = array_diff($domains, array('.','..'));
		$domains = array_values($domains);
		
		echo "Choose a Domain:\n";
		for ($fcount = 0; $fcount < count($domains); $fcount++) {
			$folder = $domains[$fcount];
			echo "\t$fcount - $folder\n";
		}
		echo "b - Back To Main Menu\n";
		echo "\n";
		
		
		// Get domain choice
		echo "Domain[]: ";
		$dopt = trim(stdin());
		
		// Valid choice - Show Domain
		if (strlen($dopt) > 0 && strtolower($dopt) != "b" && $dopt >= 0 && $dopt < count($domains)) { get_domain($domains[$dopt]); }
		else { break; }
	}
}
}



function get_domain($domain) {
global $conf;
 
 echo "\n\n\nDomain:\t\t" . $domain . "\n\n";
 
 $path = $conf->path->keys . $domain;
 
 $running = true;
 while ($running) {
	// See if folder exists
	if (!file_exists($path)) {
		echo "Would you like to create the folder now?\n";
		echo "Create Folder(y): ";
		$opt = stdin();
		
		// Create Folder
		if ($opt == "y") {
			// See if folder exists
			if (file_exists($path)) { echo $path . "\t\tAlready Exists!\n"; }
			else {
				if (mkdir($path, 0700)) { echo $path . "\t\tCreated!\n"; }
				else { die("Could not create:\t" . $path . "\nExiting now...\n"); }
			}
			
			// Back to top
			continue;
		}
//		else { $running = false; break; }
	}
	
	
	$options = array();
	
	// Check if keys exist
	$key_private = $path . '/' . $conf->selector . '.private';
	$key_public = $path . '/' . $conf->selector . '.txt';
	if (file_exists($key_private) && file_exists($key_public)) {
		$public_dkim = file_get_contents($key_public);
		
		echo "Public DKIM Key: Yes\n";
		echo "Private DKIM Key: Yes\n";
		array_push($options, "R1 - Remove DKIM Key");
		array_push($options, "1 - Re-Generate New DKIM Key");
	}
		else {
		echo "Public DKIM Key: No\n";
		echo "Private DKIM Key: No\n";
		array_push($options, "1 - Generate DKIM Key");
	}
	
	
	// See if domain is in KeyTable
	$keytable = file_get_contents($conf->path->KeyTable);
	$keytable = explode("\n", $keytable);
	$index = -1;
	for ($i = 0; $i < count($keytable); $i++) {
		$line = $keytable[$i];
		
		// Check line for domain and selector
		$pos = strrpos($line, $domain);
		$pos2 = strrpos($line, $conf->selector);
		if ($pos !== false && $pos2 != false) {
			$index = $i;
			break;
		}
	}
	if ($index >= 0) {
		$line = $keytable[$index];
		echo "KeyTable: Yes\n";
		array_push($options, "R2 - Remove Domain From KeyTable (Only Current Selector)");
	}
	else {
		echo "KeyTable: No\n";
		array_push($options, "2 - Add Domain To KeyTable");
	}
	
	
	
	// See if domain is in SigningTable
	$signingtable = file_get_contents($conf->path->SigningTable);
	$signingtable = explode("\n", $signingtable);
	$index = -1;
	for ($i = 0; $i < count($signingtable); $i++) {
		$line = $signingtable[$i];
		$pos = strrpos($line, $domain);
		$pos2 = strrpos($line, $conf->selector);
		if ($pos !== false && $pos2 != false) {
			$index = $i;
			break;
		}
	}
	if ($index >= 0) {
		$line = $signingtable[$index];
		echo "SigningTable: Yes\n";
		array_push($options, "R3 - Remove Domain From SigningTable (Only Current Selector)");
	}
	else {
		echo "SigningTable: No\n";
		array_push($options, "3 - Add Domain To SigningTable");
	}
	
	
	// See if domain is in TrustedHosts
	$trustedhosts = file_get_contents($conf->path->TrustedHosts);
	$trustedhosts = explode("\n", $trustedhosts);
	$index = -1;
	for ($i = 0; $i < count($trustedhosts); $i++) {
		$line = $trustedhosts[$i];
		$pos = strrpos($line, $domain);
		if ($pos !== false) {
			$index = $i;
			break;
		}
	}
	if ($index >= 0) {
		$line = $trustedhosts[$index];
		echo "TrustedHosts: Yes\n";
		array_push($options, "R4 - Remove Domain From TrustedHosts");
	}
	else {
		echo "TrustedHosts: No\n";
		array_push($options, "4 - Add Domain To TrustedHosts");
	}
	
	// Add extra options
	if (file_exists($key_private) && file_exists($key_public)) {
//		array_push($options, "R - Remove ");
		array_push($options, "s - Show Public DKIM Key");
	}
	array_push($options, "b - Back To Main Menu");
	
	
	// Show all options
	echo "Selector:  " . $conf->selector . "\n";
	echo "\nOptions:\n";
	foreach ($options as $o) { echo $o . "\n"; }
	echo "\n";
	
	
	echo "Option: ";
	$opt = stdin();
	echo "\n\n\n";
	switch ($opt) {
		case "s":
			echo "Public DKIM Key:\n\n";
			$key_public = $path . '/' . $conf->selector . '.txt';
			echo file_get_contents($key_public) . "\n";
		break;
		
		
		// Generate new keys
		case 1:
			// switch to dir
			$cwd = getcwd();
			chdir($path);
			
			// create keys
			$cmd = 'opendkim-genkey -s ' . $conf->selector . ' -d ' . $domain;
			exec($cmd, $output);
			
			// Switch back to cwd
			chdir($cwd);
			
			
			// check if keys were created
			$key_private = $path . '/' . $conf->selector . '.private';
			$key_public = $path . '/' . $conf->selector . '.txt';
			if (file_exists($key_private) && file_exists($key_public)) { echo "DKIM Keys Generated!\n"; }
			else { echo "Error Generating DKIM Keys!\n"; }
			
		break;
		
		case "R1":
			// check if keys were created
			$key_private = $path . '/' . $conf->selector . '.private';
			$key_public = $path . '/' . $conf->selector . '.txt';
			if (file_exists($key_private) && file_exists($key_public)) {
				echo "Removing Keys...\n";
				unlink($key_private);
				unlink($key_public);
			}
		break;
		
		
		// Add domain to KeyTable
		case 2:
			// See if domain is in KeyTable
			$keytable = file_get_contents($conf->path->KeyTable);
			$keytable = explode("\n", $keytable);
			$index = -1;
			for ($i = 0; $i < count($keytable); $i++) {
				$line = $keytable[$i];
				$pos = strrpos($line, $domain);
				$pos2 = strrpos($line, $conf->selector);
				if ($pos !== false && $pos2 != false) {
					$index = $i;
					break;
				}
			}
			
			// Domain not in KeyTable
			if ($index < 0) {
				$line = $conf->selector . '._domainkey.' . $domain . ' ' . $domain . ':' . $conf->selector . ':' . $conf->path->keys . $domain . '/' . $conf->selector . '.private';
				$con = file_get_contents($conf->path->KeyTable);
				$con = $con . "\n" . $line;
				$con = str_replace("\n\n", "\n", $con);
				file_put_contents($conf->path->KeyTable, $con);
			}
			
		break;
		
		case "R2":
			// See if domain is in KeyTable
			$keytable = file_get_contents($conf->path->KeyTable);
			$keytable = explode("\n", $keytable);
			$index = -1;
			for ($i = 0; $i < count($keytable); $i++) {
				$line = $keytable[$i];
				$pos = strrpos($line, $domain);
				$pos2 = strrpos($line, $conf->selector);
				if ($pos !== false && $pos2 != false) {
					$index = $i;
					break;
				}
			}
			
			// Domain in KeyTable
			if ($index >= 0) {
				// Remove line $index from $keytable
				$nKeyTable = $keytable;
				unset($nKeyTable[$index]);
				$nKeyTable = implode("\n", array_values($nKeyTable));
				file_put_contents($conf->path->KeyTable, $nKeyTable);
				
				echo "Domain Removed From KeyTable!\n";
			}
		break;
		
		
		// Add domain to SigningTable
		case 3:
		
			// See if domain is in SigningTable
			$signingtable = file_get_contents($conf->path->SigningTable);
			$signingtable = explode("\n", $signingtable);
			$index = -1;
			for ($i = 0; $i < count($signingtable); $i++) {
				$line = $signingtable[$i];
				$pos = strrpos($line, $domain);
				$pos2 = strrpos($line, $conf->selector);
				if ($pos !== false && $pos2 != false) {
					$index = $i;
					break;
				}
			}
			
			// Domain not in SigningTable
			if ($index < 0) {
				$line = $domain . ' ' . $conf->selector . '._domainkey.' . $domain;
				$con = file_get_contents($conf->path->SigningTable);
				$con = $con . "\n" . $line;
				$con = str_replace("\n\n", "\n", $con);
				file_put_contents($conf->path->SigningTable, $con);
			}
			
		break;
		
		case "R3":
		
			// See if domain is in SigningTable
			$signingtable = file_get_contents($conf->path->SigningTable);
			$signingtable = explode("\n", $signingtable);
			$index = -1;
			for ($i = 0; $i < count($signingtable); $i++) {
				$line = $signingtable[$i];
				$pos = strrpos($line, $domain);
				$pos2 = strrpos($line, $conf->selector);
				if ($pos !== false && $pos2 != false) {
					$index = $i;
					break;
				}
			}
			
			// Domain in SigningTable
			if ($index >= 0) {
				// Remove line $index from $keytable
				$nSigningTable = $signingtable;
				echo "Removing: " . $nSigningTable[$index] . "\n";
				unset($nSigningTable[$index]);
				$nSigningTable = implode("\n", array_values($nSigningTable));
				file_put_contents($conf->path->SigningTable, $nSigningTable);
				
				echo "Domain Removed From SigningTable!\n";
			}
			
		break;
		
		
		
		// Add domain to TrustedHosts
		case 4:
		
			// See if domain is in TrustedHosts
			$TrustedHosts = file_get_contents($conf->path->TrustedHosts);
			$TrustedHosts = explode("\n", $TrustedHosts);
			$index = -1;
			for ($i = 0; $i < count($TrustedHosts); $i++) {
				$line = $TrustedHosts[$i];
				$pos = strrpos($line, $domain);
				if ($pos !== false || $line == $domain) {
					$index = $i;
					break;
				}
			}
			
			// Domain not in TrustedHosts
			if ($index < 0) {
				$line = $domain;
				$con = file_get_contents($conf->path->TrustedHosts);
				$con = $con . "\n" . $line;
				$con = str_replace("\n\n", "\n", $con);
				file_put_contents($conf->path->TrustedHosts, $con);
			}
			
		break;
		
		case "R4":
		
			// See if domain is in TrustedHosts
			$TrustedHosts = file_get_contents($conf->path->TrustedHosts);
			$TrustedHosts = explode("\n", $TrustedHosts);
			$index = -1;
			for ($i = 0; $i < count($TrustedHosts); $i++) {
				$line = $TrustedHosts[$i];
				$pos = strrpos($line, $domain);
				if ($pos !== false || $line == $domain) {
					$index = $i;
					break;
				}
			}
			
			// Domain not in TrustedHosts
			if ($index >= 0) {
				// Remove line $index from $TrustedHosts
				$nTrustedHosts = $TrustedHosts;
				unset($nTrustedHosts[$index]);
				$nTrustedHosts = implode("\n", array_values($nTrustedHosts));
				file_put_contents($conf->path->TrustedHosts, $nTrustedHosts);
				
				echo "Domain Removed From TrustedHosts!\n";
			}
			
		break;
		
		
		
		// Back
		case "b":
		$running = false;
		break;
	}
	
	echo "\n";
 } // while
} // function

?>

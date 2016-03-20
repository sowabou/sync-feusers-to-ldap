<?php
require_once('config.php');
$mysqli = new mysqli($config['dbhost'], $config['dbuser'], $config['dbpass'], $config['db']);
$ldapconn = ldap_connect($config['ldaphost'], 389) or die("Could not connect to LDAP server.");
ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
$ldapbind = ldap_bind($ldapconn, $config['ldapbind'], $config['ldappass']) or die ("Error trying to bind: ".ldap_error($ldapconn));

if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    die();
}

$query = 'SELECT uid,username,email,usergroup,address,telephone,fax,title,zip,city,country,www,company,first_name,last_name,middle_name FROM fe_users WHERE deleted=0 AND disable=0 ORDER BY username;';
if ($result = $mysqli->query($query)) {
    $i = 1;
    $amount = $result->num_rows;
    while($row = $result->fetch_assoc()) {
        echo $i . '/' . $amount . ': ';
        createOrAdjustLdapUser($row);
	$i++;
    }
    $result->close();
}


function createOrAdjustLdapUser($row) {
	$cn = createCnFromTitle($row['username']);
	if(findLdapUser($cn)) {
		adjustLdapUser($cn, $row);
	} else {
		createLdapUser($cn, $row);
	}
}

function createCnFromTitle($title) {
	$return = strtolower($title);
	$return = str_replace(' ', '_', $return);
	$return = str_replace('-', '_', $return);
	$return = preg_replace("/[^a-z0-9_]/","", $return);
	$return = str_replace('__', '_', $return);
	return $return;
}

function findLdapUser($title) {
	global $config;
	global $ldapconn;

	$result = ldap_search($ldapconn, $config['ldappath_people'], '(uid=' . $title . ')') or die ("Error in search query: ".ldap_error($ldapconn));
	$data = ldap_get_entries($ldapconn, $result);
	return $data['count'] > 0;
}

function adjustLdapUser($cn, $row) {
	echo '... ' . $cn . ' is already imported. Currently no update planned.' . PHP_EOL;
}

function createLdapUser($uid, $row) {
	global $config;
	global $ldapconn;
	if(trim($uid) === '') {
		return;
	}
	echo '+++ ' . $uid . ' is going to be imported.' . PHP_EOL;
	$account = array();
	$account["objectclass"][0]='top';
	$account["objectclass"][1]='person';
	$account["objectclass"][2]='typo3Person';
	$account["objectclass"][3]='inetOrgPerson';
// TODO: organizationalPerson is currently not working
//	$account["objectclass"][4]='organizationalPerson';
	if(trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']) !== '') {
		$account["cn"] = $row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name'];
	}else{
		$account["cn"] = $uid;
	}
	if(trim($row['last_name']) !== '') {
		$account["sn"]=$row['last_name'];
	}else{
		$account["sn"] = $uid;
	}
	if(trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']) !== '')
		$account["displayName"]=$row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name'];
	if(trim($row['first_name'] . ' ' . $row['middle_name']) !== '')
		$account["givenName"]=$row['first_name'] . ' ' . $row['middle_name'];
	$url = filterString($row['www']);
	if(trim($url) !== '')
		$account["labeledURI"] = $url;
	$email = filterString($row['email']);
	if(trim($email) !== '')
		$account['mail'] = $email;
	//$account['o'] = 'typo3';
	//$account['ou'] = 'people';
//	if(trim($row['address']) !== '')
//		$account['street'] = $row['address'];
//	if(trim($row['zip']) !== '')
//		$account['postalCode'] = $row['zip'];
//	if(trim($row['city']) !== '')
//		$account['l'] = $row['city'];
	$phone = filterPhone($row['telephone']);
	if(trim($phone) !== '')
		$account['telephoneNumber'] = $phone;
	$account["uid"]=$uid;
	$account['userPassword'] = time() . '_' . md5(time() . $uid);
	$success=ldap_add($ldapconn, 'uid=' . $uid . ',' . $config['ldappath_people'], $account);
	if(!$success) {
		var_dump('uid=' . $uid . ',' . $config['ldappath_people']);
		var_dump($account);
		die();
	}
}

function filterPhone($number) {
        $return = preg_replace("/[^0-9+]/","", $number);
	return $return;
}

function filterString($str) {
        $return = preg_replace("/[^0-9a-zA-Z.\:\-_\/\\=\?\&\$\"#\*\<\>@+]/","", $str);
	return $return;
}

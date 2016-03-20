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

$query = 'SELECT uid,title,description FROM fe_groups WHERE deleted=0 AND hidden=0;';
if ($result = $mysqli->query($query)) {
    $i = 1;
    $amount = $result->num_rows;
    while($row = $result->fetch_assoc()) {
	echo $i . '/' . $amount . ': ';
       createOrAdjustLdapGroup($row['title'], $row['description'], $row['uid']);
	$i++;
    }
    $result->close();
}


function createOrAdjustLdapGroup($title, $desc, $id) {
	$cn = createCnFromTitle($title);
	if(findLdapGroup($cn)) {
		adjustLdapGroup($cn, $title, $desc);
	} else {
		createLdapGroup($cn, $title, $desc, $id);
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

function findLdapGroup($title) {
	global $config;
	global $ldapconn;

	$result = ldap_search($ldapconn, $config['ldappath_groups'], '(cn=' . $title . ')') or die ("Error in search query: ".ldap_error($ldapconn));
	$data = ldap_get_entries($ldapconn, $result);
	return $data['count'] > 0;
}

function adjustLdapGroup($cn, $title, $desc) {
	echo '... ' . $title . ' is already imported. Currently no update.' . PHP_EOL;
}

function createLdapGroup($cn, $title, $desc, $id) {
	global $config;
	global $ldapconn;
	echo '+++ ' . $cn . ' will be imported';
	$account = array();
	$account["objectclass"][0]='top';
	$account["objectclass"][1]='posixGroup';
	$account["gidNumber"]=$id;
	$account["cn"]=$cn;
	$account["description"]=$title . ' - ' . $desc;
	$r=ldap_add($ldapconn, 'cn=' . $title . ',' . $config['ldappath_groups'], $account);
}

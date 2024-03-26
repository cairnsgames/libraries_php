<?php
include_once dirname(__FILE__)."/../corsheaders.php";
include_once dirname(__FILE__)."/../dbconfig.php";
include_once dirname(__FILE__)."/../apicore/apicore.php";
include_once dirname(__FILE__)."/../utils.php";

$config = array(
	"database" => $dbconfig,
	
	"flags" => array(
		"key" => "id",
		"select" => array("id", "name","active"),
		"create" => array("name","active"),
		"update" => array("name","active"),
		"delete" => false,
		"beforeselect" => "securecheck",
		"beforeupdate" => "securecheck2",
		"beforeinsert" => "securecheck2",
		"beforedelete" => "securecheck2",
		"subkeys" => array(
			"tags" => array(
				"key" => "flagid",
				"tablename" => "flagtag",
				"select" => array("id", "flagid", "tag")
			),
			"values" => array(
				"key" => "flagid",
				"tablename" => "flagvalues",
				"select" => array("id", "flagid", "val", "def")
			),
			"rules" => array(
				"key" => "flagid",
				"tablename" => "flagrules",
				"select" => array("id", "flagid", "valueid", "fieldname", "fieldvalues", "seq"),
				"order" => "seq asc"
			)
		)
	),
	"tags" => array(
		"key" => "id",
		"tablename" => "flagtag",
		"select" => array("id", "flagid", "tag"),
		"create" => array("name", "active"),
		"update" => array("name", "active"),
		"delete" => false,
		"beforeselect" => "securecheck",
		"beforeupdate" => "securecheck2",
		"beforeinsert" => "securecheck2",
		"beforedelete" => "securecheck2",
	),
	"rules" => array(
		"key" => "id",
		"tablename" => "flagrules",
		"select" => array("id", "flagid", "valueid", "fieldname", "fieldvalues", "seq"),
		"create" => array("valueid", "fieldname", "fieldvalues", "seq"),
		"update" => array("valueid", "fieldname", "fieldvalues", "seq"),
		"delete" => false,
		"beforeselect" => "securecheck",
		"beforeupdate" => "securecheck2",
		"beforeinsert" => "securecheck2",
		"beforedelete" => "securecheck2",
	),
	"values" => array(
		"key" => "id",
		"tablename" => "flagvalues",
		"select" => array("id", "flagid", "val", "def"),
		"create" => array("val", "def"),
		"update" => array("val", "def"),
		"selectarray" => true,
		"delete" => false,
		"beforeselect" => "securecheck",
		"beforeupdate" => "securecheck2",
		"beforeinsert" => "securecheck2",
		"beforedelete" => "securecheck2",
	),
);

Run($config);

function securecheck($config, $info)
{
	requiresAdminRights();
	return $info;
}
function securecheck2($info)
{
	requiresAdminRights();
	return $info;
}

?>
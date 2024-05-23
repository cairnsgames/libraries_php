<?php
include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../dbconfig.php";
include_once dirname(__FILE__) . "/../apicore/apicore.php";
include_once dirname(__FILE__) . "/../utils.php";
include_once dirname(__FILE__) . "/../auth/authfunctions.php";

$appid = getAppId();

// TODO: Replace $appid in SQL with secured value

$config = array(
	"database" => $dbconfig,
	"tenant" => array(
		"tablename" => "shop_tenant",
		"key" => "tenant_id",
		"select" => array("tenant_id", "user_id", "name", "description"),
		"create" => array("user_id", "name", "description"),
		"update" => array("user_id", "name", "description"),
		"delete" => false,
		"beforeselect" => "securecheck",
		"beforeupdate" => "securecheck2",
		"beforeinsert" => "securecheck2",
		"beforedelete" => "securecheck2"
	),
	"store" => array(
		"tablename" => "shop_store",
		"key" => "store_id",
		"select" => array("store_id", "tenant_id", "app_id", "name", "description"),
		"create" => array("tenant_id", "app_id", "name", "description"),
		"update" => array("tenant_id", "app_id", "name", "description"),
		"delete" => false,
		"beforeselect" => "securecheck",
		"beforeupdate" => "securecheck2",
		"beforeinsert" => "securecheck2",
		"beforedelete" => "securecheck2",
		"subkeys" => array(
			"product" => array(
				"tablename" => "shop_product",
				"key" => "store_id",
				"select" => array("product_id", "store_id", "app_id", "name", "description", "price", "category")
			)
		)
	),
	"product" => array(
		"tablename" => "shop_product",
		"key" => "product_id",
		"select" => array("product_id", "store_id", "app_id", "name", "description", "price", "category"),
		"create" => array("store_id", "app_id", "name", "description", "price", "category"),
		"update" => array("store_id", "app_id", "name", "description", "price", "category"),
		"delete" => false,
		"beforeselect" => "securecheck",
		"afterselect" => "afterselectproduct",
		"beforeupdate" => "securecheck2",
		"beforeinsert" => "securecheck2",
		"beforedelete" => "securecheck2",
		"subkeys" => array(
			"variant" => array(
				"tablename" => "shop_variant",
				"key" => "product_id",
				"select" => "select variant_id, product_id, name, price,
				(
					SELECT JSON_ARRAYAGG(JSON_OBJECT(svp.keyname, svp.value))
					FROM shop_variant_property svp
					WHERE svp.variant_id = sv.variant_id
				) AS properties,
				(
					SELECT JSON_ARRAYAGG(JSON_OBJECT(`url`, svi.url))
					FROM shop_product_image svi
					WHERE svi.product_id = sv.product_id
				) AS images
			from `shop_variant` sv WHERE `product_id`={product_id}"
			),
			"product_keyword" => array(
				"tablename" => "shop_product_keyword",
				"key" => "product_id",
				"select" => array("keyword_id", "product_id", "keyword")
			),
			"product_image" => array(
				"tablename" => "shop_product_image",
				"key" => "product_id",
				"select" => array("photo_id", "product_id", "url")
			),
			"product_promotion" => array(
				"tablename" => "shop_product_promotion",
				"key" => "product_id",
				"select" => array("product_promotion_id", "product_id", "code", "app_id", "start_date", "expiry_date")
			)
		)
	),
	"variant" => array(
		"tablename" => "shop_variant",
		"key" => "variant_id",
		"select" => array("variant_id", "product_id", "name", "price"),
		"create" => array("product_id", "name", "price"),
		"update" => array("product_id", "name", "price"),
		"delete" => false,
		"beforeselect" => "securecheck",
		"beforeupdate" => "securecheck2",
		"beforeinsert" => "securecheck2",
		"beforedelete" => "securecheck2",
		"subkeys" => array(
			"variant_property" => array(
				"tablename" => "shop_variant_property",
				"key" => "variant_id",
				"select" => array("variant_property_id", "variant_id", "key", "value")
			)
		)
	),
	"variant_property" => array(
		"tablename" => "shop_variant_property",
		"key" => "variant_property_id",
		"select" => array("variant_property_id", "variant_id", "keyname", "value"),
		"create" => array("variant_id", "keyname", "value"),
		"update" => array("variant_id", "keyname", "value"),
		"delete" => false,
		"beforeselect" => "securecheck",
		"beforeupdate" => "securecheck2",
		"beforeinsert" => "securecheck2",
		"beforedelete" => "securecheck2"
	),
	"product_keyword" => array(
		"tablename" => "shop_product_keyword",
		"key" => "keyword_id",
		"select" => array("keyword_id", "product_id", "keyword"),
		"create" => array("product_id", "keyword"),
		"update" => array("product_id", "keyword"),
		"delete" => false,
		"beforeselect" => "securecheck",
		"beforeupdate" => "securecheck2",
		"beforeinsert" => "securecheck2",
		"beforedelete" => "securecheck2"
	),
	"product_image" => array(
		"tablename" => "shop_product_image",
		"key" => "photo_id",
		"select" => array("photo_id", "product_id", "url"),
		"create" => array("product_id", "url"),
		"update" => array("product_id", "url"),
		"delete" => false,
		"beforeselect" => "securecheck",
		"beforeupdate" => "securecheck2",
		"beforeinsert" => "securecheck2",
		"beforedelete" => "securecheck2"
	),
	"cart" => array(
		"tablename" => "shop_cart",
		"key" => "cart_id",
		"select" => array("cart_id", "user_id", "app_id", "total_price"),
		"create" => array("user_id", "app_id", "total_price"),
		"update" => array("user_id", "app_id", "total_price"),
		"delete" => false,
		"beforeselect" => "securecheck",
		"beforeupdate" => "securecheck2",
		"beforeinsert" => "securecheck2",
		"beforedelete" => "securecheck2",
		"subkeys" => array(
			"cart_item" => array(
				"tablename" => "shop_cart_item",
				"key" => "cart_id",
				"select" => array("cart_item_id", "cart_id", "product_id", "variant_id", "quantity", "price")
			)
		)
	),
	"cart_item" => array(
		"tablename" => "shop_cart_item",
		"key" => "cart_item_id",
		"select" => array("cart_item_id", "cart_id", "product_id", "variant_id", "quantity", "price"),
		"create" => array("cart_id", "product_id", "variant_id", "quantity", "price"),
		"update" => array("cart_id", "product_id", "variant_id", "quantity", "price"),
		"delete" => false,
		"beforeselect" => "securecheck",
		"beforeupdate" => "securecheck2",
		"beforeinsert" => "securecheck2",
		"beforedelete" => "securecheck2"
	),
	"category" => array(
		"tablename" => "shop_category",
		"key" => "category_id",
		"select" => array("category_id", "name", "description"),
		"create" => array("name", "description"),
		"update" => array("name", "description"),
		"delete" => false,
		"beforeselect" => "securecheck",
		"beforeupdate" => "securecheck2",
		"beforeinsert" => "securecheck2",
		"beforedelete" => "securecheck2"
	),
	"promotion" => array(
		"tablename" => "shop_promotion",
		"key" => "promotion_id",
		"select" => array("promotion_id", "app_id", "code", "discount", "start_date", "expiry_date"),
		"create" => array("app_id", "code", "discount", "start_date", "expiry_date"),
		"update" => array("app_id", "code", "discount", "start_date", "expiry_date"),
		"delete" => false,
		"beforeselect" => "securecheck",
		"beforeupdate" => "securecheck2",
		"beforeinsert" => "securecheck2",
		"beforedelete" => "securecheck2"
	),
	"product_promotion" => array(
		"tablename" => "shop_product_promotion",
		"key" => "product_promotion_id",
		"select" => array("product_promotion_id", "product_id", "code", "app_id", "start_date", "expiry_date"),
		"create" => array("product_id", "code", "app_id", "start_date", "expiry_date"),
		"update" => array("product_id", "code", "app_id", "start_date", "expiry_date"),
		"delete" => false,
		"beforeselect" => "securecheck",
		"beforeupdate" => "securecheck2",
		"beforeinsert" => "securecheck2",
		"beforedelete" => "securecheck2"
	)
);


Run($config);

function securecheck($config, $info)
{

	// echo "------------ securecheck ------------\n";
	// var_dump($info);
	// echo "-------------\n";
	// requiresAdminRights();
	return $info;
}

function afterselectproduct($result)
{
	// echo "------------ afterselectproduct ------------\n";
	// var_dump($result);
	// echo "-------------\n";
	$modifiedresult = [];
	foreach ($result as $row) {
		if (isset($row["properties"])) {
			$row["properties"] = json_decode($row["properties"]);
		} else {
			$row["properties"] = [];
		}
		if (isset($row["images"])) {
			$row["images"] = json_decode($row["images"]);
		} else {
			$row["images"] = [];
		}
		$modifiedresult[] = $row;
	}
	// $result["fields"]["images"] = json_decode($result["fields"]["images"]);
	// $result["fields"]["properties"] = json_decode($result["fields"]["properties"]);
	return $modifiedresult;
}

?>
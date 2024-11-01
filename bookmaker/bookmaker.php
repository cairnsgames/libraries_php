<?php

$appId = getAppId();
$token = getToken();

if (!hasValue($token)) {
    sendUnauthorizedResponse("Invalid token");
}
if (!hasValue($appId)) {
    sendUnauthorizedResponse("Invalid tenant");
}

$userid = getUserId($token);

include_once dirname(__FILE__) . "/bookmakerconfig.php";

// Hook function for security check
function checkBookmakerSecurity($config, $id = null) {
    global $appId, $userid;
    // Custom security check based on app_id and user ID
    $config["where"]['app_id'] = $appId;
    return [$config, $id];
}

// Modify rows after selection
function modifyBookmakerRows($config, $results) {
    // Example modification: add metadata to the results
    foreach ($results as &$result) {
        $result['retrieved'] = true;
    }
    return $results;
}

// Example function to add app_id and user_id to data during creation
function beforeCreateBookmakerItem($config, $data) {
    global $appId, $userid;
    $data["app_id"] = $appId;
    $data["user_id"] = $userid;
    return [$config, $data];
}

function getItemsInHierarchy($config, $notid) {
    $id = $config["where"]["hierarchy_id"];
    // echo ("Getting items in hierarchy with ID: $id\n");
    // var_dump("Config: ", $config);
    // echo "====id: $id\n";
    global $conn;
    $query = "SELECT 
    i.id AS item_id,
    i.app_id,
    i.type,
    i.name,
    i.role,
    i.description,
    i.keywords,
    i.aliases,
    i.classnames,
    i.hierarchy_id
FROM 
    bookmaker_items i
INNER JOIN 
    (
        WITH RECURSIVE hierarchy_tree AS (
            -- Base case: start with the given hierarchy_id
            SELECT 
                h.id,
                h.app_id,
                h.name,
                h.type,
                h.description,
                h.parent_id
            FROM 
                bookmaker_hierarchy h
            WHERE 
                h.id = ? -- Starting hierarchy_id

            UNION ALL

            -- Recursive case: find parent hierarchy items
            SELECT 
                h.id,
                h.app_id,
                h.name,
                h.type,
                h.description,
                h.parent_id
            FROM 
                bookmaker_hierarchy h
            INNER JOIN 
                hierarchy_tree ht ON h.id = ht.parent_id
        )
        SELECT * FROM hierarchy_tree
    ) AS ht ON i.hierarchy_id = ht.id;";
    $result = PrepareExecSQL($query, "s", [$id]);
    // var_dump($result);
    return $result;
}

function getHierarchyTree($config, $notid) {
    $id = $config["where"]["id"];
    global $conn;
    $query = "SELECT * 
FROM (
    WITH RECURSIVE hierarchy_tree AS (
        -- Base case: start with the given hierarchy_id
        SELECT 
            h.id,
            h.app_id,
            h.name,
            h.type,
            h.description,
            h.parent_id
        FROM 
            bookmaker_hierarchy h
        WHERE 
            h.id = ? -- Starting hierarchy_id

        UNION ALL

        -- Recursive case: find parent hierarchy items
        SELECT 
            h.id,
            h.app_id,
            h.name,
            h.type,
            h.description,
            h.parent_id
        FROM 
            bookmaker_hierarchy h
        INNER JOIN 
            hierarchy_tree ht ON h.id = ht.parent_id
    )
    SELECT * FROM hierarchy_tree
) AS full_hierarchy;

";
    $result = PrepareExecSQL($query, "s", [$id]);
    // var_dump($result);
    return $result;
}

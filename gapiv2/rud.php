<?php

function CreateData($config, $data) {
    global $conn;

    if (!isset($config['create'])) {
        die("Create operation not allowed");
    }

    if (isset($config['beforecreate']) && function_exists($config['beforecreate'])) {
        call_user_func($config['beforecreate'], $config);
    }

    $fields = implode(", ", $config['create']);
    $placeholders = implode(", ", array_fill(0, count($config['create']), "?"));
    $query = "INSERT INTO " . $config['tablename'] . " ($fields) VALUES ($placeholders)";

    $stmt = $conn->prepare($query);

    $types = str_repeat('s', count($config['create']));
    $stmt->bind_param($types, ...$data);

    $stmt->execute();
    $insert_id = $stmt->insert_id;

    $stmt->close();

    $new_record = SelectData($config, $insert_id);

    if (isset($config['aftercreate']) && function_exists($config['aftercreate'])) {
        call_user_func($config['aftercreate'], $config, $new_record);
    }

    return $new_record;
}

function UpdateData($config, $id, $data) {
    global $conn;

    if (!isset($config['update'])) {
        die("Update operation not allowed");
    }

    if (isset($config['beforeupdate']) && function_exists($config['beforeupdate'])) {
        call_user_func($config['beforeupdate'], $config);
    }

    $fields = implode(" = ?, ", $config['update']) . " = ?";
    $query = "UPDATE " . $config['tablename'] . " SET $fields WHERE " . $config['key'] . " = ?";

    $stmt = $conn->prepare($query);

    $types = str_repeat('s', count($config['update'])) . 's';
    $data[] = $id;
    $stmt->bind_param($types, ...$data);

    $stmt->execute();

    $stmt->close();

    $updated_record = SelectData($config, $id);

    if (isset($config['afterupdate']) && function_exists($config['afterupdate'])) {
        call_user_func($config['afterupdate'], $config, $updated_record);
    }

    return $updated_record;
}

function DeleteData($config, $id) {
    global $conn;

    if (!isset($config['delete']) || !$config['delete']) {
        die("Delete operation not allowed");
    }

    if (isset($config['beforedelete']) && function_exists($config['beforedelete'])) {
        call_user_func($config['beforedelete'], $config);
    }

    $query = "DELETE FROM " . $config['tablename'] . " WHERE " . $config['key'] . " = ?";

    $stmt = $conn->prepare($query);

    $stmt->bind_param('s', $id);

    $stmt->execute();

    $affected_rows = $stmt->affected_rows;

    $stmt->close();

    return $affected_rows > 0;
}
<?php

function CreateData($config, $data)
{
    global $conn;

    if (!isset($config['create'])) {
        die("Create operation not allowed");
    }

    if (isset($config['beforecreate']) && function_exists($config['beforecreate'])) {
        $res = call_user_func($config['beforecreate'], $config, $data);
        $config = $res[0];
        $data = $res[1];
    }

    $fields = implode(", ", $config['create']);
    $placeholders = implode(", ", array_fill(0, count($config['create']), "?"));
    $query = "INSERT INTO " . $config['tablename'] . " ($fields) VALUES ($placeholders)";

    $stmt = $conn->prepare($query);

    // Extract values from $data based on the keys in $config['create']
    $values = array_map(function ($field) use ($data) {
        return $data[$field];
    }, $config['create']);

    // Determine the types of the values
    $types = '';
    foreach ($values as $value) {
        if (is_int($value)) {
            $types .= 'i'; // Integer
        } elseif (is_float($value)) {
            $types .= 'd'; // Double
        } elseif (is_string($value)) {
            $types .= 's'; // String
        } else {
            $types .= 'b'; // Blob and other types
        }
    }

    $stmt->bind_param($types, ...$values);

    $stmt->execute();
    $insert_id = $stmt->insert_id;

    $stmt->close();

    $new_record = SelectData($config, $insert_id);

    if (isset($config['aftercreate']) && function_exists($config['aftercreate'])) {
        $res = call_user_func($config['aftercreate'], $config, $data, $new_record);
        if (is_array($res)) {
            $new_record = $res[2];
        }
    }

    return $new_record;
}

function CreateDataBulk($config, $data)
{
    global $conn;

    if (!isset($config['create'])) {
        die("Create operation not allowed");
    }

    if (isset($config['beforecreate']) && function_exists($config['beforecreate'])) {
        $res = call_user_func($config['beforecreate'], $config, $data);
        $config = $res[0];
        $data = $res[1];
    }

    $fields = implode(", ", $config['create']);
    $placeholders = implode(", ", array_fill(0, count($config['create']), "?"));
    $query = "INSERT INTO " . $config['tablename'] . " ($fields) VALUES ($placeholders)";

    $stmt = $conn->prepare($query);

    $insert_ids = [];

    foreach ($data as $item) {
        // Extract values from $item based on the keys in $config['create']
        $values = array_map(function ($field) use ($item) {
            return $item[$field];
        }, $config['create']);

        // Determine the types of the values
        $types = '';
        foreach ($values as $value) {
            if (is_int($value)) {
                $types .= 'i'; // Integer
            } elseif (is_float($value)) {
                $types .= 'd'; // Double
            } elseif (is_string($value)) {
                $types .= 's'; // String
            } else {
                $types .= 'b'; // Blob and other types
            }
        }

        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        $insert_ids[] = $stmt->insert_id;
    }

    $stmt->close();

    $new_records = [];
    foreach ($insert_ids as $insert_id) {
        $new_record = SelectData($config, $insert_id);
        if (isset($config['aftercreate']) && function_exists($config['aftercreate'])) {
            $res = call_user_func($config['aftercreate'], $config, $data, $new_record);
            if (is_array($res)) {
                $new_record = $res[2];
            }
        }
        $new_records[] = $new_record;
    }

    return $new_records;
}

function UpdateData($config, $id, $data)
{
    global $conn;

    if (!isset($config['update'])) {
        die("Update operation not allowed");
    }

    if (isset($config['beforeupdate']) && function_exists($config['beforeupdate'])) {
        $res = call_user_func($config['beforeupdate'], $config, $data);
        $config = $res[0];
        $data = $res[1];
    }

    $fields = implode(" = ?, ", $config['update']) . " = ?";
    $query = "UPDATE " . $config['tablename'] . " SET $fields WHERE " . $config['key'] . " = ?";

    $stmt = $conn->prepare($query);

    $types = '';
    $values = [];
    foreach ($config['update'] as $field) {
        $types .= 's';
        $values[] = $data[$field];
    }
    $types .= 's';
    $values[] = $id;

    $stmt->bind_param($types, ...$values);

    $stmt->execute();

    $stmt->close();

    $updated_record = SelectData($config, $id);

    if (isset($config['afterupdate']) && function_exists($config['afterupdate'])) {
        call_user_func($config['afterupdate'], $config, $updated_record);
    }

    return $updated_record;
}


function DeleteData($config, $id)
{
    global $conn;

    if (!isset($config['delete']) || !$config['delete']) {
        die("Delete operation not allowed");
    }

    if (isset($config['beforedelete']) && function_exists($config['beforedelete'])) {
        $res = call_user_func($config['beforedelete'], $config, []);
        $config = $res[0];
    }

    $query = "DELETE FROM " . $config['tablename'] . " WHERE " . $config['key'] . " = ?";

    $stmt = $conn->prepare($query);

    $stmt->bind_param('s', $id);

    $stmt->execute();

    $affected_rows = $stmt->affected_rows;

    $stmt->close();

    return $affected_rows > 0;
}
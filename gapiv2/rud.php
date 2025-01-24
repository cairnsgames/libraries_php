<?php

function CreateData($config, $data)
{
    global $conn;
    $new_record = null;

    // Check if create operation is allowed
    if (!isset($config['create'])) {
        die("Create operation not allowed");
    }

    // Execute before create function if it exists
    if (isset($config['beforecreate']) && function_exists($config['beforecreate'])) {
        $res = call_user_func($config['beforecreate'], $config, $data);
        $config = $res[0];
        $data = $res[1];
    }

    // Check if 'create' is a function name
    if (is_string($config['create']) && function_exists($config['create'])) {
        $res = call_user_func($config['create'], $config, $data);
        $config = $res[0];
        $data = $res[1];
        $new_record = $res[2];
    } else {
        // Build the insert query with ON DUPLICATE KEY UPDATE
        $fields = implode(", ", $config['create']);
        $placeholders = implode(", ", array_fill(0, count($config['create']), "?"));
        $updateFields = implode(", ", array_map(function ($field) {
            return "$field = VALUES($field)";
        }, $config['create']));
        $query = "INSERT INTO " . $config['tablename'] . " ($fields) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updateFields";

        $stmt = $conn->prepare($query);

        // Extract values from $data based on the keys in $config['create']
        $values = array_map(function ($field) use ($data) {
            if (!isset($data[$field])) {
                return "";
            }
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

        // echo "Query: ", $query, "\n";
        // echo "Types: ", $types, "\n";
        // echo "Values: ", json_encode($values), "\n";

        try {
            $stmt->execute();
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["error" => $e->getMessage()]);
            exit;
        }
        $insert_id = $stmt->insert_id;

        $stmt->close();

        // Fetch the new record
        $new_record = SelectData($config, $insert_id);
    }

    // Execute after create function if it exists
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
    $new_records = [];

    // Check if create operation is allowed
    if (!isset($config['create'])) {
        die("Create operation not allowed");
    }

    // Execute before delete function if it exists
    if (isset($config['beforcreate']) && function_exists($config['beforcreate'])) {
        $res = call_user_func($config['beforcreate'], $config, $data);
        $config = $res[0];
        $data = $res[1];
    }

    // Check if 'create' is a function name
    if (is_string($config['create']) && function_exists($config['create'])) {
        $res = call_user_func($config['create'], $config, $data);
        $config = $res[0];
        $new_records = $res[1];

    }

    // Execute before create function if it exists
    if (isset($config['beforecreate']) && function_exists($config['beforecreate'])) {
        $res = call_user_func($config['beforecreate'], $config, $data);
        $config = $res[0];
        $data = $res[1];
    }

    // Build the insert query
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

        $stmt->close();

        // Fetch new records
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
    }

    return $new_records;
}


function UpdateData($config, $id, $data)
{
    global $conn;
    $updated_record = null;

    // Check if update operation is allowed
    if (!isset($config['update'])) {
        die("Update operation not allowed");
    }

    // Execute before update function if it exists
    if (isset($config['beforeupdate']) && function_exists($config['beforeupdate'])) {
        $res = call_user_func($config['beforeupdate'], $config, $id, $data);
        $config = $res[0];
        $data = $res[1];
    }

    // Check if 'update' is a function name
    if (is_string($config['update']) && function_exists($config['update'])) {
        $res = call_user_func($config['update'], $config, $id, $data);
        $config = $res[0];
        $updated_record = $res[1];
    } else {

        // Build the update query// Filter $config['update'] to only include fields that exist in $data
        $updateFields = array_values(array_filter($config['update'], function ($field) use ($data) {
            return isset($data[$field]);
        }));

        // Generate the $fields string with the filtered fields
        $fields = implode(" = ?, ", $updateFields) . " = ?";

        $query = "UPDATE " . $config['tablename'] . " SET $fields WHERE " . $config['key'] . " = ?";

        $stmt = $conn->prepare($query);

        $types = '';
        $values = [];
        for ($i = 0; $i < count($updateFields); $i++) {
            $types .= 's';
            $values[] = $data[$updateFields[$i]];
        }
        $types .= 's';
        $values[] = $id;

        // echo "Update Fields: ", json_encode($updateFields), "\n";
        // echo "Query: ", $query, "\n";
        // echo "Types: ", $types, "\n";
        // echo "Values: ", json_encode($values), "\n";

        $stmt->bind_param($types, ...$values);

        $stmt->execute();

        $stmt->close();

        // Fetch the updated record
        $updated_record = SelectData($config, $id);
    }

    // Execute after update function if it exists
    if (isset($config['afterupdate']) && function_exists($config['afterupdate'])) {
        $updated_record = call_user_func($config['afterupdate'], $config, $updated_record);
    }

    return $updated_record;
}


function DeleteData($config, $id)
{
    global $conn;
    $affected_rows = 0;

    // Check if delete operation is allowed
    if (!isset($config['delete']) || !$config['delete']) {
        die("Delete operation not allowed");
    }

    // Execute before delete function if it exists
    if (isset($config['beforedelete']) && function_exists($config['beforedelete'])) {
        $res = call_user_func($config['beforedelete'], $config, $id);
        $config = $res[0];
    }

    // Check if 'delete' is a function name
    if (is_string($config['delete']) && function_exists($config['delete'])) {
        $res = call_user_func($config['delete'], $config, $id);
        $affected_rows = $res[1];
    } else {

        // Prepare and execute the delete query
        $query = "DELETE FROM " . $config['tablename'] . " WHERE " . $config['key'] . " = ?";

        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $id);

        $stmt->execute();

        $affected_rows = $stmt->affected_rows;
        $stmt->close();
    }

    // Execute before after function if it exists
    if (isset($config['afterdelete']) && function_exists($config['afterdelete'])) {
        $res = call_user_func($config['afterdelete'], $config, $id);
    }


    return $affected_rows > 0;
}

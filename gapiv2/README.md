# GAPIv2 - Generic API

GAPIv2 is a powerful and flexible tool designed to simplify the process of building RESTful APIs on top of any MySQL database. It provides a straightforward method to define API endpoints, manage database interactions, and enforce security and business logic through pre- and post-operation hooks.

## Features

- **Simple Configuration**: Easily configure API endpoints, including select, create, update, and delete operations.
- **Pre- and Post-Operation Hooks**: Implement custom logic for security, validation, and data manipulation.
- **Subkeys**: Manage related data with nested configurations.
- **OpenAPI Documentation**: Automatically generate OpenAPI (Swagger) documentation for your API.
- **Dynamic Function Calls**: Call PHP functions dynamically based on configuration values for select, create, update, and delete operations.
- **Special Endpoints**: Handle special `post` endpoints with dynamic function execution.

## Configuration

### Basic Configuration Structure

Each endpoint is configured with an associative array. Below is a breakdown of the configuration options:

- `tablename`: The name of the table in the database.
- `key`: The primary key of the table.
- `select`: An array of columns to be selected, or a SQL query string. If a string is provided and a function with that name exists, that function will be called instead.
- `create`: An array of columns allowed for creation, or a SQL query string. If a string is provided and a function with that name exists, that function will be called instead.
- `update`: An array of columns allowed for updating, or a SQL query string. If a string is provided and a function with that name exists, that function will be called instead.
- `delete`: A boolean indicating if delete operation is allowed, or a SQL query string. If a string is provided and a function with that name exists, that function will be called instead.
- `where`: An associative array of default where conditions.
- `beforeselect`, `beforecreate`, `beforeupdate`, `beforedelete`: Hook functions to be called before each operation.
- `afterselect`, `aftercreate`, `afterupdate`: Hook functions to be called after each operation.
- `subkeys`: Nested configurations for related data.

### Special Endpoints

- `post`: A top-level configuration for special endpoints that map to functions. If a request is made to an endpoint that is not found, the system will check the `post` configuration. If a corresponding function exists, it will be called with the `_POST` data or JSON body data as a parameter.
- `openapi`: Retrieve the OpenAPI (Swagger) documentation for the API.
- `$$`: Retrieve simple documentation for the API (just field name list).

### Subkeys

Subkeys only support GET with the following configuration options:

- `tablename`: The name of the table in the database.
- `key`: The foreign key of the table pointing to the parent.
- `select`: An array of columns to be selected, or a SQL query string.

to support POSt an dPUT and DELETE for subkecys add these at the top level

to prevent selection of all items at the top level, set select = false in the config

### Example Configuration

```php
$configs = [
    "calendar" => [
        'tablename' => 'kloko_calendar',
        'key' => 'id',
        'select' => ['id', 'user_id', 'app_id', 'name', 'description'],
        'create' => ['user_id', 'app_id', 'name', 'description'],
        'update' => ['name', 'description'],
        'delete' => true,
        'where' => ['user_id' => '22'],
        'beforeselect' => 'checksecurity',
        'afterselect' => 'modifyrows',
        'beforecreate' => 'checksecurity',
        'aftercreate' => '',
        'beforeupdate' => 'checksecurity',
        'afterupdate' => '',
        'beforedelete' => 'checksecurity',
        'subkeys' => [
            'event' => [
                'tablename' => 'kloko_event',
                'key' => 'id',
                'select' => ['id', 'calendar_id', 'user_id', 'event_template_id', 'app_id', 'name', 'description', 'duration', 'location', 'max_participants', 'start_time', 'end_time'],
                'beforeselect' => 'checksecurity',
                'afterselect' => ''
            ]
        ]
    ],
    // Additional endpoint configurations...
    "post" => [
        'addtocart' => 'addItemToCart',
        // Other post actions...
    ]
];

## Hook Functions

### Before Hooks

Before hooks are used to validate user authentication, permissions, and add custom where clauses.

- beforeselect($config, $id): Called before a select operation. ($id is optional)
- beforecreate($config, $data): Called before a create operation. Must return [$config, $data]
- beforeupdate($config, $id, $data): Called before an update operation. Must return [$config, $data]
- beforedelete($config, $id): Called before a delete operation.

### After Hooks

After hooks allow you to modify the results of a select operation or perform actions after create, update, or delete operations.

- afterselect($config, $results): Called after a select operation. should return $results
- aftercreate($config, $new_record): Called after a create operation. Must return [$config, $new_record]
- afterupdate($config, $updated_record): Called after an update operation. must return [$config, $updated_record]
- afterdelete($config, $id): Called after an update operation. Must return [$config]

## API Endpoints

### CRUD Operations

Each configured endpoint supports the following HTTP methods:

- GET /{endpoint}: Retrieve a list of records.
- GET /{endpoint}/{id}: Retrieve a single record by ID.
- POST /{endpoint}: Create a new record.
- PUT /{endpoint}/{id}: Update an existing record by ID.
- DELETE /{endpoint}/{id}: Delete a record by ID.

### Special Endpoints

- GET /openapi: Retrieve the OpenAPI (Swagger) documentation for the API.
- GET /$$: Retrieve simple documentation for the API (just field name list)

- POST /<name> if name does not exist as a normal endpoint, the post collection will be checked and if name is found, the vlaue will  be called as a function.

## Example Usage

### Select Data

``` sh
curl -X GET "https://example.com/api/calendar"
```

### Select a single record

``` sh
curl -X GET "https://example.com/api/calendar/1"
```

### Select Subkey Data

``` sh
curl -X GET "https://example.com/api/calendar/1/event"
```

### Create Data

```sh
curl -X POST "https://example.com/api/calendar" \
     -H "Content-Type: application/json" \
     -d '{"user_id": "22", "app_id": "1", "name": "My Calendar", "description": "This is a test calendar"}'
```

### Update Data

```sh
curl -X PUT "https://example.com/api/calendar/1" \
     -H "Content-Type: application/json" \
     -d '{"name": "Updated Calendar", "description": "Updated description"}'
```

### Delete Data

```sh
curl -X DELETE "https://example.com/api/calendar/1"
```

### Retrieve OpenAPI Documentation

```sh
curl -X GET "https://example.com/api/openapi"
```

### Retrieve Simple API Documentation

```sh
curl -X GET "https://example.com/api/$$"
```

# License

GAPIv2 is licensed under the MIT License.

# Suggested File Structure

##<dirname> where dirname is the feature
##<dirname>/api.php

contains the require dimports and then runs the api

```php
include_once dirname(__FILE__) . "/../corsheaders.php";
include_once dirname(__FILE__) . "/../gapiv2/dbconn.php";
include_once dirname(__FILE__) . "/../gapiv2/v2apicore.php";
include_once dirname(__FILE__) . "/../utils.php";
include_once dirname(__FILE__) . "/../auth/authfunctions.php";

include_once dirname(__FILE__) . "/featureconfig.php";
include_once dirname(__FILE__) . "/feature.php";

runAPI($klokoconfigs);
```

## featureconfig.php

replace feature with name of feature matching dirname

contians the configuration object

```php
<?php

// Define the configurations
$featureconfigs = [
    "calendar" => [
        'tablename' => 'kloko_calendar',
        'key' => 'id',
```

## feature.php

this file contians all the security and before/after functions for the above config. In this example the $token is used to check that the user is logged in.



```php
$appId = getAppId();
$token = getToken();

if (!hasValue($token)) {
    sendUnauthorizedResponse("Invalid token");
}
if (!hasValue($appId)) {
    sendUnauthorizedResponse("Invalid tenant");
}

$userid = getUserId($token);

include_once dirname(__FILE__) . "/klokoconfig.php";

// And then all the additional functions required by the config
// e.g.
function beforesearch($config, $data)
{
    global $appId, $userid;
    $config["params"]["app_id"] = $appId;
    $config["params"]["userid"] = $userid;
    return [$config, $data];
}
```

An example function to ensure any records added by a user are linked to the user account use something like:

```php
function beforeCreateProfile($config, $data)
{
    global $appId, $userid;
    $data["app_id"] = $appId;
    $data["user_id"] = $userid;
    return [$config, $data];
}
```

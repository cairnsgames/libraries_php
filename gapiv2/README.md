# GAPIv2 - Generic API

GAPIv2 is a powerful and flexible tool designed to simplify the process of building RESTful APIs on top of any MySQL database. It provides a straightforward method to define API endpoints, manage database interactions, and enforce security and business logic through pre- and post-operation hooks.

## Features

- **Simple Configuration**: Easily configure API endpoints, including select, create, update, and delete operations.
- **Pre- and Post-Operation Hooks**: Implement custom logic for security, validation, and data manipulation.
- **Subkeys**: Manage related data with nested configurations.
- **OpenAPI Documentation**: Automatically generate OpenAPI (Swagger) documentation for your API.

## Configuration

### Basic Configuration Structure

Each endpoint is configured with an associative array. Below is a breakdown of the configuration options:

- `tablename`: The name of the table in the database.
- `key`: The primary key of the table.
- `select`: An array of columns to be selected.
- `create`: An array of columns allowed for creation.
- `update`: An array of columns allowed for updating.
- `delete`: A boolean indicating if delete operation is allowed.
- `where`: An associative array of default where conditions.
- `beforeselect`, `beforecreate`, `beforeupdate`, `beforedelete`: Hook functions to be called before each operation.
- `afterselect`, `aftercreate`, `afterupdate`: Hook functions to be called after each operation.
- `subkeys`: Nested configurations for related data.

### Subkeys

Subkeys only support GET with the following configuration options:

- `tablename`: The name of the table in the database.
- `key`: The foreign key of the table pointing to the parent.
- `select`: An array of columns to be selected.



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
];
```

## Hook Functions

### Before Hooks

Before hooks are used to validate user authentication, permissions, and add custom where clauses.

- beforeselect($config): Called before a select operation.
- beforecreate($config): Called before a create operation.
- beforeupdate($config): Called before an update operation.
- beforedelete($config): Called before a delete operation.

### After Hooks

After hooks allow you to modify the results of a select operation or perform actions after create, update, or delete operations.

- afterselect($config, $results): Called after a select operation.
- aftercreate($config): Called after a create operation.
- afterupdate($config): Called after an update operation.

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
# Tenant API Documentation

The tenant is also referred to as an `app_id`. When calling `api.php`, the `app_id` must be sent.

## Endpoints

### Get Tenant Details

**Endpoint:** `/api.php/tenant`

This endpoint retrieves the details of a tenant.

#### HTTP headers for Get Tenant Details

- **app_id:** Tenant-specific value sent as a header. (REQUIRED)

#### Response for Get Tenant Details

```
{
  "id": 47,
  "uuid": "83b8771c-07f9-11f0-9750-1a220d8ac2c9",
  "name": "AccessElf",
  "description": null
}
```

### Get System Settings

**Endpoint:** `/getsettings.php`

This endpoint retrieves the system settings for a tenant.

#### HTTP headers for Get System Settings

- **app_id:** Tenant-specific value sent as a header. (REQUIRED)

#### Response for Get System Settings

```
[
    {
        "id": 33,
        "app_id": "83b8771c-07f9-11f0-9750-1a220d8ac2c9",
        "domain": null,
        "name": "returnURL",
        "value": "https:\/\/accesself.co.za\/subscriptions\/payment",
        "created": "2025-03-16 18:28:21",
        "modified": "2025-03-26 13:58:45"
    }
]
```

### Get Tenant Parameters

**Endpoint:** `/api.php/params`

This endpoint retrieves the parameters associated with a tenant.

#### HTTP headers for Get Tenant Parameters

- **app_id:** Tenant-specific value sent as a header. (REQUIRED)

#### Response for Get Tenant Parameters

```json
[
    {
        "id": 1,
        "name": "parameterName",
        "value": "parameterValue"
    }
]
```

### Create or Update Tenant Parameters

**Endpoint:** `/api.php/params`

This endpoint allows creating or updating tenant parameters.

#### HTTP headers for Create or Update Tenant Parameters

- **app_id:** Tenant-specific value sent as a header. (REQUIRED)

#### Response for Create or Update Tenant Parameters

```json
[
    {
        "id": 1,
        "name": "parameterName",
        "value": "parameterValue"
    }
]
```

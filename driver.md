# Drivers API Documentation

## Overview
API endpoints for managing drivers in the system. This API allows you to list, create, and delete drivers for economic operators.

## Base URL
```
/drivers
```

## Authentication
All endpoints require the following headers:
- `x-api-key`: Your API key (required)
- `x-operator-id`: Economic operator ID (required)

---

## GET /drivers
**List drivers**

Method used to list the drivers for a particular economic operator. You can filter the results using query string parameters.

### Parameters

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `term` | string | No | Name of the driver. Order of last name and first name matters based on the value of the `byLastName` filter |
| `withActiveDeclarations` | integer | No | Possible values 0 or 1. Works like in the UI interface |
| `limit` | integer | Yes | Number of returned items. Possible values a number from 1 to 250 |
| `startKey` | string | No | Marker used to indicate where the previous operation left off |
| `byLastName` | integer | No | Driver's name order filter |
| `dateOfBirth` | string | No | Driver's date of birth (e.g. 1980-05-20) |

#### Headers

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `x-api-key` | string | Yes | API authentication key |
| `x-operator-id` | string | Yes | Economic operator identifier |

### Pagination
For pagination you can use the following parameters:
- `limit`: Number of returned items. Possible values a number from 1 to 250
- `startKey`: Marker used to indicate where the previous operation left off

### Example Request
```http
GET /drivers?term=John&limit=50&withActiveDeclarations=1
x-api-key: your-api-key-here
x-operator-id: your-operator-id
```

---

## GET /drivers/{id}
**Get driver**

Method used to retrieve driver details.

### Parameters

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Driver ID to retrieve |

#### Headers

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `x-api-key` | string | Yes | API authentication key |
| `x-operator-id` | string | Yes | Economic operator identifier |

### Example Request
```http
GET /drivers/101483d-3d54-40f9-939b-175e9d90038
x-api-key: your-api-key-here
x-operator-id: your-operator-id
```

### Example Response
```json
{
  "driverId": "101483d-3d54-40f9-939b-175e9d90038",
  "driverOperatorId": "529e3dc0-8659-4797-af26-660afce58f32",
  "driverLatinFirstName": "John",
  "driverLatinLastName": "Smith",
  "driverFullName": "John Smith",
  "driverDateOfBirth": "1990-01-30",
  "driverLicenseNumber": "NEPH36234508",
  "driverDocumentType": "IDCARD",
  "driverDocumentNumber": "IDN9832412",
  "driverDocumentIssuingCountry": "FR",
  "driverAddressStreet": "Street 12",
  "driverAddressPostCode": "1000",
  "driverAddressCity": "Paris",
  "driverAddressCountry": "FR",
  "driverContractStartDate": "2000-08-25",
  "driverApplicableLaw": "FR"
}
```

---

## POST /drivers
**Create driver**

Method used to create a driver.

**Note:** This method is a prerequisite to be able to create declarations.

### Parameters

#### Headers

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `x-api-key` | string | Yes | API authentication key |
| `x-operator-id` | string | Yes | Economic operator identifier |

#### Request Body
Content-Type: `application/json`

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `driverLatinFirstName` | string | Yes | Driver's first name in Latin characters |
| `driverLatinLastName` | string | Yes | Driver's last name in Latin characters |
| `driverDateOfBirth` | string | Yes | Driver's date of birth (format: YYYY-MM-DD) |
| `driverLicenseNumber` | string | Yes | Driver's license number |
| `driverDocumentType` | string | Yes | Type of driver's document (e.g., "IDCARD") |
| `driverDocumentNumber` | string | Yes | Driver's document number |
| `driverAddressStreet` | string | Yes | Driver's street address |
| `driverAddressPostCode` | string | Yes | Driver's postal code |
| `driverAddressCity` | string | Yes | Driver's city |
| `driverAddressCountry` | string | Yes | Driver's country (e.g., "FR") |
| `driverContractStartDate` | string | Yes | Contract start date (format: YYYY-MM-DD) |
| `driverApplicableLaw` | string | Yes | Applicable law (e.g., "FR") |
| `driverDocumentIssuingCountry` | string | Yes | Document issuing country (e.g., "FR") |

### Example Request
```http
POST /drivers
Content-Type: application/json
x-api-key: your-api-key-here
x-operator-id: your-operator-id

{
  "driverLatinFirstName": "John",
  "driverLatinLastName": "Smith",
  "driverDateOfBirth": "1990-01-30",
  "driverLicenseNumber": "NPDH36234508",
  "driverDocumentType": "IDCARD",
  "driverDocumentNumber": "IDN9832412",
  "driverAddressStreet": "Street 12",
  "driverAddressPostCode": "1000",
  "driverAddressCity": "Paris",
  "driverAddressCountry": "FR",
  "driverContractStartDate": "2000-08-25",
  "driverApplicableLaw": "FR",
  "driverDocumentIssuingCountry": "FR"
}
```

---

## PUT /drivers/{id}
**Update driver**

Method used to update driver details.

**Note:** All the active declarations will be updated in the same time with the new driver details.

### Parameters

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Driver ID to update |

#### Headers

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `x-api-key` | string | Yes | API authentication key |
| `x-operator-id` | string | Yes | Economic operator identifier |

#### Request Body
Content-Type: `application/json`

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `driverId` | string | Yes | Driver's unique identifier |
| `driverLatinFirstName` | string | Yes | Driver's first name in Latin characters |
| `driverLatinLastName` | string | Yes | Driver's last name in Latin characters |
| `driverDateOfBirth` | string | Yes | Driver's date of birth (format: YYYY-MM-DD) |
| `driverLicenseNumber` | string | Yes | Driver's license number |
| `driverDocumentType` | string | Yes | Type of driver's document (e.g., "IDCARD") |
| `driverDocumentNumber` | string | Yes | Driver's document number |
| `driverAddressStreet` | string | Yes | Driver's street address |
| `driverAddressPostCode` | string | Yes | Driver's postal code |
| `driverAddressCity` | string | Yes | Driver's city |
| `driverAddressCountry` | string | Yes | Driver's country (e.g., "FR") |
| `driverContractStartDate` | string | Yes | Contract start date (format: YYYY-MM-DD) |
| `driverApplicableLaw` | string | Yes | Applicable law (e.g., "FR") |
| `driverDocumentIssuingCountry` | string | Yes | Document issuing country (e.g., "FR") |

### Example Request
```http
PUT /drivers/101483d-3d54-40f9-939b-175e9d90038
Content-Type: application/json
x-api-key: your-api-key-here
x-operator-id: your-operator-id

{
  "driverId": "101483d-3d54-40f9-939b-175e9d90038",
  "driverLatinFirstName": "John",
  "driverLatinLastName": "Smith",
  "driverDateOfBirth": "1990-01-30",
  "driverLicenseNumber": "NEPH36234508",
  "driverDocumentType": "IDCARD",
  "driverDocumentNumber": "IDN9832412",
  "driverAddressStreet": "Street 12",
  "driverAddressPostCode": "1000",
  "driverAddressCity": "Paris",
  "driverAddressCountry": "FR",
  "driverContractStartDate": "2000-08-25",
  "driverApplicableLaw": "FR",
  "driverDocumentIssuingCountry": "FR"
}
```

---

## DELETE /drivers/{id}
**Delete driver**

Method used to delete a driver.

**Note:** You are NOT able to delete a driver, if the driver has associated at least one declaration.

### Parameters

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Driver ID to delete |

#### Headers

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `x-api-key` | string | Yes | API authentication key |
| `x-operator-id` | string | Yes | Economic operator identifier |

### Example Request
```http
DELETE /drivers/driver-123
x-api-key: your-api-key-here
x-operator-id: your-operator-id
```

---

## Response Codes

### Success Responses
- `200 OK` - Request successful (GET, DELETE)
- `201 Created` - Driver created successfully (POST)

### Error Responses
- `400 Bad Request` - Invalid request parameters or body
- `401 Unauthorized` - Invalid or missing API key
- `403 Forbidden` - Access denied or invalid operator ID
- `404 Not Found` - Driver not found (DELETE)
- `409 Conflict` - Cannot delete driver with associated declarations
- `422 Unprocessable Entity` - Validation errors in request body
- `500 Internal Server Error` - Server error

---

## Notes

1. **Driver Creation**: Creating a driver is a prerequisite for creating declarations in the system.

2. **Driver Updates**: When updating a driver, all active declarations associated with that driver will be automatically updated with the new driver details.

3. **Driver Deletion Restrictions**: A driver cannot be deleted if they have at least one associated declaration.

3. **Date Formats**: All dates should be in `YYYY-MM-DD` format.

4. **Country Codes**: Use ISO 3166-1 alpha-2 country codes (e.g., "FR" for France).

5. **Pagination**: When listing drivers, use the `startKey` from the previous response to get the next page of results.

6. **Filtering**: The `term` parameter searches driver names, and the order depends on the `byLastName` parameter setting.
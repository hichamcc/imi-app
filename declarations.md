# Declarations API Documentation

## Overview
API endpoints for managing declarations in the system. This API allows you to list, create, retrieve, update, delete, submit, and withdraw declarations for economic operators.

## Base URL
```
/declarations
```

## Authentication
All endpoints require the following headers:
- `x-api-key`: Your API key (required)
- `x-operator-id`: Economic operator ID (required)

---

## GET /declarations
**List declarations**

Method used to list the declarations for a particular economic operator. You can filter the results using the query string parameters.

### Parameters

#### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `postingCountry` | string | No | Filters the results for the particular posting country. Possible values: AT, BE, BG, CY, CZ, DE, DK, EE, ES, FI, FR, GB, GR, HR, HU, IE, IT, LI, LT, LU, LV, MT, NL, NO, PL, PT, RO, SE, SI, SK |
| `limit` | integer | Yes | Number of returned items. Possible values a number from 1 to 250 |
| `endDateFrom` | string | No | Filters the results with the declarations where the end date is greater than or equal with the specified date (e.g. 2024-05-01) |
| `startKey` | string | No | Marker used to indicate where the previous operation left off |
| `driverId` | string | No | Filters the results for the particular driver |
| `status` | string | No | Filters the results by declaration status. Possible values: DRAFT, SUBMITTED, WITHDRAWN, EXPIRED |
| `endDateTo` | string | No | Filters the results with the declarations where the end date is less than or equal with the specified date (e.g. 2024-03-26) |

#### Headers

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `x-api-key` | string | Yes | API authentication key |
| `x-operator-id` | string | Yes | Economic operator identifier |

### Example Request
```http
GET /declarations?limit=50&postingCountry=AT&status=SUBMITTED
x-api-key: your-api-key-here
x-operator-id: your-operator-id
```

### Example Response
```json
{
  "count": 1,
  "items": [
    {
      "declarationId": "93534e2-6e5b-4eed-a968-fa4fa39f8c1a",
      "driverLatinFullName": "John Doe",
      "declarationPostingCountry": "AT",
      "declarationStartDate": "2024-02-15",
      "declarationEndDate": "2022-02-28",
      "declarationStatus": "DRAFT",
      "declarationLastUpdate": "2024-02-21T09:29:30.017Z"
    }
  ],
  "lastEvaluatedKey": null
}
```

---

## GET /declarations/{id}
**Get declaration**

Method used to view declaration details.

### Parameters

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Declaration ID to retrieve |

#### Headers

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `x-api-key` | string | Yes | API authentication key |
| `x-operator-id` | string | Yes | Economic operator identifier |

### Example Request
```http
GET /declarations/1a3b7cc8-24c8-4edd-bec8-cb0faee68124
x-api-key: your-api-key-here
x-operator-id: your-operator-id
```

### Example Response
```json
{
  "operatorId": "529e3dc8-8659-4797-af26-660afce58f32",
  "declarationId": "1a3b7cc8-24c8-4edd-bec8-cb0faee68124",
  "declarationPostingCountry": "AT",
  "declarationStatus": "DRAFT",
  "declarationStartDate": "2024-02-29",
  "declarationEndDate": "2024-03-31",
  "declarationOperationType": [
    "INTERNATIONAL_CARRIAGE"
  ],
  "declarationTransportType": [
    "CARRIAGE_OF_GOODS"
  ],
  "declarationVehiclePlateNumber": [
    "AT428645"
  ],
  "declarationEmailStatus": "unknown",
  "driverId": "101483d-3d54-40f9-939b-175e9d90038d",
  "driverLatinFirstName": "John",
  "driverLatinLastName": "Smith",
  "driverFullName": "John Smith",
  "driverDateOfBirth": "1990-01-30",
  "driverLicenseNumber": "NEPH36234508",
  "driverDocumentType": "IDCARD"
}
```

---

## POST /declarations
**Create declaration**

Method used to create a declaration.

**Note:** Make sure a driver is created in advance.

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
| `declarationPostingCountry` | string | Yes | Posting country code (e.g., "AT") |
| `declarationStartDate` | string | Yes | Declaration start date (format: YYYY-MM-DD) |
| `declarationEndDate` | string | Yes | Declaration end date (format: YYYY-MM-DD) |
| `declarationOperationType` | array | Yes | Array of operation types (1-2 items). Possible values: CABOTAGE_OPERATIONS, INTERNATIONAL_CARRIAGE |
| `declarationTransportType` | array | Yes | Array of transport types (1-2 items). Possible values: CARRIAGE_OF_GOODS, CARRIAGE_OF_PASSENGERS |
| `declarationVehiclePlateNumber` | array | Yes | Array of vehicle plate numbers (e.g., ["AT428645"]) |
| `driverId` | string | Yes | Driver ID associated with the declaration |
| `otherContactAsTransportManager` | boolean | No | Whether other contact is the transport manager |
| `otherContactFirstName` | string | No | Other contact's first name |
| `otherContactLastName` | string | No | Other contact's last name |
| `otherContactEmail` | string | No | Other contact's email |
| `otherContactPhone` | string | No | Other contact's phone number |

### Example Request
```http
POST /declarations
Content-Type: application/json
x-api-key: your-api-key-here
x-operator-id: your-operator-id

{
  "declarationPostingCountry": "AT",
  "declarationStartDate": "2025-09-15",
  "declarationEndDate": "2025-10-14",
  "declarationOperationType": [
    "INTERNATIONAL_CARRIAGE"
  ],
  "declarationTransportType": [
    "CARRIAGE_OF_GOODS"
  ],
  "declarationVehiclePlateNumber": [
    "AT428645"
  ],
  "driverId": "101483d-3d54-40f9-939b-175e9d90038d",
  "otherContactAsTransportManager": false,
  "otherContactFirstName": "John",
  "otherContactLastName": "Smith",
  "otherContactEmail": "john.smith@example.com",
  "otherContactPhone": "+32567234534"
}
```

---

## PUT /declarations/{id}
**Update declaration**

Method used to update declaration details.

**Note:** For DRAFT declarations you can update all details.

### Parameters

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Declaration ID to update |

#### Headers

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `x-api-key` | string | Yes | API authentication key |
| `x-operator-id` | string | Yes | Economic operator identifier |

#### Request Body
Content-Type: `application/json`

Same fields as POST /declarations request body.

### Example Request
```http
PUT /declarations/1a3b7cc8-24c8-4edd-bec8-cb0faee68124
Content-Type: application/json
x-api-key: your-api-key-here
x-operator-id: your-operator-id

{
  "declarationPostingCountry": "AT",
  "declarationStartDate": "2024-02-29",
  "declarationEndDate": "2024-03-31",
  "declarationOperationType": [
    "INTERNATIONAL_CARRIAGE"
  ],
  "declarationTransportType": [
    "CARRIAGE_OF_GOODS"
  ],
  "declarationVehiclePlateNumber": [
    "AT428645"
  ],
  "driverId": "101483d-3d54-40f9-939b-175e9d90038d",
  "otherContactAsTransportManager": false,
  "otherContactFirstName": "John",
  "otherContactLastName": "Smith",
  "otherContactEmail": "john.smith@example.com",
  "otherContactPhone": "+32567234534"
}
```

---

## DELETE /declarations/{id}
**Delete declaration**

Method used to delete a declaration.

**Note:** Only DRAFT declarations can be deleted.

### Parameters

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Declaration ID to delete |

#### Headers

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `x-api-key` | string | Yes | API authentication key |
| `x-operator-id` | string | Yes | Economic operator identifier |

### Example Request
```http
DELETE /declarations/declaration-123
x-api-key: your-api-key-here
x-operator-id: your-operator-id
```

---

## PUT /declarations/{id}/submit
**Update submitted declaration**

Method used to update a submitted declarations by ID.

**Note:** Please check the schema for the fields allowed to be updated. At this stage an email with the updates will NOT be sent.

### Parameters

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Declaration ID to update |

#### Headers

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `x-api-key` | string | Yes | API authentication key |
| `x-operator-id` | string | Yes | Economic operator identifier |

#### Request Body
Content-Type: `application/json`

Limited fields can be updated for submitted declarations. Check the update schema for allowed fields.

### Example Request
```http
PUT /declarations/declaration-123/submit
Content-Type: application/json
x-api-key: your-api-key-here
x-operator-id: your-operator-id

{
  "declarationEndDate": "2024-03-31",
  "declarationOperationType": [
    "INTERNATIONAL_CARRIAGE"
  ],
  "declarationTransportType": [
    "CARRIAGE_OF_GOODS"
  ],
  "declarationVehiclePlateNumber": [
    "AT428645"
  ],
  "otherContactAsTransportManager": false,
  "otherContactFirstName": "John",
  "otherContactLastName": "Smith",
  "otherContactEmail": "john.smith@example.com",
  "otherContactPhone": "+32567234534"
}
```

---

## POST /declarations/{id}/submit
**Submit declaration**

Method used to submit a declaration.

**Note:** If the driver email is provided the declaration PDF file will be sent automatically by email.

### Parameters

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Declaration ID to submit |

#### Headers

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `x-api-key` | string | Yes | API authentication key |
| `x-operator-id` | string | Yes | Economic operator identifier |

### Example Request
```http
POST /declarations/declaration-123/submit
x-api-key: your-api-key-here
x-operator-id: your-operator-id
```

---

## POST /declarations/{id}/withdraw
**Withdraw declaration**

Method used to withdraw a declaration.

### Parameters

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Declaration ID to withdraw |

#### Headers

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `x-api-key` | string | Yes | API authentication key |
| `x-operator-id` | string | Yes | Economic operator identifier |

### Example Request
```http
POST /declarations/declaration-123/withdraw
x-api-key: your-api-key-here
x-operator-id: your-operator-id
```

---

## Response Codes

### Success Responses
- `200 OK` - Request successful (GET, PUT, DELETE, submit, withdraw)
- `201 Created` - Declaration created successfully (POST)

### Error Responses
- `400 Bad Request` - Invalid request parameters or body
- `401 Unauthorized` - Invalid or missing API key
- `403 Forbidden` - Access denied or invalid operator ID
- `404 Not Found` - Declaration not found
- `409 Conflict` - Cannot perform operation due to declaration state
- `422 Unprocessable Entity` - Validation errors in request body
- `500 Internal Server Error` - Server error

---

## Declaration Status Values

| Status | Description |
|--------|-------------|
| `DRAFT` | Declaration is in draft state and can be fully edited |
| `SUBMITTED` | Declaration has been submitted and has limited update options |
| `WITHDRAWN` | Declaration has been withdrawn |
| `EXPIRED` | Declaration has expired |

---

## Field Enums and Values

### Posting Countries
AT, BE, BG, CY, CZ, DE, DK, EE, ES, FI, FR, GB, GR, HR, HU, IE, IT, LI, LT, LU, LV, MT, NL, NO, PL, PT, RO, SE, SI, SK

### Operation Types (1-2 items required)
- `CABOTAGE_OPERATIONS` - Cabotage operations within a country
- `INTERNATIONAL_CARRIAGE` - International carriage operations

**Validation:** Array must contain 1-2 items (minItems: 1, maxItems: 2)

### Transport Types (1-2 items required)
- `CARRIAGE_OF_GOODS` - Transportation of goods
- `CARRIAGE_OF_PASSENGERS` - Transportation of passengers  

**Validation:** Array must contain 1-2 items (minItems: 1, maxItems: 2)

---

## Update Schema for Submitted Declarations

When updating submitted declarations via PUT `/declarations/{id}/submit`, only certain fields can be modified:

| Field | Type | Description |
|-------|------|-------------|
| `declarationEndDate` | string | Declaration end date (format: YYYY-MM-DD) |
| `declarationOperationType` | array | Array of operation types |
| `declarationTransportType` | array | Array of transport types |
| `declarationVehiclePlateNumber` | array | Array of vehicle plate numbers |
| `otherContactAsTransportManager` | boolean | Whether other contact is transport manager |
| `otherContactFirstName` | string | Other contact's first name |
| `otherContactLastName` | string | Other contact's last name |
| `otherContactEmail` | string | Other contact's email address |
| `otherContactPhone` | string | Other contact's phone number |
| `otherContactAddressStreet` | string | Other contact's street address |
| `otherContactAddressCity` | string | Other contact's city |
| `otherContactAddressCountry` | string | Other contact's country |
| `otherContactAddressPostCode` | string | Other contact's postal code |
| `driverEmail` | string | Driver's email address |

---

## Notes

1. **Driver Prerequisite**: A driver must be created before creating declarations.

2. **Status Restrictions**: 
   - Only DRAFT declarations can be deleted
   - Only DRAFT declarations support full updates
   - Submitted declarations have limited update fields

3. **Email Notifications**: When submitting a declaration, if a driver email is provided, the declaration PDF will be automatically sent by email.

4. **Date Formats**: All dates should be in `YYYY-MM-DD` format.

5. **Country Codes**: Use ISO 3166-1 alpha-2 country codes.

6. **Pagination**: Use `startKey` from previous responses to paginate through results.

7. **Array Validation**: 
   - `declarationOperationType` must contain 1-2 items (minItems: 1, maxItems: 2)
   - `declarationTransportType` must contain 1-2 items (minItems: 1, maxItems: 2)
   - Both fields support multiple values but are limited to maximum 2 items each
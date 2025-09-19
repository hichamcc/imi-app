# Declaration Print & Email API

## Overview
Simple API endpoints for printing and emailing declarations.

## Base URL
```
/declarations/{id}
```

## Authentication
Both endpoints require the following headers:
- `x-api-key`: Your API key (required)
- `x-operator-id`: Economic operator ID (required)

---

## POST /declarations/{id}/print
**Print declaration**

Method used to print a declaration.

**Note:** The output of this method will be a presigned URL, you can use to download the PDF file with the declaration details. The URL is valid for 1 hour.

### Parameters

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Declaration ID to print |

#### Headers

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `x-api-key` | string | Yes | API authentication key |
| `x-operator-id` | string | Yes | Economic operator identifier |

#### Request Body
Content-Type: `application/json`

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `declarationLanguage` | string | Yes | Language code for the declaration PDF (e.g., "bg") |

### Example Request
```http
POST /declarations/declaration-123/print
Content-Type: application/json
x-api-key: your-api-key-here
x-operator-id: your-operator-id

{
  "declarationLanguage": "bg"
}
```

### Response
Returns a presigned URL that can be used to download the PDF file containing the declaration details. The URL expires after 1 hour.

---

## POST /declarations/{id}/email
**Email declaration**

Method used to email a declaration.

### Parameters

#### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Declaration ID to email |

#### Headers

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `x-api-key` | string | Yes | API authentication key |
| `x-operator-id` | string | Yes | Economic operator identifier |

#### Request Body
Content-Type: `application/json`

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `declarationLanguage` | string | Yes | Language code for the declaration PDF (e.g., "bg") |
| `emailAddress` | string | Yes | Email address to send the declaration to |

### Example Request
```http
POST /declarations/declaration-123/email
Content-Type: application/json
x-api-key: your-api-key-here
x-operator-id: your-operator-id

{
  "declarationLanguage": "bg",
  "emailAddress": "driver@example.com"
}
```

### Response
Confirmation that the declaration has been sent to the specified email address.

---

## Response Codes

### Success Responses
- `200 OK` - Request successful

### Error Responses
- `400 Bad Request` - Invalid request parameters
- `401 Unauthorized` - Invalid or missing API key
- `403 Forbidden` - Access denied or invalid operator ID
- `404 Not Found` - Declaration not found
- `500 Internal Server Error` - Server error

---

## Notes

1. **Print URL Expiration**: The presigned URL from the print endpoint is valid for only 1 hour
2. **Language Codes**: Use appropriate language codes for the declaration PDF generation
3. **Email Delivery**: The email endpoint will send the declaration PDF as an attachment
4. **Declaration Status**: Only certain declaration statuses may allow these operations
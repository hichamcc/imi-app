EU Road Transport Posting Declaration API Integration
Project Overview
This Laravel project provides a frontend interface for the EU Road Transport Posting Declaration API (api.postingdeclaration.eu) using the imacrayon/blade-starter-kit. The application is a pure API client without local data storage - all driver and declaration data is managed through the external API.
Technology Stack

Framework: Laravel 12
Frontend: Blade components with imacrayon/blade-starter-kit
CSS Framework: Tailwind CSS (included with starter kit)
JavaScript: Alpine.js (included with starter kit)
API Integration: Guzzle HTTP client
Session Storage: For temporary form data and API caching

API Endpoints Structure
Based on the API documentation:
Declarations

GET /declarations - List declarations
POST /declarations - Create declaration
GET /declarations/{id} - Get declaration
PUT /declarations/{id} - Update declaration
DELETE /declarations/{id} - Delete declaration
POST /declarations/{id}/email - Send declaration email
POST /declarations/{id}/print - Print declaration
PUT /declarations/{id}/submit - Update submitted declaration
POST /declarations/{id}/submit - Submit declaration
POST /declarations/{id}/withdraw - Withdraw declaration

Drivers

GET /drivers - List drivers
POST /drivers - Create driver
GET /drivers/{id} - Get driver
PUT /drivers/{id} - Update driver
DELETE /drivers/{id} - Delete driver

Application Architecture Workflow
1. User Authentication & API Key Management

Store API credentials in environment variables
Handle API authentication via Bearer tokens
Implement API key validation and error handling

2. Driver Management Workflow
Driver Listing:

Display paginated list of drivers from API
Implement search and filtering capabilities
Show driver status and basic information

Driver Creation:

Form validation before API submission
Real-time form validation with Alpine.js
Success/error handling with user feedback
Redirect to driver details on successful creation

Driver Editing:

Load existing driver data from API
Pre-populate form fields
Update via API PUT request
Cache invalidation after updates

Driver Deletion:

Confirmation dialog before deletion
API DELETE request
List refresh after successful deletion

3. Declaration Management Workflow
Declaration Listing:

Display all declarations with status indicators
Filter by status (draft, submitted, withdrawn)
Show associated driver information
Quick action buttons for common operations

Declaration Creation:

Multi-step form process:

Select driver from dropdown (populated via API)
Set posting period (start/end dates)
Add country-specific information
Review and create as draft



Declaration Editing:

Load existing declaration data
Allow modifications only for draft declarations
Real-time validation against API constraints
Auto-save functionality for draft declarations

Declaration Submission:

Validation check before submission
API call to submit declaration
Status change from draft to submitted
Email notification option
Generate printable version

Declaration Withdrawal:

Available only for submitted declarations
Confirmation process
API call to withdraw
Status update and user notification
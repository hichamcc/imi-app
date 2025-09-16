# Truck Management System Documentation

## Overview
Internal database system for managing trucks/vehicles within your fleet. This system allows you to track vehicle information, monitor status, manage capacity, and assign trucks to drivers for operational efficiency.

---

## Truck Data Structure

### Core Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | Auto | Unique truck identifier (auto-generated) |
| `name` | string | Yes | Truck name/identifier (e.g., "Volvo FH", "Mercedes Actros") |
| `plate` | string | Yes | Vehicle license plate number (e.g., "BG 1234 AA") |
| `capacity_tons` | decimal | Yes | Maximum load capacity in tons |
| `status` | enum | Yes | Current truck status |
| `created_at` | datetime | Auto | Record creation timestamp |
| `updated_at` | datetime | Auto | Last update timestamp |

### Status Values

| Status | Description | Color Code |
|--------|-------------|------------|
| `Available` | Truck is ready for assignment | Green |
| `In-Transit` | Truck is currently on a trip | Blue |
| `Maintenance` | Truck is under maintenance/repair | Yellow |
| `Retired` | Truck is no longer in active service | Gray |

---

## Driver Assignment System

### Assignment Table Structure

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | Auto | Assignment record ID |
| `truck_id` | integer | Yes | Reference to truck |
| `driver_id` | string | Yes | Reference to driver (from Drivers API) |
| `assigned_date` | date | Yes | Date of assignment |
| `unassigned_date` | date | No | Date of unassignment (null if active) |
| `is_active` | boolean | Yes | Whether assignment is currently active |
| `notes` | text | No | Optional assignment notes |

### Assignment Rules

1. **Multiple Driver Assignment**: A truck can be assigned to multiple drivers simultaneously
2. **Multiple Trucks**: A driver can be assigned to multiple trucks simultaneously  
3. **Status Dependency**: Only trucks with status `Available` or `In-Transit` can be assigned to drivers
4. **Historical Tracking**: All assignments are kept for historical records

---

## Database Operations

### Create Truck
Insert new truck record with name, plate, capacity, and initial status. Set creation and update timestamps automatically.

### Update Truck Status
Update the status field and modification timestamp for a specific truck by ID.

### Assign Driver to Truck
Create a new assignment record linking truck ID to driver ID with current date. Multiple drivers can be assigned to the same truck simultaneously.

### Unassign Driver from Truck
Set the assignment record to inactive, add unassignment date, but keep the historical record.

### Get Truck with All Assigned Drivers
Retrieve truck information along with all currently assigned drivers using a join query with grouping to concatenate multiple driver IDs and assignment dates.

---

## Business Logic Rules

### Truck Status Management

1. **Available → In-Transit**: When a truck is dispatched for a trip
2. **In-Transit → Available**: When a truck completes a trip and returns
3. **Any Status → Maintenance**: When maintenance is required
4. **Maintenance → Available**: When maintenance is completed
5. **Any Status → Retired**: When truck is decommissioned

### Assignment Constraints

- Check if truck status allows assignment (not in maintenance or retired)
- Validate driver exists via API call to Drivers endpoint
- Prevent duplicate assignments (same driver to same truck)
- Maintain referential integrity between trucks and drivers

---

## API Integration Points

### Driver Validation
Before assigning a driver to a truck, validate the driver exists by calling the GET drivers endpoint with the driver ID and proper authentication headers.

### Declaration Integration
When creating declarations, reference assigned trucks by including the truck's plate number in the declarationVehiclePlateNumber array field.

---

## Application Interface Requirements

### Add/Edit Truck Form
Create form with fields for truck name, plate number, capacity in tons, and status selection buttons. Include validation for required fields and proper data types.

### Driver Assignment Interface
Display truck information card showing current assigned drivers in a list with remove buttons for each driver. Include dropdown to select and add new drivers to the truck. Show truck status with appropriate color coding.

### Truck List View
Display all trucks in a table or card layout showing name, plate, capacity, status, and assigned drivers count. Include filtering by status and search functionality.

---

## Reporting and Analytics

### Fleet Status Summary
Generate report showing count and total capacity grouped by truck status.

### Driver Assignment Report  
List all active assignments showing truck details, driver IDs, assignment dates, and duration of assignments.

### Utilization Metrics
Calculate and display metrics for trucks currently assigned versus unassigned, focusing on Available and In-Transit status trucks.

---

## Validation Rules

### Data Validation
1. **Plate Number**: Must be unique across all trucks
2. **Capacity**: Must be greater than 0
3. **Name**: Required, maximum 100 characters
4. **Status**: Must be one of the defined enum values

### Business Validation
1. **Assignment Validation**: Driver must exist in the external Drivers API
2. **Status Transitions**: Validate logical status changes
3. **Duplicate Prevention**: Prevent assigning same driver to same truck multiple times

---

## Integration Workflows

### New Truck Registration
1. Add truck to internal database with unique plate validation
2. Set initial status (typically "Available")
3. Optionally assign drivers if needed immediately

### Driver Assignment Process
1. Validate driver exists via Drivers API call
2. Check truck is available for assignment
3. Verify driver is not already assigned to this truck
4. Create new assignment record
5. Update truck status if needed

### Declaration Creation Workflow
1. Select driver from available drivers dropdown
2. Auto-populate associated truck plates based on driver's assignments
3. Allow manual addition or removal of plate numbers if needed
4. Validate that at least one plate number is provided
5. Submit declaration with selected driver and truck plate information

---


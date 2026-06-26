# RTPD Vehicle Integration — Work Tracker

Status: **API integration complete** — `/plate-numbers` endpoints wired up, declaration form refactored to use the new split fields.

---

## Background

The RTPD provider (`api.postingdeclaration.eu`) is releasing a new **Vehicle register** feature by end of June 2026. Two emails from the provider explained the change:

1. **First email** — Vehicles must now be registered before they can be used in a declaration. They provided an Excel template for bulk upload (so we can prepare fleet data ahead of the release).
2. **Second email** — Technical details for API users:
   - New `/vehicles` CRUD endpoints (list, get, add, edit, update, delete).
   - Declaration API changes:
     - **Goods**: `declarationVehiclePlateNumber[]` is replaced by `vehiclePlateNumberLight[]` and/or `vehiclePlateNumberHeavy[]`.
     - **Passengers**: `vehiclePlateNumber[]` remains unchanged.
   - **Validation**: plate numbers submitted in declarations are checked against the vehicle register. Unknown plates → declaration rejected.
   - **Training env / OpenAPI**: <https://api.postingdeclaration-training.eu/api-docs>
   - **Production deadline**: end of June 2026.

### Provider's Excel template structure

Path: [public/template-upload-plate-numbers-v1.xlsx](public/template-upload-plate-numbers-v1.xlsx)

| Sheet | Purpose |
|---|---|
| `ReferenceData` | Lists valid `Registration country` (31 codes), `Type of carriage` (2 values), `Vehicle weight` (3 values). |
| `Template` | Headers: `vehicle.registrationCountry`, `vehicle.carriageType`, `vehicle.weightType`, `vehicle.plateNumber`. One row per vehicle. |

**Valid values:**
- `carriageType`: `CARRIAGE_OF_GOODS`, `CARRIAGE_OF_PASSENGERS`
- `weightType`: `LIGHT`, `HEAVY`, `N/A` (only `N/A` allowed for passenger)
- `registrationCountry`: AT, BE, BG, HR, CY, CZ, DK, EE, FI, FR, DE, GR, HU, IE, IT, IS, LV, LI, LT, LU, MT, NL, NO, PL, PT, RO, SK, SI, ES, SE, GB

---

## ✅ Done — local fleet preparation

### Database

- Migration: [2026_06_08_142625_add_vehicle_api_fields_to_trucks_table.php](database/migrations/2026_06_08_142625_add_vehicle_api_fields_to_trucks_table.php)
- New `trucks` columns:
  - `registration_country` (2 chars, nullable)
  - `carriage_type` (string, nullable)
  - `weight_type` (string, default `HEAVY`)
  - `api_vehicle_id` (nullable — for storing the ID returned by `POST /vehicles` once we start syncing to the API)

### Model

- [app/Models/Truck.php](app/Models/Truck.php)
- New `$fillable` entries + constants: `CARRIAGE_GOODS`, `CARRIAGE_PASSENGERS`, `WEIGHT_LIGHT`, `WEIGHT_HEAVY`, `WEIGHT_NA`
- Static helpers: `getCarriageTypes()`, `getWeightTypes()`, `getRegistrationCountries()`

### Controller

- [app/Http/Controllers/TruckController.php](app/Http/Controllers/TruckController.php)
- `store()` / `update()` validate the new fields and call `normalizeWeightType()` — auto-sets `weight_type = N/A` when carriage = passengers, otherwise defaults to `HEAVY`.
- `exportTemplate()` — generates `.xlsx` in the exact provider format (both `ReferenceData` and `Template` sheets), filename `fleet-upload-YYYY-MM-DD.xlsx`.
- `bulkUpdateVehicleFields()` — JSON endpoint, updates only the 3 RTPD fields on selected trucks, scoped to `auth()->id()`. Empty fields = keep existing.

### Routes

[routes/web.php](routes/web.php) — both registered **before** `Route::resource('trucks')` so they don't get caught by `{truck}` wildcards:

```php
Route::get('trucks/export-template', [TruckController::class, 'exportTemplate'])->name('trucks.export-template');
Route::post('trucks/bulk-update-vehicle-fields', [TruckController::class, 'bulkUpdateVehicleFields'])->name('trucks.bulk-update-vehicle-fields');
```

### Views

- [resources/views/trucks/create.blade.php](resources/views/trucks/create.blade.php) — "Vehicle Registration (RTPD)" section with 3 fields, Alpine.js auto-shows `N/A` when passenger.
- [resources/views/trucks/edit.blade.php](resources/views/trucks/edit.blade.php) — same section, pre-fills from `$truck`.
- [resources/views/trucks/index.blade.php](resources/views/trucks/index.blade.php) — select-all checkbox column, 3 new columns (Reg. Country, Carriage, Weight), "Export Excel" button (emerald), "Bulk Update" button (purple, hidden until ≥1 row selected) with modal.

### Dependencies

- `phpoffice/phpspreadsheet ^5.8` — installed via composer for `.xlsx` export. May also be reused later for parsing an uploaded template if the user wants two-way Excel sync.

---

## ✅ Done — API integration (completed 2026-06-26)

### Confirmed endpoint shape (from training OpenAPI 3.1.0)

- Endpoints are **`/plate-numbers`** (not `/vehicles` as we initially guessed).
- `CreatePlateNumberModelPublic` schema:
  - `plateNumber` (required)
  - `registrationCountry` (required, 2 letters)
  - `transportType` (required, `CARRIAGE_OF_GOODS | CARRIAGE_OF_PASSENGERS`)
  - `vehicleWeight` (optional, `LIGHT | HEAVY | ''` — pass empty string for passenger vehicles)
- Declaration model now exposes three plate fields, **all optional**:
  - `declarationVehiclePlateNumber[]` — passengers
  - `declarationVehiclePlateNumberLight[]` — light goods
  - `declarationVehiclePlateNumberHeavy[]` — heavy goods
- Declaration must reference plates that already exist in the register, or the API rejects it.

### What was built

- [app/Services/PlateNumberService.php](app/Services/PlateNumberService.php) — CRUD wrapper (`list`, `paginated`, `all`, `get`, `create`, `update`, `delete`) + `payloadFromTruck()` converter using the API field names (`transportType`, `vehicleWeight`).
- [config/posting.php](config/posting.php) — added `endpoints.plate_numbers => '/plate-numbers'`.
- [app/Http/Controllers/DeclarationController.php](app/Http/Controllers/DeclarationController.php):
  - `fetchPlateGroups()` — pulls the full register, buckets into `goods_light / goods_heavy / passengers`, falls back to local trucks on API error.
  - `buildPlatePayload()` — turns validated form data into the right `declarationVehiclePlateNumber{Light,Heavy,}` keys based on the selected transport types.
  - `store()` / `update()` / `updateSubmitted()` now accept all three plate fields (each `nullable|array`), reshape into the API payload, and reject submissions with no plates for the selected transport type.
  - `editSubmitted()` checks missing plates across all three plate arrays.
- [app/Http/Controllers/DriverController.php](app/Http/Controllers/DriverController.php) — bulk-clone now copies whichever of the three plate fields the source declaration used.
- [resources/views/declarations/create.blade.php](resources/views/declarations/create.blade.php) and [edit.blade.php](resources/views/declarations/edit.blade.php) — replaced single plate list with three Alpine-controlled checkbox groups (`Heavy goods`, `Light goods`, `Passenger vehicles`) that show/hide based on the selected `declarationTransportType[]`. Shows a fallback warning if the API register couldn't load.
- [app/Http/Controllers/TruckController.php](app/Http/Controllers/TruckController.php):
  - `plateNumbers()` — new page listing both the IMI register **and** local trucks side by side with a per-row "Registered / Missing in IMI / Incomplete" status pill.
  - `pushPlateNumber()` — POSTs a single truck to `/plate-numbers` and stores the returned `plateNumberId` into `api_vehicle_id`.
  - `pushAllPlateNumbers()` — idempotent bulk push: fetches the register, skips trucks whose plate already exists (updating `api_vehicle_id` if it was missing), pushes the rest, returns a summary.
- [resources/views/trucks/plate-numbers.blade.php](resources/views/trucks/plate-numbers.blade.php) + "IMI Plate Register" button on the trucks index → `GET /trucks/plate-numbers`.

### New routes

```php
Route::get('trucks/plate-numbers', [TruckController::class, 'plateNumbers'])->name('trucks.plate-numbers');
Route::post('trucks/plate-numbers/push-all', [TruckController::class, 'pushAllPlateNumbers'])->name('trucks.plate-numbers.push-all');
Route::post('trucks/{truck}/push-plate-number', [TruckController::class, 'pushPlateNumber'])->name('trucks.push-plate-number');
```

---

## 🟢 Verified field mapping

| Local Truck column | API field | Notes |
|---|---|---|
| `plate` | `plateNumber` | required |
| `registration_country` | `registrationCountry` | upper-cased before sending |
| `carriage_type` | `transportType` | values match: `CARRIAGE_OF_GOODS` / `CARRIAGE_OF_PASSENGERS` |
| `weight_type` (`LIGHT`/`HEAVY`/`N/A`) | `vehicleWeight` | `N/A` → `''` empty string (per spec) |

---

## Remaining nice-to-haves (optional)

- **Backfill command** for old local trucks without registration_country/carriage_type — still useful for fleets created before the new fields existed.
- **Excel template import** (read direction) — parse the provider's template back into local `trucks` rows.
- **Auto-link on declaration save** — when the API rejects a declaration because of a missing plate, surface a "Push to register" affordance on the error.

---

## Key file references (quick jump)

| What | Where |
|---|---|
| Provider Excel template | [public/template-upload-plate-numbers-v1.xlsx](public/template-upload-plate-numbers-v1.xlsx) |
| Local truck migration | [database/migrations/2026_06_08_142625_add_vehicle_api_fields_to_trucks_table.php](database/migrations/2026_06_08_142625_add_vehicle_api_fields_to_trucks_table.php) |
| Truck model | [app/Models/Truck.php](app/Models/Truck.php) |
| Truck controller | [app/Http/Controllers/TruckController.php](app/Http/Controllers/TruckController.php) |
| Truck routes | [routes/web.php](routes/web.php) (around line 102) |
| Truck index UI | [resources/views/trucks/index.blade.php](resources/views/trucks/index.blade.php) |
| Truck create form | [resources/views/trucks/create.blade.php](resources/views/trucks/create.blade.php) |
| Truck edit form | [resources/views/trucks/edit.blade.php](resources/views/trucks/edit.blade.php) |
| Declaration create (needs change) | [resources/views/declarations/create.blade.php](resources/views/declarations/create.blade.php) |
| Existing API service pattern | [app/Services/PostingApiService.php](app/Services/PostingApiService.php), [app/Services/DriverService.php](app/Services/DriverService.php) |
| RTPD training env API docs | <https://api.postingdeclaration-training.eu/api-docs> |

---

## Resuming this work — checklist

1. [x] Confirm production deadline status — provider released `/plate-numbers`.
2. [x] Grab the updated OpenAPI spec; add endpoint paths to `config/posting.php`.
3. [x] Build `PlateNumberService` (CRUD).
4. [x] Add "Push to IMI" UI (per-row + bulk) on the new `/trucks/plate-numbers` page.
5. [x] Refactor declaration form + controllers to use the new light/heavy fields for goods transport.
6. [ ] Test end-to-end in the training/production environment with real credentials.
7. [ ] Optional: backfill command for legacy trucks missing `registration_country` / `carriage_type`.

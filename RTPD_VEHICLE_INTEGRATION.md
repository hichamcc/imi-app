# RTPD Vehicle Integration — Work Tracker

Status: **In progress** — local prep done, API integration pending provider release.

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

## 🟡 TODO — API integration (do this when the provider releases)

### 1. Vehicle CRUD against the new `/vehicles` endpoints

Pattern to mirror: `DriverService` / `DeclarationService`.

- Create `app/Services/VehicleService.php` with: `list`, `get`, `create`, `update`, `delete`, `search`.
- Endpoint names: TBD — check the updated OpenAPI at <https://api.postingdeclaration-training.eu/api-docs> once it goes live. Add the path to `config/posting.php` under `endpoints.vehicles`.
- Use `PostingApiService` (same auth/credential pattern as drivers).

### 2. Sync local trucks → API register

Two options:
- **(a)** "Push to API" button on the truck show/index pages — pushes a single truck or selected trucks to `POST /vehicles`, stores the returned remote ID into `api_vehicle_id`.
- **(b)** Auto-push on `Truck::store()` / `Truck::update()` after local DB write. Cleaner but riskier (API outage = local create blocked). Recommend (a) first.

### 3. Declaration form changes

[resources/views/declarations/create.blade.php](resources/views/declarations/create.blade.php) currently uses `declarationVehiclePlateNumber[]` (around line 132).

Required changes:
- Source the vehicle list from `VehicleService::list()` (the API register), **not** the local `trucks` table — otherwise the API will reject the declaration.
- Show two checkbox groups when goods is selected:
  - **Light vehicles** → `vehiclePlateNumberLight[]`
  - **Heavy vehicles** → `vehiclePlateNumberHeavy[]`
- Show one checkbox group when passenger is selected:
  - **Passenger vehicles** → `vehiclePlateNumber[]` (unchanged)
- Toggle visibility dynamically based on `declarationTransportType` checkbox.
- Also update `DeclarationController::store` / `update` and any clone logic (`DriverController::bulkClone`, `clone`) to send the new field names. Grep for `declarationVehiclePlateNumber` to find every call site.

### 4. Backfill existing local trucks

Existing rows have `registration_country = null`, `carriage_type = null`, `weight_type = 'HEAVY'` (the migration default). Before syncing to the API:
- Either ask the user to bulk-update them via the new modal (works for small fleets),
- Or write a `php artisan trucks:backfill-vehicle-fields` command (similar to `drivers:backfill-address-country`) that infers values where possible — e.g. derive `weight_type` from `capacity_tons` (>3.5t = HEAVY, ≤3.5t = LIGHT) and prompt for the rest.

### 5. (Optional) Excel template **import** (read direction)

The provider's portal can ingest the Excel directly. If we also want our app to ingest it (e.g. user uploads a filled template to populate local trucks):
- Parse `Template` sheet with phpspreadsheet (already installed).
- For each row, `Truck::updateOrCreate(['plate' => ..., 'user_id' => ...], [...])`.

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

When picking this back up:

1. [ ] Confirm production deadline status — has the provider released the `/vehicles` endpoints yet? Check the training API docs.
2. [ ] Grab the updated OpenAPI spec; add endpoint paths to `config/posting.php`.
3. [ ] Build `VehicleService` (CRUD).
4. [ ] Add "Push to API" UI on trucks index/show.
5. [ ] Refactor declaration form + controllers to use the new light/heavy fields for goods transport.
6. [ ] Test end-to-end in the training environment using a test user's credentials before flipping to production.
7. [ ] Backfill / verify existing trucks have all 3 RTPD fields set before syncing.

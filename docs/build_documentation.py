import os
import sys
import subprocess
import zipfile
import xml.etree.ElementTree as ET

W = '{http://schemas.openxmlformats.org/wordprocessingml/2006/main}'
PKG_REL = 'http://schemas.openxmlformats.org/package/2006/relationships'
OFF_REL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships'
A_NS = 'http://schemas.openxmlformats.org/drawingml/2006/main'
CT_NS = 'http://schemas.openxmlformats.org/package/2006/content-types'

ET.register_namespace('w', W)
ET.register_namespace('r', OFF_REL)
ET.register_namespace('a', A_NS)

def md_table(headers, rows):
    res = ["| " + " | ".join(headers) + " |"]
    res.append("| " + " | ".join(["---"] * len(headers)) + " |")
    for r in rows:
        res.append("| " + " | ".join(str(c).replace("\n", "<br>").replace("|", "\\|") for c in r) + " |")
    return "\n".join(res)

def html_table(headers, rows, widths=None):
    res = ['<table class="doc-table"><thead><tr>']
    for i, h in enumerate(headers):
        w = f' style="width: {widths[i]};"' if widths and i < len(widths) else ''
        res.append(f'<th{w}>{h}</th>')
    res.append('</tr></thead><tbody>')
    for r in rows:
        res.append('<tr>')
        for c in r:
            res.append(f'<td>{c}</td>')
        res.append('</tr>')
    res.append('</tbody></table>')
    return "".join(res)

print("Builder base ready.")

# ==========================================
# 1. SCREENSHOTS & UI DATA (19 IMAGES)
# ==========================================
screenshots_data = [
    {
        "id": "1",
        "title": "System Authentication Interface",
        "file": "login.png",
        "route": "auth/login.php",
        "desc": "Secure login portal featuring credential validation, bcrypt password verification, and session token initialization.",
        "elements": "Branded header logo, username/password inputs, Remember Me toggle, sign-in button, error notification banner."
    },
    {
        "id": "2",
        "title": "Executive Operational Dashboard",
        "file": "home.png",
        "route": "index.php",
        "desc": "Central command center visualizing real-time agricultural operations, key performance metrics, and operational alerts.",
        "elements": "5 KPI summary cards (Farmers, Farms, Crops, Plantings, Harvests), calendar interval filter, upcoming harvest alert badge, recent activity stream."
    },
    {
        "id": "3",
        "title": "Farmers Management Directory",
        "file": "Farmer.png",
        "route": "farmers/index.php",
        "desc": "Master directory of registered agricultural producers with integrated profile creation card and directory search.",
        "elements": "Real-time search bar, location/address filtering, sortable column headers, action buttons (edit/delete), quick registration side-card."
    },
    {
        "id": "4",
        "title": "Farm Lands Management",
        "file": "Farm.png",
        "route": "farms/index.php",
        "desc": "Agricultural farmland records linked to registered owners, detailing area sizes, soil classifications, and water delivery systems.",
        "elements": "Farmer owner selector, land name, geographic location, area in hectares, soil type and irrigation badges, edit/delete actions."
    },
    {
        "id": "5",
        "title": "Crop Master Catalog",
        "file": "Crop.png",
        "route": "crops/index.php",
        "desc": "Agronomic commodity database establishing standard crops, hybrid varieties, botanical categories, and seasonal growth cycles.",
        "elements": "Crop species name, cultivar/variety label, category dropdown, maturity cycle in days, optimal season badge, edit/delete actions."
    },
    {
        "id": "6",
        "title": "Planting Cycles Module",
        "file": "Planting.png",
        "route": "plantings/index.php",
        "desc": "Operational field transaction module recording planting schedules, land allocation, and seed stock consumption.",
        "elements": "Farm land and crop variety selectors, planting date picker, cultivated hectares, seed weight (kg), forecasted harvest date, lifecycle status badge."
    },
    {
        "id": "7",
        "title": "Harvest Yields & Production Module",
        "file": "Harvest.png",
        "route": "harvests/index.php",
        "desc": "Production output recording interface capturing commodity weight, commercial valuation, quality grades, and condition remarks.",
        "elements": "Planting cycle reference, harvest date, gross quantity (kg), unit price per kg (PHP), quality grade selector, harvest remark selector, auto status transition."
    },
    {
        "id": "8",
        "title": "Operational Reports Overview",
        "file": "Report.png",
        "route": "reports/index.php",
        "desc": "Central reporting hub providing structured access to production, agronomic, and financial reporting modules.",
        "elements": "Report navigation cards, summary descriptions, one-click report launching, data export indicators."
    },
    {
        "id": "9",
        "title": "Report — Farmers & Land Ownership",
        "file": "Report-ownership.png",
        "route": "reports/farmers_farms.php",
        "desc": "Consolidated ownership report mapping registered farmers to total operational acreage and farm land distribution.",
        "elements": "Farmer search filter, land count aggregation, cumulative hectare totals, print report button, Export CSV action."
    },
    {
        "id": "10",
        "title": "Report — Crop Production per Farm",
        "file": "Report-crop-prod.png",
        "route": "reports/crop_production.php",
        "desc": "Land-specific agronomic production report detailing commodity varieties, land usage, and yield quantities.",
        "elements": "Farm land dropdown filter, commodity breakdown, cultivated land area totals, gross yield volume (kg), CSV export."
    },
    {
        "id": "11",
        "title": "Report — Planting Cycle Status Breakdown",
        "file": "Report-cycle-status.png",
        "route": "reports/planting_status.php",
        "desc": "Lifecycle monitoring report tracking active and completed planting batches across Planted, Growing, and Harvested phases.",
        "elements": "Lifecycle status filter dropdown, chronological schedule timeline, active crop count, CSV data export."
    },
    {
        "id": "12",
        "title": "Report — Harvest Yield & Revenue Analytics",
        "file": "Report-harvest-yield.png",
        "route": "reports/harvest_revenue.php",
        "desc": "Comprehensive commercial analytics report detailing physical crop volume harvested and gross financial revenue realized.",
        "elements": "Start and end date range pickers, total kilograms harvested, gross revenue sum in PHP, quality grade distribution, Export CSV."
    },
    {
        "id": "13",
        "title": "Report — Farmer Profitability Analysis",
        "file": "Report-farmer-profit.png",
        "route": "reports/farmer_profit.php",
        "desc": "Financial performance breakdown evaluating commodity sales value and economic returns generated per registered producer.",
        "elements": "Producer breakdown, volume produced, average selling price realization, gross revenue rankings, CSV export."
    },
    {
        "id": "14",
        "title": "Report — Seasonal Agro-Climatic Performance",
        "file": "Report-seasonal-performance.png",
        "route": "reports/seasonal_performance.php",
        "desc": "Comparative agronomic assessment measuring crop performance across Dry Season, Wet Season, and Year-Round cycles.",
        "elements": "Seasonal grouping, yield comparison, rainfall adaptability assessment, visual performance breakdown, CSV export."
    },
    {
        "id": "15",
        "title": "Report — Yield per Crop Variety",
        "file": "Report-yeild-per-crop.png",
        "route": "reports/yield_per_crop.php",
        "desc": "Agronomic cultivar productivity report analyzing kilograms produced per hectare across distinct hybrid crop strains.",
        "elements": "Cultivar name, category classification, total harvested yield (kg), productivity per hectare metric, CSV export."
    },
    {
        "id": "16",
        "title": "User Account Management Console",
        "file": "Users.png",
        "route": "users/index.php",
        "desc": "Administrative control interface for managing system operator accounts and configuring role authorizations.",
        "elements": "User directory, role badge (Administrator / Staff), account creation modal, password reset action, soft-deactivation toggle."
    },
    {
        "id": "17",
        "title": "Audit Activity Log",
        "file": "Activity-log.png",
        "route": "users/activity.php",
        "desc": "Immutable chronological transaction audit trail recording every insert, update, and soft-delete operation across the system.",
        "elements": "Operator identity stamp, action classification badge (Create / Update / Delete), target entity name, record ID, modification details, timestamp."
    },
    {
        "id": "18",
        "title": "Controlled Lookup Settings Manager",
        "file": "Setting.png",
        "route": "settings/index.php",
        "desc": "Administrative manager for maintaining reference tables governing controlled dropdown options without modifying source code.",
        "elements": "Tabbed navigation (Soil Types, Irrigation Methods, Categories, Seasons, Statuses, Grades, Remarks), inline add/edit modal, soft-delete toggle."
    },
    {
        "id": "19",
        "title": "Operator Profile & Session Management",
        "file": "Profile.png",
        "route": "profile/index.php",
        "desc": "Personal operator account portal displaying active role privileges and supporting self-service credential updates.",
        "elements": "Full name, username display, assigned authorization tier, password update form, active session details."
    }
]

screen_headers = ["#", "Module / View", "Script File / URI", "Interface Layout Description", "Key UI Elements & Actions"]
screen_rows = [
    [s["id"], s["title"], s["route"], s["desc"], s["elements"]] for s in screenshots_data
]

print(f"Loaded {len(screenshots_data)} screenshots.")

# Functional Requirements
fr_headers = ["#", "Requirement Name", "Description & Functional Scope", "Implementation Files", "Authorized Roles"]
fr_rows = [
    ["FR1", "Farmer Profile Management", "Register new farmers, view profile directories, edit personal/contact info, search/filter by name/address, and perform soft-deletion.", "farmers/index.php, models/Farmer.php", "Admin, Staff"],
    ["FR2", "Farm Land Management", "Register and maintain farmer lands linked to owning farmers, recording land area in hectares, and selecting soil/irrigation lookup types.", "farms/index.php, models/Farm.php", "Admin, Staff"],
    ["FR3", "Crop Master Catalog", "Manage catalog of agricultural crops, varieties, botanical categories, maturity cycle durations (days), and optimal growing seasons.", "crops/index.php, models/Crop.php", "Admin, Staff"],
    ["FR4", "Planting Transactions", "Record agricultural planting activities linking farmer lands to crops, tracking ground area planted, seed quantities, and expected harvest dates.", "plantings/index.php, models/Planting.php", "Admin, Staff"],
    ["FR5", "Harvest Transactions", "Record harvest outputs linked to plantings, logging yield quantity (kg), unit selling price, quality grades, and remarks. Automatically updates planting status.", "harvests/index.php, models/Harvest.php", "Admin, Staff"],
    ["FR6", "Executive Dashboard", "Real-time summary KPI cards (total farmers, farms, crops, active plantings, harvests, revenue), date range filtering, recent activity, and quick navigation.", "index.php", "Admin, Staff"],
    ["FR7", "Authentication & Session", "Secure authentication enforcing encrypted bcrypt passwords, session token management, and automatic unauthenticated redirection.", "auth/login.php, auth/logout.php, includes/auth_check.php", "All Users"],
    ["FR8", "Role-Based Access Control", "Enforce strict privilege boundaries where Admin accounts access user management and lookup settings, while Staff is restricted to operational modules.", "includes/auth_check.php, users/*.php, settings/*.php", "Enforced System-wide"],
    ["FR9", "Search, Filter & Sorting", "Multi-column live search, dropdown filtering (by address, irrigation, category, status, grade), and ascending/descending table sorting.", "includes/table_helpers.php, all module index.php files", "Admin, Staff"],
    ["FR10", "Operational Reports & CSV", "Generate four dedicated reporting suites: Farmers & Farms, Crop Production, Planting Status, and Harvest & Revenue with live CSV export capabilities.", "reports/*.php, includes/report_helpers.php", "Admin, Staff"],
    ["FR11", "Controlled Dropdowns", "Enforce data integrity by replacing arbitrary text fields with controlled dropdowns backed by foreign key lookup tables.", "models/Lookup.php, all create/edit modal views", "Admin, Staff"],
    ["FR12", "Destructive Action Safety", "Prevent accidental data loss via client-side confirmation modals before soft-deleting any record, with referential integrity enforcement.", "includes/confirm_modal.php, assets/js/main.js", "Admin, Staff"],
    ["FR13", "Audit Trail Logging", "Persist comprehensive audit logs recording who (user ID), what action (create/update/delete), which entity, and timestamp for all transactions.", "includes/activity_log.php, database activity_log, users/activity.php", "Admin Only"],
    ["FR14", "Responsive Interface", "Adaptive interface supporting mobile, tablet, and desktop views with persistent bottom navigation bar on mobile viewports.", "includes/header.php, assets/css/style.css", "All Users"]
]

# Entity Relationships
rel_headers = ["Parent Table", "Child Table", "Cardinality", "Foreign Key Column", "Relationship Description"]
rel_rows = [
    ["farmer", "farm", "1 : M (One-to-Many)", "farm.farmer_id", "A single farmer can own and manage multiple registered farmer lands."],
    ["farm", "planting", "1 : M (One-to-Many)", "planting.farm_id", "A farm land hosts multiple seasonal planting cycles over time."],
    ["crop", "planting", "1 : M (One-to-Many)", "planting.crop_id", "A crop master entry is referenced across numerous planting records."],
    ["planting", "harvest", "1 : M (One-to-Many)", "harvest.planting_id", "A planting cycle yields one or more harvest batches."],
    ["soil_types", "farm", "1 : M (One-to-Many)", "farm.soil_type_id", "A soil type classification characterizes multiple farmer lands."],
    ["irrigation_types", "farm", "1 : M (One-to-Many)", "farm.irrigation_type_id", "An irrigation infrastructure method is applied to multiple farms."],
    ["crop_categories", "crop", "1 : M (One-to-Many)", "crop.category_id", "A crop category groups multiple crop species."],
    ["seasons", "crop", "1 : M (One-to-Many)", "crop.season_id", "A seasonal climate condition applies to multiple crop types."],
    ["planting_status", "planting", "1 : M (One-to-Many)", "planting.status_id", "A workflow status tracks the lifecycle state of plantings."],
    ["quality_grades", "harvest", "1 : M (One-to-Many)", "harvest.grade_id", "A commercial quality standard categorizes harvest yields."],
    ["harvest_remarks", "harvest", "1 : M (One-to-Many)", "harvest.remark_id", "A controlled assessment remark describes harvest outcomes."],
    ["users", "activity_log", "1 : M (One-to-Many)", "activity_log.user_id", "A system user account generates multiple audit log entries."]
]

# Normalization Tables
unf_headers = ["Farmer Name", "Contact", "Farm Location", "Farm Name & Type", "Crops Planted (Repeating Group)", "Harvest Transactions (Repeating Group)"]
unf_rows = [
    [
        "Juan Dela Cruz", 
        "09171234567", 
        "Poblacion, Carcar City, Cebu", 
        "Green Valley Rice Farm<br>(Clay Loam, Irrigated)", 
        "• Rice (RC-222, Cereal, Wet Season, 115d): 2024-06-10, 2.5 ha, 100 kg<br>• Yellow Corn (Pioneer 30T80, Dry Season, 105d): 2024-10-15, 1.0 ha, 20 kg", 
        "• Rice: 2024-10-05, 8,200 kg @ ₱24.50/kg, Grade A, Good Harvest<br>• Corn: 2025-01-28, 3,500 kg @ ₱18.00/kg, Grade B, Acceptable Quality"
    ],
    [
        "Maria Santos", 
        "09182345678", 
        "Mantalongon, Dalaguete, Cebu", 
        "Sunrise Highland Vegetable Farm<br>(Sandy Loam, Rainfed)", 
        "• Cabbage (Scorpio Hybrid, Vegetable, Dry Season, 65d): 2024-08-01, 1.5 ha, 15 kg<br>• Tomato (Diamante Max, Dry Season, 75d): 2024-11-01, 1.3 ha, 10 kg", 
        "• Cabbage: 2024-10-10, 6,400 kg @ ₱35.00/kg, Grade A, Good Harvest<br>• Tomato: 2025-01-20, 4,200 kg @ ₱45.00/kg, Grade A, Good Harvest"
    ]
]

first_nf_headers = ["Farmer", "Contact", "Farm Name", "Soil", "Irrigation", "Crop", "Variety", "Planted", "Area", "Harvest Date", "Yield (kg)", "Price", "Grade", "Remark"]
first_nf_rows = [
    ["Juan Dela Cruz", "09171234567", "Green Valley Rice Farm", "Clay Loam", "Irrigated", "Rice", "RC-222", "2024-06-10", "2.5 ha", "2024-10-05", "8,200", "₱24.50", "Grade A", "Good Harvest"],
    ["Juan Dela Cruz", "09171234567", "Green Valley Rice Farm", "Clay Loam", "Irrigated", "Yellow Corn", "Pioneer 30T80", "2024-10-15", "1.0 ha", "2025-01-28", "3,500", "₱18.00", "Grade B", "Acceptable Quality"],
    ["Maria Santos", "09182345678", "Sunrise Highland Farm", "Sandy Loam", "Rainfed", "Cabbage", "Scorpio Hybrid", "2024-08-01", "1.5 ha", "2024-10-10", "6,400", "₱35.00", "Grade A", "Good Harvest"],
    ["Maria Santos", "09182345678", "Sunrise Highland Farm", "Sandy Loam", "Rainfed", "Tomato", "Diamante Max", "2024-11-01", "1.3 ha", "2025-01-20", "4,200", "₱45.00", "Grade A", "Good Harvest"]
]

print("Added normalization data.")

# Lookup Tables Data
lookup_tables = [
    {
        "name": "harvest_remarks",
        "title": "Lookup Table: Harvest Remarks",
        "description": "Standardized crop yield outcome and physical condition remarks.",
        "headers": ["remark_id", "remark_description", "Agricultural Evaluation Criteria"],
        "rows": [
            ["1", "Good Harvest", "Produce exceeds standard quality and yield benchmarks with zero pest damage."],
            ["2", "Acceptable Quality", "Commercial quality meets standard market specifications with minor aesthetic variances."],
            ["3", "Low Yield", "Yield quantity lower than forecasted expectations due to climatic or soil factors."],
            ["4", "Poor Quality", "Produce affected by physical defects, sub-optimal sizing, or handling damage."],
            ["5", "Damaged Harvest", "Crop damaged by severe weather, pest infestation, or plant disease outbreaks."]
        ]
    },
    {
        "name": "soil_types",
        "title": "Lookup Table: Soil Types",
        "description": "Standardized soil texture classifications for farmer lands.",
        "headers": ["soil_type_id", "soil_type_name", "Characteristics & Best Suited Crops"],
        "rows": [
            ["1", "Clay Loam", "Balanced mix of clay, silt, and sand; excellent nutrient retention for rice and corn."],
            ["2", "Sandy Loam", "Fast draining and warm; highly suited for vegetable crops and legumes."],
            ["3", "Silt Loam", "High fertility and moisture retention; optimal for leafy vegetables and cereals."],
            ["4", "Loam", "Ideal agricultural soil texture; supports universal crop rotation and orchards."],
            ["5", "Clay", "Dense water-holding capacity; optimal for lowland irrigated rice cultivation."],
            ["6", "Sandy", "Rapid percolation and low organic matter; suitable for root crops with irrigation."]
        ]
    },
    {
        "name": "irrigation_types",
        "title": "Lookup Table: Irrigation Types",
        "description": "Agricultural water delivery and irrigation methodologies.",
        "headers": ["irrigation_type_id", "irrigation_type_name", "Operational Delivery Description"],
        "rows": [
            ["1", "Irrigated", "Canal or pump gravity-fed system providing reliable, continuous water flow."],
            ["2", "Rainfed", "Natural precipitation-dependent farming without artificial conveyance infrastructure."],
            ["3", "Drip", "Targeted low-pressure emitter system delivering water directly to crop root zones."],
            ["4", "Sprinkler", "Overhead pressurized nozzle distribution simulating artificial rainfall."]
        ]
    },
    {
        "name": "crop_categories",
        "title": "Lookup Table: Crop Categories",
        "description": "Broad agronomic and botanical classifications.",
        "headers": ["category_id", "category_name", "Classification Scope & Examples"],
        "rows": [
            ["1", "Cereal", "Staple grain crops cultivated for edible seeds (e.g., Rice, Yellow Corn, White Corn)."],
            ["2", "Legume", "Nitrogen-fixing pod crops cultivated for pulses (e.g., Mungbean, Peanut, String Bean)."],
            ["3", "Root Crop", "Underground edible storage tuber and root crops (e.g., Sweet Potato, Cassava)."],
            ["4", "Fruit", "Perennial and seasonal horticultural fruit crops (e.g., Mango, Papaya, Banana)."],
            ["5", "Vegetable", "Nutritional culinary plant crops (e.g., Tomato, Eggplant, Cabbage, Bell Pepper)."]
        ]
    },
    {
        "name": "seasons",
        "title": "Lookup Table: Seasons",
        "description": "Tropical agro-climatic growing seasons in the Philippines.",
        "headers": ["season_id", "season_name", "Climatic Window & Agronomic Context"],
        "rows": [
            ["1", "Dry Season", "Period of low precipitation (November to April); ideal for irrigated cereals and solanaceous crops."],
            ["2", "Wet Season", "Monsoon rainy period (May to October); optimal for rainfed lowland rice and robust field crops."],
            ["3", "Year Round", "Tolerant cultivars capable of sustained continuous production throughout all calendar months."]
        ]
    },
    {
        "name": "planting_status",
        "title": "Lookup Table: Planting Status",
        "description": "Lifecycle phases tracking crop development from field sowing to harvest.",
        "headers": ["status_id", "status_name", "Lifecycle Stage & Operational Rule"],
        "rows": [
            ["1", "Planted", "Initial field phase: seeds sown or seedlings transplanted; monitoring germination."],
            ["2", "Growing", "Vegetative growth and flowering phase; active fertilizer and pest management applied."],
            ["3", "Harvested", "Terminal phase: maturity reached and yield gathered; automatically updated by harvest log."]
        ]
    },
    {
        "name": "quality_grades",
        "title": "Lookup Table: Quality Grades",
        "description": "Standardized commercial grading tiers for agricultural commodities.",
        "headers": ["grade_id", "grade_name", "Market Quality Specification"],
        "rows": [
            ["1", "Grade A", "Premium export/supermarket grade: uniform size, flawless appearance, peak ripeness."],
            ["2", "Grade B", "Standard wholesale/retail grade: sound internal quality with minor surface blemishes."],
            ["3", "Grade C", "Processing or discount grade: non-uniform size or cosmetic defects; fully edible."]
        ]
    }
]

print("Lookup tables data loaded.")

# 14 Data Dictionary Tables
data_dictionaries = [
    {
        "table": "farmer",
        "description": "Stores core biographical and contact profiles of registered agricultural producers.",
        "headers": ["Column Name", "Data Type", "Constraints", "Default", "Description & Field Rules"],
        "rows": [
            ["farmer_id", "SERIAL", "PRIMARY KEY, NOT NULL", "NextVal()", "Surrogate primary key uniquely identifying each farmer."],
            ["first_name", "VARCHAR(50)", "NOT NULL", "None", "Given legal first name of the registered farmer."],
            ["last_name", "VARCHAR(50)", "NOT NULL", "None", "Family name or surname of the registered farmer."],
            ["contact_number", "VARCHAR(20)", "NOT NULL", "None", "Active primary mobile or telephone contact number."],
            ["address", "VARCHAR(150)", "NOT NULL", "None", "Barangay, municipality, and provincial residence address."],
            ["date_registered", "DATE", "NOT NULL", "CURRENT_DATE", "Calendar date the farmer was registered into the system."],
            ["is_deleted", "BOOLEAN", "NOT NULL", "FALSE", "Soft delete flag: TRUE marks record archived/hidden."],
            ["created_at", "TIMESTAMP", "NOT NULL", "NOW()", "System timestamp marking initial record creation."],
            ["updated_at", "TIMESTAMP", "NOT NULL", "NOW()", "System timestamp recording most recent record update."]
        ]
    },
    {
        "table": "farm",
        "description": "Stores farmland information, agricultural properties, and references to the owning farmer.",
        "headers": ["Column Name", "Data Type", "Constraints", "Default", "Description & Field Rules"],
        "rows": [
            ["farm_id", "SERIAL", "PRIMARY KEY, NOT NULL", "NextVal()", "Surrogate primary key uniquely identifying each farm land."],
            ["farmer_id", "INT", "NOT NULL, FK -> farmer(farmer_id)", "None", "Foreign key linking land to registered owner (1:M)."],
            ["farm_name", "VARCHAR(100)", "NOT NULL", "None", "Official or popular name of the agricultural farm land."],
            ["location", "VARCHAR(150)", "NOT NULL", "None", "Sitio/Barangay and Municipality where farm is situated."],
            ["area_hectares", "NUMERIC(10,2)", "NOT NULL, CHECK (> 0)", "None", "Total cultivable land area in hectares (must be positive)."],
            ["soil_type_id", "INT", "NOT NULL, FK -> soil_types(soil_type_id)", "None", "Foreign key reference to controlled soil type lookup."],
            ["irrigation_type_id", "INT", "NOT NULL, FK -> irrigation_types(irrigation_type_id)", "None", "Foreign key reference to controlled irrigation lookup."],
            ["is_deleted", "BOOLEAN", "NOT NULL", "FALSE", "Soft delete flag for safe record deactivation."],
            ["created_at", "TIMESTAMP", "NOT NULL", "NOW()", "System timestamp recording initial insertion."],
            ["updated_at", "TIMESTAMP", "NOT NULL", "NOW()", "System timestamp recording latest modification."]
        ]
    },
    {
        "table": "crop",
        "description": "Maintains agricultural crop master data, botanical varieties, and agronomic cycles.",
        "headers": ["Column Name", "Data Type", "Constraints", "Default", "Description & Field Rules"],
        "rows": [
            ["crop_id", "SERIAL", "PRIMARY KEY, NOT NULL", "NextVal()", "Surrogate primary key identifying the crop species/variety."],
            ["crop_name", "VARCHAR(100)", "NOT NULL", "None", "Common commodity name (e.g., Rice, Yellow Corn, Tomato)."],
            ["variety", "VARCHAR(100)", "NOT NULL", "None", "Specific cultivar or hybrid designation (e.g., RC-222, Diamante Max)."],
            ["category_id", "INT", "NOT NULL, FK -> crop_categories(category_id)", "None", "Foreign key categorizing crop (Cereal, Legume, Fruit, etc.)."],
            ["growing_period_days", "INT", "NOT NULL, CHECK (> 0)", "None", "Average days required from planting to harvest maturity."],
            ["season_id", "INT", "NOT NULL, FK -> seasons(season_id)", "None", "Foreign key specifying optimal agronomic climate season."],
            ["is_deleted", "BOOLEAN", "NOT NULL", "FALSE", "Soft delete flag for archiving retired crop varieties."],
            ["created_at", "TIMESTAMP", "NOT NULL", "NOW()", "System audit timestamp at creation."],
            ["updated_at", "TIMESTAMP", "NOT NULL", "NOW()", "System audit timestamp at latest update."]
        ]
    },
    {
        "table": "planting",
        "description": "Records agricultural planting operational cycles, linking farmer lands to crops and tracking schedule.",
        "headers": ["Column Name", "Data Type", "Constraints", "Default", "Description & Field Rules"],
        "rows": [
            ["planting_id", "SERIAL", "PRIMARY KEY, NOT NULL", "NextVal()", "Surrogate primary key for the planting transaction record."],
            ["farm_id", "INT", "NOT NULL, FK -> farm(farm_id)", "None", "Foreign key linking planting event to host farm land."],
            ["crop_id", "INT", "NOT NULL, FK -> crop(crop_id)", "None", "Foreign key linking to specific crop variety planted."],
            ["planting_date", "DATE", "NOT NULL", "None", "Calendar date on which seeds or seedlings were planted."],
            ["area_planted", "NUMERIC(10,2)", "NOT NULL, CHECK (> 0)", "None", "Physical ground area allocated in hectares."],
            ["seed_quantity", "NUMERIC(10,2)", "NOT NULL, CHECK (> 0)", "None", "Weight of seed stock or seedlings utilized in kilograms."],
            ["expected_harvest_date", "DATE", "NOT NULL, CHECK (> planting_date)", "None", "Projected completion date; must strictly succeed planting_date."],
            ["status_id", "INT", "NOT NULL, FK -> planting_status(status_id)", "None", "Lifecycle phase: Planted, Growing, or Harvested."],
            ["is_deleted", "BOOLEAN", "NOT NULL", "FALSE", "Soft delete flag preserving historical relationship integrity."],
            ["created_at", "TIMESTAMP", "NOT NULL", "NOW()", "Audit creation timestamp."],
            ["updated_at", "TIMESTAMP", "NOT NULL", "NOW()", "Audit last-modified timestamp."]
        ]
    },
    {
        "table": "harvest",
        "description": "Captures agricultural harvest production yields, commodity valuation, and quality remarks.",
        "headers": ["Column Name", "Data Type", "Constraints", "Default", "Description & Field Rules"],
        "rows": [
            ["harvest_id", "SERIAL", "PRIMARY KEY, NOT NULL", "NextVal()", "Surrogate primary key identifying the harvest yield transaction."],
            ["planting_id", "INT", "NOT NULL, FK -> planting(planting_id)", "None", "Foreign key identifying originating planting cycle."],
            ["harvest_date", "DATE", "NOT NULL", "None", "Actual calendar date on which crops were harvested."],
            ["quantity_kg", "NUMERIC(10,2)", "NOT NULL, CHECK (> 0)", "None", "Net weight of crops harvested in kilograms."],
            ["selling_price_per_kg", "NUMERIC(10,2)", "NOT NULL, CHECK (> 0)", "None", "Realized or market price per kilogram in Philippine Pesos (PHP)."],
            ["grade_id", "INT", "NOT NULL, FK -> quality_grades(grade_id)", "None", "Foreign key referencing commercial grading tier."],
            ["remark_id", "INT", "NOT NULL, FK -> harvest_remarks(remark_id)", "None", "Foreign key referencing standardized condition remark."],
            ["is_deleted", "BOOLEAN", "NOT NULL", "FALSE", "Soft delete flag."],
            ["created_at", "TIMESTAMP", "NOT NULL", "NOW()", "Audit creation timestamp."],
            ["updated_at", "TIMESTAMP", "NOT NULL", "NOW()", "Audit last-modified timestamp."]
        ]
    },
    {
        "table": "users",
        "description": "Stores authorized system user credentials, display names, and role-based permissions.",
        "headers": ["Column Name", "Data Type", "Constraints", "Default", "Description & Field Rules"],
        "rows": [
            ["user_id", "SERIAL", "PRIMARY KEY, NOT NULL", "NextVal()", "Surrogate primary key identifying the user account."],
            ["username", "VARCHAR(50)", "NOT NULL, UNIQUE", "None", "Unique alphanumeric operator login identifier."],
            ["password_hash", "VARCHAR(255)", "NOT NULL", "None", "One-way cryptographic hash generated via bcrypt."],
            ["full_name", "VARCHAR(100)", "NOT NULL", "None", "Official full name displayed in application headers and logs."],
            ["role", "VARCHAR(20)", "NOT NULL, CHECK IN ('admin','staff')", "'staff'", "Access tier: 'admin' has full privileges; 'staff' has standard CRUD."],
            ["is_deleted", "BOOLEAN", "NOT NULL", "FALSE", "Soft delete flag for terminating account access."],
            ["created_at", "TIMESTAMP", "NOT NULL", "NOW()", "Account registration timestamp."],
            ["updated_at", "TIMESTAMP", "NOT NULL", "NOW()", "Account update timestamp."]
        ]
    },
    {
        "table": "activity_log",
        "description": "Maintains an immutable audit trail of all insert, update, and soft-delete transactions.",
        "headers": ["Column Name", "Data Type", "Constraints", "Default", "Description & Field Rules"],
        "rows": [
            ["log_id", "SERIAL", "PRIMARY KEY, NOT NULL", "NextVal()", "Sequential log event identifier."],
            ["user_id", "INT", "NOT NULL, FK -> users(user_id)", "None", "Identifies the user account that triggered the system change."],
            ["action", "VARCHAR(20)", "NOT NULL, CHECK IN ('create','update','delete')", "None", "Classification of mutation performed."],
            ["entity", "VARCHAR(50)", "NOT NULL", "None", "Target module or table name (e.g., 'farmer', 'harvest')."],
            ["entity_id", "INT", "NOT NULL", "None", "Primary key integer of the record impacted by the operation."],
            ["details", "VARCHAR(200)", "NOT NULL", "''", "Human-readable summary or audit snapshot of altered values."],
            ["created_at", "TIMESTAMP", "NOT NULL", "NOW()", "System timestamp indicating exact time of mutation."]
        ]
    },
    {
        "table": "soil_types",
        "description": "Lookup table specifying standardized soil classifications for agricultural lands.",
        "headers": ["Column Name", "Data Type", "Constraints", "Default", "Description & Field Rules"],
        "rows": [
            ["soil_type_id", "SERIAL", "PRIMARY KEY, NOT NULL", "NextVal()", "Surrogate primary key for soil classification code."],
            ["soil_type_name", "VARCHAR(50)", "NOT NULL", "None", "Soil name (Clay Loam, Sandy Loam, Silt Loam, Loam, Clay, Sandy)."],
            ["is_deleted", "BOOLEAN", "NOT NULL", "FALSE", "Soft delete flag for lookup value maintenance."]
        ]
    },
    {
        "table": "irrigation_types",
        "description": "Lookup table specifying recognized agricultural water delivery systems.",
        "headers": ["Column Name", "Data Type", "Constraints", "Default", "Description & Field Rules"],
        "rows": [
            ["irrigation_type_id", "SERIAL", "PRIMARY KEY, NOT NULL", "NextVal()", "Surrogate primary key for irrigation type code."],
            ["irrigation_type_name", "VARCHAR(50)", "NOT NULL", "None", "Irrigation system (Irrigated, Rainfed, Drip, Sprinkler)."],
            ["is_deleted", "BOOLEAN", "NOT NULL", "FALSE", "Soft delete flag for lookup value maintenance."]
        ]
    },
    {
        "table": "crop_categories",
        "description": "Lookup table grouping crop botanical classifications.",
        "headers": ["Column Name", "Data Type", "Constraints", "Default", "Description & Field Rules"],
        "rows": [
            ["category_id", "SERIAL", "PRIMARY KEY, NOT NULL", "NextVal()", "Surrogate primary key for crop category code."],
            ["category_name", "VARCHAR(50)", "NOT NULL", "None", "Category label (Cereal, Legume, Root Crop, Fruit, Vegetable)."],
            ["is_deleted", "BOOLEAN", "NOT NULL", "FALSE", "Soft delete flag for lookup value maintenance."]
        ]
    },
    {
        "table": "seasons",
        "description": "Lookup table specifying tropical climate seasons in the Philippines.",
        "headers": ["Column Name", "Data Type", "Constraints", "Default", "Description & Field Rules"],
        "rows": [
            ["season_id", "SERIAL", "PRIMARY KEY, NOT NULL", "NextVal()", "Surrogate primary key for season code."],
            ["season_name", "VARCHAR(50)", "NOT NULL", "None", "Season designation (Dry Season, Wet Season, Year Round)."],
            ["is_deleted", "BOOLEAN", "NOT NULL", "FALSE", "Soft delete flag for lookup value maintenance."]
        ]
    },
    {
        "table": "planting_status",
        "description": "Lookup table governing the operational status of crop planting batches.",
        "headers": ["Column Name", "Data Type", "Constraints", "Default", "Description & Field Rules"],
        "rows": [
            ["status_id", "SERIAL", "PRIMARY KEY, NOT NULL", "NextVal()", "Surrogate primary key for planting status code."],
            ["status_name", "VARCHAR(50)", "NOT NULL", "None", "Lifecycle status name (Planted, Growing, Harvested)."],
            ["is_deleted", "BOOLEAN", "NOT NULL", "FALSE", "Soft delete flag for lookup value maintenance."]
        ]
    },
    {
        "table": "quality_grades",
        "description": "Lookup table establishing commercial commodity quality grades.",
        "headers": ["Column Name", "Data Type", "Constraints", "Default", "Description & Field Rules"],
        "rows": [
            ["grade_id", "SERIAL", "PRIMARY KEY, NOT NULL", "NextVal()", "Surrogate primary key for produce quality grade."],
            ["grade_name", "VARCHAR(50)", "NOT NULL", "None", "Commercial standard designation (Grade A, Grade B, Grade C)."],
            ["is_deleted", "BOOLEAN", "NOT NULL", "FALSE", "Soft delete flag for lookup value maintenance."]
        ]
    },
    {
        "table": "harvest_remarks",
        "description": "Lookup table establishing standardized crop condition remarks upon harvest.",
        "headers": ["Column Name", "Data Type", "Constraints", "Default", "Description & Field Rules"],
        "rows": [
            ["remark_id", "SERIAL", "PRIMARY KEY, NOT NULL", "NextVal()", "Surrogate primary key for harvest remark code."],
            ["remark_description", "VARCHAR(100)", "NOT NULL", "None", "Standardized outcome remark (Good Harvest, Acceptable Quality, Low Yield, Poor Quality, Damaged Harvest)."],
            ["is_deleted", "BOOLEAN", "NOT NULL", "FALSE", "Soft delete flag for lookup value maintenance."]
        ]
    }
]

# Constraints Table
chk_headers = ["Table Name", "Constraint Name / Type", "Constraint Expression / Condition", "Enforced Business Rule"]
chk_rows = [
    ["farm", "CHECK (area_hectares)", "area_hectares > 0", "Enforces that farm farmland size must strictly be a positive real number."],
    ["crop", "CHECK (growing_period_days)", "growing_period_days > 0", "Ensures maturity cycle in days is strictly positive."],
    ["planting", "CHECK (area_planted)", "area_planted > 0", "Area cultivated in hectares must be positive."],
    ["planting", "CHECK (seed_quantity)", "seed_quantity > 0", "Seed weight utilized in kg must be greater than zero."],
    ["planting", "CHECK (chk_harvest_after_planting)", "expected_harvest_date > planting_date", "Guarantees chronological validity; expected harvest cannot precede planting date."],
    ["harvest", "CHECK (quantity_kg)", "quantity_kg > 0", "Harvested crop weight in kg must be greater than zero."],
    ["harvest", "CHECK (selling_price_per_kg)", "selling_price_per_kg > 0", "Price per kg in Philippine Pesos (PHP) must be positive."],
    ["users", "CHECK (role)", "role IN ('admin', 'staff')", "Restricts account authorization to strictly defined role tiers."],
    ["users", "UNIQUE (username)", "UNIQUE (username)", "Guarantees unique login usernames across all system accounts."],
    ["activity_log", "CHECK (action)", "action IN ('create', 'update', 'delete')", "Restricts audit transaction operations to standard CRUD verbs."],
    ["all tables", "BOOLEAN DEFAULT FALSE", "is_deleted = FALSE", "Preserves referential integrity via soft-deletion instead of destructive hard deletes."]
]

# Screenshots Table
screen_headers = ["#", "Module / View", "Script File / URI", "Interface Layout Description", "Key UI Elements & Actions"]
screen_rows = [
    ["1", "Authentication Login", "auth/login.php", "Clean centered login card with brand logo and credentials form.", "Username, password, Remember Me, submit button, error banner."],
    ["2", "Executive Dashboard", "index.php", "High-level KPI metric cards, date range filter, and recent activities.", "5 Metric cards, active plantings alert, recent harvests table."],
    ["3", "Farmers Management", "farmers/index.php", "Split layout: tabular directory on left, add-farmer card on right.", "Search input, address filter, sortable columns, edit/delete modals."],
    ["4", "Farms Management", "farms/index.php", "Comprehensive land directory joined with farmer owner and soil/irrigation.", "Farmer dropdown, soil/irrigation lookups, area validation."],
    ["5", "Crop Master Catalog", "crops/index.php", "Directory of agronomic crops with category, season, and cycle duration.", "Category & season selectors, maturity days input, action buttons."],
    ["6", "Planting Records", "plantings/index.php", "Transaction management interface linking farms and crops.", "Farm & crop dropdowns, planting & harvest date pickers, status badge."],
    ["7", "Harvest Records", "harvests/index.php", "Yield transaction log displaying quantity, revenue, and quality grading.", "Planting picker, quantity input, unit price, grade/remark lookups."],
    ["8", "Add/Edit Modal Forms", "Multiple modal components", "Standardized Bootstrap dialog with real-time field validation.", "Clean floating labels, error feedback states, save/cancel buttons."],
    ["9", "Delete Confirmation Dialog", "includes/confirm_modal.php", "Two-step confirmation modal preventing unintended record deletion.", "Destructive action warning text, entity title, Cancel & Confirm buttons."],
    ["10", "Report: Farmers & Farms", "reports/farmers_farms.php", "Consolidated report showing farmer ownership and total land holdings.", "Search filter, summary totals, print and Export CSV actions."],
    ["11", "Report: Crop Production", "reports/crop_production.php", "Production statistics filtered by individual farmer lands.", "Farm land selector, crop yield aggregation, Export CSV button."],
    ["12", "Report: Planting Status", "reports/planting_status.php", "Operational lifecycle monitoring report grouped by workflow status.", "Status filter dropdown (Planted, Growing, Harvested), Export CSV."],
    ["13", "Report: Harvest & Revenue", "reports/harvest_revenue.php", "Financial and production analytics with total yield and gross revenue.", "Date range start/end filter, total kg, total PHP revenue, CSV export."],
    ["14", "User Management (Admin)", "users/index.php", "Administrative console for managing system operator accounts.", "Role assignment (Admin/Staff), password reset modal, deactivation."],
    ["15", "Audit Activity Log (Admin)", "users/activity.php", "Immutable chronological audit trail recording system mutations.", "User badge, action tag (create/update/delete), entity ID, timestamp."],
    ["16", "Lookup Settings (Admin)", "settings/index.php", "Tabbed administration interface for maintaining all 7 lookup tables.", "Soil, irrigation, category, season, status, grade, remark managers."],
    ["17", "Mobile Adaptive Layout", "Responsive Viewport (<768px)", "Optimized view with collapsible sidebar and fixed bottom navigation.", "App bar header, touch-friendly tables, persistent bottom navigation."]
]

# Testing Matrix
test_headers = ["#", "Module", "Test Scenario / Scope", "Test Input / Action Performed", "Expected System Behavior", "Actual Verified Outcome", "Status"]
test_rows = [
    ["1", "Auth", "Admin Authentication", "Submit valid admin credentials (admin / admin123)", "Redirect to executive dashboard; store session tokens.", "Redirected to dashboard; session created with admin role.", "PASS"],
    ["2", "Auth", "Invalid Password Handling", "Submit valid username with incorrect password", "Authentication denied; error banner displayed; no session.", "Access denied; 'Invalid credentials' alert shown.", "PASS"],
    ["3", "Auth", "Protected Route Guarding", "Request /farmers/index.php while session unauthenticated", "Redirect immediately to /auth/login.php with redirect query.", "Redirected to login page; access blocked.", "PASS"],
    ["4", "Farmers", "Create New Farmer Profile", "Fill all required fields: name, contact, address", "Profile saved to Supabase DB; success banner displayed.", "Farmer record persisted; visible in directory; audit logged.", "PASS"],
    ["5", "Farmers", "Empty Field Validation", "Submit create form with missing contact number", "Submission blocked; client & server validation error shown.", "Form rejected; 'Contact number is required' error shown.", "PASS"],
    ["6", "Farms", "Negative Area Validation", "Attempt to input area_hectares = -3.50", "Blocked by HTML5 validation and DB CHECK (area > 0).", "Rejected with 'Area must be greater than zero' error.", "PASS"],
    ["7", "Plantings", "Chronological Date Check", "Set expected harvest date before planting date", "Submission blocked by JS validation and DB constraint.", "Blocked by chk_harvest_after_planting; error banner displayed.", "PASS"],
    ["8", "Harvests", "Automatic Status Transition", "Record a new harvest yield for an active planting", "Harvest saved; planting status automatically flips to 'Harvested'.", "Harvest inserted; linked planting status changed to ID 3 (Harvested).", "PASS"],
    ["9", "Dropdowns", "Lookup Table Foreign Keys", "Select 'Clay Loam' and 'Irrigated' on Farm Form", "UI shows text labels; form submits numeric IDs 1 and 1.", "Database stores integer foreign keys; joined queries display text.", "PASS"],
    ["10", "Search", "Multi-Column Filter Query", "Type 'Carcar' into farmers search input bar", "Table dynamically filters to records matching 'Carcar'.", "Only Carcar City farmers displayed; row counter updated.", "PASS"],
    ["11", "Sorting", "Column Ordering", "Click 'Area (ha)' table header in farms view", "Rows reorder ascending; second click reorders descending.", "Table sorts correctly; sorting arrow indicator toggles.", "PASS"],
    ["12", "Integrity", "Referential Deletion Protection", "Attempt to delete a farmer who has active farms", "Deletion blocked or handled via foreign key rule.", "Clean user warning displayed; cascade corruption prevented.", "PASS"],
    ["13", "Safety", "Delete Confirmation Modal", "Click delete action button on any record row", "Confirmation dialog opens; deletion requires explicit confirm.", "Modal displayed; 'Cancel' aborts; 'Confirm' soft-deletes.", "PASS"],
    ["14", "Reports", "Live SQL Data Aggregation", "Open 4 reports and verify against raw database queries", "Summary metric numbers match manual SQL aggregation.", "Live figures match PostgreSQL database queries with 100% accuracy.", "PASS"],
    ["15", "Reports", "Date Interval Filtering", "Select 30-day date interval on Harvest & Revenue report", "Table filters strictly to harvests within date range.", "Report narrows to date range; revenue recalculated dynamically.", "PASS"],
    ["16", "Reports", "CSV File Data Export", "Click 'Export CSV' button on active report view", "Browser downloads RFC 4180 formatted CSV file.", "Proper CSV file downloaded with exact headers and matching data.", "PASS"],
    ["17", "RBAC", "Staff Role Boundary Guard", "Log in as staff and attempt to access /users/ or /settings/", "Access denied; redirected to dashboard with warning.", "Redirected to dashboard; flash warning: 'Admin required'.", "PASS"],
    ["18", "RBAC", "Admin Role Privilege Access", "Log in as admin and access /users/ and /settings/", "Full access granted to user accounts and lookup settings.", "Admin console loaded with complete CRUD management tools.", "PASS"],
    ["19", "Dashboard", "Real-Time Metric Sync", "Insert new farmer and verify dashboard counter", "Dashboard summary card increments immediately by +1.", "Summary counter updated dynamically reflecting database state.", "PASS"],
    ["20", "Responsive", "Mobile Navigation Bar", "View application on mobile screen width (<768px)", "Sidebar hidden; bottom navigation bar enabled and functional.", "Layout adapts cleanly; bottom navigation provides quick tab switching.", "PASS"],
    ["21", "Audit", "Audit Trail Logging", "Perform create/update/delete operations across modules", "activity_log table records user ID, action, and timestamp.", "Activity log accurately records all transactions chronologically.", "PASS"]
]

print("All data dictionaries, constraints, screenshots, and test matrix rows defined.")

# ==========================================
# 2. MARKDOWN GENERATOR
# ==========================================

# ==========================================
# TRANSACTION SCREENSHOTS DATA (7 IMAGES)
# ==========================================
transaction_screenshots_data = [
    {
        "id": "1",
        "title": "Transaction Commit & State Persistence",
        "file": "commit.png",
        "desc": "Verification of explicit transaction boundary commit in PostgreSQL, ensuring permanent persistence of multi-table updates.",
        "concept": "COMMIT / ACID Durability"
    },
    {
        "id": "2",
        "title": "Transaction Rollback & Atomicity Safeguard",
        "file": "rollback.png",
        "desc": "Demonstration of rollback upon transaction failure, reverting all uncommitted state changes to preserve database consistency.",
        "concept": "ROLLBACK / ACID Atomicity"
    },
    {
        "id": "3",
        "title": "Partial Rollback via Savepoint Management",
        "file": "savepoint.png",
        "desc": "Utilization of named savepoints within complex transaction cycles, allowing granular rollback of nested operations without aborting the entire transaction.",
        "concept": "SAVEPOINT / Sub-transactions"
    },
    {
        "id": "4",
        "title": "Explicit Table-Level Locking Mechanisms",
        "file": "lock.png",
        "desc": "Enforcement of table-level access locks during bulk administrative imports and batch migrations to prevent conflicting concurrent writes.",
        "concept": "LOCK TABLE / Concurrency Control"
    },
    {
        "id": "5",
        "title": "Row-Level Locking via SELECT FOR UPDATE",
        "file": "lock-selected-rows.png",
        "desc": "Fine-grained row-level locking preventing race conditions when allocating farmer lands or reserving seed stock during simultaneous plantings.",
        "concept": "SELECT FOR UPDATE / Row Locks"
    },
    {
        "id": "6",
        "title": "Deadlock Prevention & Resource Ordering",
        "file": "prevent-deadlock.png",
        "desc": "Enforcement of deterministic locking sequences across related tables (Farmer -> Farm -> Planting -> Harvest) to eliminate circular deadlock conditions.",
        "concept": "Deadlock Avoidance Strategy"
    },
    {
        "id": "7",
        "title": "Transaction Completion & Lock Release",
        "file": "release-lock.png",
        "desc": "Automatic and safe release of acquired table and row locks upon transaction termination, restoring full concurrent throughput.",
        "concept": "Lock Release & Clean Termination"
    }
]

# ==========================================
# 2. MARKDOWN GENERATOR
# ==========================================
def generate_markdown():
    md = []
    
    # Title & Metadata
    md.append("# GehTanum: Agriculture Information System (AIS) — System Documentation\n")
    md.append("**Institution:** Cebu Technological University — Main Campus  ")
    md.append("**College:** College of Computer, Information and Communications Technology (CCICT)  ")
    md.append("**Course:** AP3 — Web Application Development  ")
    md.append("**Document Specification:** Course Portfolio & Midterm Documentation  ")
    md.append("**Paper Specification:** Long-Size Bond Paper (8.5\" × 13\" / Philippine Folio)  ")
    md.append("**Database Platform:** Supabase (Cloud PostgreSQL 15+)  ")
    md.append("**Application Stack:** PHP 8.2+, Tailwind CSS / Bootstrap 5, Vanilla JavaScript  ")
    md.append("**Architecture:** Fully Normalized Relational Model (3NF) — 14 Core & Lookup Tables  ")
    md.append("**Academic Term:** First Semester, Academic Year 2024–2025  \n")
    md.append("---\n")
    
    # Table of Contents
    md.append("## Table of Contents\n")
    md.append("1. [Project Title, Description, and Objectives](#1-project-title-description-and-objectives)")
    md.append("2. [Functional Requirements](#2-functional-requirements)")
    md.append("3. [Entity-Relationship Diagram (ERD)](#3-entity-relationship-diagram-erd)")
    md.append("4. [Database Normalization from UNF through 1NF, 2NF, and 3NF](#4-database-normalization-from-unf-through-1nf-2nf-and-3nf)")
    md.append("5. [Data Dictionary](#5-data-dictionary)")
    md.append("6. [Table Relationships, Constraints, and Lookup Data](#6-table-relationships-constraints-and-lookup-data)")
    md.append("7. [User Guide or Basic Operating Instructions](#7-user-guide-or-basic-operating-instructions)")
    md.append("8. [Screenshots or Evidence of All Major System Modules](#8-screenshots-or-evidence-of-all-major-system-modules)")
    md.append("9. [Test Cases and Results Showing that the Required Functions Work](#9-test-cases-and-results-showing-that-the-required-functions-work)")
    md.append("10. [Appendix: Deployment Architecture & Technical Specifications](#10-appendix-deployment-architecture--technical-specifications)\n")
    md.append("---\n")

    # Section 1: Project Title, Description, and Objectives
    md.append("## 1. Project Title, Description, and Objectives\n")
    md.append("### 1.1 Project Title & Overview")
    md.append("**GehTanum (Agriculture Information System)** — Centralized Web-Based Agricultural Operations, Farmland Mapping, Crop Lifecycle Tracking, and Yield Analytics Platform.\n")
    md.append("### 1.2 Project Description")
    md.append("**GehTanum** (Agriculture Information System) is an enterprise-grade agricultural management web platform engineered to modernize rural and cooperative farm records administration. Traditional farming operations frequently suffer from fragmented paper records, uncoordinated planting schedules, manual calculations of farm yield, and inaccurate profitability estimates. GehTanum resolves these operational inefficiencies by establishing a centralized, relational database architecture that unifies farmer profiles, farm land boundaries, crop master catalogs, seasonal planting cycles, and harvest transactions.\n")
    md.append("The system enforces strict domain integrity through controlled foreign key lookup tables, eliminates data redundancy via Third Normal Form (3NF) normalization, and delivers live operational intelligence through real-time KPI dashboards, automated cycle status workflows, and customizable reports with CSV data export capabilities. Built on PHP 8.2 and cloud-hosted Supabase PostgreSQL, GehTanum provides role-based access control, responsive mobile-first navigation, and an immutable transaction audit log to ensure administrative accountability across all farming communities.\n")
    md.append("### 1.3 Project Objectives")
    md.append("#### 1.3.1 General Objective")
    md.append("To architect, develop, and deploy a secure, fully normalized, web-based Agriculture Information System (**GehTanum**) that centralizes agricultural operations, streamlines field transaction logging from planting through harvest, and delivers real-time operational analytics for farm administrators and extension workers.\n")
    md.append("#### 1.3.2 Specific Objectives")
    md.append("1. **Farmer Directory & Land Tenure Management:** Establish a relational repository of agricultural producers and their corresponding farmer lands, recording geographic locations, land acreage in hectares, soil classifications, and irrigation systems.")
    md.append("2. **Agronomic Catalog Standardization:** Maintain a master catalog of crops, hybrid varieties, botanical categories, maturity durations (in days), and recommended growing seasons.")
    md.append("3. **Planting Lifecycle Monitoring:** Track field planting activities with automated calculation of expected harvest dates, land allocation constraints, and seed stock consumption.")
    md.append("4. **Harvest Production & Valuation:** Record harvest outputs, gross yields (kg), commercial selling prices, quality grading, and condition assessments, with automatic status updates upon yield completion.")
    md.append("5. **Operational Intelligence & Reporting:** Deliver dynamic reporting suites (Farmer Land Ownership, Crop Production per Farm, Planting Cycle Status, Harvest Yield & Revenue, Farmer Profitability, Seasonal Performance, and Variety Yield Efficiency) equipped with instant CSV data exports.")
    md.append("6. **Enterprise Security & Auditability:** Enforce bcrypt credential encryption, session state management, role-based access control (Admin vs. Staff), and an append-only transaction audit log recording all insert, update, and soft-delete operations.\n")

    # Section 2: Functional Requirements
    md.append("## 2. Functional Requirements\n")
    md.append("The system architecture satisfies fourteen comprehensive functional requirements spanning core agronomic transactions, administrative controls, data integrity, and analytical reporting.\n")
    md.append(md_table(fr_headers, fr_rows))
    md.append("\n")

    # Section 3: Entity-Relationship Diagram (ERD)
    md.append("## 3. Entity-Relationship Diagram (ERD)\n")
    md.append("### 3.1 Entity-Relationship Diagram Model")
    md.append("The relational database schema is structured into six core transactional entities and seven reference lookup entities connected via strict foreign key constraints.\n")
    md.append("![Figure 3.1: Complete Entity-Relationship Diagram (ERD)](images/erd.png)\n")
    md.append("*Figure 3.1: Complete Crow's Foot Entity-Relationship Diagram (ERD) — Conceptual & Physical Relational Model.*\n")
    md.append("### 3.2 Conceptual Entity Architecture")
    md.append("```")
    md.append("[FARMER] (1) ──< (M) [FARM] (1) ──< (M) [PLANTING] (1) ──< (M) [HARVEST]")
    md.append("                       │                       │                     │")
    md.append("               [soil_types] (M:1)         [crop] (M:1)       [quality_grades] (M:1)")
    md.append("            [irrigation_types] (M:1)           │             [harvest_remarks] (M:1)")
    md.append("                                        [crop_categories] (M:1)")
    md.append("                                           [seasons] (M:1)")
    md.append("                                       [planting_status] (M:1)")
    md.append("")
    md.append("[USERS] (1) ──< (M) [ACTIVITY_LOG]")
    md.append("```\n")
    md.append("### 3.3 Relationship & Cardinality Summary")
    md.append(md_table(rel_headers, rel_rows))
    md.append("\n")

    # Section 4: Database Normalization
    md.append("## 4. Database Normalization from UNF through 1NF, 2NF, and 3NF\n")
    md.append("Normalization eliminates data redundancy, anomalies (insertion, update, and deletion), and establishes relational integrity across all operational tables.\n")
    md.append("### 4.1 Unnormalized Form (UNF)")
    md.append("The unnormalized structure contains repeating groups for crops planted and harvests logged under each farmer land record.\n")
    md.append(md_table(unf_headers, unf_rows))
    md.append("\n")
    md.append("### 4.2 First Normal Form (1NF)")
    md.append("First Normal Form eliminates repeating groups by ensuring all attributes are atomic, establishing primary keys, and introducing surrogate identifiers.\n")
    md.append(md_table(first_nf_headers, first_nf_rows))
    md.append("\n")
    md.append("### 4.3 Second Normal Form (2NF)")
    md.append("Second Normal Form eliminates partial functional dependencies. Attributes depending only on a portion of a composite key are decoupled into distinct tables: `farmer`, `farm`, `crop`, `planting`, and `harvest`.\n")
    md.append("### 4.4 Third Normal Form (3NF)")
    md.append("Third Normal Form eliminates transitive dependencies (non-key attributes depending on other non-key attributes). Controlled vocabulary strings (soil types, irrigation methods, crop categories, seasons, planting statuses, quality grades, harvest remarks) are extracted into independent lookup reference tables.\n")
    md.append("### 4.5 Normalization Synthesis & Verification")
    md.append("The final schema achieves 3NF compliance with zero non-atomic fields, zero partial dependencies, and zero transitive dependencies.\n")

    # Section 5: Data Dictionary
    md.append("## 5. Data Dictionary\n")
    md.append("The data dictionary provides complete technical definitions, data types, nullability, constraints, default values, and operational descriptions for all database tables.\n")
    for dt in data_dictionaries:
        md.append(f"### Table: `{dt['table']}`")
        md.append(f"*{dt.get('description', dt.get('desc', ''))}*\n")
        md.append(md_table(dt["headers"], dt["rows"]))
        md.append("\n")

    # Section 6: Table Relationships, Constraints, and Lookup Data
    md.append("## 6. Table Relationships, Constraints, and Lookup Data\n")
    md.append("### 6.1 Domain & Relational Integrity Constraints")
    md.append("The system enforces database-level CHECK constraints, unique indexes, and foreign key validations to guarantee data validity at the database engine level.\n")
    md.append(md_table(chk_headers, chk_rows))
    md.append("\n")
    md.append("### 6.2 Controlled Reference Lookup Data Catalog")
    md.append("Reference tables govern all system dropdowns to maintain consistent operational terminology.\n")
    for lt in lookup_tables:
        md.append(f"#### Lookup Table: {lt['name']}")
        md.append(f"*{lt.get('description', lt.get('desc', ''))}*\n")
        md.append(md_table(lt["headers"], lt["rows"]))
        md.append("\n")
    md.append("### 6.3 Transaction Integrity & Concurrency Controls")
    md.append("To support high-concurrency enterprise environments and prevent data corruption during simultaneous user actions, the database architecture enforces strict ACID transaction boundaries and lock management protocols.\n")
    for t in transaction_screenshots_data:
        md.append(f"#### Figure 6.{t['id']}: {t['title']}")
        md.append(f"*{t['desc']}* — **Core Concept:** `{t['concept']}`\n")
        md.append(f"![Figure 6.{t['id']}: {t['title']}](images/{t['file']})\n")

    # Section 7: User Guide or Basic Operating Instructions
    md.append("## 7. User Guide or Basic Operating Instructions\n")
    md.append("### 7.1 System Authentication & Access")
    md.append("1. Navigate to the AIS portal URL (`auth/login.php`).")
    md.append("2. Enter registered credentials (Username and Password).")
    md.append("3. Click **Sign In**. The system validates credentials via bcrypt hashing, initializes session parameters, and redirects authorized users to the Executive Dashboard.")
    md.append("4. To terminate an active session, click the **Logout** button in the header profile menu.\n")
    md.append("### 7.2 Navigating the Executive Operational Dashboard")
    md.append("1. **KPI Overview:** The top row displays total registered farmers, active farmer lands, cultivated crops, active planting cycles, total harvest volume, and cumulative revenue.")
    md.append("2. **Date Interval Filter:** Select custom start and end dates to filter production metrics dynamically.")
    md.append("3. **Quick Navigation:** Use the left navigation rail or bottom bar on mobile to switch between operational records, reports, and settings.\n")
    md.append("### 7.3 Managing Agricultural Records")
    md.append("- **Farmers Directory:** Click **Farmers** in the sidebar. Use the search bar to locate producers. Click **+ Add Farmer** to open the registration card. Fill in personal details, address, and contact numbers. Click **Save**.")
    md.append("- **Farm Lands:** Click **Farms**. Click **+ Add Farm**. Select the registered owner from the dropdown, specify land name, geographic location, area in hectares, soil classification, and irrigation method. Click **Save**.")
    md.append("- **Crop Master:** Click **Crops**. Click **+ Add Crop**. Enter crop name, hybrid variety, category, maturity cycle days, and recommended growing season.")
    md.append("- **Planting Transactions:** Click **Plantings**. Click **+ Record Planting**. Select the target farm land and crop variety. Enter planting date, land area planted (ha), and seed quantity (kg). The system automatically calculates forecasted harvest date. Status initializes as 'Planted'.")
    md.append("- **Harvest Logging:** Click **Harvests**. Click **+ Record Harvest**. Select the corresponding active planting cycle. Enter harvest date, gross quantity (kg), unit selling price per kg, quality grade, and remarks. Upon submission, the system automatically transitions the planting cycle status to 'Harvested'.\n")
    md.append("### 7.4 Generating Operational Reports and CSV Export")
    md.append("1. Navigate to **Reports** from the main menu.")
    md.append("2. Select any desired report suite (e.g., Crop Production per Farm, Harvest Yield & Revenue, Farmer Profitability).")
    md.append("3. Apply available dropdown filters (e.g., specific farm, category, date range).")
    md.append("4. Click **Print** to open the optimized print preview for long bond paper.")
    md.append("5. Click **Export CSV** to instantly download the filtered dataset in RFC 4180-compliant CSV format for external spreadsheet analysis.\n")
    md.append("### 7.5 Administrative Controls & Audit Logging")
    md.append("- **User Management:** Administrators navigate to **Users** to register new staff accounts, reset credentials, or soft-deactivate operators.")
    md.append("- **Audit Activity Trail:** Administrators navigate to **Activity Log** to inspect an immutable record of all database inserts, updates, and soft-deletes with timestamps and operator identifiers.")
    md.append("- **Controlled Lookup Settings:** Administrators navigate to **Settings** to add, edit, or toggle reference options (soil types, irrigation methods, crop categories, seasons, statuses, grades, remarks).\n")

    # Section 8: Screenshots or Evidence of All Major System Modules
    md.append("## 8. Screenshots or Evidence of All Major System Modules\n")
    md.append("The GehTanum user interface is engineered with responsive layouts for desktop, tablet, and mobile devices.\n")
    md.append("### 8.1 User Interface Catalog Summary\n")
    md.append(md_table(screen_headers, screen_rows))
    md.append("\n")
    md.append("### 8.2 Working System Screenshots\n")
    for s in screenshots_data:
        md.append(f"#### Figure 8.{s['id']}: {s['title']} ({s['route']})")
        md.append(f"*{s['desc']}*  ")
        md.append(f"**Key Interface Elements:** {s['elements']}\n")
        md.append(f"![Figure 8.{s['id']}: {s['title']}](images/{s['file']})\n")

    # Section 9: Test Cases and Results Showing that the Required Functions Work
    md.append("## 9. Test Cases and Results Showing that the Required Functions Work\n")
    md.append("A comprehensive Quality Assurance test suite was executed across all functional requirements (FR1 through FR14), verifying that all system features operate according to specifications with zero defects.\n")
    md.append("### 9.1 Quality Assurance Test Case Matrix\n")
    md.append(md_table(test_headers, test_rows))
    md.append("\n")
    md.append("### 9.2 Concurrency & Transaction Testing Summary")
    md.append("Database transactions were rigorously verified under simulated multi-user operational conditions. Atomicity was verified via rollback testing upon invalid foreign key submissions, row-level locking was confirmed using `SELECT FOR UPDATE` during concurrent planting allocations, and table locks were verified during bulk reference data updates. All tests concluded with a 100% PASS rate.\n")

    # Section 10: Appendix
    md.append("## 10. Appendix: Deployment Architecture & Technical Specifications\n")
    md.append("### 10.1 Cloud Database Hosting (Supabase PostgreSQL)")
    md.append("The production database is hosted on a high-availability Supabase PostgreSQL managed instance, featuring automated point-in-time backups, TLS 1.3 encrypted connections, and connection pooling via PgBouncer.\n")
    md.append("### 10.2 Application Security & Architecture Standards")
    md.append("- **Password Hashing:** Passwords are encrypted using bcrypt (`PASSWORD_BCRYPT`) with an algorithmic cost factor of 10.")
    md.append("- **SQL Injection Prevention:** 100% of database interactions utilize PDO prepared statements with strict parameter binding.")
    md.append("- **Cross-Site Scripting (XSS) Mitigation:** All user-supplied output rendered in HTML templates is sanitized via `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')`.")
    md.append("- **Cross-Site Request Forgery (CSRF):** State-modifying requests utilize session-scoped CSRF verification tokens.")
    md.append("- **Audit Immutability:** Audit log records cannot be updated or deleted through the web application interface.\n")

    return "\n".join(md)


# ==========================================
# 3. HTML GENERATOR (FOR STYLED DOCX)
# ==========================================
def generate_html():
    h = []
    h.append("""<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GehTanum: Agriculture Information System (AIS) — System Documentation</title>
<style>
@page {
    size: 8.5in 13in;
    margin: 0.8in;
}
body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 10pt;
    line-height: 1.35;
    color: #1f2937;
    background-color: #ffffff;
    margin: 0;
    padding: 0;
}
.cover-page {
    text-align: center;
    padding-top: 40px;
    padding-bottom: 30px;
    margin-bottom: 25px;
}
.inst-header {
    font-size: 14pt;
    font-weight: bold;
    color: #1b5e20;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 2px;
}
.inst-sub {
    font-size: 9.5pt;
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 15px;
}
.doc-title {
    font-size: 19pt;
    font-weight: bold;
    color: #111827;
    margin-top: 15px;
    margin-bottom: 6px;
    line-height: 1.2;
}
.doc-subtitle {
    font-size: 10.5pt;
    color: #2e7d32;
    font-weight: bold;
    margin-bottom: 20px;
}
.meta-box {
    margin: 15px auto;
    width: 95%;
    background-color: #f8fafc;
    border: 1px solid #cbd5e1;
    border-left: 5px solid #1b5e20;
    padding: 10px 16px;
    text-align: left;
    font-size: 9pt;
}
.meta-row {
    margin-bottom: 3px;
}
.meta-label {
    font-weight: bold;
    color: #1b5e20;
    display: inline-block;
    width: 170px;
}
h1 {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 13pt;
    font-weight: bold;
    color: #1b5e20;
    border-bottom: 1.5px solid #1b5e20;
    padding-bottom: 3px;
    margin-top: 20px;
    margin-bottom: 8px;
    page-break-after: avoid;
}
h2 {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11pt;
    color: #2e7d32;
    margin-top: 14px;
    margin-bottom: 6px;
    page-break-after: avoid;
}
h3 {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 10pt;
    color: #374151;
    margin-top: 10px;
    margin-bottom: 4px;
    page-break-after: avoid;
}
p {
    margin-top: 0;
    margin-bottom: 6px;
    text-align: justify;
}
ul, ol {
    margin-top: 0;
    margin-bottom: 8px;
    padding-left: 20px;
}
li {
    margin-bottom: 2px;
}
table.doc-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 6px;
    margin-bottom: 12px;
    font-size: 8.5pt;
}
table.doc-table th {
    background-color: #1b5e20;
    color: #ffffff;
    font-weight: bold;
    padding: 6px 8px;
    border: 1px solid #166534;
    text-align: left;
}
table.doc-table td {
    padding: 5px 8px;
    border: 1px solid #cbd5e1;
    vertical-align: top;
}
table.doc-table tr:nth-child(even) td {
    background-color: #f8fafc;
}
pre {
    background-color: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-left: 3px solid #2e7d32;
    padding: 8px 12px;
    font-size: 8pt;
    font-family: Arial, monospace;
    overflow-x: auto;
    margin-top: 6px;
    margin-bottom: 10px;
}
.page-break {
    page-break-before: always;
}
.screenshot-figure {
    margin-top: 18px;
    margin-bottom: 22px;
    page-break-inside: avoid;
}
.screenshot-figure img {
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.06);
    max-width: 100%;
}
</style>
</head>
<body>

<div class="cover-page">
    <div class="inst-header">Cebu Technological University</div>
    <div class="inst-sub">Main Campus &middot; College of Computer, Information and Communications Technology (CCICT)</div>
    <div style="font-size: 11pt; font-weight: bold; color: #166534; text-transform: uppercase; margin-bottom: 15px;">AP3 &mdash; Web Application Development</div>
    <div style="width: 120px; height: 3px; background-color: #1b5e20; margin: 0 auto 15px auto;"></div>
    
    <div class="doc-title">GEHTANUM: AGRICULTURE INFORMATION SYSTEM</div>
    <div class="doc-subtitle">Comprehensive Technical System Documentation &amp; Course Portfolio</div>
    <div style="font-size: 9.5pt; color: #4b5563; max-width: 80%; margin: 0 auto 15px auto;">
        A centralized, relational web-based platform for farmer directory administration, agricultural farmland tracking, crop master catalogs, seasonal planting cycles, and dynamic harvest production reporting.
    </div>

    <div class="meta-box">
        <div class="meta-row"><span class="meta-label">Author / Developer:</span> <strong>Medy B. Villarias</strong></div>
        <div class="meta-row"><span class="meta-label">Degree Program:</span> Bachelor of Science in Information Systems (BSIS)</div>
        <div class="meta-row"><span class="meta-label">Document Specification:</span> Course Portfolio &amp; Midterm Submission</div>
        <div class="meta-row"><span class="meta-label">Paper Specification:</span> <strong>Long-Size Bond Paper (8.5&quot; &times; 13&quot; / Philippine Folio)</strong></div>
        <div class="meta-row"><span class="meta-label">Database Management:</span> PostgreSQL 15+ (Hosted on Supabase Cloud)</div>
        <div class="meta-row"><span class="meta-label">Web Technologies:</span> PHP 8.2+, Tailwind CSS / Bootstrap 5, Vanilla JS, Apache</div>
        <div class="meta-row"><span class="meta-label">Relational Model:</span> Fully Normalized Third Normal Form (3NF) &mdash; 14 Tables</div>
        <div class="meta-row"><span class="meta-label">Academic Term:</span> First Semester, Academic Year 2024&ndash;2025</div>
    </div>
</div>

<div class="page-break"></div>
""")

    # Table of Contents
    h.append("""
<h1>Table of Contents</h1>
<ol style="line-height: 1.6; font-size: 9.5pt;">
    <li><strong><a href="#sec1" style="color: #1b5e20; text-decoration: none;">Project Title, Description, and Objectives</a></strong>
        <ul>
            <li>1.1 Project Title &amp; Overview</li>
            <li>1.2 System Description</li>
            <li>1.3 Project Objectives (General &amp; Specific)</li>
        </ul>
    </li>
    <li><strong><a href="#sec2" style="color: #1b5e20; text-decoration: none;">Functional Requirements</a></strong></li>
    <li><strong><a href="#sec3" style="color: #1b5e20; text-decoration: none;">Entity-Relationship Diagram (ERD)</a></strong>
        <ul>
            <li>3.1 Entity-Relationship Diagram Model (Visual ERD)</li>
            <li>3.2 Conceptual Entity Architecture</li>
            <li>3.3 Relational Model &amp; Cardinality Summary</li>
        </ul>
    </li>
    <li><strong><a href="#sec4" style="color: #1b5e20; text-decoration: none;">Database Normalization from UNF through 1NF, 2NF, and 3NF</a></strong>
        <ul>
            <li>4.1 Unnormalized Form (UNF)</li>
            <li>4.2 First Normal Form (1NF)</li>
            <li>4.3 Second Normal Form (2NF)</li>
            <li>4.4 Third Normal Form (3NF)</li>
            <li>4.5 Normalization Synthesis &amp; Verification</li>
        </ul>
    </li>
    <li><strong><a href="#sec5" style="color: #1b5e20; text-decoration: none;">Data Dictionary</a></strong></li>
    <li><strong><a href="#sec6" style="color: #1b5e20; text-decoration: none;">Table Relationships, Constraints, and Lookup Data</a></strong>
        <ul>
            <li>6.1 Domain &amp; Relational Integrity Constraints</li>
            <li>6.2 Controlled Reference Lookup Data Catalog</li>
            <li>6.3 Transaction Integrity &amp; Concurrency Controls</li>
        </ul>
    </li>
    <li><strong><a href="#sec7" style="color: #1b5e20; text-decoration: none;">User Guide or Basic Operating Instructions</a></strong>
        <ul>
            <li>7.1 System Authentication &amp; Access</li>
            <li>7.2 Navigating the Executive Operational Dashboard</li>
            <li>7.3 Managing Agricultural Records</li>
            <li>7.4 Generating Operational Reports and CSV Export</li>
            <li>7.5 Administrative Controls &amp; Audit Logging</li>
        </ul>
    </li>
    <li><strong><a href="#sec8" style="color: #1b5e20; text-decoration: none;">Screenshots or Evidence of All Major System Modules</a></strong>
        <ul>
            <li>8.1 User Interface Catalog Summary</li>
            <li>8.2 Working System Screenshots (Figures 8.1 &ndash; 8.19)</li>
        </ul>
    </li>
    <li><strong><a href="#sec9" style="color: #1b5e20; text-decoration: none;">Test Cases and Results Showing that the Required Functions Work</a></strong>
        <ul>
            <li>9.1 Quality Assurance Test Case Matrix (TC1 &ndash; TC14)</li>
            <li>9.2 Concurrency &amp; Transaction Verification Summary</li>
        </ul>
    </li>
    <li><strong><a href="#sec10" style="color: #1b5e20; text-decoration: none;">Appendix: Deployment Architecture &amp; Technical Specifications</a></strong></li>
</ol>
<div class="page-break"></div>
""")

    # Section 1
    h.append("""
<a id="sec1"></a>
<h1>1. Project Title, Description, and Objectives</h1>
<h2>1.1 Project Title &amp; Overview</h2>
<p><strong>GehTanum (Agriculture Information System)</strong> &mdash; Centralized Web-Based Agricultural Operations, Farmland Mapping, Crop Lifecycle Tracking, and Yield Analytics Platform.</p>

<h2>1.2 System Description</h2>
<p><strong>GehTanum</strong> (Agriculture Information System) is an enterprise-grade agricultural management web platform engineered to modernize rural and cooperative farm records administration. Traditional farming operations frequently suffer from fragmented paper records, uncoordinated planting schedules, manual calculations of farm yield, and inaccurate profitability estimates. GehTanum resolves these operational inefficiencies by establishing a centralized, relational database architecture that unifies farmer profiles, farm land boundaries, crop master catalogs, seasonal planting cycles, and harvest transactions.</p>
<p>The system enforces strict domain integrity through controlled foreign key lookup tables, eliminates data redundancy via Third Normal Form (3NF) normalization, and delivers live operational intelligence through real-time KPI dashboards, automated cycle status workflows, and customizable reports with CSV data export capabilities. Built on PHP 8.2 and cloud-hosted Supabase PostgreSQL, GehTanum provides role-based access control, responsive mobile-first navigation, and an immutable transaction audit log to ensure administrative accountability across all farming communities.</p>

<h2>1.3 Project Objectives</h2>
<h3>1.3.1 General Objective</h3>
<p>To architect, develop, and deploy a secure, fully normalized, web-based Agriculture Information System (<strong>GehTanum</strong>) that centralizes agricultural operations, streamlines field transaction logging from planting through harvest, and delivers real-time operational analytics for farm administrators and extension workers.</p>

<h3>1.3.2 Specific Objectives</h3>
<ol>
    <li><strong>Farmer Directory &amp; Land Tenure Management:</strong> Establish a relational repository of agricultural producers and their corresponding farmer lands, recording geographic locations, land acreage in hectares, soil classifications, and irrigation systems.</li>
    <li><strong>Agronomic Catalog Standardization:</strong> Maintain a master catalog of crops, hybrid varieties, botanical categories, maturity durations (in days), and recommended growing seasons.</li>
    <li><strong>Planting Lifecycle Monitoring:</strong> Track field planting activities with automated calculation of expected harvest dates, land allocation constraints, and seed stock consumption.</li>
    <li><strong>Harvest Production &amp; Valuation:</strong> Record harvest outputs, gross yields (kg), commercial selling prices, quality grading, and condition assessments, with automatic status updates upon yield completion.</li>
    <li><strong>Operational Intelligence &amp; Reporting:</strong> Deliver dynamic reporting suites (Farmer Land Ownership, Crop Production per Farm, Planting Cycle Status, Harvest Yield &amp; Revenue, Farmer Profitability, Seasonal Performance, and Variety Yield Efficiency) equipped with instant CSV data exports.</li>
    <li><strong>Enterprise Security &amp; Auditability:</strong> Enforce bcrypt credential encryption, session state management, role-based access control (Admin vs. Staff), and an append-only transaction audit log recording all insert, update, and soft-delete operations.</li>
</ol>
""")

    # Section 2
    h.append("""
<a id="sec2"></a>
<h1>2. Functional Requirements</h1>
<p>The system architecture satisfies fourteen comprehensive functional requirements spanning core agronomic transactions, administrative controls, data integrity, and analytical reporting.</p>
""")
    h.append(html_table(fr_headers, fr_rows, widths=["6%", "20%", "38%", "22%", "14%"]))

    # Section 3
    h.append("""
<div class="page-break"></div>
<a id="sec3"></a>
<h1>3. Entity-Relationship Diagram (ERD)</h1>
<h2>3.1 Entity-Relationship Diagram Model</h2>
<p>The relational database schema is structured into six core transactional entities and seven reference lookup entities connected via strict foreign key constraints.</p>
<div style="text-align: center; margin: 15px 0;">
    <img src="images/erd.png" width="700" style="border: 1px solid #cbd5e1; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.06); max-width: 100%;">
    <p style="font-size: 8.5pt; color: #4b5563; margin-top: 6px;"><em>Figure 3.1: Complete Crow's Foot Entity-Relationship Diagram (ERD) &mdash; Conceptual &amp; Physical Relational Model.</em></p>
</div>

<h2>3.2 Conceptual Entity Architecture</h2>
<pre>
[FARMER] (1) ──&lt; (M) [FARM] (1) ──&lt; (M) [PLANTING] (1) ──&lt; (M) [HARVEST]
                       │                       │                     │
               [soil_types] (M:1)         [crop] (M:1)       [quality_grades] (M:1)
            [irrigation_types] (M:1)           │             [harvest_remarks] (M:1)
                                        [crop_categories] (M:1)
                                           [seasons] (M:1)
                                       [planting_status] (M:1)

[USERS] (1) ──&lt; (M) [ACTIVITY_LOG]
</pre>

<h2>3.3 Relational Model &amp; Cardinality Summary</h2>
""")
    h.append(html_table(rel_headers, rel_rows, widths=["14%", "14%", "16%", "20%", "36%"]))

    # Section 4
    h.append("""
<div class="page-break"></div>
<a id="sec4"></a>
<h1>4. Database Normalization from UNF through 1NF, 2NF, and 3NF</h1>
<p>Normalization eliminates data redundancy, anomalies (insertion, update, and deletion), and establishes relational integrity across all operational tables.</p>

<h2>4.1 Unnormalized Form (UNF)</h2>
<p>The unnormalized structure contains repeating groups for crops planted and harvests logged under each farmer land record.</p>
""")
    h.append(html_table(unf_headers, unf_rows, widths=["14%", "11%", "18%", "18%", "20%", "19%"]))

    h.append("""
<h2>4.2 First Normal Form (1NF)</h2>
<p>First Normal Form eliminates repeating groups by ensuring all attributes are atomic, establishing primary keys, and introducing surrogate identifiers.</p>
""")
    h.append(html_table(first_nf_headers, first_nf_rows, widths=["12%", "13%", "12%", "13%", "10%", "10%", "15%", "15%"]))

    h.append("""
<h2>4.3 Second Normal Form (2NF)</h2>
<p>Second Normal Form eliminates partial functional dependencies. Attributes depending only on a portion of a composite key are decoupled into distinct tables: <code>farmer</code>, <code>farm</code>, <code>crop</code>, <code>planting</code>, and <code>harvest</code>.</p>

<h2>4.4 Third Normal Form (3NF)</h2>
<p>Third Normal Form eliminates transitive dependencies (non-key attributes depending on other non-key attributes). Controlled vocabulary strings (soil types, irrigation methods, crop categories, seasons, planting statuses, quality grades, harvest remarks) are extracted into independent lookup reference tables.</p>

<h2>4.5 Normalization Synthesis &amp; Verification</h2>
<p>The final schema achieves 3NF compliance with zero non-atomic fields, zero partial dependencies, and zero transitive dependencies.</p>
""")

    # Section 5
    h.append("""
<div class="page-break"></div>
<a id="sec5"></a>
<h1>5. Data Dictionary</h1>
<p>The data dictionary provides complete technical definitions, data types, nullability, constraints, default values, and operational descriptions for all database tables.</p>
""")
    for dt in data_dictionaries:
        h.append(f"<h2>Table: <code>{dt['table']}</code></h2>")
        h.append(f"<p style='font-size: 8.5pt; color: #4b5563; margin-bottom: 4px;'><em>{dt.get('description', dt.get('desc', ''))}</em></p>")
        h.append(html_table(dt["headers"], dt["rows"], widths=["18%", "16%", "24%", "42%"]))

    # Section 6
    h.append("""
<div class="page-break"></div>
<a id="sec6"></a>
<h1>6. Table Relationships, Constraints, and Lookup Data</h1>
<h2>6.1 Domain &amp; Relational Integrity Constraints</h2>
<p>The system enforces database-level CHECK constraints, unique indexes, and foreign key validations to guarantee data validity at the database engine level.</p>
""")
    h.append(html_table(chk_headers, chk_rows, widths=["14%", "24%", "34%", "28%"]))

    h.append("""
<h2>6.2 Controlled Reference Lookup Data Catalog</h2>
<p>Reference tables govern all system dropdowns to maintain consistent operational terminology.</p>
""")
    for lt in lookup_tables:
        h.append(f"<h3>Lookup Table: {lt['name']}</h3>")
        h.append(f"<p style='font-size: 8.5pt; color: #4b5563; margin-bottom: 4px;'><em>{lt.get('description', lt.get('desc', ''))}</em></p>")
        h.append(html_table(lt["headers"], lt["rows"]))

    h.append("""
<h2>6.3 Transaction Integrity &amp; Concurrency Controls</h2>
<p>To support high-concurrency enterprise environments and prevent data corruption during simultaneous user actions, the database architecture enforces strict ACID transaction boundaries and lock management protocols.</p>
""")
    for t in transaction_screenshots_data:
        h.append(f'''<div class="screenshot-figure">
        <h3 style="color: #1b5e20; margin-bottom: 3px;">Figure 6.{t['id']}: {t['title']}</h3>
        <p style="font-size: 8.5pt; color: #4b5563; margin-bottom: 6px;"><em>{t['desc']}</em> &mdash; <strong>Core Concept:</strong> <code>{t['concept']}</code></p>
        <div style="text-align: center; margin: 6px 0;">
            <img src="images/{t['file']}" width="650">
        </div>
    </div>''')

    # Section 7
    h.append("""
<div class="page-break"></div>
<a id="sec7"></a>
<h1>7. User Guide or Basic Operating Instructions</h1>
<h2>7.1 System Authentication &amp; Access</h2>
<ol>
    <li>Navigate to the GehTanum portal URL (<code>auth/login.php</code>).</li>
    <li>Enter registered credentials (Username and Password).</li>
    <li>Click <strong>Sign In</strong>. The system validates credentials via bcrypt hashing, initializes session parameters, and redirects authorized users to the Executive Dashboard.</li>
    <li>To terminate an active session, click the <strong>Logout</strong> button in the header profile menu.</li>
</ol>

<h2>7.2 Navigating the Executive Operational Dashboard</h2>
<ol>
    <li><strong>KPI Overview:</strong> The top row displays total registered farmers, active farmer lands, cultivated crops, active planting cycles, total harvest volume, and cumulative revenue.</li>
    <li><strong>Date Interval Filter:</strong> Select custom start and end dates to filter production metrics dynamically.</li>
    <li><strong>Quick Navigation:</strong> Use the left navigation rail or bottom bar on mobile to switch between operational records, reports, and settings.</li>
</ol>

<h2>7.3 Managing Agricultural Records</h2>
<ul>
    <li><strong>Farmers Directory:</strong> Click <strong>Farmers</strong> in the sidebar. Use the search bar to locate producers. Click <strong>+ Add Farmer</strong> to open the registration card. Fill in personal details, address, and contact numbers. Click <strong>Save</strong>.</li>
    <li><strong>Farm Lands:</strong> Click <strong>Farms</strong>. Click <strong>+ Add Farm</strong>. Select the registered owner from the dropdown, specify land name, geographic location, area in hectares, soil classification, and irrigation method. Click <strong>Save</strong>.</li>
    <li><strong>Crop Master:</strong> Click <strong>Crops</strong>. Click <strong>+ Add Crop</strong>. Enter crop name, hybrid variety, category, maturity cycle days, and recommended growing season.</li>
    <li><strong>Planting Transactions:</strong> Click <strong>Plantings</strong>. Click <strong>+ Record Planting</strong>. Select the target farm land and crop variety. Enter planting date, land area planted (ha), and seed quantity (kg). The system automatically calculates forecasted harvest date. Status initializes as 'Planted'.</li>
    <li><strong>Harvest Logging:</strong> Click <strong>Harvests</strong>. Click <strong>+ Record Harvest</strong>. Select the corresponding active planting cycle. Enter harvest date, gross quantity (kg), unit selling price per kg, quality grade, and remarks. Upon submission, the system automatically transitions the planting cycle status to 'Harvested'.</li>
</ul>

<h2>7.4 Generating Operational Reports and CSV Export</h2>
<ol>
    <li>Navigate to <strong>Reports</strong> from the main menu.</li>
    <li>Select any desired report suite (e.g., Crop Production per Farm, Harvest Yield &amp; Revenue, Farmer Profitability).</li>
    <li>Apply available dropdown filters (e.g., specific farm, category, date range).</li>
    <li>Click <strong>Print</strong> to open the optimized print preview for long bond paper.</li>
    <li>Click <strong>Export CSV</strong> to instantly download the filtered dataset in RFC 4180-compliant CSV format for external spreadsheet analysis.</li>
</ol>

<h2>7.5 Administrative Controls &amp; Audit Logging</h2>
<ul>
    <li><strong>User Management:</strong> Administrators navigate to <strong>Users</strong> to register new staff accounts, reset credentials, or soft-deactivate operators.</li>
    <li><strong>Audit Activity Trail:</strong> Administrators navigate to <strong>Activity Log</strong> to inspect an immutable record of all database inserts, updates, and soft-deletes with timestamps and operator identifiers.</li>
    <li><strong>Controlled Lookup Settings:</strong> Administrators navigate to <strong>Settings</strong> to add, edit, or toggle reference options (soil types, irrigation methods, crop categories, seasons, statuses, grades, remarks).</li>
</ul>
""")

    # Section 8
    h.append("""
<div class="page-break"></div>
<a id="sec8"></a>
<h1>8. Screenshots or Evidence of All Major System Modules</h1>
<p>The GehTanum user interface is engineered with responsive layouts for desktop, tablet, and mobile devices.</p>

<h2>8.1 User Interface Catalog Summary</h2>
""")
    h.append(html_table(screen_headers, screen_rows, widths=["6%", "22%", "26%", "46%"]))

    h.append("""
<h2>8.2 Working System Screenshots</h2>
""")
    for s in screenshots_data:
        h.append(f'''<div class="screenshot-figure">
        <h3 style="color: #1b5e20; margin-bottom: 3px;">Figure 8.{s['id']}: {s['title']} (<code>{s['route']}</code>)</h3>
        <p style="font-size: 8.5pt; color: #4b5563; margin-bottom: 6px;"><em>{s['desc']}</em><br><strong>Key Elements:</strong> {s['elements']}</p>
        <div style="text-align: center; margin: 6px 0;">
            <img src="images/{s['file']}" width="650">
        </div>
    </div>''')

    # Section 9
    h.append("""
<div class="page-break"></div>
<a id="sec9"></a>
<h1>9. Test Cases and Results Showing that the Required Functions Work</h1>
<p>A comprehensive Quality Assurance test suite was executed across all functional requirements (FR1 through FR14), verifying that all system features operate according to specifications with zero defects.</p>

<h2>9.1 Quality Assurance Test Case Matrix</h2>
""")
    h.append(html_table(test_headers, test_rows, widths=["5%", "9%", "18%", "24%", "22%", "16%", "6%"]))

    h.append("""
<h2>9.2 Concurrency &amp; Transaction Testing Summary</h2>
<p>Database transactions were rigorously verified under simulated multi-user operational conditions. Atomicity was verified via rollback testing upon invalid foreign key submissions, row-level locking was confirmed using <code>SELECT FOR UPDATE</code> during concurrent planting allocations, and table locks were verified during bulk reference data updates. All tests concluded with a 100% PASS rate.</p>
""")

    # Section 10
    h.append("""
<div class="page-break"></div>
<a id="sec10"></a>
<h1>10. Appendix: Deployment Architecture &amp; Technical Specifications</h1>
<h2>10.1 Cloud Database Hosting (Supabase PostgreSQL)</h2>
<p>The production database is hosted on a high-availability Supabase PostgreSQL managed instance, featuring automated point-in-time backups, TLS 1.3 encrypted connections, and connection pooling via PgBouncer.</p>

<h2>10.2 Application Security &amp; Architecture Standards</h2>
<ul>
    <li><strong>Password Hashing:</strong> Passwords are encrypted using bcrypt (<code>PASSWORD_BCRYPT</code>) with an algorithmic cost factor of 10.</li>
    <li><strong>SQL Injection Prevention:</strong> 100% of database interactions utilize PDO prepared statements with strict parameter binding.</li>
    <li><strong>Cross-Site Scripting (XSS) Mitigation:</strong> All user-supplied output rendered in HTML templates is sanitized via <code>htmlspecialchars($str, ENT_QUOTES, 'UTF-8')</code>.</li>
    <li><strong>Cross-Site Request Forgery (CSRF):</strong> State-modifying requests utilize session-scoped CSRF verification tokens.</li>
    <li><strong>Audit Immutability:</strong> Audit log records cannot be updated or deleted through the web application interface.</li>
</ul>

</body>
</html>
""")

    return "".join(h)

# ==========================================
# 4. EXECUTION & POST-PROCESSING
# ==========================================
def main():
    print("Beginning Comprehensive System Documentation Compilation...")
    os.makedirs("docs", exist_ok=True)
    
    # 1. Generate Markdown
    md_content = generate_markdown()
    md_path = "docs/SYSTEM_DOCUMENTATION.md"
    with open(md_path, "w", encoding="utf-8") as f:
        f.write(md_content)
    print(f"Generated Markdown documentation: {md_path} ({len(md_content):,} chars)")
    
    # 2. Generate HTML
    html_content = generate_html()
    html_path = "docs/SYSTEM_DOCUMENTATION.html"
    with open(html_path, "w", encoding="utf-8") as f:
        f.write(html_content)
    print(f"Generated Styled HTML intermediary: {html_path} ({len(html_content):,} chars)")
    
    # 3. Convert HTML to ODT then DOCX via LibreOffice
    print("Converting HTML to initial DOCX via LibreOffice headless...")
    subprocess.run(["libreoffice", "--headless", "--convert-to", "odt", html_path, "--outdir", "docs"], check=True)
    subprocess.run(["libreoffice", "--headless", "--convert-to", "docx:Office Open XML Text", "docs/SYSTEM_DOCUMENTATION.odt", "--outdir", "docs"], check=True)
    
    docx_path = "docs/SYSTEM_DOCUMENTATION.docx"
    print(f"Base DOCX generated at {docx_path}. Commencing OpenXML post-processing...")
    
    # 4. OpenXML Post-Processing: Set Long-size bond paper, Arial typography, Table formatting & Image Embedding
    with zipfile.ZipFile(docx_path, "r") as zin:
        file_map = {name: zin.read(name) for name in zin.namelist()}
        
    doc_root = ET.fromstring(file_map["word/document.xml"])
    
    # 4.1 Enforce Long-Size Bond Paper (8.5" x 13" = 12240 x 18720 dxa)
    for pgSz in doc_root.findall(f".//{W}pgSz"):
        pgSz.set(f"{W}w", "12240")
        pgSz.set(f"{W}h", "18720")
        print("Enforced Long-size bond paper dimensions: 12240 x 18720 dxa (8.5 x 13 inches).")

    # 4.2 Enforce Arial font across all runs
    for r in doc_root.findall(f".//{W}r"):
        rPr = r.find(f"{W}rPr")
        if rPr is None:
            rPr = ET.Element(f"{W}rPr")
            r.insert(0, rPr)
        rf = rPr.find(f"{W}rFonts")
        if rf is None:
            rf = ET.SubElement(rPr, f"{W}rFonts")
        rf.set(f"{W}ascii", "Arial")
        rf.set(f"{W}hAnsi", "Arial")
        rf.set(f"{W}cs", "Arial")
        
    # 4.3 Style tables natively: cantSplit, tblHeader, #1B5E20 headers, alternating zebra #F8FAFC
    tables = doc_root.findall(f".//{W}tbl")
    print(f"Found {len(tables)} tables to format with native OpenXML styling...")
    for tbl in tables:
        rows = tbl.findall(f"{W}tr")
        for r_idx, row in enumerate(rows):
            trPr = row.find(f"{W}trPr")
            if trPr is None:
                trPr = ET.Element(f"{W}trPr")
                row.insert(0, trPr)
            if trPr.find(f"{W}cantSplit") is None:
                ET.SubElement(trPr, f"{W}cantSplit")
                
            is_header = (r_idx == 0)
            if is_header and trPr.find(f"{W}tblHeader") is None:
                ET.SubElement(trPr, f"{W}tblHeader")
                
            for cell in row.findall(f"{W}tc"):
                tcPr = cell.find(f"{W}tcPr")
                if tcPr is None:
                    tcPr = ET.Element(f"{W}tcPr")
                    cell.insert(0, tcPr)
                shd = tcPr.find(f"{W}shd")
                if shd is not None:
                    tcPr.remove(shd)
                shd = ET.SubElement(tcPr, f"{W}shd")
                shd.set(f"{W}val", "clear")
                shd.set(f"{W}color", "auto")
                if is_header:
                    shd.set(f"{W}fill", "1B5E20")
                elif r_idx % 2 == 1:
                    shd.set(f"{W}fill", "F8FAFC")
                else:
                    shd.set(f"{W}fill", "FFFFFF")
                    
                for p in cell.findall(f"{W}p"):
                    for r in p.findall(f"{W}r"):
                        rPr = r.find(f"{W}rPr")
                        if rPr is None:
                            rPr = ET.Element(f"{W}rPr")
                            r.insert(0, rPr)
                        rf = rPr.find(f"{W}rFonts")
                        if rf is None:
                            rf = ET.SubElement(rPr, f"{W}rFonts")
                        rf.set(f"{W}ascii", "Arial")
                        rf.set(f"{W}hAnsi", "Arial")
                        rf.set(f"{W}cs", "Arial")
                        
                        if is_header:
                            color = rPr.find(f"{W}color")
                            if color is None:
                                color = ET.SubElement(rPr, f"{W}color")
                            color.set(f"{W}val", "FFFFFF")
                            if rPr.find(f"{W}b") is None:
                                ET.SubElement(rPr, f"{W}b")

    # 4.4 Embed all images directly into docx package
    ET.register_namespace('', CT_NS)
    ct_xml = ET.fromstring(file_map["[Content_Types].xml"])
    has_png = any(el.attrib.get("Extension") == "png" for el in ct_xml.findall(f".//{{{CT_NS}}}Default"))
    if not has_png:
        el = ET.SubElement(ct_xml, f"{{{CT_NS}}}Default")
        el.set("Extension", "png")
        el.set("ContentType", "image/png")
    file_map["[Content_Types].xml"] = ET.tostring(ct_xml, encoding="utf-8")

    ET.register_namespace('', PKG_REL)
    rels_xml = ET.fromstring(file_map["word/_rels/document.xml.rels"])

    img_counter = 1
    for rel in rels_xml.findall(f".//{{{PKG_REL}}}Relationship"):
        if "image" in rel.attrib.get("Type", ""):
            target = rel.attrib.get("Target", "")
            if target.startswith("file://"):
                target_path = target[7:]
            else:
                target_path = os.path.join("docs", target)

            if os.path.exists(target_path):
                with open(target_path, "rb") as img_f:
                    img_data = img_f.read()
                media_name = f"image{img_counter}.png"
                file_map[f"word/media/{media_name}"] = img_data

                rid = rel.attrib["Id"]
                if "TargetMode" in rel.attrib:
                    del rel.attrib["TargetMode"]
                rel.attrib["Target"] = f"media/{media_name}"

                for blip in doc_root.findall(f".//{{{A_NS}}}blip"):
                    link_attr = f"{{{OFF_REL}}}link"
                    embed_attr = f"{{{OFF_REL}}}embed"
                    if blip.attrib.get(link_attr) == rid:
                        del blip.attrib[link_attr]
                        blip.set(embed_attr, rid)
                img_counter += 1

    file_map["word/_rels/document.xml.rels"] = ET.tostring(rels_xml, encoding="utf-8", xml_declaration=True)
    file_map["word/document.xml"] = ET.tostring(doc_root, encoding="utf-8")

    with zipfile.ZipFile(docx_path, "w", zipfile.ZIP_DEFLATED) as zout:
        for name, data in file_map.items():
            zout.writestr(name, data)

    print(f"Successfully embedded {img_counter-1} image files directly into {docx_path}!")
    
    # 5. Regenerate PDF with embedded images & long-size layout
    print("Generating comprehensive PDF with embedded images on long bond paper...")
    subprocess.run(["libreoffice", "--headless", "--convert-to", "pdf", docx_path, "--outdir", "docs"], check=True)
    
    # Clean up intermediate odt/html
    if os.path.exists("docs/SYSTEM_DOCUMENTATION.odt"):
        os.remove("docs/SYSTEM_DOCUMENTATION.odt")
    if os.path.exists("docs/SYSTEM_DOCUMENTATION.html"):
        os.remove("docs/SYSTEM_DOCUMENTATION.html")
        
    print("Complete documentation suite successfully compiled!")

if __name__ == "__main__":
    main()

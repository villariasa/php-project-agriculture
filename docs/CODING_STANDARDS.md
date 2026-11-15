# 📘 AI Development Guidelines & Teacher's Coding Standards
### Agriculture Information System (AIS) — AP 3 Web Application Development

> **CRITICAL DIRECTIVE FOR AI (Sonnet 3.5 / 5.5 / LLMs):**  
> You are acting as a pair-programmer for **Medy Villarias**, a university student at Cebu Technological University taking **AP 3 (Web Application Development)**.  
> All code you suggest or write MUST strictly follow the exact coding style, conventions, and architectural patterns taught by the professor.  
> **DO NOT over-engineer. DO NOT use heavy frameworks (No Laravel, No Symfony, No Composer, No Eloquent ORM).**  
> The code must look clean, concise, student-written, and 100% authentic to the teacher's teachings.

---

## 🏛️ 1. Teacher's Architectural Pattern

The project follows a **Modular Native PHP + OOP Model + Tailwind CDN** pattern.

### Directory Structure to Follow:
```text
php-project-agriculture/
├── config/
│   └── Database.php             # PDO Connection class for Supabase
├── models/
│   ├── Farmer.php               # OOP Model for farmer table
│   ├── Farm.php                 # OOP Model for farm table
│   ├── Crop.php                 # OOP Model for crop table
│   ├── Planting.php             # OOP Model for planting table
│   ├── Harvest.php              # OOP Model for harvest table
│   ├── Lookup.php               # Model for lookup tables (harvest_remarks)
│   └── User.php                 # Model for authentication & users
├── includes/
│   ├── header.php               # Universal HTML head + Tailwind CDN + Navbar
│   ├── footer.php               # Closing tags + universal scripts
│   └── auth_check.php           # Session protection (redirects if not logged in)
├── farmers/                     # Farmers Module (index, create, edit, delete)
├── farms/                       # Farms Module
├── crops/                       # Crops Module
├── plantings/                   # Planting Operations Module
├── harvests/                    # Harvest Yields Module
├── reports/                     # Filterable Reports
├── auth/
│   ├── login.php                # Clean Tailwind Login form
│   └── logout.php               # Session destroy
└── index.php                    # Main Dashboard with Summary Cards
```

---

## 🔌 2. Database Connection Standard (`Database.php`)

The connection class must use standard **PHP PDO** connected to **Supabase PostgreSQL**:

```php
<?php
class Database {
    private string $host     = "db.YOUR_SUPABASE_PROJECT.supabase.co";
    private string $port     = "5432";
    private string $dbname   = "postgres";
    private string $username = "postgres";
    private string $password = "YOUR_PASSWORD";

    public function connect(): PDO {
        try {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->dbname}";
            $pdo = new PDO($dsn, $this->username, $this->password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        } catch (PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }
}
```

---

## 📦 3. Model Classes Standard (The `Farmer.php` Convention)

Every entity class inside `models/` must follow the teacher's constructor dependency injection:

```php
<?php
class Farmer {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // 1. getAll - Always order by Primary Key
    public function getAll() {
        $sql = "SELECT * FROM farmer ORDER BY farmer_id";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    // 2. getById - Fetch 1 row
    public function getById(int $farmer_id) {
        $sql = "SELECT * FROM farmer WHERE farmer_id = :farmer_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["farmer_id" => $farmer_id]);
        return $stmt->fetch();
    }

    // 3. create - Insert and return generated ID
    public function create(string $first_name, string $last_name, string $contact_number, string $address) {
        $sql = "INSERT INTO farmer (first_name, last_name, contact_number, address) 
                VALUES (:first_name, :last_name, :contact_number, :address) 
                RETURNING farmer_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "first_name"     => $first_name,
            "last_name"      => $last_name,
            "contact_number" => $contact_number,
            "address"        => $address
        ]);
        return $stmt->fetchColumn();
    }

    // 4. update - Update row and return boolean
    public function update(int $farmer_id, string $first_name, string $last_name, string $contact_number, string $address) {
        $sql = "UPDATE farmer 
                SET first_name = :first_name, last_name = :last_name, contact_number = :contact_number, address = :address 
                WHERE farmer_id = :farmer_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            "first_name"     => $first_name,
            "last_name"      => $last_name,
            "contact_number" => $contact_number,
            "address"        => $address,
            "farmer_id"      => $farmer_id
        ]);
    }

    // 5. delete - Delete record
    public function delete(int $farmer_id) {
        $sql = "DELETE FROM farmer WHERE farmer_id = :farmer_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(["farmer_id" => $farmer_id]);
    }
}
```

---

## 🎨 4. Frontend & Styling Rules (Tailwind CSS)

1. **Use Tailwind CSS via CDN:**
   ```html
   <script src="https://cdn.tailwindcss.com"></script>
   ```
2. **Keep HTML / PHP clean and short:**
   - Use standard PHP template syntax:
     ```php
     <?php foreach ($farmers as $row): ?>
         <tr>
             <td><?= htmlspecialchars($row['first_name']) ?></td>
         </tr>
     <?php endforeach; ?>
     ```
3. **Always escape output:**
   - Use `htmlspecialchars($value)` on all dynamic data printed to the screen to prevent XSS.
4. **Use Heroicons (SVG):**
   - Embed lightweight inline SVG icons from [Heroicons](https://heroicons.com) for buttons, dashboard cards, and navigation.

---

## 🔢 5. Data Types & Parameter Discipline

Follow the exact type-casting standards taught in class:
- **Primary Keys & Foreign Keys:** `(int) $_POST["farmer_id"]`
- **Days & Quantities (Integers):** `(int) $_POST["growing_period_days"]`
- **Decimals / Currency / Area / Yield:** `(float) $_POST["area_planted"]`, `(float) $_POST["quantity_kg"]`
- **Strings:** `(string) trim($_POST["first_name"])`

---

## 🔗 6. Multi-Table SQL JOINs Standard

When fetching related data, write explicit `INNER JOIN` statements with clean table aliases:

```sql
SELECT 
    p.planting_id,
    f.farm_name,
    c.crop_name,
    c.variety,
    fm.first_name || ' ' || fm.last_name AS farmer_name,
    p.planting_date,
    p.area_planted,
    p.status
FROM planting p
JOIN farm f ON p.farm_id = f.farm_id
JOIN farmer fm ON f.farmer_id = fm.farmer_id
JOIN crop c ON p.crop_id = c.crop_id
ORDER BY p.planting_id DESC;
```

---

## 📋 7. Controlled Dropdown Lists (Lookup Tables Requirement)

According to `ap3-project.docx`:
- **Requirement:** Fields with predefined options must use `<select>` dropdowns linked to a database lookup table.
- **Example (`harvest_remarks`):**
  - Table: `harvest_remarks (remark_id PK, remark_description VARCHAR)`
  - Dropdown displays `remark_description` (`"Good Harvest"`).
  - Value submitted and stored in `harvest.remark_id` is the integer `1`.

```php
<select name="remark_id" class="w-full p-2 border rounded" required>
    <option value="">-- Select Remark --</option>
    <?php foreach ($remarks as $rem): ?>
        <option value="<?= $rem['remark_id'] ?>">
            <?= htmlspecialchars($rem['remark_description']) ?>
        </option>
    <?php endforeach; ?>
</select>
```

---

## 🔒 8. Authentication & Role-Based Access (Admin & Staff)

- Store passwords using:
  ```php
  $hashed = password_hash($password, PASSWORD_DEFAULT);
  ```
- Verify login using:
  ```php
  if (password_verify($password, $user['password_hash'])) {
      $_SESSION['user_id'] = $user['user_id'];
      $_SESSION['role']    = $user['role']; // 'admin' or 'staff'
  }
  ```
- **Permission Check:**
  ```php
  // Only Admin can delete:
  <?php if ($_SESSION['role'] === 'admin'): ?>
      <a href="delete.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
  <?php endif; ?>
  ```

---

## 🚫 9. What to AVOID (Red Flags for the Professor)

- ❌ **NO Composer or Packagist packages** (keep it pure native PHP).
- ❌ **NO complex frameworks** (No Laravel, Vue, React, Next.js).
- ❌ **NO complex design patterns** (No Repository-Service interfaces, No Dependency Injection containers, No DTO classes).
- ❌ **NO CLI scripts in the web app** (Use standard web forms `$_POST` and `$_GET`, no `$argv`).
- ❌ **NO plain-text passwords in the database.**

---

**Summary for Sonnet:** Keep the code modular, readable, short, and clean. Every file should look like it was written by a top student in a Web Application Development class who mastered PHP PDO and Tailwind CSS.

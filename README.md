# Green-Chain Decision Portal
## Enterprise Decision Intelligence Platform

---

## Project Architecture

```
/greenchain
│
├── index.html          ← Standalone frontend (no PHP needed)
├── index.php           ← PHP entry point (wraps index.html)
├── simulate.php        ← AJAX API endpoint (POST → JSON)
├── scoring_engine.php  ← PHP scoring class (mirrors JS engine)
│
├── /assets             ← (optional) extracted CSS/JS
│   ├── style.css
│   └── script.js
│
└── /partials           ← (optional) reusable HTML fragments
    └── navbar.html
```

---

## Core System Design

### Scoring Engine
The scoring engine exists in **two mirrors**:
- `scoring_engine.php` — server-side (for AJAX/API use)
- Inline JS in `index.html` — client-side (for instant feedback)

Both use identical score matrices and formulas.

### Score Matrices (per option)
Each dropdown choice maps to `{ financial, environmental, ethical }` base scores (0–100).

### Balanced Score Formula
```
Balanced = (Wf × Financial + We × Environmental + Wt × Ethical) / (Wf + We + Wt)
```
Weights come from the CEO Priority sliders.

### Risk Index
```
Risk = normalize(stddev([Financial, Environmental, Ethical]))
       normalized to 0–100 using max possible std dev of ~47
```
High risk = large spread between the three pillars.

### 5-Year Projection
```
Projected = Balanced × EnvMultiplier × EthMultiplier × AutoMultiplier

EnvMultiplier  = 1 + (Environmental/100 × 0.35)   ← up to +35% cost savings
EthMultiplier  = 1 + (Ethical/100 × 0.25)          ← up to +25% brand trust
AutoMultiplier = 1 + (Financial/100 × 0.20)        ← up to +20% productivity
```

---

## Feature Checklist

- [x] Scenario A & B configuration panels
- [x] 5 dropdowns per scenario (Energy, Supplier, Transport, Automation, Offset)
- [x] Financial / Environmental / Ethical pillar scoring
- [x] CEO Priority Weight sliders (live update)
- [x] Weighted Balanced Decision Score
- [x] Radar charts (Chart.js)
- [x] Pillar progress bars
- [x] Winner comparison banner
- [x] Trade-Off Heatmap (Green/Amber/Red)
- [x] Strategic Risk Index gauge
- [x] 5-Year Strategic Projection with multipliers
- [x] AI-style Decision Explanation Engine
- [x] Responsive layout
- [x] Glassmorphism + animated gradient UI
- [x] PHP backend (simulate.php + scoring_engine.php)
- [x] AJAX endpoint (simulate.php)
- [x] Standalone JS mode (works without PHP)

---

## Running the Project

### Option A: Static (No Server)
Open `index.html` directly in any browser. All scoring runs in JavaScript.

### Option B: PHP Server
```bash
cd /path/to/greenchain
php -S localhost:8080
# Open: http://localhost:8080
```

### Option C: Apache / Nginx
Drop the `/greenchain` folder into your web root.
Ensure PHP 7.4+ is available.

---

## Tech Stack

| Layer       | Technology                          |
|-------------|-------------------------------------|
| Frontend    | HTML5, CSS3, Vanilla JS             |
| Styling     | Custom CSS (Tailwind-inspired)      |
| Charts      | Chart.js 4.4.1                      |
| Typography  | Syne (headers) + DM Sans (body)     |
| Backend     | PHP 7.4+ (optional)                 |
| AJAX        | Fetch API → simulate.php            |
| Database    | MySQL (optional, for history)       |

---

## Extending with MySQL

To store scenario history, add to `simulate.php`:

```php
$pdo = new PDO('mysql:host=localhost;dbname=greenchain', 'user', 'pass');
$stmt = $pdo->prepare("INSERT INTO simulations 
  (scenario_a, scenario_b, winner, balanced_a, balanced_b, created_at) 
  VALUES (?,?,?,?,?,NOW())");
$stmt->execute([
  json_encode($inputsA), json_encode($inputsB),
  $winner, $balA, $balB
]);
```

```sql
CREATE TABLE simulations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  scenario_a JSON,
  scenario_b JSON,
  winner CHAR(1),
  balanced_a DECIMAL(5,1),
  balanced_b DECIMAL(5,1),
  created_at DATETIME
);
```

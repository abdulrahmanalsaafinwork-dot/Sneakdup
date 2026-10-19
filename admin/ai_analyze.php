```php
<?php
session_start();
require_once '../db_conn.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function money($value) {
    return '₱' . number_format((float)$value, 2);
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$today = (float)($conn->query("
    SELECT COALESCE(SUM(total_price),0) AS total
    FROM sales
    WHERE DATE(sale_date) = CURDATE()
")->fetch_assoc()['total'] ?? 0);

$month = (float)($conn->query("
    SELECT COALESCE(SUM(total_price),0) AS total
    FROM sales
    WHERE YEAR(sale_date) = YEAR(CURDATE())
      AND MONTH(sale_date) = MONTH(CURDATE())
")->fetch_assoc()['total'] ?? 0);

$units = (int)($conn->query("
    SELECT COALESCE(SUM(quantity),0) AS total
    FROM sales
    WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
")->fetch_assoc()['total'] ?? 0);

$best = $conn->query("
    SELECT product_name, SUM(quantity) AS units_sold,
           SUM(total_price) AS revenue
    FROM sales
    WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY product_name
    ORDER BY units_sold DESC
    LIMIT 10
");

$inventory = $conn->query("
    SELECT p.id AS product_id, p.name, p.category, p.price,
           v.id AS variant_id, v.size, v.color, v.stock
    FROM products p
    INNER JOIN product_variants v ON v.product_id = p.id
    ORDER BY v.stock ASC, p.name ASC
");

$lowStock = [];
$inventoryRows = [];

while ($row = $inventory->fetch_assoc()) {
    $row['stock'] = (int)$row['stock'];
    $inventoryRows[] = $row;

    if ($row['stock'] <= 5) {
        $lowStock[] = $row;
    }
}

/*
 * Gemini API key:
 * Prefer loading it from an environment variable named GEMINI_API_KEY.
 * Do not place the key in HTML or JavaScript.
 */
$geminiKey = 'AQ.Ab8RN6Jv6fRzsOOjGNRcgSkB8guAdF4DvzZfid4xP8JTCmcA5Q';

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'analyze') {

    header('Content-Type: text/html; charset=UTF-8');

    if ($geminiKey === '') {
        $aiError = 'Gemini API key is not configured on the server.';
    } elseif (!function_exists('curl_init')) {
        $aiError = 'PHP cURL is not enabled in XAMPP.';
    } else {
        $best->data_seek(0);
        $bestRows = [];

        while ($item = $best->fetch_assoc()) {
            $bestRows[] = $item;
        }

        $businessData = [
            'period' => 'Last 30 days',
            'today_revenue_php' => $today,
            'this_month_revenue_php' => $month,
            'units_sold_last_30_days' => $units,
            'top_products' => $bestRows,
            'inventory_variants' => $inventoryRows,
            'low_stock_threshold' => 5
        ];

        $prompt = "You are the inventory and sales analyst for SNEAKDUP,
a sneaker and streetwear store.

Analyze only the supplied data. Do not invent figures or claim to know
customer demand that is not shown by the data.

Provide:
1. Sales performance summary.
2. Best-selling products and trends supported by the data.
3. Low-stock products, sizes, and colors.
4. Suggested reorder quantities. Explain that these are estimates because
historical sales by exact variant may not be available.
5. Practical actions for the store manager.

Treat stock of 0 as out of stock and stock from 1 to 5 as low.
Do not claim that any purchase order was placed.

DATA:
" . json_encode($businessData, JSON_PRETTY_PRINT);

        $model = 'gemini-3.8-flash';
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
             . $model . ':generateContent?key=' . urlencode($geminiKey);

        $payload = [
            'contents' => [[
                'parts' => [['text' => $prompt]]
            ]],
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => 1200
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            $aiError = 'Gemini connection failed: ' . $curlError;
        } else {
            $decoded = json_decode($response, true);

            if ($status < 200 || $status >= 300) {
                $aiError = $decoded['error']['message']
                    ?? 'Gemini returned HTTP ' . $status;
            } else {
                $aiText = $decoded['candidates'][0]['content']['parts'][0]['text']
                    ?? '';

                if ($aiText === '') {
                    $aiError = 'Gemini returned no analysis. Please try again.';
                }
            }
        }
    }
}

$best->data_seek(0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/jpeg" href="../images/Sneakdup_logo.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Oswald:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
<title>SNEAKDUP AI Analyze</title>
<style>

/* ===== SNEAKDUP AI ANALYZE — DASHBOARD THEME ===== */
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    background: #f4f6f8;
    color: #1a1a1a;
    font-family: 'Inter', sans-serif;
}

/* DARK SIDEBAR */
.sidebar {
    width: 260px;
    background: #111;
    color: white;
    display: flex;
    flex-direction: column;
    padding: 25px;
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    overflow-y: auto;
    z-index: 1000;
}

.brand {
    font-family: 'Oswald', sans-serif;
    font-size: 24px;
    text-transform: uppercase;
    margin-bottom: 40px;
    color: white;
    letter-spacing: -1px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.brand span {
    color: #cc0000;
}

.admin-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    white-space: nowrap;
    box-sizing: border-box;

    background: #333;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 4px 8px;
    border-radius: 4px;
    margin-left: 5px;
    letter-spacing: 1px;
    line-height: 1;
}

.menu-item {
    text-decoration: none;
    color: #aaa;
    padding: 12px 15px;
    margin-bottom: 8px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 14px;
    transition: 0.2s;
    display: flex;
    align-items: center;
    gap: 12px;
}

.menu-item i {
    width: 18px;
    height: 18px;
    opacity: 0.7;
    transition: 0.3s;
}

.menu-item:hover,
.menu-item.active {
    background: #222;
    color: white;
}

.menu-item.active {
    border-left: 3px solid #cc0000;
    background: #222;
}

.menu-item.active i,
.menu-item:hover i {
    opacity: 1;
    color: white;
}

.logout-link {
    margin-top: auto;
    color: #cc0000;
    font-weight: 700;
    text-decoration: none;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    border-radius: 6px;
    transition: 0.2s;
}

.logout-link:hover {
    background: rgba(204, 0, 0, 0.1);
}

/* MAIN CONTENT */
main {
    width: auto;
    max-width: none;
    min-width: 0;
    margin-left: 260px;
    padding: 40px;
    flex: 1;
}

h1,
.page-title {
    font-family: 'Oswald', sans-serif;
    font-size: 36px;
    text-transform: uppercase;
    color: #111;
    margin: 0 0 8px;
}

h2,
h3 {
    font-family: 'Oswald', sans-serif;
    text-transform: uppercase;
    color: #111;
}

.muted,
.date-display {
    color: #666;
    font-size: 14px;
}

/* METRIC CARDS */
.cards {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
    margin: 25px 0 40px;
}

.card,
.panel {
    background: white;
    padding: 25px;
    border-radius: 12px;
    border: 1px solid #e5e5e5;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
    margin-bottom: 20px;
    min-width: 0;
    transition: 0.2s;
}

.card:hover {
    transform: translateY(-3px);
    border-color: #ccc;
}

.metric {
    font-family: 'Oswald', sans-serif;
    font-size: 32px;
    font-weight: 700;
    color: #111;
    line-height: 1.2;
    margin-top: 12px;
}

/* ANALYSIS SECTIONS */
.panel h2,
.panel h3 {
    margin-top: 0;
    margin-bottom: 18px;
}

.analysis {
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    line-height: 1.8;
    color: #333;
    font-size: 14px;
}

/* TABLES */
.scroll {
    width: 100%;
    overflow-x: auto;
    border-radius: 8px;
}

table {
    width: 100%;
    min-width: 600px;
    border-collapse: collapse;
    background: white;
}

th {
    text-align: left;
    padding: 15px 25px;
    font-size: 11px;
    color: #888;
    text-transform: uppercase;
    font-weight: 700;
    background: #f8fafc;
    border-bottom: 1px solid #eee;
}

td {
    text-align: left;
    padding: 15px 25px;
    border-bottom: 1px solid #f1f1f1;
    font-size: 14px;
    color: #333;
    vertical-align: middle;
}

tr:last-child td {
    border-bottom: none;
}

.warning {
    color: #b42318;
    font-weight: 700;
}

.success {
    color: #067647;
    font-weight: 700;
}

/* BUTTONS */
button,
input[type="submit"] {
    font-family: 'Inter', sans-serif;
    background: #cc0000;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 12px 18px;
    cursor: pointer;
    font-weight: 700;
    font-size: 13px;
    transition: 0.2s;
}

button:hover,
input[type="submit"]:hover {
    background: #aa0000;
}

/* FORM CONTROLS */
input,
select,
textarea {
    font-family: 'Inter', sans-serif;
    max-width: 100%;
}

input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]),
select,
textarea {
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 10px 12px;
    background: white;
    color: #222;
}

input:focus,
select:focus,
textarea:focus {
    outline: none;
    border-color: #cc0000;
}

/* MOBILE RESPONSIVENESS */
@media (max-width: 1100px) {
    .cards {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    main {
        padding: 30px;
    }
}

@media (max-width: 750px) {
    .sidebar {
        width: 210px;
        padding: 18px;
    }

    main {
        margin-left: 210px;
        padding: 20px 16px;
    }

    h1,
    .page-title {
        font-size: 28px;
    }

    .cards {
        grid-template-columns: 1fr;
        gap: 14px;
    }

    .card,
    .panel {
        padding: 18px;
    }

    .metric {
        font-size: 27px;
    }
}

@media (max-width: 480px) {
    .sidebar {
        width: 70px;
        padding: 15px 8px;
    }

    .brand {
        font-size: 0;
        justify-content: center;
        margin-bottom: 30px;
    }

    .brand span {
        font-size: 20px;
    }

    .admin-badge {
        display: none;
    }

    .menu-item {
        justify-content: center;
        padding: 12px 5px;
        font-size: 0;
    }

    .menu-item i {
        width: 20px;
        height: 20px;
    }

    .logout-link {
        justify-content: center;
        font-size: 0;
        padding: 12px 5px;
    }

    main {
        margin-left: 70px;
        padding: 20px 12px;
    }

    h1,
    .page-title {
        font-size: 24px;
    }

    .card,
    .panel {
        padding: 14px;
    }
}
</style>
</head>
<body>
<main>

   <div class="admin-layout">

    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main-content">
    
    <p class="muted">SNEAKDUP / ADMIN</p>
    <h1>AI Sales & Inventory Analysis</h1>
    <p class="muted">Review actual store data and generate AI recommendations.</p>

    <section class="cards">
        <div class="card">
            <div class="muted">Today's Revenue</div>
            <div class="metric"><?= h(money($today)) ?></div>
        </div>
        <div class="card">
            <div class="muted">This Month's Revenue</div>
            <div class="metric"><?= h(money($month)) ?></div>
        </div>
        <div class="card">
            <div class="muted">Units Sold in Last 30 Days</div>
            <div class="metric"><?= number_format($units) ?></div>
        </div>
    </section>

    <section class="panel">
        <h2>Generate AI Analysis</h2>
        <p class="muted">
            Gemini will review your sales totals, top products, and inventory.
            Recommendations are advisory only.
        </p>

        <form method="post">
            <button type="submit" name="action" value="analyze">
                Generate AI Analysis
            </button>
        </form>

        <?php if (!empty($aiError)): ?>
            <p class="warning"><?= h($aiError) ?></p>
        <?php endif; ?>

        <?php if (!empty($aiText)): ?>
            <h3>Gemini's Report</h3>
            <div class="analysis"><?= h($aiText) ?></div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Top-Selling Products — Last 30 Days</h2>
        <div class="scroll">
        <table>
            <thead><tr>
                <th>Product</th><th>Units Sold</th><th>Revenue</th>
            </tr></thead>
            <tbody>
            <?php if ($best->num_rows === 0): ?>
                <tr><td colspan="3">No sales recorded for this period.</td></tr>
            <?php else: ?>
                <?php while ($row = $best->fetch_assoc()): ?>
                <tr>
                    <td><?= h($row['product_name']) ?></td>
                    <td><?= number_format((int)$row['units_sold']) ?></td>
                    <td><?= h(money($row['revenue'])) ?></td>
                </tr>
                <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </section>

    <section class="panel">
        <h2>Low-Stock Inventory</h2>
        <p class="muted">Low stock means 5 or fewer units of a variant.</p>
        <div class="scroll">
        <table>
            <thead><tr>
                <th>Product</th><th>Size</th><th>Color</th><th>Stock</th>
            </tr></thead>
            <tbody>
            <?php if (!$lowStock): ?>
                <tr><td colspan="4" class="success">
                    No variants currently meet the low-stock threshold.
                </td></tr>
            <?php else: ?>
                <?php foreach ($lowStock as $row): ?>
                <tr>
                    <td><?= h($row['name']) ?></td>
                    <td><?= h($row['size'] ?? 'N/A') ?></td>
                    <td><?= h($row['color'] ?? 'N/A') ?></td>
                    <td class="warning"><?= number_format($row['stock']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </section>
</main>
 <script>
        lucide.createIcons();
    </script>
</body>
</html>
```
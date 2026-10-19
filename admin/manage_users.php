<?php
session_start();
include __DIR__ . '/../db_conn.php';

// 1. Security Check: Admin Only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit();
}

// 2. Handle the "Block" or "Activate" Action
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action']; 

    if ($action == 'blocked' || $action == 'active') {
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $action, $id);
        $stmt->execute();
    }
    header("Location: manage_users.php");
    exit();
}

// 3. Handle the Search Logic
$search_query = "";
$search_sql = "";

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    // Sanitize the search term to prevent SQL injection
    $search_query = trim($_GET['search']);
    $safe_search = $conn->real_escape_string($search_query);
    // Search in both fullname and email columns
    $search_sql = " AND (fullname LIKE '%$safe_search%' OR email LIKE '%$safe_search%')";
}

// 4. FETCH EMPLOYEES ONLY
$employees = $conn->query("SELECT * FROM users WHERE role='employee' $search_sql ORDER BY id DESC");
$total_staff = $employees->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Staff - SneakDup Admin</title>
    <link rel="icon" type="image/jpeg" href="../images/Sneakdup_logo.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Oswald:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        /* 1. APP LAYOUT RESET */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Inter', sans-serif; background-color: #f4f6f8; color: #1a1a1a; 
            height: 100vh; overflow: hidden; display: flex;
        }

        /* 2. DARK SIDEBAR */
        .sidebar {
            width: 260px; background: #111; color: white; display: flex; flex-direction: column;
            padding: 25px; height: 100%; flex-shrink: 0;
        }
        .brand { 
            font-family: 'Oswald', sans-serif; font-size: 24px; text-transform: uppercase; 
            margin-bottom: 40px; color: white; letter-spacing: -1px; display: flex; align-items: center; gap: 8px;
        }
        .brand span { color: #cc0000; }
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
            text-decoration: none; color: #aaa; padding: 12px 15px; margin-bottom: 8px;
            border-radius: 6px; font-weight: 600; font-size: 14px; transition: 0.2s; display: flex; align-items: center; gap: 12px;
        }
        .menu-item i { width: 18px; height: 18px; opacity: 0.7; transition: 0.3s; }
        
.menu-item.active {
    background: #222;
    color: white;
    border-left: 3px solid #ff0000;
    border-radius: 6px;
}
        .menu-item:hover:not(.active) { background: #222; color: white; }
        
        .logout-link { 
            margin-top: auto; color: #cc0000; font-weight: 700; text-decoration: none; 
            font-size: 14px; display: flex; align-items: center; gap: 10px; 
            padding: 12px 15px; border-radius: 6px; transition: 0.2s;
        }
        .logout-link i { width: 18px; height: 18px; }
        .logout-link:hover { background: rgba(204, 0, 0, 0.1); }

        /* 3. MAIN CONTENT AREA */
        .main-content { flex: 1; display: flex; flex-direction: column; height: 100%; overflow-y: auto; }
        
        .top-header {
            background: white; padding: 20px 40px; border-bottom: 1px solid #e5e5e5;
            display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 10;
        }
        .page-title { font-family: 'Oswald', sans-serif; font-size: 28px; text-transform: uppercase; margin: 0; }
        
        /* Search Bar Styles */
        .search-form { display: flex; align-items: center; gap: 10px; }
        .search-input {
            padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;
            width: 300px; font-family: 'Inter', sans-serif; outline: none; transition: 0.2s;
        }
        .search-input:focus { border-color: #111; box-shadow: 0 0 0 3px rgba(0,0,0,0.05); }
        
        .search-btn {
            background: #111; color: white; border: none; padding: 10px 15px; border-radius: 6px;
            font-size: 13px; font-weight: 700; cursor: pointer; transition: 0.2s; text-transform: uppercase;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .search-btn i { width: 16px; height: 16px; }
        .search-btn:hover { background: #cc0000; }
        
        .clear-btn {
            color: #777; text-decoration: none; font-size: 12px; font-weight: 600; margin-left: 5px;
            display: inline-flex; align-items: center; gap: 4px; transition: 0.2s;
        }
        .clear-btn i { width: 14px; height: 14px; }
        .clear-btn:hover { color: #111; text-decoration: underline; }

        /* 4. TABLE CARDS */
        .content-wrapper { padding: 40px; max-width: 1100px; margin: 0 auto; width: 100%; }
        
        .table-card {
            background: white; border-radius: 12px; border: 1px solid #eee;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); margin-bottom: 40px; overflow: hidden; 
        }

        .card-header {
            padding: 20px 25px; border-bottom: 1px solid #eee;
            display: flex; align-items: center; justify-content: space-between; background: #fafafa;
        }
        .header-title-group { display: flex; align-items: center; gap: 10px; }
        .header-title-group i { width: 22px; height: 22px; color: #111; }
        .card-header h2 { font-family: 'Oswald', sans-serif; font-size: 18px; text-transform: uppercase; margin: 0; letter-spacing: 0.5px; }
        .staff-count { background: #eee; padding: 2px 8px; border-radius: 20px; font-size: 12px; font-weight: 700; color: #555; }
        
        .btn-add { 
            background: #cc0000; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; 
            font-size: 12px; font-weight: 700; text-transform: uppercase; transition: 0.2s; border: none; 
            display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-add i { width: 16px; height: 16px; }
        .btn-add:hover { background: #aa0000; }

        /* 5. TABLE STYLES */
        table { width: 100%; border-collapse: collapse; }
        th { background: white; text-align: left; padding: 15px 25px; font-size: 11px; font-weight: 700; color: #888; text-transform: uppercase; border-bottom: 1px solid #eee; }
        td { padding: 15px 25px; font-size: 14px; color: #333; border-bottom: 1px solid #f9f9f9; font-weight: 500; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background-color: #fcfcfc; }

        /* New Avatar Styles */
        .staff-info-cell { display: flex; align-items: center; gap: 15px; }
        .avatar {
            width: 40px; height: 40px; border-radius: 50%; background: #111; color: white;
            display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;
            font-family: 'Oswald', sans-serif; letter-spacing: 1px;
        }

        /* 6. BADGES & BUTTONS */
        .badge { padding: 4px 10px; border-radius: 4px; font-size: 10px; font-weight: 800; text-transform: uppercase; display: inline-block; letter-spacing: 0.5px;}
        .badge-active { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .badge-blocked { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        .btn { 
            padding: 8px 14px; text-decoration: none; border-radius: 6px; font-size: 12px; 
            font-weight: 700; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; text-transform: uppercase; 
        }
        .btn i { width: 14px; height: 14px; }
        
        .btn-block { border: 1px solid #fee2e2; color: #dc2626; background: white; }
        .btn-block:hover { background: #fee2e2; }

        .btn-unblock { background: #111; color: white; border: 1px solid #111; }
        .btn-unblock:hover { background: #333; }

        .search-results-text { margin-bottom: 20px; font-size: 14px; color: #666; font-weight: 500; }
  
       /* POS MAIN LAYOUT */
.admin-layout {
    display: flex;
    width: 100%;
    height: 100vh;
    overflow: hidden;
}

  </style>
</head>
<body>

  <div class="admin-layout">

    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="top-header">
            <h1 class="page-title">Staff Management</h1>
            
            <form class="search-form" method="GET" action="manage_users.php">
                <input type="text" name="search" class="search-input" placeholder="Search staff by name or email..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search-btn"><i data-lucide="search"></i> Search</button>
                <?php if(!empty($search_query)): ?>
                    <a href="manage_users.php" class="clear-btn"><i data-lucide="x"></i> Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="content-wrapper">
            
            <?php if(!empty($search_query)): ?>
                <div class="search-results-text">
                    Showing results for: <strong>"<?php echo htmlspecialchars($search_query); ?>"</strong>
                </div>
            <?php endif; ?>

            <div class="table-card">
                <div class="card-header">
                    <div class="header-title-group">
                        <i data-lucide="user-cog"></i>
                        <h2 style="color: #111;">Store Employees</h2>
                        <span class="staff-count"><?php echo $total_staff; ?> Total</span>
                    </div>
                    <a href="add_employee.php" class="btn-add"><i data-lucide="user-plus"></i> Add Employee</a>
                </div>
                
                <?php if ($employees->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Employee Details</th>
                            <th>ID Number</th>
                            <th>Status</th>
                            <th style="text-align:right;">Access Control</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $employees->fetch_assoc()): 
                            // Generate initials for the avatar
                            $name_parts = explode(' ', trim($row['fullname']));
                            $initials = strtoupper(substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : ''));
                        ?>
                        <tr>
                            <td>
                                <div class="staff-info-cell">
                                    <div class="avatar"><?php echo $initials; ?></div>
                                    <div>
                                        <div style="color:#111; font-weight:700;"><?php echo htmlspecialchars($row['fullname']); ?></div>
                                        <div style="font-size: 13px; color: #888; margin-top: 2px;"><?php echo htmlspecialchars($row['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="color:#888; font-family:'Courier New', monospace; font-size: 13px;">EMP-<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                            
                            <td>
                                <span class="badge <?php echo ($row['status'] == 'active') ? 'badge-active' : 'badge-blocked'; ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            
                            <td style="text-align:right;">
                                <?php if($row['status'] == 'active'): ?>
                                    <a href="manage_users.php?action=blocked&id=<?php echo $row['id']; ?>" 
                                       class="btn btn-block"
                                       onclick="return confirm('Are you sure you want to BLOCK this Employee? They will not be able to log in or use the POS.');">
                                       <i data-lucide="ban"></i> Suspend Access
                                    </a>
                                <?php else: ?>
                                    <a href="manage_users.php?action=active&id=<?php echo $row['id']; ?>" 
                                       class="btn btn-unblock">
                                       <i data-lucide="unlock"></i> Restore Access
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div style="padding:60px; text-align:center; color:#888; font-weight: 500;">
                        <i data-lucide="users" style="width: 40px; height: 40px; opacity: 0.3; margin-bottom: 10px;"></i><br>
                        No employees found.
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
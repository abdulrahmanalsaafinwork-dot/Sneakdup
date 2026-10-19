<?php
session_start();
include __DIR__ . '/../db_conn.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /login.php");
    exit();
}

// 1. HANDLE ADDING A SCHEDULE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_schedule'])) {
    $employee_id = $_POST['employee_id'];
    $work_date = $_POST['work_date'];

    $stmt = $conn->prepare("INSERT IGNORE INTO employee_schedules (user_id, work_date) VALUES (?, ?)");
    $stmt->bind_param("is", $employee_id, $work_date);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $success_msg = "Shift assigned successfully!";
        } else {
            $error_msg = "This employee is already scheduled for this exact date.";
        }
    } else {
        $error_msg = "Error assigning schedule.";
    }
}

// 2. HANDLE DELETING A SCHEDULE
if (isset($_GET['delete'])) {
    $del_id = $_GET['delete'];
    $conn->query("DELETE FROM employee_schedules WHERE id = '$del_id'");
    header("Location: manage_schedule.php?deleted=1");
    exit();
}

// 3. FETCH ALL EMPLOYEES FOR THE DROPDOWN
$employees = $conn->query("SELECT id, fullname FROM users WHERE role = 'employee' ORDER BY fullname ASC");

// 4. FETCH CURRENT MONTH'S SCHEDULE WITH AUTOMATIC ABSENT DETECTION
$current_month = date('m');
$current_year = date('Y');
$month_name = date('F Y');
$today = date('Y-m-d');

// We use LEFT JOIN to check if they clocked in on their scheduled day!
$schedule_query = "
    SELECT s.id, u.fullname, s.work_date, a.time_in 
    FROM employee_schedules s 
    JOIN users u ON s.user_id = u.id 
    LEFT JOIN attendance a ON s.user_id = a.user_id AND s.work_date = a.date
    WHERE MONTH(s.work_date) = '$current_month' AND YEAR(s.work_date) = '$current_year'
    ORDER BY s.work_date ASC, u.fullname ASC
";
$schedules = $conn->query($schedule_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Schedule - Admin</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Oswald:wght@500;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: #f4f6f8; color: #111; display: flex; min-height: 100vh;}
        
        /* Basic Sidebar */
        .sidebar { width: 250px; background: #111; color: white; padding: 20px; }
        .sidebar h2 { font-family: 'Oswald', sans-serif; color: #cc0000; margin-bottom: 30px; letter-spacing: 1px;}
        .sidebar a { color: #ccc; text-decoration: none; display: flex; align-items: center; gap: 10px; padding: 10px 0; margin-bottom: 10px; transition: 0.2s;}
        .sidebar a:hover, .sidebar a.active { color: white; padding-left: 10px; border-left: 3px solid #cc0000; }

        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        h1 { font-family: 'Oswald', sans-serif; font-size: 32px; text-transform: uppercase; }

        .grid { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .card h3 { margin-bottom: 20px; font-size: 18px; border-bottom: 1px solid #eee; padding-bottom: 10px; }

        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 5px; }
        select, input[type="date"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-family: 'Inter'; }
        
        .btn { background: #cc0000; color: white; border: none; padding: 12px; width: 100%; border-radius: 4px; font-weight: 700; cursor: pointer; transition: 0.2s; margin-top: 10px;}
        .btn:hover { background: #111; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f9f9f9; font-weight: 600; color: #555; text-transform: uppercase; font-size: 12px; }
        
        /* Status Badges */
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; display: inline-block; }
        .badge-upcoming { background: #e0f2fe; color: #0284c7; } /* Blue */
        .badge-present { background: #dcfce7; color: #166534; }  /* Green */
        .badge-absent { background: #fee2e2; color: #991b1b; }   /* Red */
        .badge-pending { background: #fef08a; color: #b45309; }  /* Yellow */

        .action-link { color: #cc0000; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;}
        .action-link:hover { text-decoration: underline; }

        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; font-weight: 600; font-size: 14px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
   
       /* POS MAIN LAYOUT */
.admin-layout {
    display: flex;
    width: 80%;
    height: 100vh;
    overflow: hidden;
}

.pos-layout {
    display: flex;
    flex: 1;
    width: 100%;
    min-width: 0;
    height: 100%;
    overflow: hidden;
}

.product-panel {
    flex: 1;
    min-width: 0;
    overflow-y: auto;
}

.cart-panel {
    flex: 0 0 350px;
    width: 350px;
    min-width: 350px;
    box-sizing: border-box;
    height: 100%;
    display: flex;
    flex-direction: column;
    background: white;
    border-left: 1px solid #ddd;
}
   
   </style>
</head>
<body>
      <div class="admin-layout">

    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>Staff Scheduling</h1>
        </div>

        <?php if(isset($success_msg)): ?> <div class="alert alert-success"><?php echo $success_msg; ?></div> <?php endif; ?>
        <?php if(isset($error_msg)): ?> <div class="alert alert-error"><?php echo $error_msg; ?></div> <?php endif; ?>
        <?php if(isset($_GET['deleted'])): ?> <div class="alert alert-success">Shift removed successfully!</div> <?php endif; ?>

        <div class="grid">
            <div class="card">
                <h3>Assign Shift</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Select Employee</label>
                        <select name="employee_id" required>
                            <option value="">-- Choose Staff --</option>
                            <?php while($emp = $employees->fetch_assoc()): ?>
                                <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['fullname']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Select Work Date</label>
                        <input type="date" name="work_date" required>
                    </div>
                    <button type="submit" name="assign_schedule" class="btn">Add to Schedule</button>
                </form>
            </div>

            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom:20px;">
                    <h3 style="border:none; margin:0; padding:0;">Schedule for <?php echo $month_name; ?></h3>
                    <span class="badge" style="background:#f1f5f9; color:#475569;">Resets automatically next month</span>
                </div>
                
                <?php if($schedules->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Employee</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $schedules->fetch_assoc()): 
                                $work_date = $row['work_date'];
                                $time_in = $row['time_in'];
                                
                                // SMART STATUS LOGIC
                                if ($time_in) {
                                    // They clocked in
                                    $status_html = "<span class='badge badge-present'>Present</span>";
                                } elseif ($work_date < $today) {
                                    // The day passed, and they didn't clock in
                                    $status_html = "<span class='badge badge-absent'>Absent</span>";
                                } elseif ($work_date == $today) {
                                    // It's today, waiting for them to clock in
                                    $status_html = "<span class='badge badge-pending'>Pending</span>";
                                } else {
                                    // It's in the future
                                    $status_html = "<span class='badge badge-upcoming'>Upcoming</span>";
                                }
                            ?>
                                <tr>
                                    <td><strong><?php echo date('D, M d', strtotime($work_date)); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                                    <td><?php echo $status_html; ?></td>
                                    <td>
                                        <a href="manage_schedule.php?delete=<?php echo $row['id']; ?>" class="action-link" onclick="return confirm('Remove this shift?');">
                                            <i data-lucide="trash-2" style="width:14px; height:14px;"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #777; text-align: center; padding: 20px;">No shifts assigned for this month yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
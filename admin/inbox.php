<?php
session_start();
include __DIR__ . '/../db_conn.php';

// 1. Security Check: Admin Only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /login.php");
    exit();
}

// 2. Fetch all messages from the database, newest first
$messages_query = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");

// 3. Count total messages for a quick KPI
$total_messages = $messages_query->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Customer Inbox - SneakDup Admin</title>
    <link rel="icon" type="image/jpeg" href="../images/Sneakdup_logo.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Oswald:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        /* APP LAYOUT RESET */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Inter', sans-serif; background-color: #f4f6f8; color: #1a1a1a; 
            height: 100vh; overflow: hidden; display: flex;
        }

        /* DARK SIDEBAR */
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
        
        /* Minimalist Icon Styling */
        .menu-item {
            text-decoration: none; color: #aaa; padding: 12px 15px; margin-bottom: 8px;
            border-radius: 6px; font-weight: 600; font-size: 14px; transition: 0.2s; display: flex; align-items: center; gap: 12px;
        }
        .menu-item i { width: 18px; height: 18px; opacity: 0.7; transition: 0.3s; }
 .menu-item i { width: 18px; height: 18px; opacity: 0.7; transition: 0.3s; }
.menu-item.active {
    background: #222;
    color: white;
    border-left: 3px solid #ff0000;
    border-radius: 6px;
}
        .menu-item:hover:not(.active) { background: #222; color: white; }
        
        .logout-link { margin-top: auto; color: #cc0000; font-weight: 700; text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 10px; padding: 12px 15px; border-radius: 6px; transition: 0.2s; }
        .logout-link i { width: 18px; height: 18px; }
        .logout-link:hover { background: rgba(204, 0, 0, 0.1); }

        /* MAIN CONTENT AREA */
        .main-content { flex: 1; display: flex; flex-direction: column; height: 100%; overflow-y: auto; }
        
        .top-header {
            background: white; padding: 20px 40px; border-bottom: 1px solid #e5e5e5;
            display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 10;
        }
        .page-title { font-family: 'Oswald', sans-serif; font-size: 28px; text-transform: uppercase; margin: 0; }

        .content-wrapper { padding: 40px; max-width: 1200px; margin: 0 auto; width: 100%; }

        /* TABLE STYLES */
        .table-card { background: white; border-radius: 12px; border: 1px solid #eee; box-shadow: 0 4px 15px rgba(0,0,0,0.03); overflow: hidden; }
        .card-header { padding: 20px 25px; border-bottom: 1px solid #eee; background: #fafafa; display: flex; justify-content: space-between; align-items: center;}
        
        .header-title-group { display: flex; align-items: center; gap: 10px; }
        .header-title-group i { width: 22px; height: 22px; color: #111; }
        .card-header h2 { font-family: 'Oswald', sans-serif; font-size: 18px; text-transform: uppercase; margin: 0; }
        
        .msg-count { background: #cc0000; color: white; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }

        table { width: 100%; border-collapse: collapse; }
        th { background: white; text-align: left; padding: 15px 25px; font-size: 11px; font-weight: 700; color: #888; text-transform: uppercase; border-bottom: 1px solid #eee; }
        td { padding: 15px 25px; font-size: 14px; color: #333; border-bottom: 1px solid #f9f9f9; vertical-align: top; }
        tr:last-child td { border-bottom: none; }
        
        /* Added cursor pointer to indicate the row is clickable */
        .clickable-row { cursor: pointer; transition: background-color 0.2s ease; }
        .clickable-row:hover { background-color: #f5f5f5; }

        .date-col { font-size: 12px; color: #888; white-space: nowrap; }
        .sender-col { font-weight: 700; color: #111; }
        .email-text { font-size: 12px; color: #666; font-weight: 400; display: block; margin-top: 4px; }
        .subject-col { font-weight: 600; color: #cc0000; margin-bottom: 5px; }
        
        /* Truncate long messages in the table view */
        .msg-preview { 
            color: #555; 
            line-height: 1.5; 
            font-size: 13px;
            display: -webkit-box; 
            -webkit-line-clamp: 2; 
            -webkit-box-orient: vertical; 
            overflow: hidden; 
            text-overflow: ellipsis;
        }
        
        .empty-state { padding: 60px; text-align: center; color: #888; font-weight: 500; }
        .empty-state-icon { display: flex; justify-content: center; margin-bottom: 15px; opacity: 0.3; color: #111; }
        .empty-state-icon i { width: 48px; height: 48px; }

        /* =========================================
           MODAL POPUP STYLES 
           ========================================= */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6); display: none; align-items: center; justify-content: center;
            z-index: 1000; backdrop-filter: blur(4px); padding: 20px;
        }
        .modal-content {
            background: white; width: 100%; max-width: 650px; border-radius: 12px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2); overflow: hidden;
            animation: slideUp 0.3s ease; display: flex; flex-direction: column;
            max-height: 90vh; /* Prevents modal from being taller than screen */
        }
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-header-box {
            background: #111; color: white; padding: 20px 25px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .modal-header-box h3 { 
            margin: 0; font-family: 'Oswald', sans-serif; text-transform: uppercase; 
            font-size: 20px; letter-spacing: 0.5px; 
        }
        .modal-close {
            background: none; border: none; color: #aaa; cursor: pointer; transition: 0.2s;
            display: flex; align-items: center; justify-content: center; padding: 5px;
        }
        .modal-close:hover { color: white; transform: scale(1.1); }
        .modal-meta {
            padding: 20px 25px; border-bottom: 1px solid #eee; background: #fafafa;
            font-size: 14px; color: #555; line-height: 1.6;
        }
        .modal-meta strong { color: #111; display: inline-block; width: 50px; }
        .modal-body-text {
            padding: 25px; font-size: 15px; color: #333; line-height: 1.7;
            white-space: pre-wrap; overflow-y: auto; flex: 1;
        }

       /* POS MAIN LAYOUT */
.admin-layout {
    display: flex;
    width: 100%;
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
        
        <div class="top-header">
            <h1 class="page-title">Customer Inbox</h1>
        </div>

        <div class="content-wrapper">
            
            <div class="table-card">
                <div class="card-header">
                    <div class="header-title-group">
                        <i data-lucide="mail"></i>
                        <h2>Recent Messages</h2>
                    </div>
                    <span class="msg-count"><?php echo $total_messages; ?> Total</span>
                </div>
                
                <?php if ($messages_query->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th width="15%">Date Received</th>
                            <th width="20%">Sender Info</th>
                            <th width="65%">Message Content</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $messages_query->fetch_assoc()): ?>
                        <tr class="clickable-row" onclick="openMessageModal(this)"
                            data-name="<?php echo htmlspecialchars($row['name']); ?>"
                            data-email="<?php echo htmlspecialchars($row['email']); ?>"
                            data-date="<?php echo date('M d, Y - h:i A', strtotime($row['created_at'])); ?>"
                            data-subject="<?php echo htmlspecialchars($row['subject']); ?>"
                            data-message="<?php echo htmlspecialchars($row['message']); ?>">
                            
                            <td class="date-col">
                                <?php echo date('M d, Y', strtotime($row['created_at'])); ?><br>
                                <?php echo date('h:i A', strtotime($row['created_at'])); ?>
                            </td>
                            <td>
                                <div class="sender-col"><?php echo htmlspecialchars($row['name']); ?></div>
                                <span class="email-text"><?php echo htmlspecialchars($row['email']); ?></span>
                            </td>
                            <td>
                                <div class="subject-col"><?php echo htmlspecialchars($row['subject']); ?></div>
                                <div class="msg-preview"><?php echo htmlspecialchars($row['message']); ?></div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i data-lucide="mail-x"></i>
                        </div>
                        Your inbox is currently empty.
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <div id="readModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header-box">
                <h3 id="modalSubject">Message Subject</h3>
                <button class="modal-close" onclick="closeModal()"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-meta">
                <strong>From:</strong> <span id="modalSenderName"></span> &lt;<span id="modalSenderEmail" style="color:#cc0000; font-weight:600;"></span>&gt;<br>
                <strong style="margin-top: 5px;">Date:</strong> <span id="modalDate"></span>
            </div>
            <div class="modal-body-text" id="modalMessageText">
                </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // Modal Logic
        function openMessageModal(row) {
            // Pull data from the clicked row's data-* attributes
            const name = row.getAttribute('data-name');
            const email = row.getAttribute('data-email');
            const date = row.getAttribute('data-date');
            const subject = row.getAttribute('data-subject');
            const message = row.getAttribute('data-message');

            // Inject the data into the modal's HTML elements
            document.getElementById('modalSubject').textContent = subject;
            document.getElementById('modalSenderName').textContent = name;
            document.getElementById('modalSenderEmail').textContent = email;
            document.getElementById('modalDate').textContent = date;
            document.getElementById('modalMessageText').textContent = message;

            // Display the modal
            document.getElementById('readModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('readModal').style.display = 'none';
        }

        // Close the modal if the user clicks the dark background overlay
        window.onclick = function(event) {
            const modal = document.getElementById('readModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
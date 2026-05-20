<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'libtech_db');

$admin_id = $_SESSION['admin_id'];

// Get member information
$member_result = $conn->query("
    SELECT member_id, full_name, email 
    FROM member 
    WHERE admin_id = $admin_id
");

$member = $member_result->fetch_assoc();

$member_id = $member['member_id'] ?? 0;
$member_name = $member['full_name'] ?? 'Member';
$member_email = $member['email'] ?? '';

$msg = '';
$success = false;

// Add functionality - borrow book
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_borrow'])) {

    $book_id = $_POST['book_id'];

    // Current date and due date
    $borrow_date = date('Y-m-d');
    $due_date = date('Y-m-d', strtotime('+14 days'));
    
    // Get selected book details
    $book_info = $conn->query("
        SELECT * 
        FROM book 
        WHERE book_id = $book_id
    ")->fetch_assoc();
    
    // Validation - check if book is available
    $book_check = $conn->query("
        SELECT * 
        FROM book 
        WHERE book_id = $book_id 
        AND available_copies > 0
    ");

    if($book_check->num_rows > 0) {

        // Update available copies
        $conn->query("
            UPDATE book 
            SET available_copies = available_copies - 1 
            WHERE book_id = $book_id
        ");
        
        // Create borrowing transaction
        $conn->query("
            INSERT INTO transaction 
            (member_id, book_id, borrow_date, due_date, status) 
            VALUES 
            ($member_id, $book_id, '$borrow_date', '$due_date', 'Borrowed')
        ");
        
        // Notification/email simulation
        $subject = "📖 Book Borrowed Confirmation - LibTech Solutions";

        $message = "
Dear $member_name,

You have successfully borrowed \"{$book_info['title']}\" by {$book_info['author']}.

📅 Borrow Date: $borrow_date
⏰ Due Date: $due_date

Please return the book by the due date to avoid fines of $0.50 per day.

Thank you for using LibTech Solutions!

Best regards,
LibTech Team
";
        
        // Save notification
        $conn->query("
            INSERT INTO notification 
            (member_id, notification_type, subject, message, status) 
            VALUES 
            ($member_id, 'borrow', '$subject', '$message', 'sent')
        ");
        
        $success = true;

        $msg = "
        <div class='success-msg'>
            ✅ Book borrowed successfully! 
            A confirmation email has been sent to $member_email
        </div>
        ";
        
        // Redirect after successful borrowing
        echo "
        <script>
            setTimeout(function(){
                window.location.href = 'my-borrowings.php';
            }, 2000);
        </script>
        ";

    } else {

        // Validation message if book unavailable
        $msg = "
        <div class='error-msg'>
            ❌ Book is not available for borrowing!
        </div>
        ";
    }
}

// List functionality - get available books
$books = $conn->query("
    SELECT * 
    FROM book 
    WHERE available_copies > 0 
    ORDER BY title
");
?>
<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">

<title>Borrow a Book - LibTech Solutions</title>

<style>

body {
    font-family: 'Segoe UI', sans-serif;
    background: #0a0a2a;
    color: #e4e6eb;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
    padding: 20px;
}

.form-container {
    background: #1c1e26;
    border-radius: 30px;
    padding: 40px;
    width: 500px;
    border: 1px solid #2d3139;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
}

h2 {
    color: #e4e6eb;
    margin-bottom: 10px;
}

.subtitle {
    color: #8b8d94;
    margin-bottom: 25px;
    font-size: 14px;
}

select, button {
    width: 100%;
    padding: 14px;
    margin: 12px 0;
    background: #0f1419;
    border: 1px solid #2d3139;
    border-radius: 12px;
    color: #e4e6eb;
    font-size: 15px;
}

button {
    background: linear-gradient(135deg, #6366f1, #ec4899);
    color: white;
    border: none;
    cursor: pointer;
    font-weight: 600;
}

button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.back-link {
    color: #818cf8;
    display: block;
    text-align: center;
    margin-top: 20px;
    text-decoration: none;
}

.success-msg {
    background: rgba(16,185,129,0.2);
    border: 1px solid #10b981;
    color: #34d399;
    padding: 12px;
    border-radius: 12px;
    margin-bottom: 20px;
    text-align: center;
}

.error-msg {
    background: rgba(239,68,68,0.2);
    border: 1px solid #ef4444;
    color: #f87171;
    padding: 12px;
    border-radius: 12px;
    margin-bottom: 20px;
    text-align: center;
}

.book-details {
    background: #0f1419;
    border-radius: 16px;
    padding: 15px;
    margin: 15px 0;
    border: 1px solid #2d3139;
}

.book-details p {
    margin: 5px 0;
}

.fine-info {
    font-size: 12px;
    color: #8b8d94;
    margin-top: 15px;
    text-align: center;
}

a {
    color: #818cf8;
    text-decoration: none;
}

</style>

</head>

<body>

<div class="form-container">

    <h2>📚 Borrow a Book</h2>

    <p class="subtitle">
        Select a book and confirm borrowing
    </p>
        
    <?php echo $msg; ?>
        
    <?php if(!$success): ?>

    <form method="POST" id="borrowForm">

        <!-- List available books -->

        <select name="book_id" id="bookSelect" required>

            <option value="">-- Select a book --</option>

            <?php while($book = $books->fetch_assoc()): ?>

                <option value="<?php echo $book['book_id']; ?>" 

                    data-title="<?php echo htmlspecialchars($book['title']); ?>"

                    data-author="<?php echo htmlspecialchars($book['author']); ?>"

                    data-copies="<?php echo $book['available_copies']; ?>">

                    <?php echo $book['title']; ?> 
                    by 
                    <?php echo $book['author']; ?>

                    (Available: <?php echo $book['available_copies']; ?>)

                </option>

            <?php endwhile; ?>

        </select>
            
        <!-- Preview selected book -->

        <div id="bookPreview" class="book-details" style="display:none;">

            <p>
                <strong>📖 Selected Book:</strong>
                <span id="previewTitle"></span>
            </p>

            <p>
                <strong>✍️ Author:</strong>
                <span id="previewAuthor"></span>
            </p>

            <p>
                <strong>📅 Due Date:</strong>
                <?php echo date('Y-m-d', strtotime('+14 days')); ?>
            </p>

            <p>
                <strong>💰 Late Fine:</strong>
                $0.50 per day after due date
            </p>

        </div>
            
        <div class="fine-info">
            Books must be returned within 14 days.
        </div>
            
        <button type="button"
                id="confirmBtn"
                onclick="showConfirmModal()"
                disabled>

            Proceed to Borrow

        </button>

    </form>

    <?php endif; ?>
        
    <a href="../member-dashboard.php" class="back-link">
        ← Back to Dashboard
    </a>

</div>

<!-- Confirmation Modal -->

<div id="confirmModal"
     style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:1000; align-items:center; justify-content:center;">

    <div style="background:#1c1e26; border-radius:30px; padding:35px; width:450px; max-width:90%; border:1px solid #2d3139;">

        <h3 style="color:#e4e6eb; margin-bottom:15px;">
            📖 Confirm Borrowing
        </h3>

        <div id="confirmDetails"></div>

        <div style="display:flex; gap:15px; margin-top:25px;">

            <button onclick="closeConfirmModal()"
                    style="flex:1; background:#2d3139; color:#e4e6eb;">

                Cancel

            </button>

            <button onclick="submitBorrow()"
                    style="flex:1; background:linear-gradient(135deg, #6366f1, #ec4899);">

                Confirm Borrow

            </button>

        </div>

    </div>

</div>

<script>

// Get elements
const bookSelect = document.getElementById('bookSelect');
const previewDiv = document.getElementById('bookPreview');
const confirmBtn = document.getElementById('confirmBtn');

let selectedBookId = null;
let selectedTitle = '';
let selectedAuthor = '';

// Show preview when user selects a book
bookSelect.addEventListener('change', function() {

    const option = bookSelect.options[bookSelect.selectedIndex];

    if(bookSelect.value) {

        selectedBookId = bookSelect.value;
        selectedTitle = option.getAttribute('data-title');
        selectedAuthor = option.getAttribute('data-author');
                
        document.getElementById('previewTitle').innerText = selectedTitle;

        document.getElementById('previewAuthor').innerText = selectedAuthor;

        previewDiv.style.display = 'block';

        confirmBtn.disabled = false;

    } else {

        previewDiv.style.display = 'none';
        confirmBtn.disabled = true;
    }
});

// Open confirmation modal
function showConfirmModal() {

    document.getElementById('confirmDetails').innerHTML = `
        <p><strong>Book:</strong> ${selectedTitle}</p>
        <p><strong>Author:</strong> ${selectedAuthor}</p>
        <p><strong>Due Date:</strong> <?php echo date('Y-m-d', strtotime('+14 days')); ?></p>
    `;

    document.getElementById('confirmModal').style.display = 'flex';
}

// Close modal
function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
}

// Submit borrow form
function submitBorrow() {

    const form = document.getElementById('borrowForm');

    const hiddenInput = document.createElement('input');

    hiddenInput.type = 'hidden';
    hiddenInput.name = 'confirm_borrow';
    hiddenInput.value = '1';

    form.appendChild(hiddenInput);

    form.submit();
}

// Close modal when clicking outside
window.onclick = function(e) {

    const modal = document.getElementById('confirmModal');

    if(e.target == modal) {
        closeConfirmModal();
    }
}

</script>

</body>
</html>
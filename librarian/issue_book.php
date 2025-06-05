<?php
// Include header
include_once '../includes/header.php';

// Check if user is a librarian
checkUserRole('librarian');

$message = '';
$messageType = '';

// Process book issue
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = (int)$_POST['user_id'];
    $bookId = (int)$_POST['book_id'];
    $returnDate = $_POST['return_date'];
    
    // Validate input
    if (empty($userId) || empty($bookId) || empty($returnDate)) {
        $message = "All fields are required.";
        $messageType = "danger";
    } else {
        // Check if book is available
        $stmt = $conn->prepare("SELECT available_quantity, title FROM books WHERE id = ?");
        $stmt->bind_param("i", $bookId);
        $stmt->execute();
        $result = $stmt->get_result();
        $book = $result->fetch_assoc();
        
        if ($book['available_quantity'] > 0) {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Create issue record
                $stmt = $conn->prepare("
                    INSERT INTO issued_books (book_id, user_id, return_date)
                    VALUES (?, ?, ?)
                ");
                $stmt->bind_param("iis", $bookId, $userId, $returnDate);
                $stmt->execute();
                
                // Update book availability
                updateBookAvailability($conn, $bookId, 'issue');
                
                // Send notification to user
                $notificationMsg = "You have been issued the book '{$book['title']}'. Please return it by " . date('M d, Y', strtotime($returnDate));
                sendNotification($conn, $userId, $notificationMsg);
                
                $conn->commit();
                
                $message = "Book issued successfully.";
                $messageType = "success";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Error issuing book: " . $e->getMessage();
                $messageType = "danger";
            }
        } else {
            $message = "Book is not available for issue.";
            $messageType = "danger";
        }
    }
}

// Get all users
$users = [];
$userSql = "SELECT id, name, email, role FROM users WHERE role != 'librarian' ORDER BY name";
$userResult = $conn->query($userSql);
if ($userResult) {
    while ($row = $userResult->fetch_assoc()) {
        $users[] = $row;
    }
}

// Get all available books
$books = [];
$bookSql = "SELECT id, title, author, available_quantity FROM books WHERE available_quantity > 0 ORDER BY title";
$bookResult = $conn->query($bookSql);
if ($bookResult) {
    while ($row = $bookResult->fetch_assoc()) {
        $books[] = $row;
    }
}
?>

<div class="container">
    <div class="d-flex justify-between align-center mb-4">
        <h1 class="page-title">Issue Book</h1>
        <a href="books.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Books
        </a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="card" style="margin-top:30px";>
        <div class="card-header">
            <h3>Issue Book to User</h3>
        </div>
        <div class="card-body">
            <form action="" method="POST" class="issue-book-form">
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="user_id">Select User</label>
                            <select id="user_id" name="user_id" class="form-control" required>
                                <option value="">Choose a user...</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['name']); ?> 
                                        (<?php echo htmlspecialchars($user['email']); ?>) - 
                                        <?php echo ucfirst($user['role']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-col">
                        <div class="form-group">
                            <label for="book_id">Select Book</label>
                            <select id="book_id" name="book_id" class="form-control" required>
                                <option value="">Choose a book...</option>
                                <?php foreach ($books as $book): ?>
                                    <option value="<?php echo $book['id']; ?>">
                                        <?php echo htmlspecialchars($book['title']); ?> 
                                        by <?php echo htmlspecialchars($book['author']); ?>
                                        (<?php echo $book['available_quantity']; ?> available)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="return_date">Return Date</label>
                            <?php
                            $minDate = date('Y-m-d', strtotime('+1 day'));
                            $defaultDate = date('Y-m-d', strtotime('+14 days'));
                            ?>
                            <input type="date" id="return_date" name="return_date" 
                                   class="form-control" 
                                   min="<?php echo $minDate; ?>"
                                   value="<?php echo $defaultDate; ?>"
                                   required>
                            <small class="text-muted">Default return period is 14 days</small>
                        </div>
                    </div>
                </div>
                
                <div class="form-group text-right">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-book"></i> Issue Book
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Include footer
include_once '../includes/footer.php';
?>
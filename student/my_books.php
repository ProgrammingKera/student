<?php
include_once '../includes/header.php';

// Check if user is student or faculty
if ($_SESSION['role'] != 'student' && $_SESSION['role'] != 'faculty') {
    header('Location: ../index.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Get issued books
$issuedBooks = [];
$sql = "
    SELECT ib.*, b.title, b.author
    FROM issued_books ib
    JOIN books b ON ib.book_id = b.id
    WHERE ib.user_id = ?
    ORDER BY ib.issue_date DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $issuedBooks[] = $row;
    }
}

// Get pending requests
$pendingRequests = [];
$sql = "
    SELECT br.*, b.title, b.author
    FROM book_requests br
    JOIN books b ON br.book_id = b.id
    WHERE br.user_id = ? AND br.status = 'pending'
    ORDER BY br.request_date DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pendingRequests[] = $row;
    }
}

// Get fines
$fines = [];
$sql = "
    SELECT f.*, b.title
    FROM fines f
    JOIN issued_books ib ON f.issued_book_id = ib.id
    JOIN books b ON ib.book_id = b.id
    WHERE f.user_id = ? AND f.status = 'pending'
    ORDER BY f.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $fines[] = $row;
    }
}
?>

<div class="container">
    <h1 class="page-title">My Books</h1>

    <!-- Currently Issued Books -->
    <div class="card mb-4">
        <div class="card-header">
            <h3>Currently Issued Books</h3>
        </div>
        <div class="card-body">
            <?php if (count($issuedBooks) > 0): ?>
                <div class="table-container">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Book Title</th>
                                <th>Author</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Fine</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($issuedBooks as $book): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($book['issue_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($book['return_date'])); ?></td>
                                    <td>
                                        <?php 
                                        $today = new DateTime();
                                        $dueDate = new DateTime($book['return_date']);
                                        if ($book['status'] == 'returned') {
                                            echo '<span class="badge badge-success">Returned</span>';
                                        } elseif ($today > $dueDate) {
                                            echo '<span class="badge badge-danger">Overdue</span>';
                                        } else {
                                            echo '<span class="badge badge-primary">Issued</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($book['fine_amount'] > 0) {
                                            echo '$' . number_format($book['fine_amount'], 2);
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center">You don't have any books issued currently.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pending Requests -->
    <div class="card mb-4">
        <div class="card-header">
            <h3>Pending Book Requests</h3>
        </div>
        <div class="card-body">
            <?php if (count($pendingRequests) > 0): ?>
                <div class="table-container">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Book Title</th>
                                <th>Author</th>
                                <th>Request Date</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingRequests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['title']); ?></td>
                                    <td><?php echo htmlspecialchars($request['author']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                    <td><span class="badge badge-warning">Pending</span></td>
                                    <td><?php echo htmlspecialchars($request['notes']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center">You don't have any pending book requests.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pending Fines -->
    <div class="card">
        <div class="card-header">
            <h3>Pending Fines</h3>
        </div>
        <div class="card-body">
            <?php if (count($fines) > 0): ?>
                <div class="table-container">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Book Title</th>
                                <th>Fine Amount</th>
                                <th>Reason</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fines as $fine): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($fine['title']); ?></td>
                                    <td>$<?php echo number_format($fine['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($fine['reason']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($fine['created_at'])); ?></td>
                                    <td><span class="badge badge-warning">Pending</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center">You don't have any pending fines.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
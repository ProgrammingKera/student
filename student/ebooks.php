<?php
include_once '../includes/header.php';

// Check if user is student or faculty
if ($_SESSION['role'] != 'student' && $_SESSION['role'] != 'faculty') {
    header('Location: ../index.php');
    exit();
}

// Handle search and filtering
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// Get all categories
$categories = [];
$result = $conn->query("SELECT DISTINCT category FROM ebooks WHERE category != '' ORDER BY category");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

// Build search query
$sql = "SELECT e.*, u.name as uploader_name FROM ebooks e LEFT JOIN users u ON e.uploaded_by = u.id WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (e.title LIKE ? OR e.author LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

if (!empty($category)) {
    $sql .= " AND e.category = ?";
    $params[] = $category;
    $types .= "s";
}

$sql .= " ORDER BY e.created_at DESC";

// Execute search
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$ebooks = [];
while ($row = $result->fetch_assoc()) {
    $ebooks[] = $row;
}
?>

<div class="container">
    <h1 class="page-title">E-Books Library</h1>

    <div class="search-section mb-4">
        <form action="" method="GET" class="d-flex">
            <div class="form-group mr-2">
                <input type="text" name="search" placeholder="Search e-books..." class="form-control" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="form-group mr-2">
                <select name="category" class="form-control">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category == $cat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Search
            </button>
        </form>
    </div>

    <div class="table-container">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>Format</th>
                    <th>Size</th>
                    <th>Added On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($ebooks) > 0): ?>
                    <?php foreach ($ebooks as $ebook): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ebook['title']); ?></td>
                            <td><?php echo htmlspecialchars($ebook['author']); ?></td>
                            <td><?php echo htmlspecialchars($ebook['category']); ?></td>
                            <td>
                                <?php 
                                $fileType = strtoupper($ebook['file_type']);
                                $iconClass = '';
                                switch (strtolower($fileType)) {
                                    case 'pdf':
                                        $iconClass = 'fas fa-file-pdf';
                                        break;
                                    case 'epub':
                                        $iconClass = 'fas fa-book';
                                        break;
                                    default:
                                        $iconClass = 'fas fa-file';
                                }
                                ?>
                                <i class="<?php echo $iconClass; ?>"></i> <?php echo $fileType; ?>
                            </td>
                            <td><?php echo htmlspecialchars($ebook['file_size']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($ebook['created_at'])); ?></td>
                            <td>
                                <a href="<?php echo htmlspecialchars($ebook['file_path']); ?>" class="btn btn-primary btn-sm" target="_blank">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No e-books found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
<?php 
session_start();
require_once 'config/db.php'; // PDO-based connection

// 1. Session Guard: Redirect to login if user isn't authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uId = $_SESSION['user_id'];

// 2. Fetch User Role & Admin Status
$stmt = $pdo->prepare("SELECT role FROM Users WHERE id = ?");
$stmt->execute([$uId]);
$userRow = $stmt->fetch();
$isAdmin = ($userRow && $userRow['role'] === 'admin');

include 'includes/header.php'; 

// 3. ADMIN ONLY: Handle Record Insertion (Create) with Password
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_user']) && $isAdmin) {
    $newId = $_POST['new_id'];
    $username = $_POST['username'];
    $univ = $_POST['university'];
    $password = $_POST['password'];

    $insertStmt = $pdo->prepare("
        INSERT INTO Users (id, username, university, password, rating, solvedCount, role)
        VALUES (:id, :username, :university, :password, 0, 0, 'user')
    ");
    $success = $insertStmt->execute([
        ':id' => $newId,
        ':username' => $username,
        ':university' => $univ,
        ':password' => password_hash($password, PASSWORD_DEFAULT)
    ]);

    if ($success) {
        echo "<div class='alert alert-success m-3'>Record for ".htmlspecialchars($username)." created successfully!</div>";
    } else {
        echo "<div class='alert alert-danger m-3'>Database Error: Failed to insert record</div>";
    }
}

// 4. ADMIN ONLY: Handle Record Deletion (Delete)
if (isset($_GET['delete_id']) && $isAdmin) {
    $delId = $_GET['delete_id'];
    $delStmt = $pdo->prepare("DELETE FROM Users WHERE id = ?");
    $delStmt->execute([$delId]);
    header("Location: database.php?msg=deleted");
    exit();
}

// 5. Handle Search Filter
$search = $_GET['search'] ?? '';
?>

<div class="container mt-5 pb-5">
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold"><i class="bi bi-database-lock text-primary"></i> Data Management</h2>
            <p class="text-muted small">
                Active User: <span class="fw-bold"><?= htmlspecialchars($userRow['role']) ?></span> 
                (<?= $isAdmin ? "Full Privileges" : "Read-Only" ?>)
            </p>
        </div>
        <div class="col-md-6 text-md-end">
            <?php if ($isAdmin): ?>
                <button class="btn btn-success px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-person-plus-fill"></i> New User
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-10">
                    <input type="text" name="search" class="form-control" placeholder="Search records..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Username</th>
                        <th>University</th>
                        <th>Role</th>
                        <th>Rating</th>
                        <?php if ($isAdmin): ?>
                            <th class="text-center">Admin Controls</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT * FROM Users 
                            WHERE username LIKE :search 
                               OR university LIKE :search 
                               OR id LIKE :search 
                            ORDER BY role DESC, id ASC";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':search' => "%$search%"]);
                    $users = $stmt->fetchAll();

                    foreach($users as $row): ?>
                        <tr>
                            <td class="ps-3 text-muted small"><?= htmlspecialchars($row['id']) ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['university']) ?></td>
                            <td>
                                <span class="badge <?= $row['role'] === 'admin' ? 'bg-danger' : 'bg-secondary' ?>">
                                    <?= strtoupper($row['role']) ?>
                                </span>
                            </td>
                            <td class="text-primary fw-bold"><?= (int)$row['rating'] ?></td>
                            
                            <?php if ($isAdmin): ?>
                                <td class="text-center">
                                    <a href="edit_user.php?id=<?= htmlspecialchars($row['id']) ?>" 
                                       class="btn btn-sm btn-outline-primary" title="Edit User">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="database.php?delete_id=<?= htmlspecialchars($row['id']) ?>" 
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('Confirm deletion of <?= htmlspecialchars($row['username']) ?>?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">Add New User to Database</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">User ID</label>
                        <input type="text" name="new_id" class="form-control" placeholder="e.g. u99" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="Login name" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Assigned Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Secret key" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">University</label>
                        <select name="university" class="form-select">
                            <?php 
                            $stmt = $pdo->query("SELECT name FROM Universities");
                            $univs = $stmt->fetchAll();
                            foreach($univs as $un): ?>
                                <option value="<?= htmlspecialchars($un['name']) ?>"><?= htmlspecialchars($un['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <input type="hidden" name="add_user" value="1">
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4">Insert Record</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

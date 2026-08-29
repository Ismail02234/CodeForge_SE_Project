<?php
require_once __DIR__ . '/core/bootstrap.php';

$user = require_login($pdo);
$isAdmin = $user['role'] === 'admin';
$error = null;

if (request_method('POST')) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if (!$isAdmin) {
        $error = 'Read-only users cannot modify records.';
    } elseif ($action === 'create') {
        $id = trim((string) ($_POST['id'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $university = trim((string) ($_POST['university'] ?? ''));
        $role = (string) ($_POST['role'] ?? 'user');

        if ($id === '' || $username === '' || strlen($password) < 8) {
            $error = 'User ID, username and a password of at least 8 characters are required.';
        } elseif (!preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
            $error = 'User ID can contain letters, numbers, dashes and underscores only.';
        } elseif (!preg_match('/^[A-Za-z0-9_]{3,32}$/', $username)) {
            $error = 'Username must be 3–32 characters using letters, numbers or underscore.';
        } else {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO users(id,username,password,role,rating,university,`rank`)
                     VALUES(:id,:username,:password,:role,1200,:university,'Newbie')"
                );
                $stmt->execute([
                    'id' => $id,
                    'username' => $username,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => in_array($role, ['user', 'admin'], true) ? $role : 'user',
                    'university' => $university !== '' ? $university : null,
                ]);
                flash('success', 'User created successfully.');
                redirect('database.php');
            } catch (PDOException) {
                $error = 'Could not create user. ID or username may already exist.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (string) ($_POST['id'] ?? '');
        if ($id === $user['id']) {
            $error = 'You cannot delete your own active account.';
        } else {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute(['id' => $id]);
            flash('success', $stmt->rowCount() ? 'User deleted.' : 'User was already removed.');
            redirect('database.php');
        }
    }
}

$search = mb_substr(trim((string) ($_GET['search'] ?? '')), 0, 100);
$stmt = $pdo->prepare(
    "SELECT u.id, u.username, u.role, u.rating, u.university, u.`rank`, u.created_at,
            COALESCE(stats.solved_count, 0) AS solved_count,
            COALESCE(stats.submission_count, 0) AS submission_count
     FROM users u
     LEFT JOIN (
         SELECT user_id,
                COUNT(DISTINCT CASE WHEN verdict = 'AC' THEN problem_id END) AS solved_count,
                COUNT(*) AS submission_count
         FROM submissions
         GROUP BY user_id
     ) stats ON stats.user_id = u.id
     WHERE u.id LIKE :q OR u.username LIKE :q OR COALESCE(u.university, '') LIKE :q
     ORDER BY FIELD(u.role,'admin','user'), u.rating DESC, u.username ASC"
);
$stmt->execute(['q' => '%' . $search . '%']);
$users = $stmt->fetchAll();
$universities = $pdo->query('SELECT name FROM universities ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Database';
include __DIR__ . '/includes/header.php';
?>
<div class="page">
    <div class="page-head">
        <div><span class="eyebrow">Relational data management</span><h1 class="page-title">Database tab</h1><p class="page-subtitle">Authenticated users have read access. Administrators can create, update and delete user records with prepared statements, hashed passwords and CSRF protection.</p></div>
        <span class="pill <?= $isAdmin ? 'pill-hard' : 'pill-neutral' ?>"><?= $isAdmin ? 'ADMIN · full CRUD' : 'USER · read only' ?></span>
    </div>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <section class="card card-pad mb-3">
        <form method="get" class="toolbar"><input class="input" style="flex:1" name="search" value="<?= e($search) ?>" placeholder="Search ID, username or university"><button class="btn btn-primary">Search records</button></form>
    </section>

    <section class="card mb-3">
        <div class="card-head"><h3>Users</h3><span class="pill pill-neutral"><?= count($users) ?> rows</span></div>
        <div class="table-wrap"><table class="table"><thead><tr><th>ID</th><th>Username</th><th>University</th><th>Role</th><th>Rating</th><th>Solved</th><th>Submissions</th><?php if ($isAdmin): ?><th>Actions</th><?php endif; ?></tr></thead><tbody>
        <?php foreach ($users as $row): ?>
            <tr>
                <td class="mono small"><?= e($row['id']) ?></td>
                <td><a class="strong" href="profile.php?id=<?= e($row['id']) ?>"><?= e($row['username']) ?></a><div class="muted tiny"><?= e($row['rank']) ?></div></td>
                <td><?= e($row['university'] ?? '—') ?></td>
                <td><span class="pill <?= $row['role'] === 'admin' ? 'pill-hard' : 'pill-neutral' ?>"><?= e(strtoupper($row['role'])) ?></span></td>
                <td><?= (int) $row['rating'] ?></td><td><?= (int) $row['solved_count'] ?></td><td><?= (int) $row['submission_count'] ?></td>
                <?php if ($isAdmin): ?><td><div class="toolbar"><a class="btn btn-ghost btn-sm" href="edit_user.php?id=<?= e($row['id']) ?>">Edit</a><?php if ($row['id'] !== $user['id']): ?><form method="post" data-confirm-form="Delete <?= e($row['username']) ?> and all dependent history?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($row['id']) ?>"><button class="btn btn-danger btn-sm">Delete</button></form><?php endif; ?></div></td><?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    </section>

    <?php if ($isAdmin): ?>
    <section class="card card-pad">
        <span class="eyebrow">Create</span><h2>New user</h2>
        <form method="post" class="form-grid">
            <?= csrf_field() ?><input type="hidden" name="action" value="create">
            <div class="field"><label>User ID</label><input class="input" name="id" placeholder="u99" required></div>
            <div class="field"><label>Username</label><input class="input" name="username" minlength="3" maxlength="32" required></div>
            <div class="field"><label>Password</label><input class="input" type="password" name="password" minlength="8" required></div>
            <div class="field"><label>Role</label><select class="select" name="role"><option value="user">User</option><option value="admin">Admin</option></select></div>
            <div class="field full"><label>University</label><select class="select" name="university"><option value="">No university</option><?php foreach ($universities as $university): ?><option><?= e($university) ?></option><?php endforeach; ?></select></div>
            <div class="field full"><button class="btn btn-success">Insert user record</button></div>
        </form>
    </section>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

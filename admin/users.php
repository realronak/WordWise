<?php
/**
 * WordWise Admin CMS - User Management & RBAC Roles
 * Manage Administrator, Editor, Author, and Reader accounts.
 */
$pageTitle = "Users & Roles";
$pageHeader = "Access Control";
require_once __DIR__ . '/includes/admin_header.php';

auth_require('admin');

$editId = (int)($_GET['edit'] ?? 0);
$editUser = null;
if ($editId > 0) {
    $editUser = db_fetch_one("SELECT * FROM users WHERE user_id = ?", [$editId]);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = "Security token mismatch.";
    } else {
        $action = $_POST['action'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        if ($action === 'save') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $fullName = trim($_POST['full_name'] ?? '');
            $role = in_array($_POST['user_role'] ?? '', ['admin', 'editor', 'author', 'reader'], true) ? $_POST['user_role'] : 'author';
            $status = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'active';
            $password = $_POST['password'] ?? '';
            $bio = trim($_POST['bio'] ?? '');

            if ($username === '' || $email === '' || $fullName === '') {
                $errors[] = "Username, email, and full name are required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Please provide a valid email address.";
            } else {
                // Prevent self-lockout
                if ($userId === auth_id() && ($status === 'inactive' || $role !== 'admin')) {
                    $errors[] = "You cannot deactivate or demote your own administrative account.";
                } else {
                    $checkSql = "SELECT user_id FROM users WHERE (username = ? OR email = ?)" . ($userId > 0 ? " AND user_id != ?" : "");
                    $checkParams = $userId > 0 ? [$username, $email, $userId] : [$username, $email];
                    if (db_fetch_one($checkSql, $checkParams)) {
                        $errors[] = "Username or email is already registered to another account.";
                    } else {
                        if ($userId > 0) {
                            // Update User
                            if (!empty($password)) {
                                $hash = password_hash($password, PASSWORD_BCRYPT);
                                db_execute(
                                    "UPDATE users SET username = ?, email = ?, full_name = ?, user_role = ?, status = ?, bio = ?, password = ? WHERE user_id = ?",
                                    [$username, $email, $fullName, $role, $status, $bio, $hash, $userId]
                                );
                            } else {
                                db_execute(
                                    "UPDATE users SET username = ?, email = ?, full_name = ?, user_role = ?, status = ?, bio = ? WHERE user_id = ?",
                                    [$username, $email, $fullName, $role, $status, $bio, $userId]
                                );
                            }
                            db_execute("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'update_user', ?, ?)", [auth_id(), "Updated user #{$userId} '{$username}'", $ip]);
                            $_SESSION['flash_success'] = "User account &ldquo;{$username}&rdquo; updated.";
                        } else {
                            // Create New User
                            if (empty($password)) {
                                $errors[] = "Password is required for new accounts.";
                            } else {
                                $hash = password_hash($password, PASSWORD_BCRYPT);
                                db_execute(
                                    "INSERT INTO users (username, email, password, full_name, user_role, bio, status) VALUES (?, ?, ?, ?, ?, ?, ?)",
                                    [$username, $email, $hash, $fullName, $role, $bio, $status]
                                );
                                $newId = db_last_id();
                                db_execute("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'create_user', ?, ?)", [auth_id(), "Created user #{$newId} '{$username}' ({$role})", $ip]);
                                $_SESSION['flash_success'] = "User account &ldquo;{$username}&rdquo; created.";
                            }
                        }

                        if (empty($errors)) {
                            header("Location: /admin/users.php");
                            exit;
                        }
                    }
                }
            }
        } elseif ($action === 'delete') {
            $userId = (int)($_POST['user_id'] ?? 0);
            if ($userId === auth_id()) {
                $_SESSION['flash_error'] = "You cannot delete your own account.";
            } else {
                db_execute("DELETE FROM users WHERE user_id = ?", [$userId]);
                db_execute("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'delete_user', ?, ?)", [auth_id(), "Deleted user #{$userId}", $ip]);
                $_SESSION['flash_success'] = "User account deleted.";
            }
            header("Location: /admin/users.php");
            exit;
        }
    }
}

$users = db_fetch_all("SELECT * FROM users ORDER BY created_at ASC");
?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
  
  <!-- User Form -->
  <div class="lg:col-span-5">
    <div class="editorial-card p-6 rounded-sm space-y-4">
      <div class="pb-3 border-b border-paper-200 dark:border-paper-800 flex items-center justify-between">
        <h2 class="font-mono font-bold text-lg text-ink-950 dark:text-ink-50">
          <?php echo $editUser ? 'Edit User: ' . e($editUser['username']) : 'Create New Account'; ?>
        </h2>
        <?php if ($editUser): ?>
          <a href="/admin/users.php" class="font-mono text-xs text-ink-400 hover:text-accent">New +</a>
        <?php endif; ?>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="p-3 bg-red-500/10 border border-red-500/30 text-red-600 text-xs font-mono rounded">
          <?php foreach ($errors as $e): ?><p>&bull; <?php echo e($e); ?></p><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="/admin/users.php" class="space-y-4">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="user_id" value="<?php echo $editUser['user_id'] ?? 0; ?>">

        <div>
          <label for="username" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
            Username *
          </label>
          <input 
            type="text" 
            name="username" 
            id="username" 
            required 
            value="<?php echo e($editUser['username'] ?? ''); ?>" 
            placeholder="johndoe" 
            class="w-full px-3 py-1.5 text-xs font-mono bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100"
          >
        </div>

        <div>
          <label for="email" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
            Email Address *
          </label>
          <input 
            type="email" 
            name="email" 
            id="email" 
            required 
            value="<?php echo e($editUser['email'] ?? ''); ?>" 
            placeholder="john@example.com" 
            class="w-full px-3 py-1.5 text-xs font-sans bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100"
          >
        </div>

        <div>
          <label for="full_name" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
            Full Name *
          </label>
          <input 
            type="text" 
            name="full_name" 
            id="full_name" 
            required 
            value="<?php echo e($editUser['full_name'] ?? ''); ?>" 
            placeholder="John Doe" 
            class="w-full px-3 py-1.5 text-xs font-sans bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100"
          >
        </div>

        <div>
          <label for="password" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
            Password <?php echo $editUser ? '(leave blank to keep current)' : '*'; ?>
          </label>
          <input 
            type="password" 
            name="password" 
            id="password" 
            <?php echo $editUser ? '' : 'required'; ?> 
            placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" 
            class="w-full px-3 py-1.5 text-xs font-mono bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100"
          >
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="user_role" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
              Role
            </label>
            <select name="user_role" id="user_role" class="w-full px-3 py-1.5 text-xs font-mono bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100">
              <option value="author" <?php echo ($editUser['user_role'] ?? '') === 'author' ? 'selected' : ''; ?>>Author</option>
              <option value="editor" <?php echo ($editUser['user_role'] ?? '') === 'editor' ? 'selected' : ''; ?>>Editor</option>
              <option value="admin" <?php echo ($editUser['user_role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Administrator</option>
              <option value="reader" <?php echo ($editUser['user_role'] ?? '') === 'reader' ? 'selected' : ''; ?>>Reader</option>
            </select>
          </div>

          <div>
            <label for="status" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
              Status
            </label>
            <select name="status" id="status" class="w-full px-3 py-1.5 text-xs font-mono bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100">
              <option value="active" <?php echo ($editUser['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
              <option value="inactive" <?php echo ($editUser['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
          </div>
        </div>

        <div>
          <label for="bio" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
            Author Biography
          </label>
          <textarea 
            name="bio" 
            id="bio" 
            rows="2" 
            placeholder="Short contributor bio..."
            class="w-full px-3 py-1.5 text-xs font-sans bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100"
          ><?php echo e($editUser['bio'] ?? ''); ?></textarea>
        </div>

        <button type="submit" class="w-full py-2 px-4 bg-accent hover:bg-accent-hover text-white font-mono text-xs uppercase tracking-wider font-semibold rounded transition-colors">
          <?php echo $editUser ? 'Update Account' : 'Create Account'; ?>
        </button>
      </form>
    </div>
  </div>

  <!-- Users Table List -->
  <div class="lg:col-span-7">
    <div class="editorial-card rounded-sm overflow-hidden">
      <div class="p-5 border-b border-paper-200 dark:border-paper-800 flex items-center justify-between">
        <div>
          <h2 class="font-mono font-bold text-lg text-ink-950 dark:text-ink-50">Registered Accounts</h2>
          <p class="font-mono text-xs text-ink-400"><?php echo count($users); ?> active CMS users</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs font-mono">
          <thead class="bg-paper-100 dark:bg-paper-950/60 border-b border-paper-200 dark:border-paper-800 text-ink-400 uppercase">
            <tr>
              <th class="p-4">User</th>
              <th class="p-4">Role</th>
              <th class="p-4">Status</th>
              <th class="p-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-paper-200 dark:divide-paper-800">
            <?php foreach ($users as $u): ?>
              <tr class="hover:bg-paper-50 dark:hover:bg-paper-800/40 transition-colors">
                <td class="p-4">
                  <p class="font-bold text-ink-950 dark:text-ink-50"><?php echo e($u['full_name']); ?></p>
                  <p class="text-[11px] text-ink-400">@<?php echo e($u['username']); ?> &bull; <?php echo e($u['email']); ?></p>
                </td>
                <td class="p-4">
                  <span class="typewriter-badge uppercase text-[10px] text-accent border-accent/40 font-bold">
                    <?php echo e($u['user_role']); ?>
                  </span>
                </td>
                <td class="p-4">
                  <?php if ($u['status'] === 'active'): ?>
                    <span class="typewriter-badge text-emerald-600 border-emerald-500/40 bg-emerald-500/10">Active</span>
                  <?php else: ?>
                    <span class="typewriter-badge text-ink-400">Inactive</span>
                  <?php endif; ?>
                </td>
                <td class="p-4 text-right space-x-2">
                  <a href="/admin/users.php?edit=<?php echo $u['user_id']; ?>" class="text-accent hover:underline font-bold">Edit</a>
                  <?php if ($u['user_id'] !== auth_id()): ?>
                    <span>&bull;</span>
                    <form method="POST" action="/admin/users.php" class="inline" onsubmit="return confirm('Permanently remove user @<?php echo e($u['username']); ?>?');">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                      <button type="submit" class="text-red-500 hover:text-red-700">Delete</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

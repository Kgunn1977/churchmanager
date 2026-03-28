<?php
$pageTitle = 'Users — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';
require_once __DIR__ . '/../config/database.php';

// Admins only
requireRole('admin');

$db      = getDB();
$error   = '';
$success = $_GET['saved'] ?? null;

// Role definitions
$roles = [
    'admin'     => ['label' => 'Admin',     'color' => 'bg-red-100 text-red-700'],
    'scheduler' => ['label' => 'Scheduler', 'color' => 'bg-blue-100 text-blue-700'],
    'custodial' => ['label' => 'Custodial', 'color' => 'bg-green-100 text-green-700'],
    'staff'     => ['label' => 'Staff',     'color' => 'bg-gray-100 text-gray-600'],
];

// ── Handle POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';

    // Add user
    if ($type === 'add_user') {
        $name     = trim($_POST['name']     ?? '');
        $title    = trim($_POST['title']    ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');
        $role     = $_POST['role']          ?? 'staff';

        if (!$name || !$email || !$password) {
            $error = 'Name, email, and password are all required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif (!array_key_exists($role, $roles)) {
            $error = 'Invalid role selected.';
        } else {
            // Check for duplicate email
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'A user with that email address already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (name, title, email, password, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $title, $email, $hash, $role]);
                header('Location: /pages/users.php?saved=1');
                exit;
            }
        }
    }

    // Edit user
    if ($type === 'edit_user') {
        $id      = (int)($_POST['id']    ?? 0);
        $name    = trim($_POST['name']   ?? '');
        $title   = trim($_POST['title']  ?? '');
        $role    = $_POST['role']        ?? 'staff';
        $active  = isset($_POST['is_active']) ? 1 : 0;
        $newPass = trim($_POST['new_password'] ?? '');

        if (!$name) {
            $error = 'Name is required.';
        } elseif (!array_key_exists($role, $roles)) {
            $error = 'Invalid role.';
        } else {
            if ($newPass) {
                if (strlen($newPass) < 8) {
                    $error = 'New password must be at least 8 characters.';
                } else {
                    $hash = password_hash($newPass, PASSWORD_DEFAULT);
                    $db->prepare("UPDATE users SET name=?, title=?, role=?, is_active=?, password=?, updated_at=NOW() WHERE id=?")
                       ->execute([$name, $title, $role, $active, $hash, $id]);
                }
            } else {
                $db->prepare("UPDATE users SET name=?, title=?, role=?, is_active=?, updated_at=NOW() WHERE id=?")
                   ->execute([$name, $title, $role, $active, $id]);
            }
            if (!$error) {
                header('Location: /pages/users.php?saved=1');
                exit;
            }
        }
    }

    // Delete user
    if ($type === 'delete_user') {
        $id = (int)($_POST['id'] ?? 0);
        // Prevent deleting yourself
        if ($id !== (int)$currentUser['id']) {
            $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        }
        header('Location: /pages/users.php?saved=1');
        exit;
    }
}

// ── Fetch users ───────────────────────────────────────────────────────────────
$users = $db->query("SELECT * FROM users ORDER BY role, name")->fetchAll();
?>

<main class="max-w-5xl mx-auto px-4 py-8">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Users</h1>
            <p class="text-gray-500 text-sm mt-0.5">Manage staff accounts and access roles.</p>
        </div>
        <button onclick="document.getElementById('addUserModal').classList.remove('hidden')"
                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add User
        </button>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-3 mb-5 text-sm flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            Saved successfully.
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-3 mb-5 text-sm flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Role Legend -->
    <div class="flex flex-wrap gap-2 mb-6">
        <?php foreach ($roles as $key => $r): ?>
            <span class="<?= $r['color'] ?> text-xs font-semibold px-3 py-1 rounded-full">
                <?= $r['label'] ?>
            </span>
        <?php endforeach; ?>
        <span class="text-gray-400 text-xs self-center ml-1">— Role access levels</span>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Name</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Email</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Role</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-gray-400">No users found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-gray-50 transition <?= !$u['is_active'] ? 'opacity-50' : '' ?>">
                            <td class="px-5 py-3.5">
                                <div class="font-medium text-gray-800">
                                    <?= htmlspecialchars($u['name']) ?>
                                    <?php if ($u['id'] == $currentUser['id']): ?>
                                        <span class="ml-1 text-xs text-gray-400">(you)</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($u['title'])): ?>
                                    <div class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($u['title']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3.5 text-gray-500"><?= htmlspecialchars($u['email']) ?></td>
                            <td class="px-5 py-3.5">
                                <span class="<?= $roles[$u['role']]['color'] ?? 'bg-gray-100 text-gray-600' ?> text-xs font-semibold px-2.5 py-1 rounded-full capitalize">
                                    <?= $roles[$u['role']]['label'] ?? ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <?php if ($u['is_active']): ?>
                                    <span class="text-green-600 font-medium text-xs">Active</span>
                                <?php else: ?>
                                    <span class="text-gray-400 font-medium text-xs">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Edit -->
                                    <button
                                        onclick="openEditModal(<?= htmlspecialchars(json_encode($u)) ?>)"
                                        class="text-blue-500 hover:text-blue-700 transition text-xs font-medium px-2 py-1 rounded-lg hover:bg-blue-50">
                                        Edit
                                    </button>
                                    <!-- Delete (can't delete yourself) -->
                                    <?php if ($u['id'] != $currentUser['id']): ?>
                                        <form method="POST"
                                              onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($u['name'])) ?>? This cannot be undone.')">
                                            <input type="hidden" name="type" value="delete_user">
                                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                            <button type="submit"
                                                    class="text-red-400 hover:text-red-600 transition text-xs font-medium px-2 py-1 rounded-lg hover:bg-red-50">
                                                Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Role Permissions Summary -->
    <div class="mt-8 bg-white rounded-2xl shadow-sm p-6">
        <h2 class="font-bold text-gray-800 mb-4">Role Permissions</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-center">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 pr-4 font-semibold text-gray-600">Permission</th>
                        <th class="py-2 px-3 font-semibold text-red-600">Admin</th>
                        <th class="py-2 px-3 font-semibold text-blue-600">Scheduler</th>
                        <th class="py-2 px-3 font-semibold text-green-600">Custodial</th>
                        <th class="py-2 px-3 font-semibold text-gray-500">Staff</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-600">
                    <?php
                    $permGroups = [
                        'System' => [
                            'System settings & configuration'   => [1,0,0,0],
                            'Manage user accounts & roles'       => [1,0,0,0],
                            'Manage buildings, floors & rooms'   => [1,0,0,0],
                        ],
                        'Catalogs' => [
                            'Manage equipment catalog'           => [1,1,0,0],
                            'Manage task catalog'                => [1,1,0,0],
                            'Manage materials, supplies & tools' => [1,1,0,0],
                            'Assign equipment to rooms'          => [1,1,0,0],
                            'Assign & schedule tasks in rooms'   => [1,1,0,0],
                        ],
                        'Reservations' => [
                            'Create & approve reservations'      => [1,1,0,0],
                            'Submit room reservation requests'   => [1,1,0,1],
                        ],
                        'Cleaning & Maintenance' => [
                            'View cleaning schedule'             => [1,1,1,0],
                            'Complete (check off) tasks'         => [1,1,1,0],
                            'Submit maintenance requests'        => [1,1,1,1],
                        ],
                        'General' => [
                            'View room profiles'                 => [1,1,1,0],
                            'View facility map'                  => [1,1,1,1],
                        ],
                    ];
                    foreach ($permGroups as $groupName => $groupPerms):
                    ?>
                        <tr class="bg-gray-50">
                            <td colspan="5" class="text-left px-0 pt-4 pb-1">
                                <span class="text-xs font-bold uppercase tracking-wider text-gray-400"><?= $groupName ?></span>
                            </td>
                        </tr>
                        <?php foreach ($groupPerms as $label => $access): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="text-left py-2.5 pr-4 text-gray-700 pl-2"><?= $label ?></td>
                                <?php foreach ($access as $a): ?>
                                    <td class="py-2.5 px-3">
                                        <?php if ($a): ?>
                                            <svg class="w-4 h-4 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        <?php else: ?>
                                            <span class="text-gray-200">—</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach;
                    endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<!-- Add User Modal -->
<div id="addUserModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md">
        <h2 class="text-lg font-bold text-gray-800 mb-4">Add User</h2>
        <form method="POST">
            <input type="hidden" name="type" value="add_user">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Full Name *</label>
                <input type="text" name="name" required placeholder="e.g. John Smith"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Title / Position</label>
                <input type="text" name="title" placeholder="e.g. Lead Pastor"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Email Address *</label>
                <input type="email" name="email" required placeholder="john@yourchurch.org"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Password *</label>
                <input type="password" name="password" required placeholder="Minimum 8 characters"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Role *</label>
                <select name="role" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <option value="staff">Staff — submit requests only</option>
                    <option value="custodial">Custodial — complete & request tasks</option>
                    <option value="scheduler">Scheduler — create events & assign tasks</option>
                    <option value="admin">Admin — full access</option>
                </select>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')"
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 rounded-xl text-sm transition">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl text-sm transition">
                    Add User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md">
        <h2 class="text-lg font-bold text-gray-800 mb-4">Edit User</h2>
        <form method="POST">
            <input type="hidden" name="type" value="edit_user">
            <input type="hidden" name="id" id="edit_id">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Full Name *</label>
                <input type="text" name="name" id="edit_name" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Title / Position</label>
                <input type="text" name="title" id="edit_title" placeholder="e.g. Lead Pastor"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-1">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                <input type="text" id="edit_email" disabled
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 text-gray-400 cursor-not-allowed">
                <p class="text-xs text-gray-400 mt-1">Email cannot be changed here. Delete and re-add user to change email.</p>
            </div>
            <div class="mb-4 mt-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Role *</label>
                <select name="role" id="edit_role"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    <option value="staff">Staff — submit requests only</option>
                    <option value="custodial">Custodial — complete & request tasks</option>
                    <option value="scheduler">Scheduler — create events & assign tasks</option>
                    <option value="admin">Admin — full access</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">New Password <span class="font-normal text-gray-400">(leave blank to keep current)</span></label>
                <input type="password" name="new_password" placeholder="Minimum 8 characters"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-5 flex items-center gap-2">
                <input type="checkbox" name="is_active" id="edit_active" value="1" class="w-4 h-4 rounded text-blue-600">
                <label for="edit_active" class="text-sm font-semibold text-gray-700">Account active</label>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('editUserModal').classList.add('hidden')"
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 rounded-xl text-sm transition">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl text-sm transition">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(user) {
    document.getElementById('edit_id').value    = user.id;
    document.getElementById('edit_name').value  = user.name;
    document.getElementById('edit_title').value = user.title ?? '';
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value  = user.role;
    document.getElementById('edit_active').checked = user.is_active == 1;
    document.getElementById('editUserModal').classList.remove('hidden');
}
</script>

</body>
</html>

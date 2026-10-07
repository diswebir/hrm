<?php
/**
 * نقش‌ها و دسترسی‌ها
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\DB;
use App\Core\Log;

final class RolesController extends Controller
{
    public function index(): void
    {
        $roles = DB::all(
            "SELECT r.*, (SELECT COUNT(*) FROM role_permissions WHERE role_id = r.id) perm_count,
                    (SELECT COUNT(*) FROM users WHERE role_id = r.id) user_count
             FROM roles r ORDER BY r.id"
        );
        $perms = DB::all('SELECT * FROM permissions ORDER BY grp, sort');
        $byRole = [];
        foreach ($roles as $r) {
            $byRole[(int)$r['id']] = array_column(
                DB::all('SELECT permission FROM role_permissions WHERE role_id = ?', [(int)$r['id']]),
                'permission'
            );
        }
        $groups = [];
        foreach ($perms as $p) {
            $groups[$p['grp']][] = $p;
        }
        $this->render('roles/index', [
            'title' => 'نقش‌ها و دسترسی‌ها',
            'roles' => $roles, 'groups' => $groups, 'byRole' => $byRole,
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $role = DB::row('SELECT * FROM roles WHERE id = ?', [(int)$id]);
        if (!$role) {
            flash_set('error', 'نقش یافت نشد.');
            redirect('/roles');
        }
        if ($role['slug'] === 'super-admin' && !Auth::isSuper()) {
            flash_set('error', 'دسترسی نقش «مدیر کل» فقط توسط مدیر کل قابل تغییر است.');
            redirect('/roles');
        }
        $allowed = array_column(DB::all('SELECT slug FROM permissions'), 'slug');
        $chosen = post('perm', []);
        if (!is_array($chosen)) $chosen = [];
        $chosen = array_values(array_intersect($chosen, $allowed));

        // هیچ‌وقت نمی‌توانید دسترسی users.manage را از خودتان بگیرید
        if ((int)$id === (int)Auth::user()['role_id']) {
            $chosen[] = 'users.manage';
            $chosen = array_values(array_unique($chosen));
        }

        DB::begin();
        try {
            DB::run('DELETE FROM role_permissions WHERE role_id = ?', [(int)$id]);
            foreach (array_unique($chosen) as $slug) {
                DB::insert('role_permissions', ['role_id' => (int)$id, 'permission' => $slug]);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            flash_set('error', 'ذخیره ناموفق بود.');
            redirect('/roles');
        }
        Log::info('role_perms', 'به‌روزرسانی دسترسی نقش', ['role' => $role['slug'], 'count' => count($chosen)]);
        flash_set('success', 'دسترسی‌های «' . $role['name'] . '» ذخیره شد (' . to_fa((string)count($chosen)) . ' مورد).');
        redirect('/roles');
    }

    public function store(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $name = trim((string)post('name', ''));
        if ($name === '') {
            flash_set('error', 'نام نقش را وارد کنید.');
            redirect('/roles');
        }
        $slug = strtolower(trim((string)post('slug', '')));
        if (!preg_match('/^[a-z0-9-]{2,32}$/', $slug)) {
            $slug = 'role-' . substr(md5($name), 0, 6);
        }
        if (DB::val('SELECT id FROM roles WHERE slug = ?', [$slug])) {
            flash_set('error', 'این شناسه نقش تکراری است.');
            redirect('/roles');
        }
        DB::insert('roles', [
            'name' => $name, 'slug' => $slug,
            'description' => trim((string)post('description', '')),
            'is_system' => 0, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        Log::info('role_create', 'ایجاد نقش', ['slug' => $slug]);
        flash_set('success', 'نقش ایجاد شد؛ اکنون دسترسی‌های آن را تعیین کنید.');
        redirect('/roles');
    }
}

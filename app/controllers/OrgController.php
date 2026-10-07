<?php
/**
 * واحدهای سازمانی، پست‌ها و چارت
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\DB;
use App\Core\Log;
use App\Core\Validator;

final class OrgController extends Controller
{
    public function index(): void
    {
        $depts = DB::all('SELECT d.*,
                          (SELECT COUNT(*) FROM employees e WHERE e.dept_id = d.id AND e.deleted_at IS NULL AND e.status = "active") AS members,
                          (SELECT COUNT(*) FROM departments c WHERE c.parent_id = d.id) AS children
                          FROM departments d
                          ORDER BY d.name');
        foreach ($depts as &$d) {
            if ($d['manager_id']) {
                $m = DB::row('SELECT first_name, last_name FROM employees WHERE id = ?', [$d['manager_id']]);
                $d['manager_name'] = $m ? trim($m['first_name'] . ' ' . $m['last_name']) : null;
            } else {
                $d['manager_name'] = null;
            }
        }
        unset($d);

        $positions = DB::all('SELECT p.*, d.name AS dept_name,
                              (SELECT COUNT(*) FROM employees e WHERE e.position_id = p.id AND e.deleted_at IS NULL) AS holders
                              FROM positions p LEFT JOIN departments d ON d.id = p.department_id
                              ORDER BY p.level DESC, p.title');
        $employees = DB::all("SELECT id, first_name, last_name FROM employees WHERE deleted_at IS NULL ORDER BY first_name, last_name");

        $this->render('org/index', [
            'title' => 'واحدها و پست‌ها',
            'depts' => $depts, 'positions' => $positions, 'employees' => $employees,
        ]);
    }

    public function saveDept(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $id = (int)post('id', 0);
        $v = Validator::make($_POST)->check('name', 'required|max:120', 'نام واحد');
        if ($v->fails()) {
            flash_set('error', $v->allMessages());
            redirect('/org');
        }
        $data = [
            'name' => post('name'),
            'code' => post('code'),
            'parent_id' => ((int)post('parent_id', 0)) ?: null,
            'manager_id' => ((int)post('manager_id', 0)) ?: null,
            'description' => post('description'),
        ];
        if ($id) {
            unset($data['id']);
            DB::update('departments', $data, $id);
            Log::info('dept_update', 'ویرایش واحد: ' . $data['name']);
            flash_set('success', 'واحد سازمانی به‌روزرسانی شد.');
        } else {
            $newId = DB::insert('departments', $data);
            Log::info('dept_create', 'افزودن واحد: ' . $data['name'], ['id' => $newId]);
            flash_set('success', 'واحد سازمانی افزوده شد.');
        }
        redirect('/org');
    }

    public function deleteDept(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $dept = DB::row('SELECT * FROM departments WHERE id = ?', [(int)$id]);
        if (!$dept) {
            flash_set('error', 'واحد یافت نشد.');
            redirect('/org');
        }
        $members = (int)DB::val('SELECT COUNT(*) FROM employees WHERE dept_id = ? AND deleted_at IS NULL', [(int)$id]);
        if ($members > 0) {
            flash_set('error', 'این واحد دارای ' . to_fa((string)$members) . ' کارمند است؛ ابتدا آن‌ها را منتقل کنید.');
            redirect('/org');
        }
        $children = (int)DB::val('SELECT COUNT(*) FROM departments WHERE parent_id = ?', [(int)$id]);
        if ($children > 0) {
            flash_set('error', 'این واحد دارای زیرواحد است؛ ابتدا زیرواحدها را حذف یا انتقال دهید.');
            redirect('/org');
        }
        DB::delete('departments', (int)$id);
        Log::info('dept_delete', 'حذف واحد: ' . $dept['name']);
        flash_set('success', 'واحد حذف شد.');
        redirect('/org');
    }

    public function savePos(): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $id = (int)post('id', 0);
        $v = Validator::make($_POST)
            ->check('title', 'required|max:120', 'عنوان پست')
            ->check('level', 'required|integer|min:1|max:9', 'سطح');
        if ($v->fails()) {
            flash_set('error', $v->allMessages());
            redirect('/org');
        }
        $data = [
            'title' => post('title'),
            'code' => post('code'),
            'level' => (int)post('level', 1),
            'department_id' => ((int)post('department_id', 0)) ?: null,
            'description' => post('description'),
        ];
        if ($id) {
            DB::update('positions', $data, $id);
            Log::info('pos_update', 'ویرایش پست: ' . $data['title']);
            flash_set('success', 'پست به‌روزرسانی شد.');
        } else {
            DB::insert('positions', $data);
            Log::info('pos_create', 'افزودن پست: ' . $data['title']);
            flash_set('success', 'پست افزوده شد.');
        }
        redirect('/org');
    }

    public function deletePos(string $id): void
    {
        Csrf::verify($_POST['_token'] ?? '');
        $holders = (int)DB::val('SELECT COUNT(*) FROM employees WHERE position_id = ? AND deleted_at IS NULL', [(int)$id]);
        if ($holders > 0) {
            flash_set('error', 'این پست دارای ' . to_fa((string)$holders) . ' دارنده است؛ قابل حذف نیست.');
            redirect('/org');
        }
        DB::delete('positions', (int)$id);
        Log::info('pos_delete', 'حذف پست');
        flash_set('success', 'پست حذف شد.');
        redirect('/org');
    }

    /** نمودار چارت سازمانی */
    public function chart(): void
    {
        $roots = DB::all('SELECT d.*,
                          (SELECT COUNT(*) FROM employees e WHERE e.dept_id = d.id AND e.deleted_at IS NULL AND e.status = "active") members
                          FROM departments d WHERE d.parent_id IS NULL ORDER BY d.name');
        $children = DB::all('SELECT d.*,
                             (SELECT COUNT(*) FROM employees e WHERE e.dept_id = d.id AND e.deleted_at IS NULL AND e.status = "active") members
                             FROM departments d WHERE d.parent_id IS NOT NULL ORDER BY d.name');
        $byParent = [];
        foreach ($children as $c) {
            $byParent[(int)$c['parent_id']][] = $c;
        }
        $managers = [];
        foreach (array_merge($roots, $children) as $d) {
            if ($d['manager_id']) {
                $m = DB::row('SELECT first_name, last_name FROM employees WHERE id = ?', [$d['manager_id']]);
                $managers[(int)$d['id']] = $m ? trim($m['first_name'] . ' ' . $m['last_name']) : null;
            }
        }

        $render = function (array $dept) use (&$render, $byParent, $managers): string {
            $id = (int)$dept['id'];
            $kids = $byParent[$id] ?? [];
            $mgr = $managers[$id] ?? null;
            $html = '<div class="org-col"><div class="org-node' . ($dept['parent_id'] ? '' : ' root') . '">'
                . '<b>' . e($dept['name']) . '</b>'
                . '<span>' . ($mgr ? '👤 ' . e($mgr) : e($dept['code'] ?: '—')) . '</span>'
                . '<span>' . to_fa((string)((int)$dept['members'])) . ' نیرو</span>'
                . '</div>';
            if ($kids) {
                $html .= '<div class="org-children">';
                foreach ($kids as $k) $html .= $render($k);
                $html .= '</div>';
            }
            $html .= '</div>';
            return $html;
        };

        $tree = '';
        foreach ($roots as $r) $tree .= $render($r);

        $this->render('org/chart', [
            'title' => 'چارت سازمانی',
            'tree' => $tree,
        ]);
    }
}

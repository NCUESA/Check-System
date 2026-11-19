<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AdminController extends Controller
{
    // 管理員名單：列出所有人員，可依姓名或學號搜尋、依權限篩選
    public function index(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:255',
            'filter' => 'nullable|in:all,admin,normal',
        ]);

        $q = $request->query('q');
        $filter = $request->query('filter', 'all');

        $users = User::query()
            ->when($q, fn ($query) => $query->where(
                fn ($w) => $w->where('name', 'like', "%{$q}%")
                    ->orWhere('stu_id', 'like', "%{$q}%")
            ))
            ->when($filter === 'admin', fn ($query) => $query->where('is_admin', true))
            ->when($filter === 'normal', fn ($query) => $query->where('is_admin', false))
            ->orderByDesc('is_admin')
            ->orderBy('stu_id', 'asc')
            ->get(['id', 'name', 'stu_id', 'status', 'is_admin']);

        return Inertia::render('System/Admins', [
            'users' => $users,
            'filters' => ['q' => $q, 'filter' => $filter],
            'adminCount' => User::where('is_admin', true)->count(),
        ]);
    }

    // 授予或移除管理員權限
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'is_admin' => 'required|boolean',
        ]);

        // 不能變更自己的權限
        // 否則唯一的管理員把自己降級之後，就再也沒有人能進到這頁了
        if ($user->id === $request->user()->id) {
            throw ValidationException::withMessages([
                'is_admin' => '不能變更自己的管理員權限，請由其他管理員操作。',
            ]);
        }

        $user->update(['is_admin' => $validated['is_admin']]);

        return redirect()->back();
    }
}

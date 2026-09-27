<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\Auditing\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('admin.users.index', [
            'users' => User::query()->latest('id')->paginate(15)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.users.create', [
            'roles' => UserRole::options(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $request): void {
            $user = User::query()->create(Arr::only($data, ['name', 'email', 'password', 'role', 'is_active']));

            $this->auditLogger->record(
                'user.created',
                $user,
                [],
                $user->only(['name', 'email', 'role', 'is_active']),
                $request->user(),
            );
        }, attempts: 3);

        return redirect()->route('admin.users.index')->with('status', 'کاربر با موفقیت ایجاد شد.');
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('admin.users.edit', [
            'editedUser' => $user,
            'roles' => UserRole::options(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $request, $user): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $removesActiveManager = $lockedUser->role === UserRole::Manager
                && $lockedUser->is_active
                && ($data['role'] !== UserRole::Manager->value || ! $data['is_active']);

            if ($removesActiveManager
                && User::query()->where('role', UserRole::Manager->value)->where('is_active', true)->count() <= 1) {
                throw ValidationException::withMessages([
                    'role' => 'حداقل یک مدیر فعال باید در سامانه باقی بماند.',
                ]);
            }

            $before = $lockedUser->only(['name', 'email', 'role', 'is_active']);
            $attributes = Arr::only($data, ['name', 'email', 'role', 'is_active']);

            if (filled($data['password'] ?? null)) {
                $attributes['password'] = $data['password'];
            }

            $lockedUser->update($attributes);
            $this->auditLogger->record(
                'user.updated',
                $lockedUser,
                $before,
                $lockedUser->only(['name', 'email', 'role', 'is_active']),
                $request->user(),
            );
        }, attempts: 3);

        return redirect()->route('admin.users.index')->with('status', 'اطلاعات کاربر به‌روزرسانی شد.');
    }
}

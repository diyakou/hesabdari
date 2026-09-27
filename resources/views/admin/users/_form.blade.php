@php
    $editing = isset($editedUser);
    $selectedRole = old('role', $editing ? $editedUser->role->value : App\Enums\UserRole::Salesperson->value);
    $active = (bool) old('is_active', $editing ? $editedUser->is_active : true);
@endphp

<form method="POST" action="{{ $editing ? route('admin.users.update', $editedUser) : route('admin.users.store') }}" class="space-y-6" novalidate>
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="name" class="form-label">نام و نام خانوادگی</label>
            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $editing ? $editedUser->name : '') }}"
                class="form-input"
                autocomplete="name"
                maxlength="120"
                required
                autofocus
                @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
            >
            <x-field-error name="name" />
        </div>

        <div>
            <label for="email" class="form-label">ایمیل</label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email', $editing ? $editedUser->email : '') }}"
                class="form-input text-left"
                dir="ltr"
                autocomplete="username"
                inputmode="email"
                maxlength="255"
                required
                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
            >
            <x-field-error name="email" />
        </div>

        <div>
            <label for="role" class="form-label">نقش کاربری</label>
            <select id="role" name="role" class="form-input" required @error('role') aria-invalid="true" aria-describedby="role-error" @enderror>
                @foreach ($roles as $value => $label)
                    <option value="{{ $value }}" @selected($selectedRole === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-field-error name="role" />
        </div>

        <div class="sm:row-span-2">
            <label for="password" class="form-label">{{ $editing ? 'رمز عبور جدید' : 'رمز عبور' }}</label>
            <input
                id="password"
                name="password"
                type="password"
                class="form-input text-left"
                dir="ltr"
                autocomplete="new-password"
                {{ $editing ? '' : 'required' }}
                aria-describedby="password-help @error('password') password-error @enderror"
                @error('password') aria-invalid="true" @enderror
            >
            <p id="password-help" class="form-help">حداقل ۱۰ نویسه و شامل حرف و عدد باشد. {{ $editing ? 'برای حفظ رمز فعلی، این قسمت را خالی بگذارید.' : '' }}</p>
            <x-field-error name="password" />
        </div>

        <div>
            <label class="form-label">وضعیت حساب</label>
            <input type="hidden" name="is_active" value="0">
            <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-700">
                <input type="checkbox" name="is_active" value="1" @checked($active) class="size-4 rounded border-slate-300 text-teal-700 focus:ring-teal-600">
                حساب فعال باشد
            </label>
            <x-field-error name="is_active" />
        </div>

        <div>
            <label for="password_confirmation" class="form-label">تکرار رمز عبور</label>
            <input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                class="form-input text-left"
                dir="ltr"
                autocomplete="new-password"
                {{ $editing ? '' : 'required' }}
            >
        </div>
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.users.index') }}" class="button-secondary">انصراف</a>
        <button type="submit" class="button-primary">{{ $editing ? 'ذخیره تغییرات' : 'ایجاد کاربر' }}</button>
    </div>
</form>

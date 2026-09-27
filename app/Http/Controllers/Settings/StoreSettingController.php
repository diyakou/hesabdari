<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdateStoreSetup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateStoreSettingsRequest;
use App\Models\Branch;
use App\Models\StoreSetting;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StoreSettingController extends Controller
{
    public function edit(): View
    {
        Gate::authorize('manage-settings');

        return view('settings.edit', [
            'store' => StoreSetting::query()->where('key', 'primary')->first(),
            'branch' => Branch::query()->where('code', 'MAIN')->first(),
            'warehouse' => Warehouse::query()->where('code', 'MAIN-WH')->first(),
        ]);
    }

    public function update(UpdateStoreSettingsRequest $request, UpdateStoreSetup $action): RedirectResponse
    {
        $action->handle($request->validated(), $request->user());

        return redirect()->route('settings.edit')->with('status', 'تنظیمات پایه فروشگاه ذخیره شد.');
    }
}

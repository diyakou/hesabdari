<?php

namespace App\Http\Controllers;

use App\Models\ServiceDefinition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ServiceDefinitionController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-settings');
        return view('service-definitions.index', ['definitions' => ServiceDefinition::withCount('orders')->orderBy('category')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        Gate::authorize('manage-settings');
        return view('service-definitions.form', ['definition' => new ServiceDefinition]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-settings');
        $data = $this->validated($request);
        $definition = ServiceDefinition::create($data + ['code' => 'custom_'.Str::lower(Str::random(12)), 'is_active' => true]);
        $definition->formVersions()->create(['version' => 1, 'fields_schema' => $this->schema($data['category'])]);
        return redirect()->route('service-definitions.index')->with('status', 'خدمت جدید با موفقیت تعریف شد.');
    }

    public function edit(ServiceDefinition $serviceDefinition): View
    {
        Gate::authorize('manage-settings');
        return view('service-definitions.form', ['definition' => $serviceDefinition]);
    }

    public function update(Request $request, ServiceDefinition $serviceDefinition): RedirectResponse
    {
        Gate::authorize('manage-settings');
        $serviceDefinition->update($this->validated($request));
        return redirect()->route('service-definitions.index')->with('status', 'خدمت به‌روزرسانی شد.');
    }

    public function destroy(ServiceDefinition $serviceDefinition): RedirectResponse
    {
        Gate::authorize('manage-settings');
        $serviceDefinition->update(['is_active' => ! $serviceDefinition->is_active]);
        return back()->with('status', $serviceDefinition->is_active ? 'خدمت دوباره فعال شد.' : 'خدمت غیرفعال شد؛ سوابق قبلی حفظ شدند.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate(['name' => ['required','string','max:150'], 'category' => ['required','in:software,hardware'], 'description' => ['nullable','string','max:1000'], 'default_fee_toman' => ['nullable','numeric','min:0']]);
        $data['default_fee_rials'] = (int) (($data['default_fee_toman'] ?? 0) * 10);
        unset($data['default_fee_toman']);
        return $data;
    }

    private function schema(string $category): array
    {
        return [
            ['name'=>'device_model','label'=>'برند و مدل دستگاه','type'=>'text','required'=>true],
            ['name'=>'imei','label'=>'IMEI / شماره سریال','type'=>'text','required'=>false],
            ['name'=>'reported_issue','label'=>$category === 'hardware' ? 'شرح ایراد دستگاه' : 'شرح درخواست مشتری','type'=>'textarea','required'=>true],
            ['name'=>'appearance','label'=>'وضعیت ظاهری هنگام پذیرش','type'=>'checklist','required'=>false,'options'=>['خط و خش','شکستگی','مشکل تاچ','خمیدگی','آب‌خوردگی','خاموش تحویل شد']],
            ['name'=>'estimated_duration','label'=>'زمان تقریبی تحویل','type'=>'select','required'=>false,'options'=>['همان روز','۱ تا ۲ روز کاری','۳ تا ۵ روز کاری','پس از تأمین قطعه']],
        ];
    }
}

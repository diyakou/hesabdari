<?php

namespace App\Http\Middleware;

use App\Support\Localization\PersianDate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeJalaliDates
{
    private const DATE_FIELDS = ['date', 'issue_date', 'promised_date', 'due_date', 'start_date', 'end_date'];

    public function handle(Request $request, Closure $next): Response
    {
        $request->merge($this->normalize($request->all()));

        return $next($request);
    }

    private function normalize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->normalize($value);
            } elseif (in_array($key, self::DATE_FIELDS, true) && is_string($value) && trim($value) !== '') {
                try {
                    $data[$key] = PersianDate::toGregorian($value);
                } catch (\InvalidArgumentException) {
                    // Keep invalid input so Laravel's date validation reports it normally.
                }
            }
        }

        return $data;
    }
}

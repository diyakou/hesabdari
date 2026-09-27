<?php

namespace App\Http\Middleware;

use App\Support\Localization\LocalizedDigits;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeLocalizedNumbers
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->merge($this->normalize($request->all()));

        return $next($request);
    }

    private function normalize(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $item) => $this->normalize($item), $value);
        }

        if (! is_string($value)) {
            return $value;
        }

        $ascii = LocalizedDigits::toAscii($value);

        return preg_match('/^-?[\d,٬]+(?:\.\d+)?$/u', $ascii)
            ? str_replace([',', '٬'], '', $ascii)
            : $ascii;
    }
}

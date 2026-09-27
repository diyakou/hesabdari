<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'key',
    'name',
    'legal_name',
    'phone',
    'address',
    'timezone',
    'storage_currency',
    'display_currency',
])]
class StoreSetting extends Model {}

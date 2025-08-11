<?php

namespace App\Models;

use App\Traits\HasUuid;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    /** @use HasFactory<\Database\Factories\CustomFieldFactory> */
    use HasFactory;
}

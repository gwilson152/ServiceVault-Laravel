<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasUuid
{
    /**
     * Boot the HasUuid trait for a model.
     */
    protected static function bootHasUuid(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Initialize the HasUuid trait for an instance.
     */
    public function initializeHasUuid(): void
    {
        $this->keyType = 'string';
        $this->incrementing = false;
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * Get the auto-incrementing key type.
     */
    public function getKeyType(): string
    {
        return 'string';
    }
}

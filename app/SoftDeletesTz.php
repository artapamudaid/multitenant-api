<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

trait SoftDeletesTz
{
    use SoftDeletes {
        SoftDeletes::trashed as parentTrashed;
        SoftDeletes::restore as parentRestore;
        SoftDeletes::runSoftDelete as parentRunSoftDelete;
    }

    /**
     * Override soft delete agar mendukung timestamptz.
     */
    protected function runSoftDelete()
    {
        $query = $this->setKeysForSaveQuery($this->newModelQuery());

        $time = $this->freshTimestamp(); // Ini akan menyimpan dengan timezone Laravel default

        $this->{$this->getDeletedAtColumn()} = $time;

        $query->update([
            $this->getDeletedAtColumn() => $this->fromDateTime($time),
        ]);
    }

    /**
     * Override agar SoftDeletes aware terhadap timezone.
     */
    public function getDeletedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone(config('app.timezone')) : null;
    }
}

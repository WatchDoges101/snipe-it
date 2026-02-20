<?php

namespace App\Models;

use App\Models\Traits\CompanyableTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Watson\Validating\ValidatingTrait;

class ConsumableAssignment extends Model
{
    use CompanyableTrait;
    use ValidatingTrait;

    protected $table = 'consumables_users';

    protected $fillable = [
        'consumable_id',
        'created_by',
        'assigned_to',
        'assigned_type',
        'note',
    ];

    public $rules = [
        'assigned_to'        => 'required|numeric',
        'assigned_type'      => 'required|string',
    ];

    public function consumable()
    {
        return $this->belongsTo(\App\Models\Consumable::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    public function asset()
    {
        return $this->belongsTo(\App\Models\Asset::class, 'assigned_to');
    }

    public function assignedTo()
    {
        return $this->morphTo('assigned', 'assigned_type', 'assigned_to')->withTrashed();
    }

    public function scopeUserAssigned(Builder $query): void
    {
        $query->where('assigned_type', '=', User::class);
    }

    public function scopeAssetsAssigned(Builder $query): void
    {
        $query->where('assigned_type', '=', Asset::class);
    }

    public function adminuser()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}

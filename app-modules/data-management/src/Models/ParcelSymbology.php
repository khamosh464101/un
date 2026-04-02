<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ParcelSymbology extends Model
{

    protected $table = 'dm_parcel_symbologies';
    protected $fillable = [
        'name',
        'description',
        'project_id',
        'query_structure',
        'created_by'
    ];

    protected $casts = [
        'query_structure' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationship with user
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scope for filtering by project
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    // Scope for filtering by user
    public function scopeForUser($query, $userId)
    {
        return $query->where('created_by', $userId);
    }
}

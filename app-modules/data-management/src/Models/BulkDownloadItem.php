<?php

namespace Modules\DataManagement\Models;


use Illuminate\Database\Eloquent\Model;

class BulkDownloadItem extends Model
{
    protected $table = 'dm_bulk_download_items';
    
    protected $fillable = [
        'batch_id', 'submission_id', 'status', 'progress',
        'file_name', 'file_path', 'error_message', 'metadata',
        'started_at', 'completed_at'
    ];
    
    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];
    
    public function batch()
    {
        return $this->belongsTo(BulkDownloadBatch::class, 'batch_id', 'batch_id');
    }
    
    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }
}
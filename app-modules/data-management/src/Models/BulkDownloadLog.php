<?php

namespace Modules\DataManagement\Models;


use Illuminate\Database\Eloquent\Model;

class BulkDownloadLog extends Model
{
    protected $table = 'dm_bulk_download_logs';
    
    protected $fillable = [
        'batch_id', 'submission_id', 'level', 'message', 'context'
    ];
    
    protected $casts = [
        'context' => 'array'
    ];
    
    public function batch()
    {
        return $this->belongsTo(BulkDownloadBatch::class, 'batch_id', 'batch_id');
    }
}

<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Projects\Models\Project;

class ImportFormatFile extends Model
{
    protected $table = 'dm_import_format_files';

    protected $fillable = [
        'project_id',
        'excel_file_path'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }


}

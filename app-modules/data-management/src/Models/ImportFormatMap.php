<?php

namespace Modules\DataManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Projects\Models\Project;

class ImportFormatMap extends Model
{
    protected $table = "dm_import_format_maps";
    protected $fillable = [
        'project_id',
        'kobo_form_field_name',
        'excel_file_column_name'
    ];
 

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

}

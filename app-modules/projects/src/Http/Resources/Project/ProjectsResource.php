<?php

namespace Modules\Projects\Http\Resources\Project;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public static $wrap = null;
    public function toArray($request)
    {
        // Merge all attributes and add the specific column `progress`
        return array_merge(
            parent::toArray($request), // All model attributes
            [
                'progress' => $this->getProgress() // Add the custom progress field
            ]
        );
    }
}

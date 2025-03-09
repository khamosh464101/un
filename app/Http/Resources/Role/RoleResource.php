<?php

namespace App\Http\Resources\Role;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Permission;

class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tmp = array();
        $permissions = Permission::all();

        foreach ($permissions as $key => $value) {
            array_push($tmp, [
                "id" => $value->id, 
                "name" => $value->name, 
                'description' => $value->description,
                "checked" => $this->hasPermissionTo($value->name) ? true : false 
            ]);
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'permissions' => $tmp,
        ];
    }
}

<?php

namespace Modules\DataManagement\Http\Controllers;

use Illuminate\Http\Request;
use Modules\DataManagement\Models\ImportFormatMap;
use Modules\DataManagement\Http\Requests\ConnectionRequest;

class MapController
{
    public function store(ConnectionRequest $request) {
        $data = $request->validated();

        $connection = ImportFormatMap::create($data);
        return response()->json($connection, 201);

    }

    public function update(Request $request, $id) {

    }

    public function destroy($id) {
        $connection = ImportFormatMap::find($id);
        if (!$connection) {
            return response()->json(['message' => 'Connection not found'], 404);
        }

        $connection->delete();
        return response()->json(['message' => 'Connection deleted successfully'], 200);
    }
}

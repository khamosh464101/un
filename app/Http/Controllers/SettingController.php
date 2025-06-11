<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    public function index() {
        return response()->json(Setting::all(), 201);
    }

    public function store(Request $request)
{
    foreach ($request->settings as $key => $value) {
        $setting = Setting::find($value['id']);

        if ($value['type'] === 'file' && $request->file("settings.$key.value")) {
             $file = $request->file("settings.$key.value");
            $uuidPrefix = Str::uuid();
            $path = $file->storeAs(
                'settings',
                $this->getFileName($file)
            );
            $setting->value = $path;
        } else {
            $setting->value = $value['value'];
            $setting->save();
        }
    }
}

}

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
        $files = [
            'firebase_admin_credentials',
            'google_application_credentials',
        ];
        $data = $request->except($files);
        foreach ($data as $key => $value) {
            $setting = Setting::where('key', $key)->first();

                $setting->value = $value;
                $setting->save();
            
        }
        foreach ($files as $key => $value) {   
            if ($request->hasFile($value) && $request->file($value)->isValid()) {
                $setting = Setting::where('key', $value)->first();
                $get_file = $request->file($value)->storeAs('settings', $request->file($value)->getClientOriginalName());
                $setting->value = $get_file;
                $setting->save();
            }
        }

        return response()->json(["message" => "Successfully added!"], 201);

    }

}

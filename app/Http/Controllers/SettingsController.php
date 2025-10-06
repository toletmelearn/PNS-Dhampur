<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Display the settings page
     */
    public function index()
    {
        $settings = SystemSetting::orderBy('category')->orderBy('sort_order')->get()->groupBy('category');
        
        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update system settings
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $settings = $request->input('settings', []);
        
        foreach ($settings as $key => $value) {
            SystemSetting::set($key, $value);
        }
        
        // Clear cache to ensure updated settings are loaded
        Cache::forget('system_settings');
        
        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}
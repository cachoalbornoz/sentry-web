<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingsPageController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('admin.settings', [
            'user' => $request->session()->get('api_user'),
        ]);
    }
}

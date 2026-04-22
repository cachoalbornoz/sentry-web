<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CrudHubController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('admin.crud-hub', [
            'user' => $request->session()->get('api_user'),
        ]);
    }
}

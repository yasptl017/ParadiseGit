<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuVisibility;

class MenuVisibilityController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index()
    {
        $menus = MenuVisibility::orderBy('id')->get();

        return view('admin.menu_visibility', compact('menus'));
    }

    public function update($id)
    {
        $menu = MenuVisibility::findOrFail($id);
        $menu->status = $menu->status ? 0 : 1;
        $menu->save();

        return response()->json(trans('admin_validation.Update Successfully'));
    }
}

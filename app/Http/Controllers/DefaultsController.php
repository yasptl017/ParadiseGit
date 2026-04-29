<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class DefaultsController extends Controller
{
    public function updateDefaults(Request $request)
    {
        $request->validate([
            'default_discount' => 'required|numeric',
            'free_delivery_threshold' => 'required|numeric',
            'maximum_distance' => 'nullable|numeric',
            'minimum_order_amount' => 'required|numeric',
            'order_delete_password' => 'nullable|string|max:255',
        ]);

        $setting = Setting::first();

        if (!$setting) {
            $notification = array('messege' => 'Settings row not found.', 'alert-type' => 'error');

            return redirect()->back()->with($notification);
        }

        $setting->default_discount = $request->input('default_discount');
        $setting->default_delivery = $request->input('free_delivery_threshold');
        $setting->maximum_distance = $request->input('maximum_distance', 0);
        $setting->minimum_order_amount = $request->input('minimum_order_amount');

        if ($request->filled('order_delete_password')) {
            $setting->order_delete_password = $request->input('order_delete_password');
        }

        $setting->save();

        return redirect()->back()->with('success', 'Default values updated successfully.');
    }
}

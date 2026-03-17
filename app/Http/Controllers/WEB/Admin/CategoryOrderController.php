<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CategoryOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index()
    {
        $homeCategories    = Category::where('show_homepage', 1)->where('status', 1)
            ->orderBy('home_sort_order')->orderBy('id')->get();
        $posCategories     = Category::where('status', 1)
            ->orderBy('pos_sort_order')->orderBy('id')->get();
        $receiptCategories = Category::where('status', 1)
            ->orderBy('receipt_sort_order')->orderBy('id')->get();

        return view('admin.category_order', compact('homeCategories', 'posCategories', 'receiptCategories'));
    }

    public function save(Request $request)
    {
        $context = $request->input('context');
        $column  = match ($context) {
            'home'    => 'home_sort_order',
            'pos'     => 'pos_sort_order',
            'receipt' => 'receipt_sort_order',
            default   => null,
        };

        if (!$column) {
            return response()->json(['error' => 'Invalid context'], 422);
        }

        foreach ($request->input('order', []) as $position => $id) {
            Category::where('id', (int) $id)->update([$column => $position + 1]);
        }

        return response()->json(['message' => 'Order saved successfully']);
    }

    public function updateReceiptVisibility(Request $request, Category $category)
    {
        if (!Schema::hasColumn('categories', 'show_on_place_order_receipt')) {
            return response()->json([
                'message' => 'Please run the latest migration before updating receipt visibility.',
            ], 503);
        }

        $validated = $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $category->show_on_place_order_receipt = (bool) $validated['enabled'];
        $category->save();

        return response()->json([
            'message' => $category->show_on_place_order_receipt
                ? 'Category will appear on place-order receipts'
                : 'Category will be hidden from place-order receipts',
            'enabled' => $category->show_on_place_order_receipt,
        ]);
    }
}

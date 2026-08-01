<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Setting;
class CouponController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(){
        // Regular coupons only - first time customer offers and Buy & Get
        // offers live on their own pages.
        $coupons = Coupon::where(function($query){
                $query->where('first_time_basis', Coupon::FIRST_TIME_NONE)
                    ->orWhereNull('first_time_basis');
            })
            ->where(function($query){
                $query->where('offer_kind', Coupon::KIND_COUPON)
                    ->orWhereNull('offer_kind');
            })
            ->orderBy('id','desc')->get();
        $setting = Setting::first();

        return view('admin.coupon', compact('coupons','setting'));
    }

    public function firstTimeUsers(){
        $coupons = Coupon::whereNotNull('first_time_basis')
            ->where('first_time_basis', '!=', Coupon::FIRST_TIME_NONE)
            ->where(function($query){
                $query->where('offer_kind', Coupon::KIND_COUPON)
                    ->orWhereNull('offer_kind');
            })
            ->orderBy('id','desc')->get();
        $setting = Setting::first();

        return view('admin.first_time_users', compact('coupons','setting'));
    }

    public function buyGetDiscount(){
        $coupons = Coupon::where('offer_kind', Coupon::KIND_BUY_GET_DISCOUNT)
            ->orderBy('id','desc')->get();
        $setting = Setting::first();

        return view('admin.buy_get_discount', compact('coupons','setting'));
    }

    public function buyGetFreeProduct(){
        $coupons = Coupon::where('offer_kind', Coupon::KIND_BUY_GET_FREE_PRODUCT)
            ->orderBy('id','desc')->get();
        $setting = Setting::first();
        $products = Product::where('status', 1)->orderBy('name')->get();

        return view('admin.buy_get_free_product', compact('coupons','setting','products'));
    }

    public function store(Request $request){
        $offerKind = $request->offer_kind ?: Coupon::KIND_COUPON;
        $isFreeProduct = $offerKind === Coupon::KIND_BUY_GET_FREE_PRODUCT;

        $rules = [
            'name'=>'required',
            'code'=>'required|unique:coupons',
            'number_of_time'=>'required|numeric',
            'min_purchase_price'=>'required|numeric',
            'status'=>'required',
            'expired_date'=>'required',
            'first_time_basis'=>'nullable|in:'.implode(',', array_keys(Coupon::FIRST_TIME_OPTIONS)),
            'order_types'=>'nullable|array',
            'order_types.*'=>'in:'.implode(',', array_keys(Coupon::ORDER_TYPE_OPTIONS)),
            'auto_apply'=>'nullable|boolean',
            'offer_kind'=>'nullable|in:'.implode(',', array_keys(Coupon::KIND_OPTIONS)),
            'max_discount'=>'nullable|numeric|min:0',
        ];

        if ($isFreeProduct) {
            $rules['gift_product_id'] = 'required|exists:products,id';
            $rules['gift_qty'] = 'nullable|integer|min:1';
        } else {
            $rules['offer_type'] = 'required';
            $rules['discount'] = 'required|numeric';
        }

        $customMessages = [
            'code.required' => trans('admin_validation.Code is required'),
            'code.unique' => trans('admin_validation.Code already exist'),
            'name.required' => trans('admin_validation.Name is required'),
            'number_of_time.required' => trans('admin_validation.Number of time is required'),
            'min_purchase_price.required' => trans('admin_validation.Minimum price is required'),
            'offer_type.required' => trans('admin_validation.Offer type is required'),
            'discount.required' => trans('admin_validation.Discount is required'),
            'status.required' => trans('admin_validation.Status is required'),
            'expired_date.required' => trans('admin_validation.Expired date is required'),
            'gift_product_id.required' => 'Please select the free product',
            'gift_product_id.exists' => 'The selected product is invalid',
        ];
        $this->validate($request, $rules,$customMessages);

        $coupon = new Coupon();
        $coupon->name = $request->name;
        $coupon->code = $request->code;
        $coupon->max_quantity = $request->number_of_time;
        $coupon->min_purchase_price = $request->min_purchase_price;
        $coupon->expired_date = $request->expired_date;
        $coupon->status = $request->status;
        $coupon->first_time_basis = $request->first_time_basis ?: Coupon::FIRST_TIME_NONE;
        $coupon->order_types = $request->order_types ? array_values($request->order_types) : null;
        $coupon->offer_kind = $offerKind;

        if ($isFreeProduct) {
            // A free product offer has no percentage/amount discount of its own.
            $coupon->offer_type = 2;
            $coupon->discount = 0;
            $coupon->gift_product_id = $request->gift_product_id;
            $coupon->gift_qty = $request->gift_qty ?: 1;
            $coupon->auto_apply = true;
        } else {
            $coupon->offer_type = $request->offer_type;
            $coupon->discount = $request->discount;
            $coupon->max_discount = $request->max_discount ?: null;
            // A Buy & Get discount always auto-applies once the spend threshold is met.
            $coupon->auto_apply = $offerKind === Coupon::KIND_BUY_GET_DISCOUNT ? true : $request->boolean('auto_apply');
        }

        $coupon->save();

        $notification=trans('admin_validation.Created Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

    public function update(Request $request, $id){
        $coupon = Coupon::find($id);
        $offerKind = $request->offer_kind ?: ($coupon->offer_kind ?: Coupon::KIND_COUPON);
        $isFreeProduct = $offerKind === Coupon::KIND_BUY_GET_FREE_PRODUCT;

        $rules = [
            'name'=>'required',
            'code'=>'required|unique:coupons,code,'.$id,
            'number_of_time'=>'required|numeric',
            'min_purchase_price'=>'required|numeric',
            'status'=>'required',
            'expired_date'=>'required',
            'first_time_basis'=>'nullable|in:'.implode(',', array_keys(Coupon::FIRST_TIME_OPTIONS)),
            'order_types'=>'nullable|array',
            'order_types.*'=>'in:'.implode(',', array_keys(Coupon::ORDER_TYPE_OPTIONS)),
            'auto_apply'=>'nullable|boolean',
            'max_discount'=>'nullable|numeric|min:0',
        ];

        if ($isFreeProduct) {
            $rules['gift_product_id'] = 'required|exists:products,id';
            $rules['gift_qty'] = 'nullable|integer|min:1';
        } else {
            $rules['offer_type'] = 'required';
            $rules['discount'] = 'required|numeric';
        }

        $customMessages = [
            'code.required' => trans('admin_validation.Code is required'),
            'code.unique' => trans('admin_validation.Code already exist'),
            'name.required' => trans('admin_validation.Name is required'),
            'number_of_time.required' => trans('admin_validation.Number of time is required'),
            'min_purchase_price.required' => trans('admin_validation.Minimum price is required'),
            'offer_type.required' => trans('admin_validation.Offer type is required'),
            'discount.required' => trans('admin_validation.Discount is required'),
            'status.required' => trans('admin_validation.Status is required'),
            'expired_date.required' => trans('admin_validation.Expired date is required'),
            'gift_product_id.required' => 'Please select the free product',
            'gift_product_id.exists' => 'The selected product is invalid',
        ];
        $this->validate($request, $rules,$customMessages);

        $coupon->name = $request->name;
        $coupon->code = $request->code;
        $coupon->max_quantity = $request->number_of_time;
        $coupon->min_purchase_price = $request->min_purchase_price;
        $coupon->expired_date = $request->expired_date;
        $coupon->status = $request->status;

        if ($isFreeProduct) {
            $coupon->offer_type = 2;
            $coupon->discount = 0;
            $coupon->gift_product_id = $request->gift_product_id;
            $coupon->gift_qty = $request->gift_qty ?: 1;
            $coupon->auto_apply = true;
        } else {
            $coupon->offer_type = $request->offer_type;
            $coupon->discount = $request->discount;
            $coupon->max_discount = $request->max_discount ?: null;
            $coupon->auto_apply = $offerKind === Coupon::KIND_BUY_GET_DISCOUNT ? true : $request->boolean('auto_apply');
        }

        // Only touch the shared offer fields when the submitting form
        // contains them (the legacy Ecommerce > Coupon page does not).
        if ($request->has('first_time_basis') || $request->has('order_types')) {
            $coupon->first_time_basis = $request->first_time_basis ?: Coupon::FIRST_TIME_NONE;
            $coupon->order_types = $request->order_types ? array_values($request->order_types) : null;
        }
        $coupon->save();

        $notification=trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

    public function show($id){
        $coupon = Coupon::find($id);
        return response()->json(['coupon' => $coupon], 200);
    }

    public function destroy($id){
        $coupon = Coupon::find($id);
        $coupon->delete();
        $notification=trans('admin_validation.Delete Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

    public function changeStatus($id){
        $coupon = Coupon::find($id);
        if($coupon->status == 1){
            $coupon->status = 0;
            $coupon->save();
            $message =  trans('admin_validation.Inactive Successfully');
        }else{
            $coupon->status = 1;
            $coupon->save();
            $message = trans('admin_validation.Active Successfully');
        }
        return response()->json($message);
    }

}

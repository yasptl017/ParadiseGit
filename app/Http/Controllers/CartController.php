<?php

namespace App\Http\Controllers;

use App\Models\BannerImage;
use App\Models\BreadcrumbImage;
use App\Models\Coupon;
use App\Models\OrderControl;
use App\Models\Product;
use Auth;
use Cart;
use Illuminate\Http\Request;
use Session;

class CartController extends Controller
{

    public function cart()
    {
        Session::forget('coupon_price');
        Session::forget('offer_type');
        Session::forget('coupon_name');
        Session::forget('coupon_auto');

        $user = Auth::guard('web')->user();
        Coupon::syncGiftInCart($this->cartSubTotal(), Session::get('order_type'), $user->email ?? null, $user->phone ?? null, false);

        $cart_contents = Cart::content();
        $cart_banner = BannerImage::where('id', 5)->first();
        $orderControl = OrderControl::first() ?? new OrderControl(['pickup_enabled' => 1, 'delivery_enabled' => 1]);
        return view('cart')->with(['cart_contents' => $cart_contents, 'cart_banner' => $cart_banner, 'orderControl' => $orderControl]);
    }

    public function add_to_cart(Request $request)
    {
        $product = Product::find($request->product_id);

        $optional_items = array();
        $optional_item_price = 0;
        if ($request->optional_items) {
            foreach ($request->optional_items as $index => $optional_item) {
                $arr = explode('(::)', $request->optional_items[$index]);
                $single_item = array(
                    'optional_name' => $arr[0],
                    'optional_price' => $arr[1]
                );
                $optional_items[] = $single_item;
                $optional_item_price += $arr[1];
            }
        }

        // Split variant array correctly
        $variant_array = explode('(::)', $request->size_variant);
        $size_variant = isset($variant_array[0]) ? $variant_array[0] : null;
        $variant_price = isset($variant_array[1]) ? $variant_array[1] : $product->price;

        $cart_contents = Cart::content();
        $item_exist = false;

        foreach ($cart_contents as $index => $cart_content) {
            if ($cart_content->id == $request->product_id) {
                if ($cart_content->options->size == $size_variant) {
                    $item_exist = true;
                }
            }
        }

        if ($item_exist) {
            $notification = trans('user_validation.Item already added');
            return response()->json(['message' => $notification], 403);
        }
        session()->forget('coupon_price');

        $data = array();
        $data['id'] = $product->id;
        $data['name'] = $product->name;
        $data['qty'] = $request->qty;
        $data['price'] = $variant_price;
        $data['weight'] = 1;
        $data['options']['image'] = $product->thumb_image;
        $data['options']['slug'] = $product->slug;
        $data['options']['size'] = $size_variant; // Use the actual size variant or null
        $data['options']['size_price'] = $variant_price;
        $data['options']['optional_items'] = $optional_items;
        $data['options']['optional_item_price'] = $optional_item_price;
        Cart::add($data);

        $this->resyncGift();

        return response()->json(['message' => 'success']);
    }


    public function cart_quantity_update(Request $request)
    {
        $item = Cart::get($request->rowid);
        if ($item && !empty($item->options['is_gift'])) {
            // The free gift's quantity is controlled by the offer, not the customer.
            $notification = trans('user_validation.Item updated successfully');
            return response()->json(['message' => $notification]);
        }

        Cart::update($request->rowid, ['qty' => $request->quantity]);
        $this->resyncGift();

        $notification = trans('user_validation.Item updated successfully');
        return response()->json(['message' => $notification]);

    }

    public function remove_cart_item($rowId)
    {
        $item = Cart::get($rowId);
        if ($item && !empty($item->options['is_gift'])) {
            // The free gift disappears on its own once the order no longer qualifies.
            $notification = trans('user_validation.Remove successfully');
            return response()->json(['message' => $notification]);
        }

        Cart::remove($rowId);
        $this->resyncGift();

        $notification = trans('user_validation.Remove successfully');
        return response()->json(['message' => $notification]);
    }

    public function cart_clear()
    {
        Cart::destroy();
        Session::forget('coupon_price');
        Session::forget('offer_type');
        Session::forget('coupon_name');
        Session::forget('coupon_auto');

        $notification = trans('user_validation.Cart clear successfully');
        return response()->json(['message' => $notification]);
    }

    protected function resyncGift()
    {
        $user = Auth::guard('web')->user();
        Coupon::syncGiftInCart($this->cartSubTotal(), Session::get('order_type'), $user->email ?? null, $user->phone ?? null, false);
    }


    public function load_cart_item()
    {
        return view('mini_single_item');
    }

    protected function cartSubTotal()
    {
        $sub_total = 0;
        foreach (Cart::content() as $cart_content) {
            if (!empty($cart_content->options['is_gift'])) {
                continue; // free gift item never counts towards spend thresholds
            }
            $item_price = $cart_content->price * $cart_content->qty;
            $sub_total += $item_price + $cart_content->options->optional_item_price;
        }

        return $sub_total;
    }

    /**
     * Validate a coupon code for the current cart / visitor.
     * Returns [coupon, null] on success or [null, error message] on failure.
     */
    protected function resolveCoupon($code)
    {
        $coupon = Coupon::where(['code' => $code, 'status' => 1])->first();

        if (!$coupon) {
            return [null, trans('user_validation.Invalid Coupon')];
        }

        $user = Auth::guard('web')->user();

        // Guests are validated leniently here; the first-time rule is
        // enforced again at payment time once email/phone are known.
        $error = $coupon->validateFor(
            $this->cartSubTotal(),
            Session::get('order_type'),
            $user->email ?? null,
            $user->phone ?? null,
            (bool) $user
        );

        return [$error ? null : $coupon, $error];
    }

    protected function putCouponInSession(Coupon $coupon)
    {
        Session::put('coupon_price', $coupon->discount);
        Session::put('offer_type', $coupon->offer_type);
        Session::put('coupon_name', $coupon->code);
        Session::forget('coupon_auto');
    }

    /**
     * Live re-check of offers while the customer fills in the checkout form.
     * Called whenever the email/phone fields change so first-time customer
     * offers are applied (or removed) as soon as the identity is known.
     */
    public function refresh_offer(Request $request)
    {
        $sub_total = $this->cartSubTotal();

        $user = Auth::guard('web')->user();
        $email = $request->email ?: ($user->email ?? null);
        $phone = $request->phone ?: ($user->phone ?? null);
        $orderType = Session::get('order_type');

        Coupon::syncSession($sub_total, $email, $phone);
        $giftBefore = Cart::content()->first(function ($item) { return !empty($item->options['is_gift']); });
        Coupon::syncGiftInCart($sub_total, $orderType, $email, $phone, false);
        $gift = Cart::content()->first(function ($item) { return !empty($item->options['is_gift']); });

        $coupon_price = Coupon::sessionDiscount($sub_total);

        return response()->json([
            'coupon_name' => Session::get('coupon_name'),
            'coupon_auto' => (bool) Session::get('coupon_auto'),
            'coupon_price' => round($coupon_price, 2),
            'sub_total' => round($sub_total, 2),
            'grand_total_base' => round($sub_total - $coupon_price, 2),
            'gift_product_name' => $gift ? $gift->name : null,
            'gift_changed' => optional($gift)->rowId !== optional($giftBefore)->rowId,
        ]);
    }

    public function apply_coupon(Request $request)
    {
        if ($request->coupon == null) {
            $notification = trans('user_validation.Coupon field is required');
            return response()->json(['message' => $notification], 403);
        }

        [$coupon, $error] = $this->resolveCoupon($request->coupon);

        if (!$coupon) {
            return response()->json(['message' => $error], 403);
        }

        $this->putCouponInSession($coupon);

        return response()->json(['message' => trans('user_validation.Coupon applied successfully'), 'discount' => $coupon->discount, 'offer_type' => $coupon->offer_type]);

    }

    public function apply_coupon_from_checkout(Request $request)
    {
        if ($request->coupon == null) {
            $notification = trans('user_validation.Coupon field is required');
            $notification = array('messege' => $notification, 'alert-type' => 'error');
            return redirect()->back()->with($notification);
        }

        [$coupon, $error] = $this->resolveCoupon($request->coupon);

        if (!$coupon) {
            $notification = array('messege' => $error, 'alert-type' => 'error');
            return redirect()->back()->with($notification);
        }

        $this->putCouponInSession($coupon);

        $notification = array('messege' => trans('user_validation.Coupon applied successfully'), 'alert-type' => 'success');
        return redirect()->back()->with($notification);
    }
}

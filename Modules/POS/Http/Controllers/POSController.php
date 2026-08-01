<?php


namespace Modules\POS\Http\Controllers;

use App\Helpers\MailHelper;
use App\Models\Address;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\DeliveryArea;
use App\Models\EmailTemplate;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\DeliveryChargeResolver;
use App\Services\PrinterService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\POS\POSTable;
use Modules\POS\Table;
use Session;
use stdClass;
use Str;


class POSController extends Controller
{
    protected $printerService;
    protected $deliveryChargeResolver;

    public function __construct(PrinterService $printerService, DeliveryChargeResolver $deliveryChargeResolver)
    {
        $this->printerService = $printerService;
        $this->deliveryChargeResolver = $deliveryChargeResolver;
    }

    function countOrdersForUserWithinTimeframe($conn, $userId, $timeframe)
    {
        $sql = "SELECT COUNT(*) AS order_count FROM Order WHERE user_id = '$userId' AND order_date >= DATE_SUB(NOW(), INTERVAL $timeframe)";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row["order_count"];
        } else {
            return 0;
        }
    }


    public function index(Request $request)
    {

        Paginator::useBootstrap();

        $products = Product::where('status', 1)->orderBy('id', 'desc');

        if ($request->category_id) {

            $products = $products->where('category_id', $request->category_id);
        }

        if ($request->name) {
            $products = $products->where('name', 'LIKE', '%' . $request->name . '%');
        }

        $products = $products->paginate(18);
        $products = $products->appends($request->all());


        $categories = Category::where('status', 1)->orderBy('pos_sort_order')->orderBy('id')->get();
        $customers = Customer::orderBy('id', 'desc')->get();

        $delivery_areas = DeliveryArea::where('status', 1)->get();
        $delivery_postcode_charges = \App\Models\DeliveryPostcodeCharge::where('status', 1)->get();


        $active_table = POSTable::find(Session::get('active_table')) ?? POSTable::first();


        $timeframe = '30 DAY';

        foreach ($customers as $customer) {
            $orderCount = Order::where('user_id', $customer->id)
                ->where('order_completed_date', '>=', now()->sub($timeframe))
                ->count();
            $customer->orderCount = $orderCount;
        }

        $orders = Order::with('user')->orderBy('id', 'desc')->where('order_status', 0)->get();
        $pendingOrderCount = $orders->count();

        return view('pos::index')->with([
            'products' => $products,
            'categories' => $categories,
            'customers' => $customers,
            'cart_contents' => $active_table->cart,
            'delivery_areas' => $delivery_areas,
            'delivery_postcode_charges' => $delivery_postcode_charges,
            'pendingOrderCount' => $pendingOrderCount,
            'active_table' => $active_table,
        ]);
    }

    public function load_products(Request $request)
    {
        Paginator::useBootstrap();

        $products = Product::where('status', 1)->orderBy('id', 'desc');

        if ($request->category_id) {

            $products = $products->where('category_id', $request->category_id);
        }

        if ($request->name) {
            $products = $products->where('name', 'LIKE', '%' . $request->name . '%');
        }

        $products = $products->paginate(18);
        $products = $products->appends($request->all());

        return view('pos::ajax_products')->with([
            'products' => $products,
        ]);
    }

    public function load_cart()
    {
        $active_table = POSTable::find(Session::get('active_table')) ?? POSTable::first();

        return view('pos::ajax_cart')->with([
            'cart_contents' => $active_table->cart
        ]);

    }

    public function load_product_modal($product_id)
    {
        $product = Product::with('category')->where(['status' => 1, 'id' => $product_id])->first();
        if (!$product) {
            $notification = trans('Something went wrong');
            return response()->json(['message' => $notification], 403);
        }

        if ($product->size_variant != null) {
            $size_variants = json_decode($product->size_variant);
        } else {
            $size_variants = array();
        }

        if ($product->optional_item != null) {
            $optional_items = json_decode($product->optional_item);
        } else {
            $optional_items = array();
        }

        return view('pos::ajax_product_modal')->with([
            'product' => $product,
            'size_variants' => $size_variants,
            'optional_items' => $optional_items,
        ]);
    }

    public function add_to_cart(Request $request)
    {
        $active_table = POSTable::find(Session::get('active_table')) ?? POSTable::first();
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

        $variant_array = explode('(::)', $request->size_variant);

        $cart_contents = $active_table->cart;

        $item_exist = false;

        foreach ($cart_contents as $index => $cart_content) {
            if ($cart_content['product_id'] == $request->product_id) {
                if ($cart_content['options']['size'] == $variant_array[0]) {
                    $item_exist = true;
                }
            }
        }

        if ($item_exist) {
            $notification = trans('admin_validation.Item already added');
            return response()->json(['message' => $notification], 403);
        }

        $data = array();
        $data['id'] = Str::uuid();
        $data['product_id'] = $product->id;
        $data['name'] = $product->name;
        $data['qty'] = $request->qty;
        $data['price'] = $request->variant_price;
        $data['weight'] = 1;
        $data['options']['image'] = $product->thumb_image;
        $data['options']['slug'] = $product->slug;
        $data['options']['size'] = $variant_array[0];
        $data['options']['size_price'] = $variant_array[1];
        $data['options']['optional_items'] = $optional_items;
        $data['options']['optional_item_price'] = $optional_item_price;

        $new_cart = $active_table->cart;
        $new_cart[] = $data;
        $active_table->cart = $new_cart;
        $this->resyncGift($active_table, $request->customer_id ?? null, $request->order_type ?? null);
        $active_table->save();


        return view('pos::ajax_cart')->with([
            'cart_contents' => $active_table->cart
        ]);
    }

    /**
     * Recompute the POS cart subtotal (excluding any free-gift line) and
     * add/refresh/remove the free-gift line for a "Buy & Get Free Product"
     * offer. Saves the table's cart array but leaves persisting to the
     * caller so it can be combined with other changes in one save().
     */
    protected function resyncGift(POSTable $active_table, $customerId = null, $orderType = null)
    {
        $cart_contents = $active_table->cart ?? [];

        $sub_total = 0;
        foreach ($cart_contents as $item) {
            if (!empty($item['options']['is_gift'])) {
                continue;
            }
            $sub_total += ($item['price'] * $item['qty']) + $item['options']['optional_item_price'];
        }

        $customer = $customerId ? Customer::find($customerId) : null;

        $active_table->cart = Coupon::syncGiftInPosCartArray(
            $cart_contents,
            $sub_total,
            $orderType,
            $customer->email ?? null,
            $customer->phone ?? null,
            true
        );
    }

    public function cart_quantity_update(Request $request)
    {
        // Get the active table or the first one if none is active
        $active_table = POSTable::find(Session::get('active_table', POSTable::first()->id));


        // Update the cart item quantity if it exists
        $cart_contents = collect($active_table->cart)->map(function ($item) use ($request) {
            if ($item['id'] == $request->rowid) {
                if (!empty($item['options']['is_gift'])) {
                    return $item; // the free gift's quantity is controlled by the offer
                }
                $item['qty'] = $request->quantity;
            }
            return $item;
        });

        // Save the updated cart to the table
        $active_table->cart = $cart_contents->toArray();
        $this->resyncGift($active_table, $request->customer_id ?? null, $request->order_type ?? null);
        $active_table->save();

        return view('pos::ajax_cart')->with([
            'cart_contents' => $active_table->cart
        ]);
    }

    public function remove_cart_item($rowId)
    {

        $active_table = POSTable::find(Session::get('active_table', POSTable::first()->id));

        $target = collect($active_table->cart)->first(function ($item) use ($rowId) {
            return $item['id'] == $rowId;
        });

        if ($target && !empty($target['options']['is_gift'])) {
            // The free gift disappears on its own once the order no longer qualifies.
            return view('pos::ajax_cart')->with(['cart_contents' => $active_table->cart]);
        }

        $cart_contents = collect($active_table->cart)->filter(function ($item) use ($rowId) {
            return $item['id'] != $rowId;
        });

        $active_table->cart = $cart_contents->toArray();
        $this->resyncGift($active_table);
        $active_table->save();


        return view('pos::ajax_cart')->with([
            'cart_contents' => $active_table->cart
        ]);
    }

    public function cart_clear()
    {

        $active_table = POSTable::find(Session::get('active_table', POSTable::first()->id));
        $active_table->cart = [];
        $active_table->resolved_order = [];
        $active_table->save();

        $notification = trans('admin_validation.Cart clear successfully');
        $notification = array('messege' => $notification, 'alert-type' => 'success');
        return redirect()->back()->with($notification);

    }

    public function create_new_customer(Request $request)
    {
        // Log the incoming request data for debugging
//        Log::info('Create new customer request: ', $request->all());
        // Validate the incoming request data
        $validatedData = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|unique:users',
            'phone' => 'required|unique:users',
            'distance' => 'required',
            'address' => 'required',
        ], [
            'name.required' => trans('Name is required'),
            'email.required' => trans('Email is required'),
            'email.unique' => trans('Email already exists'),
            'phone.required' => trans('Phone is required'),
            'address.required' => trans('Address is required'),
            'distance.required' => trans('Address is required'),
            'phone.unique' => trans('Phone already exists'),
        ])->validate();

        try {
            // Create a new user

            Customer::create(
                [
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'address_distance' => $request->distance,
                    'postal_code' => $this->deliveryChargeResolver->normalizePostcode($request->postal_code),
                ]
            );
            // Fetch all active customers
            $customers = Customer::orderBy('id', 'desc')->get();
            $customer_html = "<option value=''
disabled
style='display: none'

>" . trans('admin.Select Customer') . "</option>";
            foreach ($customers as $customer) {
                $customer_html .= "<option value=" . $customer->id . ">" . $customer->name . " - " . $customer->phone . "</option>";
            }
            $notification = trans('admin_validation.Created Successfully');
            return response()->json(['customer_html' => $customer_html, 'message' => $notification]);
        } catch (Exception $e) {
            // Log the error message for debugging
            Log::error('Error creating new customer: ' . $e->getMessage());
            return response()->json(['message' => trans('admin.Server error occurred')], 500);
        }
    }

    public function create_new_address(Request $request)
    {

        $validatedData = Validator::make($request->all(), [
            'customer_id' => 'required',
            'delivery_area_id' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'address' => 'required',
            'address_type' => 'required',
        ], [
            'customer_id.required' => trans('admin_validation.Customer is required'),
            'delivery_area_id.required' => trans('admin_validation.Delivery area is required'),
            'first_name.required' => trans('admin_validation.First name is required'),
            'last_name.required' => trans('admin_validation.Last name is required'),
            'address.required' => trans('admin_validation.Address is required'),
            'address_type.required' => trans('admin_validation.Address type is required'),
        ])->validate();

        $user = User::find($request->customer_id);
        $is_exist = Address::where(['user_id' => $user->id])->count();
        $address = new Address();
        $address->user_id = $user->id;
        $address->delivery_area_id = $request->delivery_area_id;
        $address->first_name = $request->first_name;
        $address->last_name = $request->last_name;
        $address->email = $request->email;
        $address->phone = $request->phone;
        $address->address = $request->address;
        $address->type = $request->address_type;
        if ($is_exist == 0) {
            $address->default_address = 'Yes';
        }
        $address->save();

        $delivery_area = DeliveryArea::find($request->delivery_area_id);

        $notification = trans('admin_validation.Create Successfully');
        return redirect()->back();
//        return response()->json(['address' => $address, 'delivery_fee' => $delivery_area->delivery_fee, 'message' => $notification]);

    }

    /**
     * Validate a coupon code for the POS cart, or - when no code is given -
     * look for an auto-apply "default discount" offer for the selected
     * customer and order type.
     */
    public function apply_coupon(Request $request)
    {
        $sub_total = (float) $request->sub_total;
        $orderType = $request->order_type;

        $customer = $request->customer_id ? Customer::find($request->customer_id) : null;
        $email = $customer->email ?? null;
        $phone = $customer->phone ?? null;

        // Keep the free-gift line item in sync with the current cart/customer/order type.
        $active_table = POSTable::find(Session::get('active_table', optional(POSTable::first())->id));
        $gift = null;
        if ($active_table) {
            $this->resyncGift($active_table, $request->customer_id, $orderType);
            $active_table->save();
            $gift = collect($active_table->cart)->first(function ($item) {
                return !empty($item['options']['is_gift']);
            });
        }

        if ($request->filled('coupon')) {
            $coupon = Coupon::where(['code' => $request->coupon, 'status' => 1])->first();

            if (!$coupon) {
                return response()->json(['message' => trans('user_validation.Invalid Coupon')], 403);
            }

            $error = $coupon->validateFor($sub_total, $orderType, $email, $phone, true);
            if ($error) {
                return response()->json(['message' => $error], 403);
            }

            return response()->json([
                'message' => trans('user_validation.Coupon applied successfully'),
                'code' => $coupon->code,
                'discount' => $coupon->discount,
                'offer_type' => $coupon->offer_type,
                'gift_product_name' => $gift['name'] ?? null,
            ]);
        }

        $coupon = Coupon::findAutoApply($sub_total, $orderType, $email, $phone, true);

        if (!$coupon) {
            return response()->json(['code' => null, 'gift_product_name' => $gift['name'] ?? null]);
        }

        return response()->json([
            'message' => 'Offer "' . $coupon->name . '" applied automatically',
            'code' => $coupon->code,
            'discount' => $coupon->discount,
            'offer_type' => $coupon->offer_type,
            'gift_product_name' => $gift['name'] ?? null,
        ]);
    }

    public function place_order(Request $request)
    {
        $active_table = POSTable::find(Session::get('active_table'));
        if (!$active_table->cart) {
            $notification = trans('admin_validation.Your cart is empty!');
            $notification = array('messege' => $notification, 'alert-type' => 'error');
            return redirect()->back()->with($notification);
        }
        Validator::make($request->all(), [
            'customer_id' => 'required',
        ], [
            'customer_id.required' => trans('admin_validation.Customer is required'),
        ])->validate();

        $user = Customer::find($request->customer_id);
        $cart_contents = $active_table->cart;


        $deliveryCharge = 0;
        if (($request->order_type ?? 'Pickup') === 'Delivery') {
            $resolvedDelivery = $this->deliveryChargeResolver->resolvePosCharge(
                $user->postal_code ?? null,
                $user->address_distance ?? null
            );
            $deliveryCharge = $resolvedDelivery['charge'];
        }

        // Final free-gift check with the real customer/order type, right
        // before the order is stored - never trust the client's cart state
        // for whether the gift is actually earned.
        $this->resyncGift($active_table, $request->customer_id, $request->order_type);
        $active_table->save();
        $cart_contents = $active_table->cart;

        // Server-side coupon handling: when a validated coupon code was
        // applied in the UI, recompute the discount here instead of trusting
        // the client's amount, and count the redemption.
        $coupon_price = $request->coupon_price;
        $coupon_name = null;
        if ($request->filled('coupon_code')) {
            // A coupon drove the client-side discount, so never trust the
            // submitted amount: recompute it, or drop it when invalid.
            $coupon_price = 0;

            $sub_total = 0;
            foreach ($cart_contents as $cart_content) {
                if (!empty($cart_content['options']['is_gift'])) {
                    continue;
                }
                $item_price = $cart_content['price'] * $cart_content['qty'];
                $sub_total += $item_price + $cart_content['options']['optional_item_price'];
            }

            $coupon = Coupon::where(['code' => $request->coupon_code, 'status' => 1])->first();
            if ($coupon && $coupon->validateFor($sub_total, $request->order_type, $user->email ?? null, $user->phone ?? null, true) === null) {
                $coupon_price = $coupon->discountAmount($sub_total);
                $coupon_name = $coupon->code;
                Coupon::redeemByCode($coupon->code);
            }
        }

        $calculate_amount = $this->calculate_amount($deliveryCharge, $coupon_price, $cart_contents);

        // Resolve payment method and status from request
        $rawMethod = $request->payment_method ?? 'card';
        if ($rawMethod === 'card') {
            $payment_method = 'Card';
            $transaction_id = 'card_pos';
            $payment_status = 1;
            $cash_on_delivery = 0;
        } elseif ($rawMethod === 'cash') {
            $payment_method = 'Cash';
            $transaction_id = 'cash_pos';
            $payment_status = 1;
            $cash_on_delivery = 1;
        } else {
            // unpaid / COD
            $payment_method = 'Unpaid - COD';
            $transaction_id = 'cod_pos';
            $payment_status = 0;
            $cash_on_delivery = 1;
        }

        $specialInstructions = $request->input('special_instructions', '');
        $order_result = $this->orderStore($user, $calculate_amount, $payment_method, $transaction_id, $payment_status, $cash_on_delivery, $request->address_id, $request->order_type, $cart_contents, $specialInstructions, $coupon_name);

        //$this->sendOrderSuccessMail($user, $order_result, 'Cash on Delivery', 0);
        $customerDetails = $request->customerDetails ?? "Walking Customer";
        $this->printOrder($order_result, $customerDetails, $active_table, $specialInstructions);
        $active_table->cart = [];
        $active_table->resolved_order = [];
        $active_table->special_instructions = null;
        $active_table->save();
        $notification = trans('admin_validation.Order created successfully');
        $notification = array('messege' => $notification, 'alert-type' => 'success');
        return redirect()->route('admin.pos')->with($notification);
    }

    public function calculate_amount($delivery_charge, $coupon_price, $cart_contents)
    {

        $sub_total = 0;
        $coupon = $coupon_price;

        foreach ($cart_contents as $index => $cart_content) {
            $item_price = $cart_content['price'] * $cart_content['qty'];
            $item_total = $item_price + $cart_content['options']['optional_item_price'];
            $sub_total += $item_total;
        }

        $grand_total = ($sub_total - $coupon) + $delivery_charge;

        return array(
            'sub_total' => $sub_total,
            'coupon_price' => $coupon,
            'delivery_charge' => $delivery_charge,
            'grand_total' => $grand_total,
        );
    }

    public function orderStore($user, $calculate_amount, $payment_method, $transaction_id, $payment_status, $cash_on_delivery, $address_id, $type, $cart_contents, $specialInstructions = '', $coupon_name = null)
    {

        $order = new Order();
        $order->order_id = substr(rand(0, time()), 0, 10);
        $order->user_id = $user->id;
        $order->grand_total = $calculate_amount['grand_total'];
        $order->delivery_charge = $calculate_amount['delivery_charge'];
        $order->coupon_price = $calculate_amount['coupon_price'];
        $order->sub_total = $calculate_amount['sub_total'];
        $order->product_qty = count($cart_contents);
        $order->payment_method = $payment_method;
        $order->transection_id = $transaction_id;
        $order->payment_status = $payment_status;
        $order->order_status = 3;
        $order->order_approval_date = date('Y-m-d');
        $order->cash_on_delivery = $cash_on_delivery;
        $order->order_type = $type ?? 'Pickup';
        $order->special_instructions = $specialInstructions ?: null;
        $order->coupon_name = $coupon_name;
        $order->save();

        foreach ($cart_contents as $index => $cart_content) {
            $optional_item_arr = array();
            foreach ($cart_content['options']['optional_items'] as $optional_item) {
                $new_item = array(
                    'item' => $optional_item['optional_name'],
                    'price' => $optional_item['optional_price'],
                );
                $optional_item_arr[] = $new_item;
            }

            $orderProduct = new OrderProduct();
            $orderProduct->order_id = $order->id;
            $orderProduct->product_id = $cart_content['product_id'];
            $orderProduct->product_name = $cart_content['name'];
            $orderProduct->unit_price = $cart_content['price'];
            $orderProduct->qty = $cart_content['qty'];
            $orderProduct->product_size = $cart_content['options']['size'];
            $orderProduct->optional_price = $cart_content['options']['optional_item_price'];
            $orderProduct->optional_item = json_encode($optional_item_arr);
            $orderProduct->save();
        }

        // store address
        $orderAddress = new OrderAddress();
        $orderAddress->order_id = $order->id;
        $orderAddress->name = $user->name; // Use user-provided name
        $orderAddress->email = $user->email ?? "No mail"; // Use user-provided email
        $orderAddress->phone = $user->phone ?? "No Phone"; // Use user-provided phone
        $orderAddress->address = $user->address ?? "Pickup Order";
        $orderAddress->postal_code = $user->postal_code ?? null;
        $orderAddress->longitude = Null;
        $orderAddress->latitude = Null;
        $orderAddress->save();


        Session::forget('delivery_id');
        Session::forget('delivery_charge');
        Session::forget('coupon_price');
        Session::forget('offer_type');
        Session::forget('coupon_price');
        Session::forget('offer_type');


        return $order;
    }

    public function printOrder($order_result, $customerDetails, $active_table, $specialInstructions = '')
    {
        $order = $this->getOrderDetails($order_result, $customerDetails, $active_table, $specialInstructions);
        try {
            $receipt = $this->printerService->getFormattedReceipt($order);

            // Save receipt to the order record
            \App\Models\Order::where('id', $order_result['id'])->update(['print_receipt' => $receipt]);

            // Print the same full receipt to both kitchen and desk printers
            $this->printerService->sendToKitchen($receipt);
            $this->printerService->sendToDesk($receipt);

            return response()->json(['message' => 'Order printed successfully']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Printing failed: ' . $e->getMessage()], 500);
        }
    }

    private function getOrderDetails($order, $customerDetails, $active_table, $specialInstructions = '')
    {
        $orderId = $order['id'];
        $orderProducts = OrderProduct::where('order_id', $orderId)->get();

        $formattedItems = [];

        foreach ($orderProducts as $product) {
            $formattedItem = new stdClass();
            $formattedItem->name = $product->product_name;
            $formattedItem->quantity = $product->qty;
            $formattedItem->price = $product->unit_price * $product->qty;
            $formattedItem->size = $product->product_size;
            $formattedItem->product_id = (int) $product->product_id;
            $formattedItem->category = $product->category_name ?? '';
            $formattedItems[] = $formattedItem;
        }

        $details =

            (object)[
                'id' => $orderId,
                'items' => $formattedItems,
                'type' => $order['order_type'],
                'discount' => $order['coupon_price'],
                'delivery' => $order['delivery_charge'],
                'total' => $order['grand_total'],
                'status' => $active_table->meta['payment_status'] ?? 'paid',
                'payment_method' => $order['payment_method'] ?? 'Card',
                'customerDetails' => $customerDetails,
                'tableNumber' => $active_table->name,
                'coupon_name' => $order['coupon_name'] ?? null,
                'inst' => $specialInstructions ?: null,
                'order_date' => optional(\App\Models\Order::find($orderId)->created_at)->format('d/m/Y H:i'),
            ];

        return $details;
    }

    public function print_order(Request $request)
    {
        $active_table = POSTable::find(Session::get('active_table'));

        if (!$active_table) {
            return redirect()->back();
        }

        $cart = $active_table->cart ?? [];
        if (!$cart) {
            $notification = trans('admin_validation.Your cart is empty!');
            $notification = array('messege' => $notification, 'alert-type' => 'error');
            return redirect()->back()->with($notification);
        }

        // Persist updated special instructions
        $active_table->special_instructions = $request->input('special_instructions', $active_table->special_instructions);
        $active_table->save();

        $resolved_orders = collect($active_table->resolved_order);

        $new_orders = collect([]);
        $new_resolved_orders = collect([]);

        // Remove resolved orders from the cart or update the quantity
// Remove resolved orders from the cart or update the quantity
        foreach ($cart as $item) {

            $new_resolved_orders->push($item);

//            get the resolved order
            $resolved_order = $resolved_orders->where('id', $item['id'])->first();

//            if the order is not resolved add it to the new orders
            if (!$resolved_order) {
                $new_orders->push($item);
                continue;
            }

//            if the order is resolved and the quantity is less than the cart quantity
            if ($resolved_order['qty'] < $item['qty']) {
                $updated_item = $item;
                $updated_item['qty'] = $item['qty'] - $resolved_order['qty'];
                $new_orders->push($updated_item);
            }


        }
        //check if all orders are already resolved
        if ($new_orders->count() == 0) {
            $notification = trans('All orders are already sent to printers');
            $notification = array('messege' => $notification, 'alert-type' => 'success');
            return redirect()->back()->with($notification);
        }

        if ($resolved_orders->isEmpty()) {
            $stp = "******* New Order ********\n";
        } else {
            $stp = "****** Running Order ******\n";
        }
        $output = "         Punjabi Paradise\n";
        $output .= "     419 High Street, Penrith, 2750\n";
        $output .= "Phone: 0247076700     ABN: 86673991529\n";
        $output .= "Order Online at: www.punjabiparadise.com.au\n";
        $output .= str_repeat("-", 42) . "\n";
        $output .= "              " . $active_table->name . "\n";
        $output .= str_repeat("-", 42) . "\n";
        $output .= "     \n";

        if ($new_orders->count() > 0) {
            $output .= "\n";
            $output .= $stp;
            $output .= str_repeat("-", 42) . "\n";
            foreach ($new_orders as $item) {
                $name = $item['name'] . ($item['options']['size'] !== 'Regular' ? " -" . $item['options']['size'] . "" : '');
                $output .= sprintf("%-4s %-23s \n\n", $item['qty'], $name);
            }
            $output .= str_repeat("-", 42) . "\n";
        }

        // Append special instructions if set
        $instructions = $active_table->special_instructions;
        if (!empty($instructions)) {
            $output .= "SPECIAL INSTRUCTIONS:\n";
            $output .= wordwrap($instructions, 42) . "\n";
            $output .= str_repeat("-", 42) . "\n";
        }

        //append current time
        $output .= "\n";
        $output .= date('d-m-Y H:i:s') . "\n";
        $output .= str_repeat("\n", 3); // feed paper so date isn't cut off
        $this->printerService->sendToKitchen($output);
        $this->printerService->sendToDesk($output);


        $resolved_orders = $new_resolved_orders->map(function ($item) {
            return [
                'id' => $item['id'],
                'qty' => $item['qty'],
                'name' => $item['name'],
                'options' => $item['options'],
            ];
        });
        //dd($resolved_orders);

        $active_table->resolved_order = $resolved_orders;
        $active_table->save();

        $notification = trans('Order sent to both printers');
        $notification = array('messege' => $notification, 'alert-type' => 'success');
        return redirect()->route('admin.pos')->with($notification);
    }

    public function sendOrderSuccessMail($user, $order_result, $payment_method, $payment_status)
    {

        $setting = Setting::first();

        MailHelper::setMailConfig();

        $template = EmailTemplate::where('id', 6)->first();

        $payment_status = $payment_status == 1 ? 'Success' : 'Pending';
        $subject = $template->subject;
        $message = $template->description;
        $message = str_replace('{{user_name}}', $user->name, $message);
        $message = str_replace('{{total_amount}}', $setting->currency_icon . $order_result->grand_total, $message);
        $message = str_replace('{{payment_method}}', $payment_method, $message);
        $message = str_replace('{{payment_status}}', $payment_status, $message);
        $message = str_replace('{{order_status}}', 'Processing', $message);
        $message = str_replace('{{order_date}}', $order_result->created_at->format('d F, Y'), $message);
        // Mail::to($user->email)->send(new OrderSuccessfully($message,$subject));
    }

    public function getPendingOrderCount()
    {
        $pendingOrderCount = Order::where('order_status', 0)->count();
        return response()->json(['count' => $pendingOrderCount]);
    }

}

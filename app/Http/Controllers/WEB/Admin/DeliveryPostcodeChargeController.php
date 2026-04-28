<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPostcodeCharge;
use App\Services\DeliveryChargeResolver;
use Illuminate\Http\Request;

class DeliveryPostcodeChargeController extends Controller
{
    public function __construct(private readonly DeliveryChargeResolver $deliveryChargeResolver)
    {
        $this->middleware('auth:admin');
    }

    public function index()
    {
        $charges = DeliveryPostcodeCharge::orderBy('postcode')->get();

        return view('admin.delivery_postcode_charge.index', compact('charges'));
    }

    public function create()
    {
        return view('admin.delivery_postcode_charge.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['postcode'] = $this->deliveryChargeResolver->normalizePostcode($data['postcode']);

        DeliveryPostcodeCharge::create($data);

        $notification = trans('admin_validation.Created Successfully');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->route('admin.delivery-postcode-charge.index')->with($notification);
    }

    public function edit($id)
    {
        $charge = DeliveryPostcodeCharge::findOrFail($id);

        return view('admin.delivery_postcode_charge.edit', compact('charge'));
    }

    public function update(Request $request, $id)
    {
        $charge = DeliveryPostcodeCharge::findOrFail($id);
        $data = $this->validateData($request, $charge->id);
        $data['postcode'] = $this->deliveryChargeResolver->normalizePostcode($data['postcode']);

        $charge->update($data);

        $notification = trans('admin_validation.Update Successfully');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->route('admin.delivery-postcode-charge.index')->with($notification);
    }

    public function destroy($id)
    {
        $charge = DeliveryPostcodeCharge::findOrFail($id);
        $charge->delete();

        $notification = trans('admin_validation.Delete Successfully');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    protected function validateData(Request $request, ?int $ignoreId = null): array
    {
        $request->merge([
            'postcode' => $this->deliveryChargeResolver->normalizePostcode($request->postcode),
        ]);

        $postcodeRule = 'required|string|max:20|unique:delivery_postcode_charges,postcode';
        if ($ignoreId) {
            $postcodeRule .= ',' . $ignoreId;
        }

        return $request->validate([
            'postcode' => $postcodeRule,
            'delivery_fee' => 'required|numeric|min:0',
            'status' => 'required|boolean',
        ], [
            'postcode.required' => 'Postcode is required',
            'postcode.unique' => 'This postcode already has a delivery charge',
            'delivery_fee.required' => trans('admin_validation.Fee is required'),
            'delivery_fee.numeric' => 'Delivery fee must be a valid number',
            'status.required' => trans('admin_validation.Status is required'),
        ]);
    }
}

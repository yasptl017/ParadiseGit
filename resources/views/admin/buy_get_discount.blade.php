@extends('admin.master_layout')
@section('title')
<title>Buy & Get Offer</title>
@endsection
@section('admin-content')
      <!-- Main Content -->
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>Buy & Get Offer</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
              <div class="breadcrumb-item">Buy & Get Offer</div>
            </div>
          </div>

          <div class="section-body">
            <p class="text-muted">Spend a minimum amount and automatically get a percentage or fixed amount off - no code needed. Percentage discounts can be capped with a maximum discount amount.</p>
            <a href="javascript:;" data-toggle="modal" data-target="#createOffer" class="btn btn-primary"><i class="fas fa-plus"></i> {{__('admin.Add New')}}</a>
            <div class="row mt-4">
                <div class="col">
                  <div class="card">
                    <div class="card-body">
                      <div class="table-responsive table-invoice">
                        <table class="table table-striped" id="dataTable">
                            <thead>
                                <tr>
                                    <th>{{__('admin.SN')}}</th>
                                    <th>{{__('admin.Name')}}</th>
                                    <th>{{__('admin.Minimum Purchase Price')}}</th>
                                    <th>{{__('admin.Discount')}}</th>
                                    <th>Max Discount</th>
                                    <th>Order Types</th>
                                    <th>{{__('admin.Expired')}}</th>
                                    <th>{{__('admin.Status')}}</th>
                                    <th>{{__('admin.Action')}}</th>
                                  </tr>
                            </thead>
                            <tbody>
                                @foreach ($coupons as $index => $coupon)
                                    <tr>
                                        <td>{{ ++$index }}</td>
                                        <td>{{ $coupon->name }}</td>
                                        <td>{{ $setting->currency_icon }}{{ number_format($coupon->min_purchase_price, 2) }}+</td>
                                        <td>{{ $coupon->offer_type == 1 ? '' : $setting->currency_icon }}{{ $coupon->discount }}{{ $coupon->offer_type == 1 ? '%' : '' }}</td>
                                        <td>{{ $coupon->maxDiscountLabel($setting->currency_icon) ?? '—' }}</td>
                                        <td>{{ $coupon->orderTypeLabels() }}</td>
                                        <td>{{ date('d M, Y',strtotime($coupon->expired_date)) }}</td>
                                        <td>
                                            @if($coupon->status == 1)
                                            <a href="javascript:;" onclick="changeCouponStatus({{ $coupon->id }})">
                                                <input id="status_toggle" type="checkbox" checked data-toggle="toggle" data-on="{{__('admin.Active')}}" data-off="{{__('admin.Inactive')}}" data-onstyle="success" data-offstyle="danger">
                                            </a>
                                            @else
                                            <a href="javascript:;" onclick="changeCouponStatus({{ $coupon->id }})">
                                                <input id="status_toggle" type="checkbox" data-toggle="toggle" data-on="{{__('admin.Active')}}" data-off="{{__('admin.Inactive')}}" data-onstyle="success" data-offstyle="danger">
                                            </a>
                                            @endif
                                        </td>
                                        <td>
                                        <a href="javascript:;" data-toggle="modal" data-target="#editOffer-{{ $coupon->id }}" class="btn btn-primary btn-sm"><i class="fa fa-edit" aria-hidden="true"></i></a>

                                        <a href="javascript:;" data-toggle="modal" data-target="#deleteModal" class="btn btn-danger btn-sm" onclick="deleteData({{ $coupon->id }})"><i class="fa fa-trash" aria-hidden="true"></i></a>
                                        </td>
                                    </tr>
                                  @endforeach
                            </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
          </div>
        </section>
      </div>

      <!-- Create Modal -->
      <div class="modal fade" id="createOffer" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
          <div class="modal-dialog" role="document">
              <div class="modal-content">
                      <div class="modal-header">
                              <h5 class="modal-title">Create Buy & Get Offer</h5>
                                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                      <span aria-hidden="true">&times;</span>
                                  </button>
                          </div>
                  <div class="modal-body">
                      <div class="container-fluid">
                        <form action="{{ route('admin.coupon.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="offer_kind" value="{{ App\Models\Coupon::KIND_BUY_GET_DISCOUNT }}">
                            <input type="hidden" name="code" value="AUTO-{{ strtoupper(Str::random(8)) }}">
                            <div class="row">
                                <div class="form-group col-12">
                                    <label>{{__('admin.Name')}} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" placeholder="e.g. Spend $100 Get 10% Off">
                                </div>

                                <div class="form-group col-12">
                                    <label>Minimum Spend <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ $setting->currency_icon }}</span>
                                        </div>
                                        <input type="text" class="form-control" name="min_purchase_price" placeholder="100">
                                    </div>
                                    <small class="form-text text-muted">Customers spending this much or more get the discount automatically.</small>
                                </div>

                                <div class="form-group col-12">
                                    <label>Discount <span class="text-danger">*</span></label>
                                    <div class="input-group mb-2">
                                        <div class="input-group-prepend">
                                            <select name="offer_type" id="create_offer_type" class="form-control">
                                                <option value="1">{{__('admin.Percentage')}}(%)</option>
                                                <option value="2">{{__('admin.Amount')}}({{ $setting->currency_icon }})</option>
                                            </select>
                                        </div>
                                        <input type="text" name="discount" class="form-control" placeholder="Discount">
                                    </div>
                                </div>

                                <div class="form-group col-12" id="create_max_discount_wrap">
                                    <label>Max Discount (optional)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ $setting->currency_icon }}</span>
                                        </div>
                                        <input type="text" class="form-control" name="max_discount" placeholder="e.g. 20">
                                    </div>
                                    <small class="form-text text-muted">Caps a percentage discount's money value. Leave blank for no cap. Not used for fixed amount discounts.</small>
                                </div>

                                <div class="form-group col-12">
                                    <label>{{__('admin.Number of times')}} <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="number_of_time" value="100000">
                                    <small class="form-text text-muted">Total number of orders this offer can apply to, across all customers.</small>
                                </div>

                                <div class="form-group col-12">
                                    <label>{{__('admin.Expired Date')}} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control datepicker" name="expired_date" autocomplete="off">
                                </div>

                                <div class="form-group col-12">
                                    <label>Enable On Orders</label>
                                    <div>
                                        @foreach (App\Models\Coupon::ORDER_TYPE_OPTIONS as $value => $label)
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="order_types[]" value="{{ $value }}" id="create_bgd_order_type_{{ $value }}" checked>
                                                <label class="form-check-label" for="create_bgd_order_type_{{ $value }}">{{ $label }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                    <small class="form-text text-muted">Leave all checked to allow the offer on every order type.</small>
                                </div>

                                <div class="form-group col-12">
                                    <label>{{__('admin.Status')}} <span class="text-danger">*</span></label>
                                    <select name="status" class="form-control">
                                        <option value="1">{{__('admin.Active')}}</option>
                                        <option value="0">{{__('admin.Inactive')}}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">{{__('admin.Save')}}</button>
                                    <button type="button" class="btn btn-danger" data-dismiss="modal">{{__('admin.Close')}}</button>
                                </div>
                            </div>
                        </form>
                      </div>
                  </div>
              </div>
          </div>
      </div>

      {{-- edit modals --}}
      @foreach ($coupons as $coupon)
      <div class="modal fade" id="editOffer-{{ $coupon->id }}" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                    <div class="modal-header">
                            <h5 class="modal-title">Edit Buy & Get Offer</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                        </div>
                <div class="modal-body">
                    <div class="container-fluid">
                      <form action="{{ route('admin.coupon.update', $coupon->id) }}" method="POST">
                          @csrf
                          @method('PUT')
                          <input type="hidden" name="offer_kind" value="{{ App\Models\Coupon::KIND_BUY_GET_DISCOUNT }}">
                          <input type="hidden" name="code" value="{{ $coupon->code }}">
                          <div class="row">
                              <div class="form-group col-12">
                                  <label>{{__('admin.Name')}} <span class="text-danger">*</span></label>
                                  <input type="text" class="form-control" name="name" value="{{ $coupon->name }}">
                              </div>

                              <div class="form-group col-12">
                                  <label>Minimum Spend <span class="text-danger">*</span></label>
                                  <div class="input-group">
                                      <div class="input-group-prepend">
                                          <span class="input-group-text">{{ $setting->currency_icon }}</span>
                                      </div>
                                      <input type="text" class="form-control" name="min_purchase_price" value="{{ $coupon->min_purchase_price }}">
                                  </div>
                              </div>

                              <div class="form-group col-12">
                                  <label>Discount <span class="text-danger">*</span></label>
                                  <div class="input-group mb-2">
                                      <div class="input-group-prepend">
                                          <select name="offer_type" class="form-control edit-offer-type" data-id="{{ $coupon->id }}">
                                              <option {{ $coupon->offer_type == 1 ? 'selected' : '' }} value="1">{{__('admin.Percentage')}}(%)</option>
                                              <option {{ $coupon->offer_type == 2 ? 'selected' : '' }} value="2">{{__('admin.Amount')}}({{ $setting->currency_icon }})</option>
                                          </select>
                                      </div>
                                      <input type="text" name="discount" class="form-control" value="{{ $coupon->discount }}">
                                  </div>
                              </div>

                              <div class="form-group col-12" id="edit_max_discount_wrap_{{ $coupon->id }}">
                                  <label>Max Discount (optional)</label>
                                  <div class="input-group">
                                      <div class="input-group-prepend">
                                          <span class="input-group-text">{{ $setting->currency_icon }}</span>
                                      </div>
                                      <input type="text" class="form-control" name="max_discount" value="{{ $coupon->max_discount }}">
                                  </div>
                                  <small class="form-text text-muted">Caps a percentage discount's money value. Leave blank for no cap.</small>
                              </div>

                              <div class="form-group col-12">
                                  <label>{{__('admin.Number of times')}} <span class="text-danger">*</span></label>
                                  <input type="number" class="form-control" name="number_of_time" value="{{ $coupon->max_quantity }}">
                              </div>

                              <div class="form-group col-12">
                                  <label>{{__('admin.Expired Date')}} <span class="text-danger">*</span></label>
                                  <input type="text" class="form-control datepicker" value="{{ $coupon->expired_date }}" name="expired_date" autocomplete="off">
                              </div>

                              <div class="form-group col-12">
                                  <label>Enable On Orders</label>
                                  @php
                                      $selectedOrderTypes = array_filter((array) $coupon->order_types);
                                  @endphp
                                  <div>
                                      @foreach (App\Models\Coupon::ORDER_TYPE_OPTIONS as $value => $label)
                                          <div class="form-check form-check-inline">
                                              <input class="form-check-input" type="checkbox" name="order_types[]" value="{{ $value }}" id="edit_bgd_{{ $coupon->id }}_order_type_{{ $value }}"
                                                  {{ (empty($selectedOrderTypes) || in_array($value, $selectedOrderTypes)) ? 'checked' : '' }}>
                                              <label class="form-check-label" for="edit_bgd_{{ $coupon->id }}_order_type_{{ $value }}">{{ $label }}</label>
                                          </div>
                                      @endforeach
                                  </div>
                              </div>

                              <div class="form-group col-12">
                                  <label>{{__('admin.Status')}} <span class="text-danger">*</span></label>
                                  <select name="status" class="form-control">
                                      <option {{ $coupon->status == 1 ? 'selected' : '' }} value="1">{{__('admin.Active')}}</option>
                                      <option {{ $coupon->status == 0 ? 'selected' : '' }} value="0">{{__('admin.Inactive')}}</option>
                                  </select>
                              </div>
                          </div>
                          <div class="row">
                              <div class="col-12">
                                  <button type="submit" class="btn btn-primary">{{__('admin.Save')}}</button>
                                  <button type="button" class="btn btn-danger" data-dismiss="modal">{{__('admin.Close')}}</button>
                              </div>
                          </div>
                      </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
      @endforeach

<script>
    function deleteData(id){
        $("#deleteForm").attr("action",'{{ url("admin/coupon/") }}'+"/"+id)
    }
    function changeCouponStatus(id){
        var isDemo = "{{ env('APP_MODE') }}"
        if(isDemo == 0){
            toastr.error('This Is Demo Version. You Can Not Change Anything');
            return;
        }
        $.ajax({
            type:"put",
            data: { _token : '{{ csrf_token() }}' },
            url:"{{url('/admin/coupon-status/')}}"+"/"+id,
            success:function(response){
                toastr.success(response)
            },
            error:function(err){
            }
        })
    }

    function toggleMaxDiscountVisibility(selectEl, wrapEl) {
        $(wrapEl).toggle($(selectEl).val() == '1');
    }

    $(document).ready(function () {
        toggleMaxDiscountVisibility('#create_offer_type', '#create_max_discount_wrap');
        $('#create_offer_type').on('change', function () {
            toggleMaxDiscountVisibility(this, '#create_max_discount_wrap');
        });

        $('.edit-offer-type').each(function () {
            var id = $(this).data('id');
            toggleMaxDiscountVisibility(this, '#edit_max_discount_wrap_' + id);
        });
        $('.edit-offer-type').on('change', function () {
            var id = $(this).data('id');
            toggleMaxDiscountVisibility(this, '#edit_max_discount_wrap_' + id);
        });
    });
</script>
@endsection

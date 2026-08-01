@extends('admin.master_layout')
@section('title')
<title>First Time Users</title>
@endsection
@section('admin-content')
      <!-- Main Content -->
      <div class="main-content">
        <section class="section">
          <div class="section-header">
            <h1>First Time Users</h1>
            <div class="section-header-breadcrumb">
              <div class="breadcrumb-item active"><a href="{{ route('admin.dashboard') }}">{{__('admin.Dashboard')}}</a></div>
              <div class="breadcrumb-item">First Time Users</div>
            </div>
          </div>

          <div class="section-body">
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
                                    <th>{{__('admin.Discount')}}</th>
                                    <th>First Time Rule</th>
                                    <th>Auto Apply</th>
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
                                        <td>{{ $coupon->offer_type == 1 ? '' : $setting->currency_icon }}{{ $coupon->discount }}{{ $coupon->offer_type == 1 ? '%' : '' }}</td>
                                        <td>{{ $coupon->firstTimeLabel() }}</td>
                                        <td>
                                            @if($coupon->auto_apply)
                                                <span class="badge badge-success">Yes</span>
                                            @else
                                                <span class="badge badge-secondary">No</span>
                                            @endif
                                        </td>
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
                              <h5 class="modal-title">Create First Time User Offer</h5>
                                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                      <span aria-hidden="true">&times;</span>
                                  </button>
                          </div>
                  <div class="modal-body">
                      <div class="container-fluid">
                        <form action="{{ route('admin.coupon.store') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="form-group col-12">
                                    <label>{{__('admin.Name')}} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name">
                                </div>
                                <div class="form-group col-12">
                                    <label>{{__('admin.Code')}} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="code">
                                    <small class="form-text text-muted">Customers enter this code, unless Auto Apply is enabled below.</small>
                                </div>
                                <div class="form-group col-12">
                                    <label>{{__('admin.Number of times')}} <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="number_of_time">
                                </div>

                                <div class="form-group col-12">
                                    <label>{{__('admin.Minimum Purchase Price')}} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="min_purchase_price">
                                </div>

                                <div class="form-group col-12">
                                    <label>{{__('admin.Expired Date')}} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control datepicker" name="expired_date" autocomplete="off">
                                </div>

                                <div class="form-group col-12">
                                    <label>{{__('admin.Discount')}} <span class="text-danger">*</span></label>
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <select name="offer_type" class="form-control">
                                                <option value="1">{{__('admin.Percentage')}}(%)</option>
                                                <option value="2">{{__('admin.Amount')}}({{ $setting->currency_icon }})</option>
                                            </select>
                                        </div>
                                        <input type="text" name="discount" class="form-control" placeholder="Discount" aria-label="Discount">
                                    </div>
                                </div>

                                <div class="form-group col-12">
                                    <label>First Time Customer Check <span class="text-danger">*</span></label>
                                    <select name="first_time_basis" class="form-control">
                                        @foreach (App\Models\Coupon::FIRST_TIME_OPTIONS as $value => $label)
                                            @if ($value === App\Models\Coupon::FIRST_TIME_NONE) @continue @endif
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">How a first time customer is detected: by phone and/or email checked against all previous orders.</small>
                                </div>

                                <div class="form-group col-12">
                                    <label>Enable On Orders</label>
                                    <div>
                                        @foreach (App\Models\Coupon::ORDER_TYPE_OPTIONS as $value => $label)
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" name="order_types[]" value="{{ $value }}" id="create_order_type_{{ $value }}" checked>
                                                <label class="form-check-label" for="create_order_type_{{ $value }}">{{ $label }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                    <small class="form-text text-muted">Leave all checked to allow the offer on every order type.</small>
                                </div>

                                <div class="form-group col-12">
                                    <label>Auto Apply (default discount)</label>
                                    <select name="auto_apply" class="form-control">
                                        <option value="0">No - customer must enter the coupon code</option>
                                        <option value="1">Yes - discount applies automatically</option>
                                    </select>
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
                            <h5 class="modal-title">Edit First Time User Offer</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                        </div>
                <div class="modal-body">
                    <div class="container-fluid">
                      <form action="{{ route('admin.coupon.update', $coupon->id) }}" method="POST">
                          @csrf
                          @method('PUT')
                          <div class="row">
                              <div class="form-group col-12">
                                  <label>{{__('admin.Name')}} <span class="text-danger">*</span></label>
                                  <input type="text" class="form-control" name="name" value="{{ $coupon->name }}">
                              </div>
                              <div class="form-group col-12">
                                  <label>{{__('admin.Code')}} <span class="text-danger">*</span></label>
                                  <input type="text" class="form-control" name="code" value="{{ $coupon->code }}">
                              </div>
                              <div class="form-group col-12">
                                  <label>{{__('admin.Number of times')}} <span class="text-danger">*</span></label>
                                  <input type="number" class="form-control" name="number_of_time" value="{{ $coupon->max_quantity }}">
                              </div>

                              <div class="form-group col-12">
                                  <label>{{__('admin.Minimum Purchase Price')}} <span class="text-danger">*</span></label>
                                  <input type="text" class="form-control" name="min_purchase_price" value="{{ $coupon->min_purchase_price }}">
                              </div>

                              <div class="form-group col-12">
                                  <label>{{__('admin.Expired Date')}} <span class="text-danger">*</span></label>
                                  <input type="text" class="form-control datepicker" value="{{ $coupon->expired_date }}" name="expired_date" autocomplete="off">
                              </div>

                              <div class="form-group col-12">
                                  <label>{{__('admin.Discount')}} <span class="text-danger">*</span></label>
                                  <div class="input-group mb-3">
                                      <div class="input-group-prepend">
                                          <select name="offer_type" class="form-control">
                                              <option {{ $coupon->offer_type == 1 ? 'selected' : '' }} value="1">{{__('admin.Percentage')}}(%)</option>
                                              <option {{ $coupon->offer_type == 2 ? 'selected' : '' }} value="2">{{__('admin.Amount')}}({{ $setting->currency_icon }})</option>
                                          </select>
                                      </div>
                                      <input type="text" name="discount" class="form-control" placeholder="Discount" aria-label="Discount" value="{{ $coupon->discount }}">
                                  </div>
                              </div>

                              <div class="form-group col-12">
                                  <label>First Time Customer Check <span class="text-danger">*</span></label>
                                  <select name="first_time_basis" class="form-control">
                                      @foreach (App\Models\Coupon::FIRST_TIME_OPTIONS as $value => $label)
                                          @if ($value === App\Models\Coupon::FIRST_TIME_NONE) @continue @endif
                                          <option {{ ($coupon->first_time_basis ?? 'none') == $value ? 'selected' : '' }} value="{{ $value }}">{{ $label }}</option>
                                      @endforeach
                                  </select>
                              </div>

                              <div class="form-group col-12">
                                  <label>Enable On Orders</label>
                                  @php
                                      $selectedOrderTypes = array_filter((array) $coupon->order_types);
                                  @endphp
                                  <div>
                                      @foreach (App\Models\Coupon::ORDER_TYPE_OPTIONS as $value => $label)
                                          <div class="form-check form-check-inline">
                                              <input class="form-check-input" type="checkbox" name="order_types[]" value="{{ $value }}" id="edit_{{ $coupon->id }}_order_type_{{ $value }}"
                                                  {{ (empty($selectedOrderTypes) || in_array($value, $selectedOrderTypes)) ? 'checked' : '' }}>
                                              <label class="form-check-label" for="edit_{{ $coupon->id }}_order_type_{{ $value }}">{{ $label }}</label>
                                          </div>
                                      @endforeach
                                  </div>
                              </div>

                              <div class="form-group col-12">
                                  <label>Auto Apply (default discount)</label>
                                  <select name="auto_apply" class="form-control">
                                      <option {{ !$coupon->auto_apply ? 'selected' : '' }} value="0">No - customer must enter the coupon code</option>
                                      <option {{ $coupon->auto_apply ? 'selected' : '' }} value="1">Yes - discount applies automatically</option>
                                  </select>
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
</script>
@endsection

@extends('admin.master_layout')
@section('title')
    <title>Edit Delivery Postcode Charge</title>
@endsection
@section('admin-content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Edit Delivery Postcode Charge</h1>
            </div>

            <div class="section-body">
                <a href="{{ route('admin.delivery-postcode-charge.index') }}" class="btn btn-primary">
                    <i class="fas fa-list"></i> Postcode Charges
                </a>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form action="{{ route('admin.delivery-postcode-charge.update', $charge->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label>Postcode <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="postcode" value="{{ old('postcode', $charge->postcode) }}">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Delivery Fee <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="delivery_fee" value="{{ old('delivery_fee', $charge->delivery_fee) }}">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>{{ __('admin.Status') }} <span class="text-danger">*</span></label>
                                            <select name="status" class="form-control">
                                                <option value="1" {{ old('status', $charge->status) == 1 ? 'selected' : '' }}>Active</option>
                                                <option value="0" {{ old('status', $charge->status) == 0 ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <button class="btn btn-primary">{{ __('admin.Update') }}</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

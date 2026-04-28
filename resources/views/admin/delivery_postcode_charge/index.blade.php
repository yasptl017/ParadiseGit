@extends('admin.master_layout')
@section('title')
    <title>Delivery Postcode Charges</title>
@endsection
@section('admin-content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Delivery Postcode Charges</h1>
            </div>

            <div class="section-body">
                <a href="{{ route('admin.delivery-area.index') }}" class="btn btn-secondary mr-2">
                    <i class="fas fa-map-marked-alt"></i> Delivery Areas
                </a>
                <a href="{{ route('admin.delivery-postcode-charge.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Postcode Charge
                </a>
                <div class="row mt-4">
                    <div class="col">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive table-invoice">
                                    <table class="table table-striped" id="dataTable">
                                        <thead>
                                        <tr>
                                            <th>SN</th>
                                            <th>Postcode</th>
                                            <th>Delivery Fee</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach ($charges as $index => $charge)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $charge->postcode }}</td>
                                                <td>{{ $currency_icon }}{{ number_format($charge->delivery_fee, 2) }}</td>
                                                <td>
                                                    @if ($charge->status == 1)
                                                        <span class="badge badge-success">Active</span>
                                                    @else
                                                        <span class="badge badge-danger">Inactive</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('admin.delivery-postcode-charge.edit', $charge->id) }}"
                                                       class="btn btn-primary btn-sm">
                                                        <i class="fa fa-edit" aria-hidden="true"></i>
                                                    </a>
                                                    <a href="javascript:" data-toggle="modal" data-target="#deleteModal"
                                                       class="btn btn-danger btn-sm"
                                                       onclick="deleteData({{ $charge->id }})">
                                                        <i class="fa fa-trash" aria-hidden="true"></i>
                                                    </a>
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
            </div>
        </section>
    </div>

    <script>
        function deleteData(id) {
            $("#deleteForm").attr("action", '{{ url("admin/delivery-postcode-charge/") }}' + "/" + id)
        }
    </script>
@endsection

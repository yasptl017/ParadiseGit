@extends('layout')
@section('title')
    <title>{{__('user.Checkout')}}</title>
@endsection
@section('meta')
    <meta name="description" content="{{__('user.Checkout')}}">
    <style>
        .free-item-coupon {
            background-color: #fff5e6;
            border: 2px dashed #ff7c08;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            position: relative;
            overflow: hidden;
        }

        .free-item-coupon:before {
            content: 'FREE';
            position: absolute;
            top: 10px;
            right: -30px;
            background: #ff7c08;
            color: white;
            padding: 5px 30px;
            transform: rotate(45deg);
            font-size: 12px;
            font-weight: bold;
        }

        .free-item-coupon h4 {
            color: #ff7c08;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .free-item {
            font-size: 16px;
            font-weight: bold;
            color: black;
            margin-bottom: 0px;
        }

        .free-item:before {
            content: '✓ ';
            color: #ff7c08;
        }

        .next-offer {
            font-size: 14px;
            color: #666;
            font-style: italic;
            margin-top: 10px;
        }

        .coupon-scissors {
            position: absolute;
            top: -10px;
            left: 10px;
            font-size: 24px;
            color: #ff7c08;
            transform: rotate(-90deg);
        }
        .common_btn.disabled {
        opacity: 0.6;
        cursor: not-allowed;
        pointer-events: none;
    }
    </style>
@endsection

@section('public-content')

    <!--=============================
        BREADCRUMB START
    ==============================-->
    <section class="tf__breadcrumb" style="background: url({{ asset($breadcrumb) }});">
        <div class="tf__breadcrumb_overlay">
            <div class="container">
                <div class="tf__breadcrumb_text">
                    <h1>Delivery </h1>
                    <ul>
                        <li><a href="{{ route('home') }}">{{__('user.Home')}}</a></li>
                        <li><a href="javascript:">Delivery</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    <!--=============================
        BREADCRUMB END
    ==============================-->

    <!--============================
        CHECK OUT PAGE START
    ==============================-->
    <section class="tf__cart_view mt_30 xs_mt_30 mb_100 xs_mb_70">
        <div class="container">
            <div class="row">
                <!-- Restaurant Details Column -->
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-duration="1s">

                </div>

                <!-- User Details Form Column -->
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-duration="1s" style="margin-bottom:25px;">
                    <div class="tf__checkout_form user-details-container">
                        <div class="tf__check_form">
                            <form action="" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label for="user_name">{{__('user.Name')}}</label>
                                    <input type="text" id="user_name" name="user_name" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="user_email">{{__('user.Email')}}</label>
                                    <input type="email" id="user_email" name="user_email" class="form-control" required>
                                    <label style="color:red;">Confirmation will be sent via email.</label>
                                </div>
                                <div class="form-group">
                                    <label for="user_phone">{{__('user.Phone')}}</label>
                                    <input type="text" id="user_phone" name="user_phone" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="address">{{__('user.Address')}} (Select from Dropdown)</label>
                                    <x-address-input>
                                        <div class="col-md-12 col-lg-12 col-xl-12 position-relative">
                                            <div class="tf__check_single_form">
                                                <input type="text" id="address-input" placeholder="Enter address"
                                                       name="address" autocomplete="off">
                                            </div>
                            
                                            <div id="address-warning" class="alert alert-warning d-none" role="alert">
                                                Please select an address from the dropdown.
                                            </div>
                                        </div>
                                    </x-address-input>
                                </div>
                                <div id="distance-warning" class="alert alert-warning d-none" role="alert">
                                    Not Deliverable: The delivery address is more than 10km away.
                                </div>
                                <div id="distance-warning-dd" class="alert alert-warning d-none" role="alert">
                                    Not Deliverable or try to select from the address dropdown.
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Payment Details Column -->
                <div class="col-lg-4 wow fadeInUp" data-wow-duration="1s">
    @php
        $sub_total = 0;
        $coupon_price = 0.00;
    @endphp
    @foreach ($cart_contents as $index => $cart_content)
        @php
            $item_price = $cart_content->price * $cart_content->qty;
            $item_total = $item_price + $cart_content->options->optional_item_price;
            $sub_total += $item_total;
        @endphp
    @endforeach

    @php
        $coupon_price = App\Models\Coupon::sessionDiscount($sub_total);
    @endphp

    <div id="sticky_sidebar" class="tf__cart_list_footer_button tf__cart_list_footer_button_text">
        <h6>{{__('user.total price')}}</h6>
        <p>{{__('user.subtotal')}}: <span>{{ $currency_icon }}{{ $sub_total }}</span></p>
        <p>{{__('user.discount')}} (-): <span id="discount_display">{{ $currency_icon }}{{ number_format($coupon_price, 2) }}</span></p>
        <p>{{__('user.delivery')}} (+): <span class="delivery_charge">{{ $currency_icon }}0.00</span></p>
        <p class="total"><span>{{__('user.Total')}}:</span> <span class="grand_total">{{ $currency_icon }}{{ $sub_total - $coupon_price }}</span></p>
        <input type="hidden" id="grand_total" value="{{ $sub_total - $coupon_price }}">
        <input type="hidden" id="cart_subtotal" value="{{ $sub_total }}">
        
        <!-- Add the offer display here -->
         <!--
        @if($sub_total >= 50)
            <div class="free-item-coupon">
                <div class="coupon-scissors">✂</div>
                <h4>Offer:</h4>
                @if($sub_total >= 150)
                    <label class="free-item">Free (Butter Chicken / Dal Makhni) and Mix Bread Basket Added To Your Order</label>
                @elseif($sub_total >= 100)
                    <label class="free-item">Free Butter Chicken / Dal Makhni Added To Your Order</label>
                @elseif($sub_total >= 80)
                    <label class="free-item">Free Mix Bread Basket Added To Your Order</label>
                @elseif($sub_total >= 60)
                    <label class="free-item">Free Rice Added To Your Order</label>
                @elseif($sub_total >= 50)
                    <label class="free-item">Free Plain Naan Added To Your Order</label>
                @endif
            </div>
        @endif
        -->
        <div class="form-group">
    <label for="delivery-inst" style="margin-top: 8px;"><b>Delivery Instructions:</b></label>
    <textarea id="delivery-inst" class="form-control" name="delivery-inst" rows="3" placeholder="Enter any special delivery instructions and offer option here..."></textarea>
</div>

@php
    $minimum_order_amount = App\Support\AppSettings::value('minimum_order_amount', env('MINIMUM_AMOUNT', 40));
    $maximum_delivery_distance = App\Support\AppSettings::value('maximum_distance', env('MAXIMUM_DISTANCE', 0));
@endphp

@if($sub_total < $minimum_order_amount)
    <div class="alert alert-warning" role="alert">
        Your order total ({{ $currency_icon }}{{ $sub_total }}) is below the minimum order amount of {{ $currency_icon }}{{ $minimum_order_amount }}. Please add more items to your cart to proceed with the order.
    </div>
@endif

@php
    $minimum_order_amount = App\Support\AppSettings::value('minimum_order_amount', env('MINIMUM_AMOUNT', 40));
    $is_below_minimum = $sub_total < $minimum_order_amount;
@endphp

<a class="common_btn @if($is_below_minimum) disabled @endif" href="javascript:" id="continue_to_pay">
    {{__('user.Continue to pay')}}
</a>


    </div>
</div>

            </div>
        </div>
        <div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.7); z-index: 9999;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: #ffffff;">
        <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;">
            <span class="sr-only">Loading...</span>
        </div>
        <p style="margin-top: 10px; font-size: 1.6rem; font-weight: 600; color:white;">Please wait, we are processing your order...</p>
    </div>
</div>
        <div class="tf__payment_modal">
            <div class="modal fade" id="stripePaymentModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered pay-modal-dialog">
                    <div class="modal-content pay-modal-content">
                        {{-- ── Header ── --}}
                        <div class="pay-modal-header">
                            <div class="pay-modal-logo">
                                <i class="fas fa-lock"></i>
                                <span>Secure Payment</span>
                            </div>
                            <button type="button" class="pay-modal-close" data-bs-dismiss="modal">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        {{-- ── Order summary strip ── --}}
                        <div class="pay-order-summary">
                            <div class="pay-summary-row">
                                <div class="pay-summary-badge">
                                    <i class="fas fa-motorcycle"></i>
                                    Delivery
                                </div>
                                <div class="pay-summary-amounts">
                                    <span class="pay-summary-label">Subtotal</span>
                                    <span class="pay-summary-val">{{ $currency_icon }}{{ number_format($sub_total, 2) }}</span>
                                </div>
                                <div class="pay-summary-amounts pay-summary-discount" id="pay-discount-row" style="{{ $coupon_price > 0 ? '' : 'display:none;' }}">
                                    <span class="pay-summary-label"><i class="fas fa-tag"></i> Discount</span>
                                    <span class="pay-summary-val" id="pay-discount-val">− {{ $currency_icon }}{{ number_format($coupon_price, 2) }}</span>
                                </div>
                                <div class="pay-summary-amounts pay-summary-total" id="pay-modal-total-row">
                                    <span class="pay-summary-label">Total</span>
                                    <span class="pay-summary-val pay-grand-total" id="pay-modal-grand-total">
                                        {{ $currency_icon }}{{ number_format($sub_total - $coupon_price, 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="modal-body pay-modal-body">
                            <form role="form" action="{{ route('stripe-payment') }}" method="POST"
                                  class="require-validation" data-cc-on-file="false"
                                  data-stripe-publishable-key="{{ $stripePaymentInfo->stripe_key }}"
                                  id="payment-form">
                                @csrf

                                {{-- Card number --}}
                                <div class="pay-field-group">
                                    <label class="pay-label">Card Number</label>
                                    <div class="pay-input-wrap">
                                        <i class="far fa-credit-card pay-input-icon"></i>
                                        <input type="text" class="pay-input card-number" name="card_number"
                                               placeholder="1234  5678  9012  3456" autocomplete="off" maxlength="19">
                                        <span class="pay-card-icons">
                                            <i class="fab fa-cc-visa"></i>
                                            <i class="fab fa-cc-mastercard"></i>
                                        </span>
                                    </div>
                                </div>

                                {{-- Expiry + CVC --}}
                                <div class="pay-fields-row">
                                    <div class="pay-field-group pay-field-half">
                                        <label class="pay-label">Month</label>
                                        <div class="pay-input-wrap">
                                            <i class="far fa-calendar pay-input-icon"></i>
                                            <input type="text" class="pay-input card-expiry-month" name="month"
                                                   placeholder="MM" autocomplete="off" maxlength="2">
                                        </div>
                                    </div>
                                    <div class="pay-field-group pay-field-half">
                                        <label class="pay-label">Year</label>
                                        <div class="pay-input-wrap">
                                            <i class="far fa-calendar-alt pay-input-icon"></i>
                                            <input type="text" class="pay-input card-expiry-year" name="year"
                                                   placeholder="YY" autocomplete="off" maxlength="4">
                                        </div>
                                    </div>
                                    <div class="pay-field-group pay-field-half">
                                        <label class="pay-label">CVV</label>
                                        <div class="pay-input-wrap">
                                            <i class="fas fa-lock pay-input-icon"></i>
                                            <input type="text" class="pay-input card-cvc" name="cvc"
                                                   placeholder="•••" autocomplete="off" maxlength="4">
                                        </div>
                                    </div>
                                </div>

                                <div class="error d-none mt-2">
                                    <div class="pay-error-msg">
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{__('user.Please provide your valid card information')}}
                                    </div>
                                </div>

                                <input type="hidden" name="delivery_instructions" id="delivery-instructions-input">

                                <div class="pay-action-row">
                                    <button type="button" class="pay-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="pay-btn-submit" id="pay-submit-btn">
                                        <i class="fas fa-lock"></i>
                                        <span id="pay-btn-label">Pay {{ $currency_icon }}{{ number_format($sub_total - $coupon_price, 2) }}</span>
                                    </button>
                                </div>

                                <p class="pay-secure-note">
                                    <i class="fas fa-shield-alt"></i>
                                    Your payment is encrypted and secure
                                </p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .pay-modal-dialog { max-width: 420px; }
            .pay-modal-content {
                border: none;
                border-radius: 20px;
                overflow: hidden;
                box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            }
            .pay-modal-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 18px 24px 14px;
                background: #fff;
                border-bottom: 1px solid #f0f0f0;
            }
            .pay-modal-logo {
                display: flex;
                align-items: center;
                gap: 8px;
                font-weight: 700;
                font-size: 15px;
                color: #1a1a1a;
            }
            .pay-modal-logo i { color: #ff7c08; font-size: 16px; }
            .pay-modal-close {
                background: #f5f5f5;
                border: none;
                width: 32px;
                height: 32px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #666;
                cursor: pointer;
                transition: all .2s;
            }
            .pay-modal-close:hover { background: #ffe0cc; color: #ff7c08; }

            .pay-order-summary {
                background: #1e1e1e;
                padding: 16px 24px;
                color: #fff;
            }
            .pay-summary-row { display: flex; flex-direction: column; gap: 7px; }
            .pay-summary-badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: #ff7c08;
                border-radius: 20px;
                padding: 3px 12px;
                font-size: 12px;
                font-weight: 600;
                width: fit-content;
                margin-bottom: 4px;
                color: #fff;
            }
            .pay-summary-amounts {
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 13px;
            }
            .pay-summary-label { color: #aaa; font-weight: 500; }
            .pay-summary-val { font-weight: 600; color: #fff; }
            .pay-summary-discount .pay-summary-label { color: #4caf50; }
            .pay-summary-discount .pay-summary-val { color: #4caf50; }
            .pay-summary-total {
                border-top: 1px solid #333;
                padding-top: 10px;
                margin-top: 4px;
                font-size: 15px;
            }
            .pay-grand-total { font-size: 22px; font-weight: 800; color: #ff7c08; }

            .pay-modal-body { padding: 20px 24px 16px; background: #fff; }

            .pay-field-group { margin-bottom: 14px; }
            .pay-label {
                display: block;
                font-size: 11px;
                font-weight: 700;
                color: #888;
                text-transform: uppercase;
                letter-spacing: .5px;
                margin-bottom: 6px;
            }
            .pay-input-wrap {
                position: relative;
                display: flex;
                align-items: center;
            }
            .pay-input-icon {
                position: absolute;
                left: 12px;
                color: #bbb;
                font-size: 14px;
                pointer-events: none;
            }
            .pay-input {
                width: 100%;
                border: 1.5px solid #e8e8e8;
                border-radius: 10px;
                padding: 11px 12px 11px 36px;
                font-size: 14px;
                color: #1a1a1a;
                background: #fafafa;
                transition: all .2s;
                outline: none;
            }
            .pay-input:focus {
                border-color: #ff7c08;
                background: #fff;
                box-shadow: 0 0 0 3px rgba(255,124,8,.1);
            }
            .pay-card-icons {
                position: absolute;
                right: 12px;
                display: flex;
                gap: 4px;
                font-size: 20px;
                color: #bbb;
            }
            .pay-fields-row {
                display: grid;
                grid-template-columns: 1fr 1fr 1fr;
                gap: 10px;
            }
            .pay-error-msg {
                background: #fff3f3;
                border: 1px solid #ffcdd2;
                border-radius: 8px;
                padding: 10px 14px;
                font-size: 13px;
                color: #c62828;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .pay-action-row {
                display: flex;
                gap: 10px;
                margin-top: 18px;
            }
            .pay-btn-cancel {
                flex: 0 0 auto;
                padding: 12px 18px;
                border: 1.5px solid #e0e0e0;
                border-radius: 10px;
                background: #fff;
                color: #666;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                transition: all .2s;
            }
            .pay-btn-cancel:hover { background: #f5f5f5; border-color: #ccc; }
            .pay-btn-submit {
                flex: 1;
                padding: 14px 20px;
                border: none;
                border-radius: 10px;
                /* Darker orange so white text keeps a strong contrast ratio */
                background: linear-gradient(135deg, #e06c00, #b85400);
                color: #ffffff !important;
                font-size: 16px;
                font-weight: 800;
                letter-spacing: .3px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                transition: all .2s;
                box-shadow: 0 4px 15px rgba(224,108,0,.35);
            }
            /* Force the label + icon white regardless of inherited theme colours */
            .pay-btn-submit #pay-btn-label,
            .pay-btn-submit span,
            .pay-btn-submit i {
                color: #ffffff !important;
                font-weight: 800;
            }
            .pay-btn-submit:hover {
                transform: translateY(-1px);
                background: linear-gradient(135deg, #b85400, #9c4700);
                box-shadow: 0 6px 20px rgba(224,108,0,.45);
            }
            .pay-secure-note {
                text-align: center;
                font-size: 12px;
                color: #aaa;
                margin-top: 12px;
                margin-bottom: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 5px;
            }
            .pay-secure-note i { color: #4caf50; }
        </style>
    </section>
    <!-- Bottom Navigation Bar -->
<nav class="bottom-nav">
    <a href="{{ route('home') }}" class="bottom-nav-item">
        <i class="fas fa-utensils"></i>
        <span>Menu</span>
    </a>
    <a href="{{ route('reserve-table') }}" class="bottom-nav-item">
        <i class="far fa-calendar-alt"></i>
        <span>Reservation</span>
    </a>
    <a href="{{ route('offers') }}" class="bottom-nav-item">
        <i class="fas fa-percent"></i>
        <span>Offers</span>
    </a>
</nav>

<style>
    .accordion-button {
        background-color: #f8f9fa;
        color: #333;
        font-weight: bold;
    }
    .accordion-button:not(.collapsed) {
        background-color: #e9ecef;
        color: #0056b3;
    }
    .accordion-button::after {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23333'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
    }
    .tf__menu_item {
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .tf__menu_item_text {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    .tf__add_to_cart {
        margin-top: auto;
    }

    /* Bottom Navigation Styles */
    .bottom-nav {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background-color: #fff;
        display: flex;
        justify-content: space-around;
        padding: 10px 0;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        z-index: 1000;
    }

    .bottom-nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        color: #333;
        text-decoration: none;
    }

    .bottom-nav-item i {
        font-size: 24px;
        margin-bottom: 5px;
    }

    .bottom-nav-item span {
        font-size: 12px;
    }

    /* Adjust main content to account for bottom nav */
    body {
        padding-bottom: 70px;
    }
</style>
    <!--============================
        CHECK OUT PAGE END
    ==============================-->
    <script type="text/javascript" src="https://js.stripe.com/v2/"></script>
    <script>
(function ($) {
    "use strict";

    $(document).ready(function () {

        function checkCardDetails() {
            var cardNumber = $('.card-number').val().trim();
            var cardCvc = $('.card-cvc').val().trim();
            var cardMonth = $('.card-expiry-month').val().trim();
            var cardYear = $('.card-expiry-year').val().trim();

            if (cardNumber && cardCvc && cardMonth && cardYear) {
                $('#stripe-submit-btn').prop('disabled', false);
            } else {
                $('#stripe-submit-btn').prop('disabled', true);
            }
        }

        // Add event listeners to card detail inputs
        $('.card-number, .card-cvc, .card-expiry-month, .card-expiry-year').on('input', checkCardDetails);

        // Function to validate user details
        function validateUserDetails() {
            var name = $('#user_name').val();
            var email = $('#user_email').val();
            var phone = $('#user_phone').val();
            var address = $('#address-input').val();
            var phonePattern = /^\d{10}$/;
            if (!name || !email || !phone || !address) {
                alert("Please fill in all required fields: Name, Email, Phone (10 Digit), and Address.");
                return false;
            }
            else if (!phonePattern.test(phone)) {
                alert("Please enter a valid 10-digit phone number.");
                return false;
            }
            return true;
        }

        // Function to check distance and show warning
        function checkDistanceAndWarn() {
            var distance = parseFloat($('#distance').val()) / 1000; // Convert to km
            var maximumDistance = parseFloat('{{ $maximum_delivery_distance }}');
            if (maximumDistance > 0 && distance > maximumDistance) {
                $('#continue_to_pay').css('display', 'none');
                $('#distance-warning').removeClass('d-none');
            } 
            else if(isNaN(distance)){
                $('#continue_to_pay').css('display', 'none');
                $('#distance-warning-dd').removeClass('d-none');
            }
            else {
                $('#distance-warning').addClass('d-none');
                $('#distance-warning-dd').addClass('d-none'); // Hide the other warning as well
                $('#continue_to_pay').css('display', 'block'); // Re-enable the button
            }
        }

        // Attach event to address input
        $('#address-input').on('change', function() {
            setTimeout(checkDistanceAndWarn, 1000); // Delay to allow distance calculation
        });

        // Attach click event to "Continue to pay" button
        $('#continue_to_pay').on('click', function(e) {
    e.preventDefault();
    
    var subTotal = parseFloat('{{ $sub_total }}');
    var minimumAmount = parseFloat('{{ App\Support\AppSettings::value('minimum_order_amount', env('MINIMUM_AMOUNT', 40)) }}');
    
    if (subTotal < minimumAmount) {
        alert('Your order total is below the minimum order amount. Please add more items to your cart to proceed.');
        return;
    }
    
    if (validateUserDetails()) {
        checkDistanceAndWarn();
        $('#stripePaymentModal').modal('show');
    }
});

        // Function to handle form submission
        $('form.require-validation').bind('submit', function (e) {
            var $form = $(".require-validation"),
                inputSelector = ['input[type=email]', 'input[type=password]',
                    'input[type=text]', 'input[type=file]',
                    'textarea'].join(', '),
                $inputs = $form.find('.required').find(inputSelector),
                $errorMessage = $form.find('div.error'),
                valid = true;
            e.preventDefault();
            var deliveryInstructions = $('#delivery-inst').val();
            $('#delivery-instructions-input').val(deliveryInstructions);

            // Append user details to the form as hidden inputs
            $form.append('<input type="hidden" name="user_name" value="' + $('#user_name').val() + '">');
            $form.append('<input type="hidden" name="user_email" value="' + $('#user_email').val() + '">');
            $form.append('<input type="hidden" name="user_phone" value="' + $('#user_phone').val() + '">');
            $form.append('<input type="hidden" name="address" value="' + $('#address-input').val() + '">');
            $form.append('<input type="hidden" name="distance" value="' + ($('#distance').val() || '') + '">');
            $form.append('<input type="hidden" name="postal_code" value="' + ($('#postal_code').val() || '') + '">');
            $form.append('<input type="hidden" name="delivery_instructions" value="' + $('#delivery-inst').val() + '">');
            // Validate other form inputs
            $errorMessage.addClass('d-none');
            $('.has-error').removeClass('has-error');
            $inputs.each(function (i, el) {
                var $input = $(el);
                if ($input.val() === '') {
                    $input.parent().addClass('has-error');
                    $errorMessage.removeClass('d-none');
                    valid = false;
                }
            });

            if (valid) {
                // Proceed with Stripe payment form submission
                if (!$form.data('cc-on-file')) {
                    Stripe.setPublishableKey($form.data('stripe-publishable-key'));
                    Stripe.createToken({
                        number: $('.card-number').val(),
                        cvc: $('.card-cvc').val(),
                        exp_month: $('.card-expiry-month').val(),
                        exp_year: $('.card-expiry-year').val()
                    }, stripeResponseHandler);
                }
            }
        });

        // Function to handle Stripe response
        function stripeResponseHandler(status, response) {
            var $form = $('#payment-form');

            if (response.error) {
                // Display error message
                $('.error')
                    .removeClass('d-none')
                    .find('.alert')
                    .text(response.error.message);
            } else {
                // Tokenize the form and submit to backend
                var token = response['id'];
                $form.find('input[type=text]').empty();
                $form.append("<input type='hidden' name='stripeToken' value='" + token + "'/>");

                // Submit the form to backend (assuming action attribute is set to stripe-payment route)
                $form.get(0).submit();
            }
        }

        // Force user to select from autocomplete
        $('#address-input').on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $('#address-input').val(''); // Clear input
                $('#address-warning').removeClass('d-none'); // Show warning
            }
        });

        $('#address-input').on('blur', function() {
            if (!$(this).val()) {
                $('#address-warning').removeClass('d-none'); // Show warning if input is empty
            }
        });

    });

    // Force user to select from autocomplete
    $('#address-input').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#address-input').val(''); // Clear input
            $('#address-warning').removeClass('d-none'); // Show warning
        }
    });

    $('#address-input').on('blur', function() {
        if (!$(this).val()) {
            $('#address-warning').removeClass('d-none'); // Show warning if input is empty
        }
    });

    // Hide address warning on typing
    $('#address-input').on('input', function() {
        $('#address-warning').addClass('d-none'); // Hide warning
    });

    function updateCheckoutTotals(fee) {
        const deliveryFee = parseFloat(fee) || 0;
        const baseTotal = parseFloat(document.querySelector('#grand_total').value) || 0;
        const payableTotal = baseTotal + deliveryFee;
        const formattedTotal = '{{ $currency_icon }}' + payableTotal.toFixed(2);

        document.querySelector('.delivery_charge').innerText = '{{ $currency_icon }}' + deliveryFee.toFixed(2);
        document.querySelector('.grand_total').innerText = formattedTotal;

        const modalTotal = document.querySelector('#pay-modal-grand-total');
        const payButtonLabel = document.querySelector('#pay-btn-label');

        if (modalTotal) {
            modalTotal.innerText = formattedTotal;
        }

        if (payButtonLabel) {
            payButtonLabel.innerText = 'Pay ' + formattedTotal;
        }
    }

    function syncDeliveryCharge() {
        const deliveryDistance = document.querySelector("#distance").value;
        const postalCode = document.querySelector("#postal_code").value;
        const subTotal = document.querySelector('#cart_subtotal').value;

        if (!deliveryDistance) {
            $('#continue_to_pay').css('display', 'none');
            $('#distance-warning-dd').removeClass('d-none');
            return;
        }

        $.ajax({
            url: "{{ route('set-delivery-charge') }}",
            type: "GET",
            data: {
                distance: deliveryDistance,
                postal_code: postalCode,
                subtotal: subTotal
            },
            success: function (response) {
                if (!response.available) {
                    $('#continue_to_pay').css('display', 'none');
                    $('#distance-warning').removeClass('d-none');
                    toastr.error('Delivery is not available for this location');
                    return;
                }

                $('#distance-warning').addClass('d-none');
                $('#distance-warning-dd').addClass('d-none');
                $('#continue_to_pay').css('display', 'block');
                updateCheckoutTotals(response.charge);
                $('#continue_to_pay').prop('disabled', false);
            },
            error: function () {
                toastr.error("{{ __('user.Server error occured') }}");
            }
        });
    }

    document.addEventListener('distance-loaded', function () {
        syncDeliveryCharge();
    });

})(jQuery);

    </script>
    <script>
  // Get the textbox element
  const textbox = document.getElementById('address-input');

  // Define the function to be called when typing
  function handleTyping(event) {
    addressWarning.classList.add('d-none');
  }
  textbox.addEventListener('input', handleTyping);
</script>
<script>

document.getElementById('payment-form').addEventListener('submit', function() {
    document.getElementById('loading-overlay').style.display = 'block';
});
</script>

<script>
    // Live offer check: first-time customer offers are only applied once the
    // email/phone are known, and re-checked whenever those fields change.
    (function ($) {
        "use strict";

        var offerRefreshTimer = null;
        var lastOfferCode = {{ Js::from(Session::get('coupon_name')) }};

        function currentDeliveryFee() {
            var text = $('.delivery_charge').first().text() || '';
            var fee = parseFloat(text.replace(/[^0-9.]/g, ''));
            return isNaN(fee) ? 0 : fee;
        }

        function renderOfferAmounts(response) {
            var discountText = '{{ $currency_icon }}' + response.coupon_price.toFixed(2);
            var payableTotal = response.grand_total_base + currentDeliveryFee();
            var totalText = '{{ $currency_icon }}' + payableTotal.toFixed(2);

            $('#discount_display').text(discountText);
            $('#grand_total').val(response.grand_total_base);
            $('.grand_total').text(totalText);

            if (response.coupon_price > 0) {
                $('#pay-discount-row').show();
                $('#pay-discount-val').text('− ' + discountText);
            } else {
                $('#pay-discount-row').hide();
            }
            $('#pay-modal-grand-total').text(totalText);
            $('#pay-btn-label').text('Pay ' + totalText);

            if (response.coupon_name && response.coupon_name !== lastOfferCode) {
                toastr.success('Offer applied - discount ' + discountText);
            } else if (!response.coupon_name && lastOfferCode) {
                toastr.info('The offer is not available for these details.');
            }
            lastOfferCode = response.coupon_name;
        }

        function refreshOffer() {
            $.ajax({
                type: 'get',
                url: "{{ route('refresh-offer') }}",
                data: {
                    email: $('#user_email').val(),
                    phone: $('#user_phone').val()
                },
                success: renderOfferAmounts
            });
        }

        $('#user_email, #user_phone').on('input change blur', function () {
            clearTimeout(offerRefreshTimer);
            offerRefreshTimer = setTimeout(refreshOffer, 500);
        });

        // Initial check on page load: clears any stale discount from a
        // previous session and applies offers valid without identity.
        $(document).ready(refreshOffer);
    })(jQuery);
</script>

@endsection

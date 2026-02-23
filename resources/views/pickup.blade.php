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
            color: #333;
            margin-bottom: 10px;
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
                    <h1>PickUp</h1>
                    <ul>
                        <li><a href="{{ route('home') }}">{{__('user.Home')}}</a></li>
                        <li><a href="javascript:;">PickUp</a></li>
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
                    <div class="tf__checkout_form restaurant-container">
                        <div class="tf__check_form">
                            <h2 class="restaurant-title">Punjabi Paradise</h2>
                            <div class="restaurant-details">
                                <p><strong>Address:</strong> 419 High St, Penrith NSW 2750, Australia</p>
                                <p><strong>Phone:</strong> (02) 4707 6700</p>
                                <p><strong>Location:</strong> <a href="https://maps.app.goo.gl/VSnVXbY2jN26nFiR7" style="color: blue;" target="_blank">Click Here</a></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Details Form Column -->
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-duration="1s" style="margin-bottom:25px;">
                    <div class="tf__checkout_form user-details-container">
                        <div class="tf__check_form">
                            <form id="user_details_form">
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
                                <input type="hidden" name="delivery_instructions" id="hidden_delivery_instructions">
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

                    @if (Session::get('coupon_price') && Session::get('offer_type'))
                        @php
                            if(Session::get('offer_type') == 1) {
                                $coupon_price = Session::get('coupon_price');
                                $coupon_price = ($coupon_price / 100) * $sub_total;
                            } else {
                                $coupon_price = Session::get('coupon_price');
                            }
                        @endphp
                    @endif

                    <div id="sticky_sidebar" class="tf__cart_list_footer_button tf__cart_list_footer_button_text">
        <h6>{{__('user.total price')}}</h6>
        <p>{{__('user.subtotal')}}: <span>{{ $currency_icon }}{{ $sub_total }}</span></p>
        <p>{{__('user.discount')}} (-): <span>{{ $currency_icon }}{{ $coupon_price }}</span></p>
        <p>{{__('user.delivery')}} (+): <span class="delivery_charge">{{ $currency_icon }}0.00</span></p>
        <p class="total"><span>{{__('user.Total')}}:</span> <span class="grand_total">{{ $currency_icon }}{{ number_format($sub_total - $coupon_price, 2) }}</span></p>
        <input type="hidden" id="grand_total" value="{{ $sub_total - $coupon_price }}">
        
       
        <!-- Add the offer display here -->
        @php
            $total = $sub_total - $coupon_price;
        @endphp
         <!--
        @if($total >= 50)
            <div class="free-item-coupon">
                <div class="coupon-scissors">✂</div>
                <h4>Offer:</h4>
                @if($total >= 150)
                    <label class="free-item">Free (Butter Chicken / Dal Makhni) and Mix Bread Basket Added To Your Order</label>
                @elseif($total >= 100)
                    <label class="free-item">Free Butter Chicken / Dal Makhni Added To Your Order</label>
                @elseif($total >= 80)
                    <label class="free-item">Free Mix Bread Basket Added To Your Order</label>
                @elseif($total >= 60)
                    <label class="free-item">Free Rice Added To Your Order</label>
                @elseif($total >= 50)
                    <label class="free-item">Free Plain Naan Added To Your Order</label>
                @endif
                <br/>
            </div>
        @endif
        -->
        <div class="form-group" style="margin-top:8px;">
        <label for="delivery-instructions"><b>Instructions:</b></label>
        <textarea id="delivery-inst" class="form-control" name="delivery-inst" rows="3" placeholder="Enter any special delivery instructions and offer option here..."></textarea>
        </div>
 
     <a class="common_btn" href="javascript:;" id="continue_to_pay">{{__('user.Continue to pay')}}</a>
    
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
                                    <i class="fas fa-shopping-bag"></i>
                                    Pickup
                                </div>
                                <div class="pay-summary-amounts">
                                    <span class="pay-summary-label">Subtotal</span>
                                    <span class="pay-summary-val">{{ $currency_icon }}{{ number_format($sub_total, 2) }}</span>
                                </div>
                                @if($coupon_price > 0)
                                <div class="pay-summary-amounts pay-summary-discount">
                                    <span class="pay-summary-label"><i class="fas fa-tag"></i> Discount</span>
                                    <span class="pay-summary-val">− {{ $currency_icon }}{{ number_format($coupon_price, 2) }}</span>
                                </div>
                                @endif
                                <div class="pay-summary-amounts pay-summary-total">
                                    <span class="pay-summary-label">Total</span>
                                    <span class="pay-summary-val pay-grand-total">{{ $currency_icon }}{{ number_format($sub_total - $coupon_price, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="modal-body pay-modal-body">
                            <form role="form" action="{{ route('stripe-payment') }}" method="POST" class="require-validation" data-cc-on-file="false" data-stripe-publishable-key="{{ $stripePaymentInfo->stripe_key }}" id="payment-form">
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

                                <div class="pay-action-row">
                                    <button type="button" class="pay-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="pay-btn-submit" id="stripe-submit-btn">
                                        <i class="fas fa-lock"></i>
                                        Pay {{ $currency_icon }}{{ number_format($sub_total - $coupon_price, 2) }}
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
            .pay-field-half {}

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
                padding: 12px 20px;
                border: none;
                border-radius: 10px;
                background: linear-gradient(135deg, #ff7c08, #e06c00);
                color: #fff;
                font-size: 15px;
                font-weight: 700;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                transition: all .2s;
                box-shadow: 0 4px 15px rgba(255,124,8,.35);
            }
            .pay-btn-submit:hover {
                transform: translateY(-1px);
                box-shadow: 0 6px 20px rgba(255,124,8,.45);
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
    (function($) {
        "use strict";
        
        $(document).ready(function () {

           // document.getElementById('stripe-submit-btn').addEventListener('click', function() { this.disabled = true; });
           $('#stripe-submit-btn').prop('disabled', true);

        // Function to check if all card details are filled
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

            // Function to handle form submission
            $('form.require-validation').bind('submit', function(e) {
                var $form = $(".require-validation"),
                    inputSelector = ['input[type=email]', 'input[type=password]',
                                     'input[type=text]', 'input[type=file]',
                                     'textarea'].join(', '),
                    $inputs = $form.find('.required').find(inputSelector),
                    $errorMessage = $form.find('div.error'),
                    valid = true;

                // Prevent default form submission
                e.preventDefault();

                // Append user details to the form as hidden inputs
                $form.append('<input type="hidden" name="user_name" value="' + $('#user_name').val() + '">');
                $form.append('<input type="hidden" name="user_email" value="' + $('#user_email').val() + '">');
                $form.append('<input type="hidden" name="user_phone" value="' + $('#user_phone').val() + '">');
                $form.append('<input type="hidden" name="delivery_instructions" value="' + $('#hidden_delivery_instructions').val() + '">');

                // Validate other form inputs
                $errorMessage.addClass('d-none');
                $('.has-error').removeClass('has-error');
                $inputs.each(function(i, el) {
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

            // Handle change in address (not directly related to Stripe, but for delivery charge)
            $("input[name='address_id']").on("change", function() {
                var delivery_id = $("input[name='address_id']:checked").val();
                $(".delivery_charge").html(`{{ $currency_icon }}${0}`);
                let grand_total = $("#grand_total").val();
                grand_total = parseInt(grand_total) + parseInt(0);
                $(".grand_total").html(`{{ $currency_icon }}${grand_total}`);
                
                // Ajax call to update delivery charge (if required)
                $.ajax({
                    type: 'get',
                    data: { delivery_id: delivery_id },
                    url: "{{ url('/set-delivery-charge') }}",
                    success: function (response) {
                        console.log(response);
                    },
                    error: function(response) {
                        toastr.error("{{__('user.Server error occured')}}")
                    }
                });
            });

            // Handle continue to pay button click
            $("#continue_to_pay").on("click", function(e) {
                e.preventDefault();
                var name = $("#user_name").val();
                var email = $("#user_email").val();
                var phone = $("#user_phone").val();
                var deliveryInstructions = $("#delivery-inst").val();
                var phonePattern = /^\d{10}$/;

                if (name === '' || email === '' || phone === '') {
                    alert("Please fill in all required fields: Name, Email, and Phone.");
                } 
                else if (!phonePattern.test(phone)) {
                alert("Please enter a valid 10-digit phone number.");
                return false;
                }
                else {
                    $("#hidden_delivery_instructions").val(deliveryInstructions);
                    $("#stripePaymentModal").modal('show');
                }
            });

        });
    })(jQuery);
</script>
<script>
    
document.getElementById('payment-form').addEventListener('submit', function() {
    document.getElementById('stripe-submit-btn').addEventListener('click', function() { this.disabled = true; });
    document.getElementById('loading-overlay').style.display = 'block';
});
</script>

@endsection
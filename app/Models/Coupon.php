<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class Coupon extends Model
{
    use HasFactory;

    const FIRST_TIME_NONE = 'none';
    const FIRST_TIME_NEW_PHONE = 'new_phone';
    const FIRST_TIME_NEW_EMAIL = 'new_email';
    const FIRST_TIME_NEW_PHONE_AND_EMAIL = 'new_phone_and_email';
    const FIRST_TIME_NEW_PHONE_OR_EMAIL = 'new_phone_or_email';

    const FIRST_TIME_OPTIONS = [
        self::FIRST_TIME_NONE => 'Any customer',
        self::FIRST_TIME_NEW_PHONE => 'First time (new phone)',
        self::FIRST_TIME_NEW_EMAIL => 'First time (new email)',
        self::FIRST_TIME_NEW_PHONE_AND_EMAIL => 'First time (new phone AND new email)',
        self::FIRST_TIME_NEW_PHONE_OR_EMAIL => 'First time (new phone OR new email)',
    ];

    const ORDER_TYPE_OPTIONS = [
        'dine_in' => 'Dine In',
        'pickup' => 'Pick Up',
        'delivery' => 'Delivery',
    ];

    const KIND_COUPON = 'coupon';
    const KIND_BUY_GET_DISCOUNT = 'buy_get_discount';
    const KIND_BUY_GET_FREE_PRODUCT = 'buy_get_free_product';

    const KIND_OPTIONS = [
        self::KIND_COUPON => 'Coupon',
        self::KIND_BUY_GET_DISCOUNT => 'Buy & Get Offer (spend threshold discount)',
        self::KIND_BUY_GET_FREE_PRODUCT => 'Buy & Get Free Product',
    ];

    protected $casts = [
        'order_types' => 'array',
        'auto_apply' => 'boolean',
    ];

    /**
     * Normalize the different order type spellings used across the app
     * (POS: DineIn/Pickup/Delivery, web session order_type: 1/2, order rows: 'Dine In')
     * to the canonical slugs stored on the coupon.
     */
    public static function normalizeOrderType($type)
    {
        if ($type === null || $type === '') {
            return null;
        }

        $key = strtolower(preg_replace('/[\s_\-]+/', '', (string) $type));

        switch ($key) {
            case 'dinein':
                return 'dine_in';
            case 'pickup':
            case '1':
                return 'pickup';
            case 'delivery':
            case '2':
                return 'delivery';
            default:
                return null;
        }
    }

    public function allowsOrderType($type)
    {
        $allowed = array_filter((array) $this->order_types);
        if (empty($allowed)) {
            return true; // no restriction
        }

        $normalized = self::normalizeOrderType($type);
        if ($normalized === null) {
            return true; // order type not known yet (e.g. cart page) - checked again later
        }

        return in_array($normalized, $allowed, true);
    }

    public function orderTypeLabels()
    {
        $allowed = array_filter((array) $this->order_types);
        if (empty($allowed)) {
            return 'All';
        }

        return implode(', ', array_map(function ($type) {
            return self::ORDER_TYPE_OPTIONS[$type] ?? $type;
        }, $allowed));
    }

    public function firstTimeLabel()
    {
        return self::FIRST_TIME_OPTIONS[$this->first_time_basis ?? self::FIRST_TIME_NONE]
            ?? self::FIRST_TIME_OPTIONS[self::FIRST_TIME_NONE];
    }

    protected static function isRealEmail($email)
    {
        if (!$email) {
            return false;
        }
        $email = trim($email);

        return filter_var($email, FILTER_VALIDATE_EMAIL)
            && !in_array(strtolower($email), ['no@email.com', 'johndoe@gmail.com'], true);
    }

    protected static function isRealPhone($phone)
    {
        if (!$phone) {
            return false;
        }
        $digits = preg_replace('/\D+/', '', $phone);

        return strlen($digits) >= 6;
    }

    protected static function emailHasOrdered($email)
    {
        return OrderAddress::where('email', trim($email))->exists();
    }

    protected static function phoneHasOrdered($phone)
    {
        return OrderAddress::where('phone', trim($phone))->exists();
    }

    /**
     * Check the first-time-customer rule against the given identity.
     *
     * Returns null when eligible, otherwise an error message.
     * When $strict is false, missing identity information is tolerated
     * (used at cart stage for guests - the final check runs again at payment).
     */
    public function firstTimeError($email = null, $phone = null, $strict = true)
    {
        $basis = $this->first_time_basis ?? self::FIRST_TIME_NONE;
        if ($basis === self::FIRST_TIME_NONE) {
            return null;
        }

        $hasEmail = self::isRealEmail($email);
        $hasPhone = self::isRealPhone($phone);
        $notEligible = 'This offer is only available for first time customers';

        switch ($basis) {
            case self::FIRST_TIME_NEW_PHONE:
                if (!$hasPhone) {
                    return $strict ? 'A phone number is required to use this first time customer offer' : null;
                }
                return self::phoneHasOrdered($phone) ? $notEligible : null;

            case self::FIRST_TIME_NEW_EMAIL:
                if (!$hasEmail) {
                    return $strict ? 'An email address is required to use this first time customer offer' : null;
                }
                return self::emailHasOrdered($email) ? $notEligible : null;

            case self::FIRST_TIME_NEW_PHONE_AND_EMAIL:
                if (!$hasPhone || !$hasEmail) {
                    return $strict ? 'A phone number and email address are required to use this first time customer offer' : null;
                }
                return (self::phoneHasOrdered($phone) || self::emailHasOrdered($email)) ? $notEligible : null;

            case self::FIRST_TIME_NEW_PHONE_OR_EMAIL:
                if (!$hasPhone && !$hasEmail) {
                    return $strict ? 'A phone number or email address is required to use this first time customer offer' : null;
                }
                $phoneIsNew = $hasPhone && !self::phoneHasOrdered($phone);
                $emailIsNew = $hasEmail && !self::emailHasOrdered($email);
                return ($phoneIsNew || $emailIsNew) ? null : $notEligible;
        }

        return null;
    }

    /**
     * Full validation of this coupon for a given context.
     * Returns null when the coupon can be used, otherwise an error message.
     */
    public function validateFor($subTotal, $orderType = null, $email = null, $phone = null, $strict = true)
    {
        if ((int) $this->status !== 1) {
            return 'Invalid Coupon';
        }

        if ($this->expired_date < date('Y-m-d')) {
            return 'Coupon already expired';
        }

        if ($this->apply_qty >= $this->max_quantity) {
            return 'Sorry! You can not apply this coupon';
        }

        if ((float) $subTotal < (float) $this->min_purchase_price) {
            return 'Minimum purchase of ' . $this->min_purchase_price . ' is required for this coupon';
        }

        if (!$this->allowsOrderType($orderType)) {
            return 'This offer is not available for the selected order type';
        }

        return $this->firstTimeError($email, $phone, $strict);
    }

    /**
     * The money amount this coupon takes off the given subtotal.
     * Percentage discounts are capped by max_discount when it is set.
     */
    public function discountAmount($subTotal)
    {
        if ((int) $this->offer_type === 1) {
            $amount = round(((float) $this->discount / 100) * (float) $subTotal, 2);

            if ((float) $this->max_discount > 0 && $amount > (float) $this->max_discount) {
                $amount = (float) $this->max_discount;
            }

            return $amount;
        }

        return (float) $this->discount;
    }

    public function isBuyGetDiscount()
    {
        return $this->offer_kind === self::KIND_BUY_GET_DISCOUNT;
    }

    public function isBuyGetFreeProduct()
    {
        return $this->offer_kind === self::KIND_BUY_GET_FREE_PRODUCT;
    }

    public function giftProduct()
    {
        return $this->belongsTo(Product::class, 'gift_product_id');
    }

    /**
     * Find the best auto-apply (no-code) discount for the context: either a
     * plain coupon marked Auto Apply, or a "Buy & Get" spend-threshold
     * discount (which always auto-applies). Only one discount ever applies
     * at a time, so this picks whichever is worth the most on this cart.
     */
    public static function findAutoApply($subTotal, $orderType = null, $email = null, $phone = null, $strict = true)
    {
        $candidates = static::where('status', 1)
            ->where(function ($query) {
                $query->where('offer_kind', self::KIND_BUY_GET_DISCOUNT)
                    ->orWhere(function ($q) {
                        $q->where('auto_apply', 1)
                            ->where(function ($q2) {
                                $q2->where('offer_kind', self::KIND_COUPON)->orWhereNull('offer_kind');
                            });
                    });
            })
            ->get();

        $valid = $candidates->filter(function ($coupon) use ($subTotal, $orderType, $email, $phone, $strict) {
            return $coupon->validateFor($subTotal, $orderType, $email, $phone, $strict) === null;
        });

        // Prefer the coupon giving the biggest discount for this subtotal
        return $valid->sortByDesc(function ($coupon) use ($subTotal) {
            return $coupon->discountAmount($subTotal);
        })->first();
    }

    /**
     * Find the best "Buy & Get Free Product" offer for the context.
     * "Best" = the one with the highest min purchase price the cart still
     * clears, so a bigger spend unlocks the better gift instead of both firing.
     */
    public static function findBuyGetFreeProduct($subTotal, $orderType = null, $email = null, $phone = null, $strict = true)
    {
        $candidates = static::where('status', 1)
            ->where('offer_kind', self::KIND_BUY_GET_FREE_PRODUCT)
            ->whereNotNull('gift_product_id')
            ->get();

        $valid = $candidates->filter(function ($coupon) use ($subTotal, $orderType, $email, $phone, $strict) {
            return $coupon->validateFor($subTotal, $orderType, $email, $phone, $strict) === null;
        });

        return $valid->sortByDesc(function ($coupon) {
            return (float) $coupon->min_purchase_price;
        })->first();
    }

    /**
     * Same idea as syncGiftInCart() but for the POS cart, which is a plain
     * array stored on the POSTable model rather than the shoppingcart
     * package. Returns the (possibly updated) cart array.
     */
    public static function syncGiftInPosCartArray(array $cartContents, $subTotal, $orderType = null, $email = null, $phone = null, $strict = true)
    {
        $existingGiftIndex = null;
        foreach ($cartContents as $index => $item) {
            if (!empty($item['options']['is_gift'])) {
                $existingGiftIndex = $index;
                break;
            }
        }

        $offer = static::findBuyGetFreeProduct($subTotal, $orderType, $email, $phone, $strict);

        if (!$offer || !$offer->giftProduct) {
            if ($existingGiftIndex !== null) {
                unset($cartContents[$existingGiftIndex]);
                return array_values($cartContents);
            }
            return $cartContents;
        }

        if ($existingGiftIndex !== null) {
            $existing = $cartContents[$existingGiftIndex];
            if ((int) $existing['product_id'] === (int) $offer->gift_product_id
                && (int) $existing['options']['coupon_id'] === (int) $offer->id) {
                return $cartContents; // already applied
            }
            unset($cartContents[$existingGiftIndex]);
            $cartContents = array_values($cartContents);
        }

        $product = $offer->giftProduct;

        $cartContents[] = [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'product_id' => $product->id,
            'name' => $product->name . ' (Free Gift)',
            'qty' => $offer->gift_qty ?: 1,
            'price' => 0,
            'weight' => 1,
            'options' => [
                'image' => $product->thumb_image,
                'slug' => $product->slug,
                'size' => 'Regular',
                'size_price' => 0,
                'optional_items' => [],
                'optional_item_price' => 0,
                'is_gift' => true,
                'coupon_id' => $offer->id,
                'coupon_code' => $offer->code,
            ],
        ];

        return $cartContents;
    }

    public function maxDiscountLabel($currencyIcon = '')
    {
        if ((int) $this->offer_type !== 1 || !((float) $this->max_discount > 0)) {
            return null;
        }

        return 'Max discount ' . $currencyIcon . number_format($this->max_discount, 2);
    }

    /**
     * Count one use of the coupon with the given code.
     */
    public static function redeemByCode($code)
    {
        if ($code) {
            static::where('code', $code)->increment('apply_qty');
        }
    }

    public static function forgetSession()
    {
        Session::forget('coupon_price');
        Session::forget('offer_type');
        Session::forget('coupon_name');
        Session::forget('coupon_auto');
    }

    /**
     * The money value of the coupon currently held in the session, for the
     * given subtotal. Single source of truth for every checkout screen and
     * payment gateway, so percentage caps (max_discount) are always honoured.
     */
    public static function sessionDiscount($subTotal)
    {
        if (!Session::get('coupon_price') || !Session::get('offer_type')) {
            return 0.00;
        }

        $coupon = static::where('code', Session::get('coupon_name'))->first();
        if ($coupon) {
            return $coupon->discountAmount($subTotal);
        }

        // Fall back to the raw session values when the coupon row is gone.
        if (Session::get('offer_type') == 1) {
            return round((Session::get('coupon_price') / 100) * $subTotal, 2);
        }

        return (float) Session::get('coupon_price');
    }

    /**
     * Add/refresh/remove the free-gift line item in the website cart based on
     * whether a "Buy & Get Free Product" offer is currently earned.
     * The gift line always has price 0 and is tagged options.is_gift so it
     * can be told apart from real items and never edited/removed by hand.
     */
    public static function syncGiftInCart($subTotal, $orderType = null, $email = null, $phone = null, $strict = true)
    {
        $existingGift = \Cart::content()->first(function ($item) {
            return !empty($item->options['is_gift']);
        });

        $offer = static::findBuyGetFreeProduct($subTotal, $orderType, $email, $phone, $strict);

        if (!$offer || !$offer->giftProduct) {
            if ($existingGift) {
                \Cart::remove($existingGift->rowId);
            }
            return;
        }

        if ($existingGift) {
            if ((int) $existingGift->id === (int) $offer->gift_product_id
                && (int) $existingGift->options['coupon_id'] === (int) $offer->id) {
                return; // already applied
            }
            \Cart::remove($existingGift->rowId);
        }

        $product = $offer->giftProduct;

        \Cart::add([
            'id' => $product->id,
            'name' => $product->name . ' (Free Gift)',
            'qty' => $offer->gift_qty ?: 1,
            'price' => 0,
            'weight' => 1,
            'options' => [
                'image' => $product->thumb_image,
                'slug' => $product->slug,
                'size' => 'Regular',
                'size_price' => 0,
                'optional_items' => [],
                'optional_item_price' => 0,
                'is_gift' => true,
                'coupon_id' => $offer->id,
                'coupon_code' => $offer->code,
            ],
        ]);
    }

    /**
     * Keep the coupon stored in the session in sync with the current cart:
     * drop it when it is no longer valid, and auto-apply the best
     * "default discount" offer when nothing is applied yet.
     *
     * Lenient about missing identity - the strict check happens at payment.
     */
    public static function syncSession($subTotal, $email = null, $phone = null)
    {
        $orderType = Session::get('order_type');

        $code = Session::get('coupon_name');
        if ($code) {
            $coupon = static::where(['code' => $code, 'status' => 1])->first();
            // Auto-applied offers must stay fully verifiable with the identity
            // we currently have; manually entered codes are checked leniently
            // here and strictly again at payment.
            $strict = (bool) Session::get('coupon_auto');
            if ($coupon && $coupon->validateFor($subTotal, $orderType, $email, $phone, $strict) === null) {
                return; // still valid, keep it
            }
            static::forgetSession();
        }

        // Never auto-apply an offer we can not fully verify - a first-time
        // customer offer only kicks in once the email/phone are known.
        $auto = static::findAutoApply($subTotal, $orderType, $email, $phone, true);
        if ($auto) {
            Session::put('coupon_price', $auto->discount);
            Session::put('offer_type', $auto->offer_type);
            Session::put('coupon_name', $auto->code);
            Session::put('coupon_auto', 1);
        }
    }

    /**
     * Final check of the session coupon right before taking payment,
     * with the customer's real email/phone. Clears the coupon from the
     * session and returns an error message when it can not be used.
     */
    public static function validateSessionStrict($subTotal, $email = null, $phone = null)
    {
        $code = Session::get('coupon_name');
        if (!$code) {
            return null;
        }

        $coupon = static::where(['code' => $code, 'status' => 1])->first();
        $error = $coupon
            ? $coupon->validateFor($subTotal, Session::get('order_type'), $email, $phone, true)
            : 'Invalid Coupon';

        if ($error) {
            static::forgetSession();

            return $error . '. The discount has been removed - please review your order and try again.';
        }

        return null;
    }
}

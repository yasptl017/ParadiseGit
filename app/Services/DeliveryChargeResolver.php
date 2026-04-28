<?php

namespace App\Services;

use App\Models\DeliveryArea;
use App\Models\DeliveryPostcodeCharge;

class DeliveryChargeResolver
{
    public function normalizePostcode(?string $postcode): ?string
    {
        if ($postcode === null) {
            return null;
        }

        $postcode = strtoupper(trim($postcode));
        $postcode = preg_replace('/\s+/', '', $postcode);

        return $postcode !== '' ? $postcode : null;
    }

    public function resolveWebsiteCharge(?string $postcode, ?float $distanceMeters, float $subTotal): array
    {
        $distanceKm = $this->distanceInKm($distanceMeters);
        $maxDistance = (float) env('MAXIMUM_DISTANCE', 0);

        if ($distanceKm === null) {
            return [
                'available' => false,
                'charge' => 0,
                'source' => 'missing-distance',
            ];
        }

        if ($maxDistance > 0 && $distanceKm > $maxDistance) {
            return [
                'available' => false,
                'charge' => 0,
                'source' => 'distance-limit',
            ];
        }

        $postcodeCharge = $this->findPostcodeCharge($postcode);
        if ($postcodeCharge) {
            return [
                'available' => true,
                'charge' => (float) $postcodeCharge->delivery_fee,
                'source' => 'postcode',
            ];
        }

        $deliveryArea = $this->findDeliveryAreaByDistance($distanceKm);
        if ($deliveryArea) {
            return [
                'available' => true,
                'charge' => (float) $deliveryArea->delivery_fee,
                'source' => 'delivery-area',
                'delivery_area' => $deliveryArea,
            ];
        }

        return [
            'available' => true,
            'charge' => (float) env('DEFAULTS_DELIVERY', 0),
            'source' => 'default',
        ];
    }

    public function resolvePosCharge(?string $postcode, ?float $distanceMeters): array
    {
        $distanceKm = $this->distanceInKm($distanceMeters);

        $postcodeCharge = $this->findPostcodeCharge($postcode);
        if ($postcodeCharge) {
            return [
                'available' => true,
                'charge' => (float) $postcodeCharge->delivery_fee,
                'source' => 'postcode',
            ];
        }

        if ($distanceKm !== null) {
            $deliveryArea = $this->findDeliveryAreaByDistance($distanceKm);
            if ($deliveryArea) {
                return [
                    'available' => true,
                    'charge' => (float) $deliveryArea->delivery_fee,
                    'source' => 'delivery-area',
                    'delivery_area' => $deliveryArea,
                ];
            }
        }

        return [
            'available' => true,
            'charge' => 0,
            'source' => 'none',
        ];
    }

    protected function findPostcodeCharge(?string $postcode): ?DeliveryPostcodeCharge
    {
        $normalized = $this->normalizePostcode($postcode);
        if (!$normalized) {
            return null;
        }

        return DeliveryPostcodeCharge::where('postcode', $normalized)
            ->where('status', 1)
            ->first();
    }

    protected function findDeliveryAreaByDistance(float $distanceKm): ?DeliveryArea
    {
        return DeliveryArea::where('status', 1)
            ->where('min_range', '<=', $distanceKm)
            ->where('max_range', '>=', $distanceKm)
            ->orderBy('min_range')
            ->first();
    }

    protected function distanceInKm(?float $distanceMeters): ?float
    {
        if ($distanceMeters === null || $distanceMeters === '') {
            return null;
        }

        return ((float) $distanceMeters) / 1000;
    }
}

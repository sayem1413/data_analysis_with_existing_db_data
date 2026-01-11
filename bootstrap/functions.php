<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

if (! function_exists('getCountryCode')) {
    function getCountryCode()
    {
        try {
            $items = Storage::disk('local')->get('country-codes.json');
            if (! empty($items)) {
                return collect(json_decode($items));
            }
        } catch (Exception $e) {
            Log::critical('getCountryCode', [$e->getMessage()]);
        }

        return null;
    }
}

if (! function_exists('getCountryCodeByPhone')) {
    /**
     * @throws Exception
     */
    function getCountryCodeByPhone(?string $mobile = null): ?string
    {
        if (empty($mobile)) {
            return null;
        }
        if (str_starts_with($mobile, '01') && strlen($mobile) == 11) {
            return '88';
        }

        $codes = getCountryCode();
        if (empty($codes)) {
            throw new Exception(trans('message.no_country_code'));
        }

        $data = [
            'len4' => [],
            'len3' => [],
            'len2' => [],
            'len1' => [],
        ];

        $collection = collect($codes->pluck('dial_code')->toArray());
        $collection->sortByDesc(function ($item) use (&$data) {
            $strlen = strlen($item);
            if ($strlen == 4) {
                $data['len4'][] = $item;
            } elseif ($strlen == 3) {
                $data['len3'][] = $item;
            } elseif ($strlen == 2) {
                $data['len2'][] = $item;
            } else {
                $data['len1'][] = $item;
            }

            return $strlen;
        })->toArray();

        $sub4 = substr($mobile, 0, 4);
        if (in_array($sub4, $data['len4'])) {
            return $sub4;
        }

        $sub3 = substr($mobile, 0, 3);
        if (in_array($sub3, $data['len3'])) {
            if ($sub3 == '880' && strlen($mobile) == 13) {
                return '88';
            }

            return $sub3;
        }

        $sub2 = substr($mobile, 0, 2);
        if (in_array($sub2, $data['len2'])) {
            return $sub2;
        }

        $sub1 = substr($mobile, 0, 1);
        if (in_array($sub1, $data['len1'])) {
            return $sub1;
        }

        return null;
    }
}


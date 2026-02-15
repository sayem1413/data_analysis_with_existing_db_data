<?php

namespace App\Http\Services;

use App\Http\Contracts\VisaContract;
use App\Http\Contracts\DataContract;
use App\Models\Country;
use App\Models\Visa\VisaCheckLink;
use App\Models\Visa\VisaVerificationRequest;
use App\Models\Visa\VisaVerificationUpdateLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;

class VisaService implements VisaContract
{
    protected DataContract $data;

    public function __construct(DataContract $data)
    {
        $this->data = $data;
    }

    public function getAcceptedCountries()
    {
        return $this->data->countries();
    }

    public function getCountryDetails(int $country_id)
    {
        $locale = App::getLocale();

        $country = Country::findOrFail($country_id);
        $visa_check_link = VisaCheckLink::where('country_id', $country->id)
            ->where('type', 1)
            ->where('active_status', 'Active')
            ->first();

        return [
            'title' => $locale == 'en' ? trim($country->title) : trim($country->title_bn),
            'title_en' => trim($country->title),
            'title_bn' => trim($country->title_bn),
            'required_properties' => $visa_check_link?->required_properties ? json_decode($visa_check_link->required_properties) : null,
        ];
    }

    public function getVerificationRequestedCountries()
    {
        $locale = App::getLocale();
        $data = [];
        $expat = expat();
        $requests = VisaVerificationRequest::with([
            'country:id,title,title_bn'
        ])
            // ->where('expat_id', $expat->id)
            ->where('expat_id', 4457637)
            ->get();

        foreach ($requests as $request) {
            $country = $request->country;
            $data[] = [
                'request_id' => $request->request_id,
                'request_status' => $this->getVisaRequestStatus($request->visa_status),
                'country_id' => $country?->id ?? $request->visa_country_id,
                'country_title' => $locale == 'en' ? $country?->title : $country?->title_bn,
                'country_title_en' => $country?->title,
                'country_title_bn' => $country?->title_bn,
                'country_code' => $country?->code,
                'country_logo' => $country?->logo
            ];
        }

        return $data;
    }

    public function getVerificationRequestInfo(int $request_id)
    {
        $expat = expat();
        $request = VisaVerificationRequest::where('expat_id', 4457637)
            ->where('request_id', $request_id)
            ->first();

        if(empty($request)) {
            throw ValidationException::withMessages([
                'no_data_found' => [trans('message.no_data_found')],
            ]);
        }

        $tracking_data = [
            [
                "title" => "Application Submitted",
                "value" => $request->created_at ? Carbon::parse($request->created_at)->format('M d, h:i A') : null,
                "status" => 1,
            ],
            [
                "title" => "Payment Done",
                "value" => $request->payment_date ? Carbon::parse($request->payment_date)->format('M d, h:i A') : ($request->payment_request_date ? Carbon::parse($request->payment_request_date)->format('M d, h:i A') : null),
                "status" => $request->payment_status == 'Paid' ? 1 : 0,
            ],
            [
                "title" => "Visa checking",
                "value" => $request->check_date ? Carbon::parse($request->check_date)->format('M d, h:i A') : 'In Progress',
                "status" => $request->check_date ? 1 : 0,
            ],
            [
                "title" => $this->getVisaRequestResTitle($request->visa_status),
                "value" => $request->check_date ? Carbon::parse($request->check_date)->format('M d, h:i A') : 'Pending',
                "status" => $request->check_date ? 1 : 0,
            ]
        ];

        $data = [
            'request_id' => $request->request_id,
            'request_status' => $this->getVisaRequestStatus($request->visa_status),
            'payment_status' => $this->getVisaRequestPaymentStatus($request->payment_status),
            'tracking_data' => $tracking_data
        ];

        return $data;
    }

    public function storeVerificationRequestInfo(array $data)
    {
        $expat = expat();
        $user = auth()->user();
        $request_id = $data['request_id'] ?? null;
        $country_id = $data['country_id'] ?? null;
        $passport_number = $data['passport_number'] ?? null;
        $visa_no = $data['visa_no'] ?? null;
        $visa_ref_no = $data['visa_ref_no'] ?? null;
        $date_of_birth = $data['date_of_birth'] ?? null;

        $data = [
            'expat_id' => $expat->id,
            'full_name' => $expat->first_name,
            'mobile' => $expat->phone ?? ($user->mobileNo ?? null),
            'passport' => $passport_number ?? $expat->passport_number,
            'date_of_birth' => Carbon::parse($date_of_birth),
            'visa_country_id' => $country_id,
            'visa_no' => $visa_no,
            'visa_ref_no' => $visa_ref_no,
        ];

        if(!empty($request_id)) {
            $visa_verification_request = VisaVerificationRequest::where('request_id', $request_id)->first();
            $visa_verification_request->update($data);
        } else {
            $visa_verification_request = VisaVerificationRequest::create($data);
            $request_id = VisaVerificationRequest::getRequestId($visa_verification_request);
            $visa_verification_request->update([
                'request_id' => $request_id
            ]);
        }

        return $visa_verification_request;
    }

    private function getVisaRequestStatus($request_status)
    {
        $status = ['Pending' => 0, 'Document-re-request' => 1, 'Document-re-submitted' => 2, 'Valid' => 3, 'Invalid' => 4];
        if (empty($request_status)) {
            return $status['Pending'];
        }

        return $status[$request_status];
    }

    private function getVisaRequestResTitle($request_status)
    {
        $title = "Verification Result";
        if($request_status == "Valid") {
            $title = "Visa Verified";
        } else if($request_status == "Invalid") {
            $title = "Invalid Visa";
        }
        return $title;
    }

    private function getVisaRequestPaymentStatus($request_payment_status)
    {
        $status = ['Pending' => 0, 'Initiated' => 1, 'Paid' => 2, 'Failed' => 3];
        if (empty($request_payment_status)) {
            return $status['Pending'];
        }

        return $status[$request_payment_status];
    }

}

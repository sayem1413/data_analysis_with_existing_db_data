<?php

namespace App\Http\Controllers\Api\Visa;

use App\Http\Controllers\Controller;
use App\Http\Contracts\VisaContract;
use App\Http\Requests\Visa\VisaVerificationStoreRequest;

class VisaVerificationController extends Controller
{
    protected VisaContract $visa;

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct(VisaContract $visa)
    {
        $this->visa = $visa;
    }

    /**
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/{locale}/visa-verification",
     *     tags={"Visa Verification"},
     *     summary="Get Visa Verification predefined data",
     *     security={{"jwt":{}}},
     *     operationId="get-visa-verification-predefined-data",
     *
     *     @OA\Parameter(ref="#/components/parameters/XApiKey"),
     *     @OA\Parameter(ref="#/components/parameters/XPushKey"),
     *     @OA\Parameter(ref="#/components/parameters/XPlatform"),
     *     @OA\Parameter(ref="#/components/parameters/PathLocale"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Examples(example="Success", value={"status":true,"response_code":1000,"message":"Data retrieved","errors":null,"data":{{"id":194,"title":"সৌদি আরব","title_bn":"সৌদি আরব","code":"SA","dial_code":null,"title_en":"Saudi Arabia"},{"id":156,"title":"ইউনাইটেড  আরব আমিরাত","title_bn":"ইউনাইটেড  আরব আমিরাত","code":"AE","dial_code":null,"title_en":"United Arab Emirates"}}}, summary="An result object.")
     *         )
     *     )
     * )
     */
    public function getAcceptedCountries()
    {
        $data = $this->visa->getAcceptedCountries();

        return successResponse(trans('message.data_retrieved'), $data);
    }

    /**
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/{locale}/visa-verification/country/{country_id}",
     *     tags={"Visa Verification"},
     *     summary="Get Visa Verification selected country data by country id",
     *     security={{"jwt":{}}},
     *     operationId="get-visa-verification-selected-country-data-country-id",
     *
     *     @OA\Parameter(ref="#/components/parameters/XApiKey"),
     *     @OA\Parameter(ref="#/components/parameters/XPushKey"),
     *     @OA\Parameter(ref="#/components/parameters/XPlatform"),
     *     @OA\Parameter(ref="#/components/parameters/PathLocale"),
     * 
     *     @OA\Parameter(
     *          name="country_id",
     *          in="path",
     *          description="Country ID",
     *          required=true,
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Examples(example="Success", value={"status":true,"response_code":1000,"message":"Data retrieved","errors":null,"data":{"title":"Saudi Arabia","title_en":"Saudi Arabia","title_bn":"সৌদি আরব","required_properties":{"passport_number","visa_no"}}}, summary="An result object.")
     *         )
     *     )
     * )
     */
    public function getCountryDetails($country_id)
    {
        $data = $this->visa->getCountryDetails($country_id);

        return successResponse(trans('message.data_retrieved'), $data);
    }

    /**
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/{locale}/visa-verification/countries",
     *     tags={"Visa Verification"},
     *     summary="Get Visa Verification selected countries data",
     *     security={{"jwt":{}}},
     *     operationId="get-visa-verification-selected-countries-data",
     *
     *     @OA\Parameter(ref="#/components/parameters/XApiKey"),
     *     @OA\Parameter(ref="#/components/parameters/XPushKey"),
     *     @OA\Parameter(ref="#/components/parameters/XPlatform"),
     *     @OA\Parameter(ref="#/components/parameters/PathLocale"),
     * 
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Examples(example="Success", value={"status":true,"response_code":1000,"message":"Data retrieved","errors":null,"data":{{"request_id":1125000117,"request_status":0,"country_id":121,"country_title":"Qatar","country_title_en":"Qatar","country_title_bn":"কাতার","country_code":null,"country_logo":null}}}, summary="An result object.")
     *         )
     *     )
     * )
     */
    public function getVerificationRequestedCountries()
    {
        $data = $this->visa->getVerificationRequestedCountries();

        return successResponse(trans('message.data_retrieved'), $data);
    }

    /**
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/{locale}/visa-verification/request/{request_id}",
     *     tags={"Visa Verification"},
     *     summary="Get Visa Verification request info by request id",
     *     security={{"jwt":{}}},
     *     operationId="get-visa-verification-request-info-by-request-id",
     *
     *     @OA\Parameter(ref="#/components/parameters/XApiKey"),
     *     @OA\Parameter(ref="#/components/parameters/XPushKey"),
     *     @OA\Parameter(ref="#/components/parameters/XPlatform"),
     *     @OA\Parameter(ref="#/components/parameters/PathLocale"),
     * 
     *     @OA\Parameter(ref="#/components/parameters/VisaVerificationReqId"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Examples(example="Success", value={"status":true,"response_code":1000,"message":"Data retrieved","errors":null,"data":{"request_id":1125000117,"request_status":0,"payment_status":1,"tracking_data":{{"title":"Application Submitted","value":"Nov 18, 05:06 PM","status":1},{"title":"Payment Done","value":"Nov 18, 05:06 PM","status":0},{"title":"Visa checking","value":"In Progress","status":0},{"title":"Verification Result","value":"Pending","status":0}}}}, summary="An result object.")
     *         )
     *     )
     * )
     */
    public function getVerificationRequestInfo($request_id)
    {
        $data = $this->visa->getVerificationRequestInfo($request_id);

        return successResponse(trans('message.data_retrieved'), $data);
    }

    /**
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/{locale}/visa-verification/request",
     *     tags={"Visa Verification"},
     *     summary="Update Visa Verification request data by request id",
     *     security={{"jwt":{}}},
     *     operationId="update-visa-verification-request-data-by-request-id",
     *
     *     @OA\Parameter(ref="#/components/parameters/XApiKey"),
     *     @OA\Parameter(ref="#/components/parameters/XPushKey"),
     *     @OA\Parameter(ref="#/components/parameters/XPlatform"),
     *     @OA\Parameter(ref="#/components/parameters/PathLocale"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Examples(example="Success", value={"status":true,"message":"Data retrieved","errors":null,"data":{}}, summary="An result object.")
     *         )
     *     )
     * )
     */
    public function storeVerificationRequestInfo(VisaVerificationStoreRequest $request)
    {
        $data = $this->visa->storeVerificationRequestInfo($request->all());

        return successResponse(trans('message.data_retrieved'), $data);
    }
}

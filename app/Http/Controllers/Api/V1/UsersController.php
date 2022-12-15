<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\{LoginApiRequest, RegisterApiRequest, LoginVerifyOTPApiRequest};
use App\Services\UserService;
use App\Models\User;

class UsersController extends Controller
{
    protected $service;
    public function __construct()
    {
        $this->service = new UserService(new User);
    }

    public function register(RegisterApiRequest $request)
    {
        $response = $this->service->register($request->all());
        return response()->send($response['message'], $response['status'], $response['data']);
    }

    public function loginVerifyOtp(LoginVerifyOTPApiRequest $request)
    {
        $response = $this->service->loginVerifyOtp($request->all());
        return response()->send($response['message'], $response['status'], $response['data']);
    }

    public function login(LoginApiRequest $request)
    {
        $response = $this->service->login($request->all());
        return response()->send($response['message'], $response['status'], $response['data']);
    }

    public function addWishList(Request $request)
    {
        $response = $this->service->addWishList($request);
        return response()->send($response['message'], $response['status'], $response['data']);
    }

    public function getWishlist(Request $request)
    {
        $response = $this->service->getWishlist($request->all());
        return response()->send($response['message'], $response['status'], $response['data']);
    }

    public function removeFromWishlist(Request $request)
    {
        $response = $this->service->removeFromWishlist($request->all());
        return response()->send($response['message'], $response['status'], $response['data']);
    }

    public function addFavouriteRequest(Request $request)
    {
        $response = $this->service->addFavouriteRequest($request);
        return response()->send($response['message'], $response['status'], $response['data']);
    }

    public function getMyFavouriteRequests(Request $request)
    {
        $response = $this->service->getFavouriteRequest($request);
        return response()->send($response['message'], $response['status'], $response['data']);
    }

    public function removeFavourite(Request $request)
    {
        $response = $this->service->removeFavourite($request);
        return response()->send($response['message'], $response['status'], $response['data']);
    }
}

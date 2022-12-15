<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendOtp;
use App\Models\{User, UserOtp, Wishlist, MyFavourite};
use DB;
use Auth;
use Carbon\Carbon;

class UserService
{
    protected $success;
    protected $failure;
    protected $obj;

    public function __construct($obj)
    {
        $this->obj = $obj;
        $this->success = Response::HTTP_OK;
        $this->failure = Response::HTTP_BAD_REQUEST;
    }

    public function register($request)
    {
        $user = User::updateOrCreate(
            ["email" => $request['email']],
            [
                "email" => $request['email'],
                "name" => $request['name'],
                "password" => Hash::make($request['password']),
            ]
        );

        $otp = $this->generateOtp($user);

        // send OTP
        $data = [
            'name' => $user->name,
            'email' => $user->email,
            'otp' => $otp
        ];
        $status = $this->success;
        $status = $this->sendOTP($status, $data); // send OTP and returns status

        $message = trans("messages.otpSuccess");
        if ($status != $this->success) {
            $message = trans("messages.failedToSendOtp");
        }

        return prepareApiResponse($message, $status, $user);
    }

    public function generateOtp($user)
    {
        $userOtp = UserOtp::updateOrCreate(
            ["user_id" => $user->id],
            ["otp" => $this->generateBarcodeNumber()]
        );

        return $userOtp->otp;
    }

    public function generateBarcodeNumber()
    {
        $number = mt_rand(10000, 99999); // better than rand()

        // call the same function if the barcode exists already
        if ($this->numberExists($number)) {
            return $this->generateBarcodeNumber();
        }

        // otherwise, it's valid and can be used
        return $number;
    }

    public function numberExists($number)
    {
        return UserOtp::whereOtp($number)->exists();
    }

    public function loginVerifyOtp($request)
    {
        $user = User::whereEmail($request['email'])->first();
        UserOtp::where([["otp", $request['otp']], ["user_id", $user->id]])->delete();
        $status = $this->success;
        $user->email_verified_at = date("Y-m-d H:i:s");
        $user = DB::transaction(function () use ($user) {
            $user->save();
            $user->token = $user->createToken('token')->accessToken;
            return $user;
        });
        return prepareApiResponse(trans('messages.verifyotp'), $status, $user);
    }

    public function login($data)
    {
        if (Auth::attempt(["email" => $data['email'], "password" => $data['password']])) {
            $user = Auth::user();
            $otp = $this->generateOtp($user);
            // send OTP
            $data = [
                'email' => $user->email,
                'otp' => $otp,
                'name' => $user->name
            ];
            $status = $this->success;
            $status = $this->sendOTP($status, $data); // send OTP and returns status
            $message = trans("messages.otpSuccess");
            if ($status != $this->success) {
                $message = trans("messages.failedToSendOtp");
            }
        } else {
            $status = $this->failure;
            $message = trans("messages.invalidCredentials");
            $user = array();
        }
        return prepareApiResponse($message, $status, $user);
    }

    public function sendOTP($status, $data = []) // send OTP and returns status
    {
        Mail::to($data['email'])->send(new SendOtp($data));
        // check for failures
        if (Mail::failures()) {
            $status = 4;
            return Mail::failures();
        }
        return $status;
    }

    public function addWishList($request)
    {
        $response = $this->obj->rulesaddWishList($request->all());
        if ($response->fails()) {
            return prepareApiResponse($response->errors()->first(), $this->failure); //error
        }
        $userId = Auth::user()->id;

        $previousWhishlist = Wishlist::where([['treatment_id', $request->treatment_id], ['user_id', $userId]]);

        if ($previousWhishlist->count() > 0) {
            return prepareApiResponse(trans("messages.alreadySelected"), $this->success);
        } else {
            $addWishlist = new Wishlist;
            $addWishlist->treatment_id = $request->treatment_id;
            $addWishlist->user_id = $userId;
            $addWishlist->save();
            return prepareApiResponse(trans("messages.addWishlist"), $this->success);
        }
    }

    public function getWishlist($request)
    {
        $userId = Auth::user()->id;

        $user = Wishlist::select('treatments.title', 'wishlists.id as wishlists_id', 'wishlists.treatment_id', 'treatment_relations.area_id', 'treatment_relations.category_id', 'treatment_relations.sub_category_id')->join('treatments', 'treatments.id', '=', 'wishlists.treatment_id')->leftJoin('treatment_relations', 'treatment_relations.treatment_id', '=', 'wishlists.treatment_id')->groupBy('treatment_id')->where('user_id', $userId)->paginate(10);
        $user->getCollection()->transform(function ($value) {
            return [
                'treatment_id' => $value->treatment_id,
                'title' => $value->title,
                'id' => $value->wishlists_id,
                'area' => areaname($value->area_id),
                'category' => categoryname($value->category_id),
                'subcategory' => subcategoryname($value->sub_category_id),
                'treatments' => treatmentsname($value->treatment_id),
                'video' => $value->treatmentdetailsforapi->map(function ($item, $key) {
                    return [
                        'url' => $item->url
                    ];
                }),
                'video_count' => count($value->treatmentdetailsforapi)
            ];
        });

        if (!empty($user)) {
            return prepareApiResponse(trans("messages.getAllWishlist"), Response::HTTP_OK, $user);
        } else {
            return prepareApiResponse(trans("messages.error"), Response::HTTP_BAD_REQUEST);
        }
    }

    public function removeFromWishlist($request)
    {
        $validator = Validator::make($request, [
            "id" => "required",
        ]);
        if ($validator->fails()) {
            return prepareApiResponse($validator->errors()->first(), Response::HTTP_BAD_REQUEST);
        }
        $wishlist = Wishlist::where('id', $request['id'])->first();
        if (!empty($wishlist)) {
            Wishlist::where('id', $request['id'])->delete();
            return prepareApiResponse(trans("messages.removeWishlist"), Response::HTTP_OK);
        } else {
            return prepareApiResponse(trans("messages.error"), Response::HTTP_BAD_REQUEST);
        }
    }

    public function addFavouriteRequest($request)
    {
        $response = $this->obj->rulesFavouriteRequest($request->all());
        if ($response->fails()) {
            return prepareApiResponse($response->errors()->first(), $this->failure);
        }
        $userId = Auth::user()->id;
        $previousfavlist = MyFavourite::where([['user_request_id', $request->request_id], ['user_id', $userId]]);

        if ($previousfavlist->count() > 0) {
            $previousfavlist->delete();
            return prepareApiResponse(trans("messages.removeFavRequest"), $this->success);
        } else {
            $addFavRequest = new MyFavourite;
            $addFavRequest->user_request_id = $request->request_id;
            $addFavRequest->doctor_id = $userId;
            $addFavRequest->save();
            return prepareApiResponse(trans("messages.addFavRequest"), $this->success);
        }
    }

    public function getFavouriteRequest($request)
    {
        $message = trans("messages.listofFavourites");
        $status = $this->success;
        $myFavourites = MyFavourite::with(["userRequest", "userRequest.user", "userRequest.subCategory", "userRequest.treatment"])
            ->where("user_id", $request->user()->id)
            ->paginate();
        $collection =  $myFavourites->getCollection();

        $filteredRequests = $collection->map(function ($item) {
            $name = !empty($item->userRequest->treatment) ? $item->userRequest->treatment->title : $item->userRequest->subCategory->title;
            $user_name = ucwords(trim($item->userRequest->user->first_name . ' ' . $item->userRequest->user->last_name));
            $request_id = $item->userRequest->id;
            $user_id = $item->userRequest->user->id;
            $posted_on = date("d M,Y", strtotime($item->userRequest->created_at));
            $expires_in = Carbon::now()->diffInDays($item->userRequest->expiry_date);
            $expires_on = date("d F Y", strtotime($item->userRequest->expiry_date));
            return compact('name', 'user_name', 'request_id', 'user_id', 'posted_on', 'expires_in', 'expires_on');
        });

        $myFavourites->setCollection($filteredRequests);
        if (!$myFavourites->count()) {
            $message = trans("messages.noRecordFound");
            $status = $this->failure;
        }

        return prepareApiResponse($message, $status, $myFavourites);
    }

    public function removeFavourite($request)
    {
        $validator = Validator::make($request->all(), [
            "user_request_id" => "required|integer",
        ]);
        if ($validator->fails()) {
            return prepareApiResponse($validator->errors()->first(), Response::HTTP_BAD_REQUEST);
        }
        $id = Auth::user()->id;
        $user_request_id = $request->user_request_id;
        $delfav =
            $id1 = MyFavourite::where('user_id', $id)
            ->where('user_request_id', $user_request_id)->forcedelete();

        if ($id1) {
            return prepareApiResponse("Deleted successfully", $this->success, $delfav);
        } else {
            return prepareApiResponse("Record not found", $this->failure);
        }
    }
}

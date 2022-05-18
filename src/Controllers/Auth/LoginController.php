<?php

namespace Nevestul4o\NetworkController\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Models\Transformers\UserTransformer;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use League\Fractal\Resource\Item;
use Nevestul4o\NetworkController\ResponseHelper;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * @param Request $request
     * @param $user
     * @return JsonResponse
     */
    protected function authenticated(Request $request, $user): JsonResponse
    {
        return new Item($user, new UserTransformer);
    }

    /**
     * @param Request $request
     * @return Response
     */
    protected function loggedOut(Request $request): JsonResponse
    {
        $responseHelper = new ResponseHelper();
        return $responseHelper->noContentResponse();
    }

    /**
     * Get the currently logged in user data
     *
     * @return JsonResponse
     */
    protected function getCurrentUser(): JsonResponse
    {
        return new Item(Auth::user(), new UserTransformer());
    }
}

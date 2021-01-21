<?php

namespace Nevestul4o\NetworkController\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Transformers\UserTransformer;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Helpers;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use Helpers;
    use AuthenticatesUsers;

    /**
     * @param Request $request
     * @param $user
     * @return Response
     */
    protected function authenticated(Request $request, $user): Response
    {
        return $this->response->item($user, new UserTransformer);
    }

    /**
     * @param Request $request
     * @return Response
     */
    protected function loggedOut(Request $request): Response
    {
        return $this->response->noContent();
    }

    /**
     * Get the currently logged in user data
     *
     * @return Response
     */
    protected function getCurrentUser(): Response
    {
        return $this->response->item(Auth::user(), new UserTransformer());
    }
}

<?php

namespace Nevestul4o\NetworkController\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Nevestul4o\NetworkController\Models\BaseModel;
use Nevestul4o\NetworkController\Models\Transformers\UserTransformer;

class ChangePasswordController extends Controller
{
    use Helpers;

    const F_PASSWORD_CURRENT = 'password_current';
    const F_USER_ID = 'user_id';

    /**
     * Allows the current user to change his password
     *
     * @param Request $request
     * @return Response
     * @throws ValidationException
     */
    public function changePassword(Request $request): Response
    {
        $this->validate(
            $request,
            [
                User::F_PASSWORD         => 'required|confirmed|min:8',
                self::F_PASSWORD_CURRENT => 'required',
            ]
        );

        /** @var User $user */
        $user = Auth::user();
        if (!Hash::check($request->{self::F_PASSWORD_CURRENT}, $user->getAuthPassword())) {
            throw ValidationException::withMessages(["Provide your current password"]);
        }
        $user->{User::F_PASSWORD} = Hash::make($request->{User::F_PASSWORD});
        $user->save();

        return $this->response->item($user, new UserTransformer());
    }

    /**
     * Allows to change the password to the user, by it's id (user_id)
     *
     * @param Request $request
     * @return Response
     * @throws ValidationException
     */
    public function changePasswordForced(Request $request): Response
    {
        $this->validate(
            $request,
            [
                self::F_USER_ID  => 'required|integer|exists:' . User::TABLE_NAME . ',id,' . BaseModel::F_DELETED_AT . ',NULL',
                User::F_PASSWORD => 'required|confirmed|min:8',
            ]
        );

        /** @var User $user */
        $user = User::findOrFail($request->{self::F_USER_ID});
        $user->{User::F_PASSWORD} = Hash::make($request->{User::F_PASSWORD});
        $user->save();

        return $this->response->item($user, new UserTransformer());
    }
}

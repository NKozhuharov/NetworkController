<?php

namespace Nevestul4o\NetworkController\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Models\Transformers\UserTransformer;
use App\Http\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use League\Fractal\Resource\Item;
use Nevestul4o\NetworkController\Models\BaseModel;

class ChangePasswordController extends Controller
{
    const F_PASSWORD_CURRENT = 'password_current';
    const F_PASSWORD_CONFIRMATION = 'password_confirmation';
    const F_USER_ID = 'user_id';

    /**
     * Allows the current user to change his password
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function changePassword(Request $request): JsonResponse
    {
        $this->validate(
            $request,
            [
                self::F_PASSWORD_CURRENT      => 'required',
                User::F_PASSWORD              => 'required|confirmed|min:8',
                self::F_PASSWORD_CONFIRMATION => 'required',
            ]
        );

        /** @var User $user */
        $user = Auth::user();
        if (!Hash::check($request->{self::F_PASSWORD_CURRENT}, $user->getAuthPassword())) {
            throw ValidationException::withMessages(["Wrong current password"]);
        }
        $user->{User::F_PASSWORD} = Hash::make($request->{User::F_PASSWORD});
        $user->save();

        return new Item($user, new UserTransformer());
    }

    /**
     * Allows to change the password to the user, by its id (user_id)
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function changePasswordForced(Request $request): JsonResponse
    {
        $this->validate(
            $request,
            [
                self::F_USER_ID  => 'required|integer|exists:' . User::TABLE_NAME . ',id,' . BaseModel::F_DELETED_AT . ',NULL',
                User::F_PASSWORD => 'required|confirmed|min:8',
                self::F_PASSWORD_CONFIRMATION => 'required',
            ]
        );

        /** @var User $user */
        $user = User::findOrFail($request->{self::F_USER_ID});
        $user->{User::F_PASSWORD} = Hash::make($request->{User::F_PASSWORD});
        $user->save();

        return new Item($user, new UserTransformer());
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Redis as Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function getConnectedUsers()
    {
        $presenceKey = "presence-connected-users";
        $connectedMembers = Redis::hkeys($presenceKey);

        return response()->json(['connected_users' => $connectedMembers]);
    }

    public function initUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unique_id' => [
                'required', 'string', 'max:255',
                Rule::unique('users')->where(function ($query) use ($request) {
                    return $query->where('call_id', $request->call_id)->where('id', '<>', $request->id);
                }),
            ],
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $errorMessage = $errors->first();
            return response()->json(['success' => false, 'message' => $errorMessage], 400);
        }

        try {
            $user = User::create([
                'call_id' => $request->unique_id,
            ]);
            $user = User::where('call_id', $request->call_id)->get();
            return response()->json(['success' => true, 'response' => $user]);
        } catch (\Exception $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 400);
        }
    }
}

<?php


namespace App\Http\Controllers;


use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    public function getOne(int $userId)
    {
        $user = User::where("id", $userId)->first();

        if (!$user) {
            return response("user not found.", 404);
        }

        return response()->json($user, 200);
    }

    public function update(int $userId, Request $request)
    {
        $this->validate($request, [
            "email" => "unique:users",
            "name" => "",
            "password" => ""
        ]);
        $user = User::where("id", $userId)->first();

        if (!$user) {
            return response("user not found.", 404);
        }

        try {
            $user->name = $request->has("name") ? $request->get("name") : $user->name;
            $user->password = $request->has("password") ? Hash::make($request->get("password")) : $user->password;
            $user->email = $request->has("email") ? $request->get("email") : $user->email;
            $user->save();

            return response()->json($user, 200);
        } catch (Exception $e) {
            return response()->json("error updating", 500);
        }
    }

    public function getAll()
    {
        $users = User::all();

        return response()->json($users, 200);
    }

    public function remove(int $userId)
    {
        $user = User::where("id", $userId)->first();

        if (!$user) {
            return response("user not found.", 404);
        }

        $user->delete();

        return response("user deleted", 204);
    }
}

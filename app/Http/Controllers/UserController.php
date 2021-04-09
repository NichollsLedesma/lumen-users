<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * @param int $userId
     * @return JsonResponse
     */
    public function getOne(int $userId): JsonResponse
    {
        $user = User::where("id", $userId)->first();

        if (!$user) {
            return response()->json(['message' => "user not found."], 404);
        }

        return response()->json($user, 200);
    }

    /**
     * @param int $userId
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(int $userId, Request $request): ?JsonResponse
    {
        $this->validate($request, [
            "email" => "unique:users",
            "name" => "",
            "password" => ""
        ]);
        $user = User::where("id", $userId)->first();

        if (!$user) {
            return response()->json(['message' => "user not found."], 404);
        }

        try {
            $user->name = $request->has("name") ? $request->get("name") : $user->name;
            $user->password = $request->has("password") ? Hash::make($request->get("password")) : $user->password;
            $user->email = $request->has("email") ? $request->get("email") : $user->email;
            $user->save();

            return response()->json($user, 200);
        } catch (Exception $e) {
            return response()->json(['message' => "error updating"], 500);
        }
    }

    /**
     * @return JsonResponse
     */
    public function getAll(): JsonResponse
    {
        $users = User::all();

        return response()->json($users, 200);
    }

    /**
     * @param int $userId
     *
     * @return JsonResponse
     */
    public function remove(int $userId): JsonResponse
    {
        $user = User::where("id", $userId)->first();

        if (!$user) {
            return response()->json(['message' => "user not found."], 404);
        }

        $user->delete();

        return response()->json(['message' => "user deleted."], 204);
    }
}

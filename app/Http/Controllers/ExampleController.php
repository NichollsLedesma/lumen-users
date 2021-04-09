<?php

namespace App\Http\Controllers;

use App\Http\Services\Email;
use App\Http\Services\SmsMessage;
use App\Models\CustomConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class ExampleController extends Controller
{


    public function test(Request $request): JsonResponse
    {
        $serv = new Email();
        $email = "nicoledesma36@gmail.com";
//        $serv->createTemplate(Email::TEMPLATE_LOGIN);
//$serv->getTemplates();
//        $serv->sendRegisterCode($email, "dkjvbh3");
//        $serv->sendLoginCode($email, "dkjvbh3");
        return response()->json("ok", 200);
    }
}

<?php

namespace App\Auth;

use Illuminate\Auth\RequestGuard;
use Illuminate\Http\Request;

class JwtRequestGuard extends RequestGuard
{
    public function setRequest(Request $request)
    {
        $this->forgetUser();

        return parent::setRequest($request);
    }
}

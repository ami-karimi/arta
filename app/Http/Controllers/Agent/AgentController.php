<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\Api\AgentDetailResource;

class AgentController extends Controller
{
    public function index(){
        return new AgentDetailResource(auth()->user());
    }
}

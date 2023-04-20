<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\FinancialCollection;
use App\Models\Financial;
use Illuminate\Http\Request;

class FinancialController extends Controller
{
    public function index(){
        $financial =  Financial::where('for',auth()->user()->id);
        return new FinancialCollection($financial->orderBy('id','DESC')->paginate(4));
    }
}

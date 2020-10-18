<?php

namespace App\Http\Controllers;

use App\Services\FireflyService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HomeController extends Controller
{
    public function index()
    {
        /*$service = new FireflyService();
        $categories = $service->getBudgetsStat();
        $categories->each(function ($category) {
            if (isset($category->get('spent')[0])) {
                dd($category);
            }
        });*/
        /*$result = $service->sendTransaction(array(
            'transactions' => array([
                'type' => 'withdrawal',
                'date' => Carbon::now(),
                'amount' => 542,
                'description' => 'тесттест3434',
                'category_id' => 1,
                'source_id' => 1,
                'destination_id' => 6
            ])
        ));
        dd($result);*/
        return view('welcome');
    }
}

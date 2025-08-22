<?php

namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon; 

class DashboardController extends Controller
{
    function DashboardPage():View{
        return view('pages.dashboard.dashboard-page');
    }

    

function Summary(Request $request):array{

    $user_id = $request->header('id');

    $product   = Product::where('user_id',$user_id)->count();
    $category  = Category::where('user_id',$user_id)->count();
    $customer  = Customer::where('user_id',$user_id)->count();
    $invoice   = Invoice::where('user_id',$user_id)->count();

    // All time totals
    $total     = Invoice::where('user_id',$user_id)->sum('total');
    $vat       = Invoice::where('user_id',$user_id)->sum('vat');
    $payable   = Invoice::where('user_id',$user_id)->sum('payable');

    // This month totals
    $monthTotal   = Invoice::where('user_id',$user_id)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->sum('total');

    $monthVat     = Invoice::where('user_id',$user_id)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->sum('vat');

    $monthPayable = Invoice::where('user_id',$user_id)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->sum('payable');

    // Today totals
    $todayTotal   = Invoice::where('user_id',$user_id)
                    ->whereDate('created_at', Carbon::today())
                    ->sum('total');

    $todayVat     = Invoice::where('user_id',$user_id)
                    ->whereDate('created_at', Carbon::today())
                    ->sum('vat');

    $todayPayable = Invoice::where('user_id',$user_id)
                    ->whereDate('created_at', Carbon::today())
                    ->sum('payable');

    return [
        'product'      => $product,
        'category'     => $category,
        'customer'     => $customer,
        'invoice'      => $invoice,

        // all time
        'total'        => round($total,2),
        'vat'          => round($vat,2),
        'payable'      => round($payable,2),

        // this month
        'monthTotal'   => round($monthTotal,2),
        'monthVat'     => round($monthVat,2),
        'monthPayable' => round($monthPayable,2),

        // today
        'todayTotal'   => round($todayTotal,2),
        'todayVat'     => round($todayVat,2),
        'todayPayable' => round($todayPayable,2),
    ];
}
}

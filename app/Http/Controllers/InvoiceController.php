<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{

    function InvoicePage():View{
        return view('pages.dashboard.invoice-page');
    }

    function SalePage():View{
        return view('pages.dashboard.sale-page');
    }

    function invoiceCreate(Request $request){
        DB::beginTransaction();

        try {
            $user_id=$request->header('id');
            $total=$request->input('total');
            $discount=$request->input('discount');
            $vat=$request->input('vat');
            $payable=$request->input('payable');
            $customer_id=$request->input('customer_id');

            // Create Invoice
            $invoice= Invoice::create([
                'total'=>$total,
                'discount'=>$discount,
                'vat'=>$vat,
                'payable'=>$payable,
                'user_id'=>$user_id,
                'customer_id'=>$customer_id,
            ]);

            $invoiceID=$invoice->id;
            $products= $request->input('products');

            foreach ($products as $EachProduct) {
                
                // Save invoice products
                InvoiceProduct::create([
                    'invoice_id' => $invoiceID,
                    'user_id'=>$user_id,
                    'product_id' => $EachProduct['product_id'],
                    'qty' =>  $EachProduct['qty'],
                    'sale_price'=>  $EachProduct['sale_price'],
                ]);

                // Decrease stock from product table
                // Product::where('id', $EachProduct['product_id'])
                //     ->decrement('qty', $EachProduct['qty']);
                Product::where('id',$EachProduct['product_id'])->update([
                    'unit' => Product::where('id',$EachProduct['product_id'])->value('unit') - $EachProduct['qty'],
                ]);
            }

            DB::commit();
            return 1;

        } catch (Exception $e) {
            DB::rollBack();
            return 0;
        }
    }

    function invoiceSelect(Request $request){
        $user_id=$request->header('id');
        return Invoice::where('user_id',$user_id)->with('customer')->get();
    }

    function InvoiceDetails(Request $request){
        $user_id=$request->header('id');
        $customerDetails=Customer::where('user_id',$user_id)->where('id',$request->input('cus_id'))->first();
        $invoiceTotal=Invoice::where('user_id','=',$user_id)->where('id',$request->input('inv_id'))->first();
        $invoiceProduct=InvoiceProduct::where('invoice_id',$request->input('inv_id'))
            ->where('user_id',$user_id)->with('product')
            ->get();
        return array(
            'customer'=>$customerDetails,
            'invoice'=>$invoiceTotal,
            'product'=>$invoiceProduct,
        );
    }

    function invoiceDelete(Request $request){
        DB::beginTransaction();
        try {
            $user_id=$request->header('id');

            // Get all products of this invoice before deleting
            $invoiceProducts = InvoiceProduct::where('invoice_id',$request->input('inv_id'))
                ->where('user_id',$user_id)
                ->get();

            // Restore stock qty
            foreach ($invoiceProducts as $EachProduct) {
                Product::where('id',$EachProduct->product_id)->update([
                    'unit' => Product::where('id',$EachProduct->product_id)->value('unit') + $EachProduct->qty,
                ]);
            }

            // Delete invoice products
            InvoiceProduct::where('invoice_id',$request->input('inv_id'))
                ->where('user_id',$user_id)
                ->delete();

            // Delete invoice
            Invoice::where('id',$request->input('inv_id'))->delete();

            DB::commit();
            return 1;
        }
        catch (Exception $e){
            DB::rollBack();
            return 0;
        }
    }
}

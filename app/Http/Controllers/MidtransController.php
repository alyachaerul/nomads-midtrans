<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use Midtrans\Config;
use Midtrans\Notification;

class MidtransController extends Controller
{
    public function notificationHandler(Request $request){

        //set konfigurasi midtrans

        Config::$serverKey = Config('midtrans.serverKey');
        Config::$isProduction = Config('midtrans.isProduction');
        Config::$isSanitized = Config('midtrans.isSanitized');
        Config::$is3ds = Config('midtrans.is3ds');

        //instance midtrans notifications --> helper untuk ngambil notif dari midtrans
        $notification = new Notification();

        //pecah order id agar diterima database
        $order = explode('-', $notification->order_id);

        //assign ke var utk mudahin config
        $status = $notification->transaction_status;
        $type = $notification->payment_type;
        $fraud = $notification->fraud_status;
        $order_id = $order[1];

        //cari transaksi berdasar ID
        $transaction = Transaction::findOrFail($order_id);

        //Handle notification status midtrans
        if ($status == 'capture') {
            if ($type == 'credit_card') {
                if ($fraud == 'challenge') {
                    $transaction->transaction_status = "CHALLENGE";
                } else {
                    $transaction->transaction_status = "SUCCESS";
                }
            }
        }
        elseif ($status == 'settlement') {
            $transaction->transaction_status = 'SUCCESS';
        }

        elseif ($status == 'pending') {
            $transaction->transaction_status = 'PENDING';
        }

        elseif ($status == 'deny') {
            $transaction->transaction_status = 'FAILED';
        }

        elseif ($status == 'expire') {
            $transaction->transaction_status = 'EXPIRED';
        }

        elseif ($status == 'cancel') {
            $transaction->transaction_status = 'FAILED';
        }

        //SIMPAN TRANSAKSI
        $transaction->save();

        //kirimkan email
    }

    public function finishRedirect(Request $request){
        return view('pages.success');
    }

    public function unfinishRedirect(Request $request){
        return view('pages.unfinish');
    }

    public function errorRedirect(Request $request){
        return view('pages.failed');
    }


}

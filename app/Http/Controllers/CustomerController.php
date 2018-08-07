<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Customer;
use App\Transaction;
use App\Event;
use Illuminate\Http\Request;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class CustomerController extends Controller
{

    public function index()
    {
        return Customer::all();
    }

    public function show(Customer $customer)
    {
        return $customer;
    }

    public function store(Request $request)
    {
        $secretKey = 'sk_live_a36687896d52aa5ec980ae75f8cff2b2448fd245';
        // $secretKey = 'sk_test_3582bc5c00d9f5d4f0b8e168e883c4d0f0f0a976';
        
        $amount = $request->input('amount');
        $due_amount = $request->input('due_amount');

        $model = $request->all();
        // $model['due_date'] = Carbon::now()->addDays(30);
        $model['due_date'] = Carbon::now()->addMinutes(10); 
        $model['billable'] = $amount;
        $model['due_amount'] = $due_amount;
        $customer = Customer::create($model);


        $client = new Client(); //GuzzleHttp\Client
        $response = $client->post('https://api.paystack.co/transaction/initialize', [
            'headers' => [
                'authorization' => 'Bearer ' . $secretKey,
                'content-type' => 'application/json',
                'cache-control' => 'no-cache'
            ],
            'json' => [
                'amount' => $amount,
                'email' => $customer->email
            ]
        ]);

        if ($response->getStatusCode() != 200) {
            return response()->json("cannot_initiate");
        }
        // $reason = $response->getReasonPhrase(); // OK

        $body = json_decode($response->getBody());

        $tranxReference = $body->data->reference;

        $transaction =  Transaction::create([
            'amount' => $amount,
            'reference' => $tranxReference,
            'customer_id' => $customer->id, 
            'status' => 'pending'
        ]);

        // return response()->json();
        return response()->json($body->data->authorization_url, 201);
    }

    public function confirmed(Request $request)
    {
        $body = json_decode($request->getContent());

        Event::create([
            'event' => 'Paystack Callback',
            'field_one' => $body->data->reference,
        ]);

        $secretKey = 'sk_live_a36687896d52aa5ec980ae75f8cff2b2448fd245';
        // $secretKey = 'sk_test_3582bc5c00d9f5d4f0b8e168e883c4d0f0f0a976';

        // $body = json_decode($request->getContent());

        if ($body->event == "charge.success") {
            $transaction = Transaction::where('reference', $body->data->reference)->first();
            $transaction->status = 'success';
            $transaction->save();

            Event::create([
                'event' => 'Paystack Charge Success',
                'field_one' => $body->data->reference,
            ]);

            $client = new Client(); //GuzzleHttp\Client
            $response = $client->get('https://api.paystack.co/transaction/verify/' . $body->data->reference, [
                'headers' => [
                    'authorization' => 'Bearer ' . $secretKey,
                    'content-type' => 'application/json'
                ]
            ]);

            if ($response->getStatusCode() != 200) {
                return response()->json("verification_failed");
            }
            // $reason = $response->getReasonPhrase(); // OK
    
            $body = json_decode($response->getBody());

            // return response()->json($body->data->authorization, 200);
            
            $customer = $transaction->customer;
            $customer->authCode = $body->data->authorization->authorization_code;
            $customer->save();

            Event::create([
                'event' => 'Verification Success',
                'field_one' => $body->data->authorization->authorization_code,
            ]);

            return response()->json($transaction, 200);
        }

        return response()->json("not_handled");
    }

    public function update(Request $request, Customer $customer)
    {
        $customer->update($request->all());

        return response()->json($customer, 200);
    }

    public function delete(Customer $customer)
    {
        $customer->delete();

        return response()->json(null, 204);
    }
}

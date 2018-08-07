<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Customer;
use App\Event;
use App\Transaction;
use Illuminate\Http\Request;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class ChargeCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ChargeCustomers:chargecustomers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Charge customers due on this date';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Event::create([
            'event' => 'Charge Customers Schedule',
        ]);

        $secretKey = 'sk_live_a36687896d52aa5ec980ae75f8cff2b2448fd245';
        foreach (Customer::where([
                                    ['due_date', '<', Carbon::now()],
                                    ['charge_status', '=', false]
                            ])->cursor() as $customer) {
            //
            try {

                Event::create([
                    'event' => 'Charge this customer',
                    'field_one' => $customer->authCode,
                    'field_two' => $customer->due_amount
                ]);

            $client = new Client(); //GuzzleHttp\Client
            $response = $client->post('https://api.paystack.co/transaction/charge_authorization', [
                'headers' => [
                    'authorization' => 'Bearer ' . $secretKey,
                    'content-type' => 'application/json'
                ],
                'json' => [
                    'authorization_code' => $customer->authCode,
                    'amount' => $customer->due_amount,
                    'email' => $customer->email
                ]
            ]);

            if ($response->getStatusCode() == 200) {
                $body = json_decode($response->getBody());
                Event::create([
                    'event' => 'Customer charged',
                    'field_one' => $customer->email,
                    'field_two' => $body->message
                ]);
                
                $tranxReference = $body->data->reference;
                $transaction =  Transaction::create([
                    'amount' => $customer->due_amount,
                    'reference' => $tranxReference,
                    'customer_id' => $customer->id, 
                    'status' => 'success'
                ]);
                $customer->billable = 1005;
                $customer->charge_status = true;
                // $customer->due_date = Carbon::now()->addDays(1);
            } else {
                Event::create([
                    'event' => 'Failed to charge customer',
                    'field_one' => $customer->email
                ]);
                $customer->due_date = Carbon::now()->addMinutes(60);
                $customer->billable = 1004;
            }

            $customer->save();
            // $reason = $response->getReasonPhrase(); // OK

            
        }
        catch(\Exception $e) {
            Event::create([
                'event' => 'Error charging customer',
                'field_one' => $customer->email
            ]);
            
        }

        }

    }
}

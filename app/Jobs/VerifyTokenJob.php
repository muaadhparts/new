<?php

namespace App\Jobs;

use App\Models\Token;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class VerifyTokenJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $token;
    public  $queue ;

    public $tries = 3;
    public $timeout = 120;
    public function __construct()
    {
//        $token = ;

        $this->token = Token::latest()->first();
        $this->queue = 'token';
    }

    public function handle()
    {
        $response = $this->verify($this->token); /// chlidern
//            dd($response);
//        if(!$response){
        $newToken  =$this->reAuthenticate($this->token);
//            dd('ddd' ,$newToken);
//        }
//        dd('ddd22' ,$response);
//        return  $response;


    }

    public static function verify(Token  $token): bool
    {

//        dd($token ,'NissanGetData');
//        curl -X GET --header 'Accept: application/json' 'https://api.superservice.com/v1/auth/verify'

        $response =   Http::asJson()
//            ->contentType('application/json')
            ->withHeaders(
                [
                    'authorization' => $token->accessToken ?? '',

                    'X-IFM-SID' => env('X_IFM_SID'),

                ]
            )->get('https://api.superservice.com/v1/auth/verify');

        return $response->successful();


    }
    public static function reAuthenticate(Token  $token)
    {


        $response = Http::withHeaders([
            'authorization' => $token->accessToken ?? '',
//            'authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJFNFI3NWp0N1ZGbzUzZ3pzdTF3a1M3aUxqYngzSTJZTiIsInZmX2lmbSI6dHJ1ZSwiaXNzIjoiaHR0cHM6Ly9hcGkuc3VwZXJzZXJ2aWNlLmNvbSIsIlgtSUZNLVVJRCI6ImF0d2pyeS5zQGdtYWlsLmNvbSIsIlgtSUZNLUNPVU5UUlkiOiJTQSIsImV4cCI6MTcwNjg4NDc0OSwiaWF0IjoxNzA2ODEyNzQ5fQ.9LfrFfohffoeciXlybT1osCXyzVdtcVAuGNtpsZEZ0c',
//            'cookie' => $Cookie,
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->asForm()->post('https://api.superservice.com/v1/auth/login', [
//            'username' => $token->refreshToken,
//            'password' => $token->refreshToken,
            'refreshToken' => $token->refreshToken,
            'createRefreshToken' => 'true',
        ]);


//        dd($response ,$response->status()   ,$response->body());
        if ($response->successful()) {
            // Handle successful response
            $response = json_decode($response, true);
//            dd($response);
            return     Token::create([
                'accessToken' => $response['accessToken'],
                'refreshToken' => $response['refreshToken'],
                'expires_at' => now()->seconds($response['expiresInSeconds']),

            ]);


//            $data = $response->json();
//            dd($data);
        } elseif ($response->failed()) {
            // Handle error
            return    $response->body();
//            dd($err);
        }



    }
}

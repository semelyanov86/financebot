<?php


namespace App\Services;


use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class FireflyService
{
    protected $token;

    public function __construct()
    {
        $response = Http::asForm()->post(config('services.firefly.server') . '/oauth/token', [
            'grant_type' => 'password',
            'client_id' => config('services.firefly.client_id'),
            'client_secret' => config('services.firefly.client_secret'),
            'username' => config('services.firefly.user'),
            'password' => config('services.firefly.pass'),
            'scope' => '*'
        ]);
        if ($response->ok()) {
            $res = $response->json();
            $this->token = $res['access_token'];
        }
    }

    public function getCategories() : array
    {
        if ($this->token) {
            $response = Http::withToken($this->token)->get(config('services.firefly.server') . '/api/v1/categories');
            if ($response->ok()) {
                $res = $response->json();
                return $res['data'];
            } else {
                return array();
            }
        } else {
            return array();
        }
    }

    public function getBudgets() : array
    {
        if ($this->token) {
            $response = Http::withToken($this->token)->get(config('services.firefly.server') . '/api/v1/budgets');
            if ($response->ok()) {
                $res = $response->json();
                return $res['data'];
            } else {
                return array();
            }
        } else {
            return array();
        }
    }

    public function getAccounts() : array
    {
        if ($this->token) {
            $response = Http::withToken($this->token)->get(config('services.firefly.server') . '/api/v1/accounts');
            if ($response->ok()) {
                $res = $response->json();
                return $res['data'];
            } else {
                return array();
            }
        } else {
            return array();
        }
    }

    public function sendTransaction(array $data) : int
    {
        if ($this->token) {
            $response = Http::withHeaders([
                'Accept' => 'application/json'
            ])->withToken($this->token)->post(config('services.firefly.server') . '/api/v1/transactions', $data);
            if ($response->ok()) {
                $res = $response->json();
                return intval($res['data']['id']);
            } else {
                return 0;
            }
        }
    }

    public function getBalance() : Collection
    {
        if ($this->token) {
            $response = Http::withHeaders([
                'Accept' => 'application/json'
            ])->withToken($this->token)->get(config('services.firefly.server') . '/api/v1/summary/basic', [
                'start' => Carbon::now()->startOfMonth()->format('Y-m-d'),
                'end' => Carbon::now()->format('Y-m-d')
            ]);
            if ($response->ok()) {
                $res = $response->json();
                return collect($res)->map(function($row) {
                    return collect($row);
                });
            } else {
                return collect(array());
            }
        } else {
            return collect(array());
        }
    }

    public function getAvailableAccounts() : Collection
    {
        if ($this->token) {
            $response = Http::withHeaders([
                'Accept' => 'application/json'
            ])->withToken($this->token)->get(config('services.firefly.server') . '/api/v1/accounts');
            if ($response->ok()) {
                $res = $response->json();
                return collect($res['data'])->map(function($row) {
                    return collect($row);
                });
            } else {
                return collect(array());
            }
        } else {
            return collect(array());
        }
    }

    public function getTransactions() : Collection
    {
        if ($this->token) {
            $response = Http::withHeaders([
                'Accept' => 'application/json'
            ])->withToken($this->token)->get(config('services.firefly.server') . '/api/v1/transactions', [
                'start' => Carbon::yesterday()->format('Y-m-d'),
                'end' => Carbon::tomorrow()->format('Y-m-d'),
            ]);
            if ($response->ok()) {
                $res = $response->json();
                return collect($res['data'])->map(function($row) {
                    return collect($row);
                })->pluck('attributes.transactions')->map(function($row) {
                    return collect($row);
                })->pluck(0);
            } else {
                return collect(array());
            }
        } else {
            return collect(array());
        }
    }

    public function getCategoriesStat() : Collection
    {
        if ($this->token) {
            $response = Http::withHeaders([
                'Accept' => 'application/json'
            ])->withToken($this->token)->get(config('services.firefly.server') . '/api/v1/categories', [
                'start' => Carbon::now()->firstOfMonth()->format('Y-m-d H:i:s'),
                'end' => Carbon::now()->format('Y-m-d H:i:s')
            ]);
            if ($response->ok()) {
                $res = $response->json();
                return collect($res['data'])->map(function($row) {
                    return collect($row);
                })->pluck('attributes')->map(function($row) {
                    return collect($row);
                });
            } else {
                return collect(array());
            }
        } else {
            return collect(array());
        }
    }

    public function getBudgetsStat() : Collection
    {
        if ($this->token) {
            $response = Http::withHeaders([
                'Accept' => 'application/json'
            ])->withToken($this->token)->get(config('services.firefly.server') . '/api/v1/budgets', [
                'start' => Carbon::now()->firstOfMonth()->format('Y-m-d H:i:s'),
                'end' => Carbon::now()->format('Y-m-d H:i:s')
            ]);
            if ($response->ok()) {
                $res = $response->json();
                return collect($res['data'])->map(function($row) {
                    return collect($row);
                })->pluck('attributes')->map(function($row) {
                    return collect($row);
                });
            } else {
                return collect(array());
            }
        } else {
            return collect(array());
        }
    }
}

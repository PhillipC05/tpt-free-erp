<?php

namespace App\Providers;

use App\Models\Finance\Transaction;
use App\Models\Inventory\Product;
use App\Models\Inventory\StockMovement;
use App\Models\Sales\Invoice;
use App\Models\Sales\Order;
use App\Observers\Finance\TransactionObserver;
use App\Observers\Inventory\ProductObserver;
use App\Observers\Inventory\StockMovementObserver;
use App\Observers\Sales\InvoiceObserver;
use App\Observers\Sales\OrderObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        Transaction::observe(TransactionObserver::class);
        Product::observe(ProductObserver::class);
        StockMovement::observe(StockMovementObserver::class);
        Order::observe(OrderObserver::class);
        Invoice::observe(InvoiceObserver::class);
    }
}

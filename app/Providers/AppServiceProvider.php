<?php

namespace App\Providers;

use App\Models\Buyer;
use App\Models\BusinessProfile;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FatteningBatch;
use App\Models\LivestockType;
use App\Models\LivestockMarketPrice;
use App\Models\Pen;
use App\Models\Sale;
use App\Models\SaleProposal;
use App\Models\Sheep;
use App\Models\SheepIncidentRecord;
use App\Models\SheepPurchase;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WeighingRecord;
use App\Policies\BuyerPolicy;
use App\Policies\BusinessProfilePolicy;
use App\Policies\ExpenseCategoryPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\FatteningBatchPolicy;
use App\Policies\LivestockTypePolicy;
use App\Policies\LivestockMarketPricePolicy;
use App\Policies\PenPolicy;
use App\Policies\SalePolicy;
use App\Policies\SaleProposalPolicy;
use App\Policies\SheepIncidentRecordPolicy;
use App\Policies\SheepPolicy;
use App\Policies\SheepPurchasePolicy;
use App\Policies\StockMovementPolicy;
use App\Policies\SupplierPolicy;
use App\Policies\UserPolicy;
use App\Policies\WeighingRecordPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Pen::class, PenPolicy::class);
        Gate::policy(BusinessProfile::class, BusinessProfilePolicy::class);
        Gate::policy(LivestockType::class, LivestockTypePolicy::class);
        Gate::policy(LivestockMarketPrice::class, LivestockMarketPricePolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(Buyer::class, BuyerPolicy::class);
        Gate::policy(ExpenseCategory::class, ExpenseCategoryPolicy::class);
        Gate::policy(SheepPurchase::class, SheepPurchasePolicy::class);
        Gate::policy(FatteningBatch::class, FatteningBatchPolicy::class);
        Gate::policy(Sheep::class, SheepPolicy::class);
        Gate::policy(WeighingRecord::class, WeighingRecordPolicy::class);
        Gate::policy(SheepIncidentRecord::class, SheepIncidentRecordPolicy::class);
        Gate::policy(Expense::class, ExpensePolicy::class);
        Gate::policy(Sale::class, SalePolicy::class);
        Gate::policy(SaleProposal::class, SaleProposalPolicy::class);
        Gate::policy(StockMovement::class, StockMovementPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }
}

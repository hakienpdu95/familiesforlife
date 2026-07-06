<?php

use App\Enums\PermissionEnum as P;
use Illuminate\Support\Facades\Route;
use Modules\Subscription\Features\AdminSubscriptions\Http\AdminSubscriptionController;
use Modules\Subscription\Features\Analytics\Http\AnalyticsController;
use Modules\Subscription\Features\Payment\Http\Admin\InvoiceController;
use Modules\Subscription\Features\Payment\Http\Portal\InvoicePortalController;
use Modules\Subscription\Features\Cancel\Http\CancelController;
use Modules\Subscription\Features\ChangePlan\Http\ChangePlanController;
use Modules\Subscription\Features\Payment\Http\CheckoutController;
use Modules\Subscription\Features\Payment\Http\PaymentReturnController;
use Modules\Subscription\Features\Payment\Http\WebhookController;
use Modules\Subscription\Features\Plans\Http\PlanController;
use Modules\Subscription\Features\Portal\Http\BillingPortalController;
use Modules\Subscription\Features\Subscribe\Http\SubscribeController;

// ── Admin (subscription.admin permission) ─────────────────────────────────
Route::middleware(['web', 'auth', 'can:' . P::SUBSCRIPTION_ADMIN->value])
    ->prefix('dashboard/subscription/admin')
    ->name('subscription.admin.')
    ->group(function (): void {

        Route::resource('plans', PlanController::class)->except(['show']);
        Route::post('plans/{plan}/toggle',        [PlanController::class, 'toggle'])->name('plans.toggle');
        Route::post('plans/{plan}/features/sync', [PlanController::class, 'syncFeatures'])->name('plans.features.sync');

        Route::get('subscriptions',                              [AdminSubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::post('subscriptions/{organization}/assign',       [AdminSubscriptionController::class, 'assign'])->name('subscriptions.assign');
        Route::post('subscriptions/{organization}/extend',       [AdminSubscriptionController::class, 'extend'])->name('subscriptions.extend');
        Route::post('subscriptions/{organization}/override',     [AdminSubscriptionController::class, 'override'])->name('subscriptions.override');

        Route::get('invoices',                              [InvoiceController::class, 'index'])->name('invoices.index');
        Route::post('invoices/{invoice}/mark-paid',         [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
        Route::post('invoices/{invoice}/void',              [InvoiceController::class, 'void'])->name('invoices.void');

        Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    });

// ── Portal (subscription.view permission) ────────────────────────────────
Route::middleware(['web', 'auth', 'can:' . P::SUBSCRIPTION_VIEW->value])
    ->prefix('billing')
    ->name('subscription.portal.')
    ->group(function (): void {
        Route::get('/',     [BillingPortalController::class, 'billing'])->name('billing');
        Route::get('plans', [BillingPortalController::class, 'plans'])->name('plans');

        Route::post('subscribe',  [SubscribeController::class, 'subscribe'])->name('subscribe');
        Route::post('upgrade',    [ChangePlanController::class, 'upgrade'])->name('upgrade');
        Route::post('downgrade',  [ChangePlanController::class, 'downgrade'])->name('downgrade');
        Route::post('cancel',     [CancelController::class, 'cancel'])->name('cancel');
        Route::post('resume',     [CancelController::class, 'resume'])->name('resume');

        // Invoice portal — yêu cầu billing permission
        Route::middleware('can:' . P::SUBSCRIPTION_BILLING->value)->group(function (): void {
            Route::get('invoices',           [InvoicePortalController::class, 'index'])->name('invoices');
            Route::get('invoices/{invoice}', [InvoicePortalController::class, 'show'])->name('invoices.show');
        });
    });

// ── Billing checkout + payment return (subscription.billing permission) ──
Route::middleware(['web', 'auth', 'can:' . P::SUBSCRIPTION_BILLING->value])
    ->prefix('billing')
    ->name('subscription.billing.')
    ->group(function (): void {
        // Checkout flow
        Route::get('checkout/{plan}',       [CheckoutController::class, 'show'])->name('checkout.show');
        Route::post('checkout/initiate',    [CheckoutController::class, 'initiate'])->name('checkout.initiate');

        // Return & transfer instructions
        Route::get('payment/return/{gateway}',     [PaymentReturnController::class, 'handleReturn'])->name('return');
        Route::get('payment/transfer/{invoice}',   [PaymentReturnController::class, 'showTransfer'])->name('transfer');
        Route::get('payment/cancel',               [PaymentReturnController::class, 'cancel'])->name('cancel');

        // Manual gateway (dev/admin)
        Route::get('payment/manual/{invoice}',         [PaymentReturnController::class, 'showManual'])->name('manual');
        Route::post('payment/manual/{invoice}/confirm', [PaymentReturnController::class, 'confirmManual'])->name('manual.confirm');
    });

// ── Webhooks — CSRF exempt (configured in bootstrap/app.php) ────────────
// No auth middleware — called by payment gateways, not users
Route::middleware(['web'])
    ->prefix('billing/webhook')
    ->name('subscription.webhook.')
    ->group(function (): void {
        Route::post('{gateway}', [WebhookController::class, 'handle'])->name('handle');
    });

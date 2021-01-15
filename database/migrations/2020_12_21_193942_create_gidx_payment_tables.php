<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGidxPaymentTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $users) {
            $users->string('merchant_customer_id', 36)->nullable()->unique()
                ->comment('Customer id to interact with GIDX service')->after('role_id');
        });

        Schema::create('gidx_sessions', function (Blueprint $sessions) {
            $sessions->bigIncrements('id');
            $sessions->unsignedInteger('user_id');
            $sessions->string('merchant_session_id', 36)->unique()->comment('Session id to interact with GIDX service');
            $sessions->string('merchant_customer_id', 36)->comment('Customer id to interact with GIDX service')->index();
            $sessions->string('merchant_transaction_id', 36)->nullable()->comment('Transaction id to interact with GIDX service')->index();
            $sessions->string('service_type')->comment('Name of GIDX service');
            $sessions->string('ip_address')->nullable();
            $sessions->text('device_location')->nullable();
            $sessions->text('request_raw')->nullable()->comment('Store the QueryString or JSON of the request that is being made to a GIDX Service');
            $sessions->timestamps();

            $sessions->foreign(['user_id'])->references('id')->on('users');
        });

        Schema::create('gidx_session_responses', function (Blueprint $responses) {
            $responses->bigIncrements('id');
            $responses->unsignedInteger('user_id');
            $responses->unsignedBigInteger('gidx_session_id')->nullable();
            $responses->string('merchant_session_id', 36)->comment('Session id to interact with GIDX service')->index();
            $responses->string('merchant_customer_id', 36)->nullable()->comment('Customer id to interact with GIDX service')->index();
            $responses->string('merchant_transaction_id', 36)->nullable()->comment('Transaction id to interact with GIDX service')->index();
            $responses->string('service_type')->comment('Name of GIDX service');
            $responses->smallInteger('status_code')->nullable();
            $responses->string('status_message', 255)->nullable();
            $responses->decimal('session_score', 10,2)->nullable();
            $responses->text('response_raw')->nullable()->comment('Store the JSON response that is returned by the GIDX Service.');
            $responses->timestamps();

            $responses->foreign(['user_id'])->references('id')->on('users');
            $responses->foreign(['gidx_session_id'])->references('id')->on('gidx_sessions');
        });

        Schema::create('payment_requests', function (Blueprint $paymentRequests) {
            $paymentRequests->bigIncrements('id');
            $paymentRequests->unsignedInteger('user_id');
            $paymentRequests->enum('status', $this->paymentRequestStatuses())->default('new')->index();
            $paymentRequests->enum('type', ['deposit', 'withdraw', 'refund'])->comment('deposit: Deposit from customer, withdraw: Payout winning prizes to customer, refund: Refund deposited coins to customer')->index();
            $paymentRequests->string('merchant_transaction_id', 36)->nullable()->unique()->comment('Transaction id to interact with GIDX service');
            $paymentRequests->unsignedBigInteger('gidx_session_id')->nullable();
            $paymentRequests->string('method_type', 40)->nullable()->comment('Type of payment method from GIDX service')->index();
            $paymentRequests->decimal('amount', 10, 2)->comment('Amount of the transaction that is processed by the bank/processing service');
            $paymentRequests->string('currency', 3)->default('USD')->comment('USD or other ISO 4217 Currency Codes');
            $paymentRequests->timestamp('created_at')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $paymentRequests->timestamp('updated_at')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $paymentRequests->softDeletes();

            $paymentRequests->foreign(['user_id'])->references('id')->on('users');
            $paymentRequests->foreign(['gidx_session_id'])->references('id')->on('gidx_sessions');
        });

        Schema::create('payment_status_tracking', function (Blueprint $statusTracking) {
            $statusTracking->bigIncrements('id');
            $statusTracking->unsignedBigInteger('payment_request_id');
            $statusTracking->unsignedInteger('action_by')->nullable()->comment('User who perform status changed');
            $statusTracking->enum('action_type', ['manual', 'automatic', 'gidx_callback'])->default('automatic')->index();
            $statusTracking->enum('old_status', $this->paymentRequestStatuses())->nullable()->index();
            $statusTracking->enum('status', $this->paymentRequestStatuses())->default('new')->index();
            $statusTracking->unsignedBigInteger('gidx_session_response_id')->nullable();
            $statusTracking->timestamp('created_at')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $statusTracking->timestamp('updated_at')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));

            $statusTracking->foreign(['payment_request_id'])->references('id')->on('payment_requests');
            $statusTracking->foreign(['action_by'])->references('id')->on('users');
            $statusTracking->foreign(['gidx_session_response_id'])->references('id')->on('gidx_session_responses');
        });
    }

    /**
     * Returns values for status field of payment_requests.
     *
     * @return array
     */
    private function paymentRequestStatuses(): array
    {
        return [
            'new',
            'approved',
            'rejected',
            'approval_failed',
            'transaction_created',
            'session_created',
            'session_failed',
            'pending',
            'failed',
            'transaction_reversed',
            'completed',
        ];
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('merchant_customer_id');
        });
        Schema::dropIfExists('payment_status_tracking');
        Schema::dropIfExists('payment_requests');
        Schema::dropIfExists('gidx_session_responses');
        Schema::dropIfExists('gidx_sessions');
    }
}

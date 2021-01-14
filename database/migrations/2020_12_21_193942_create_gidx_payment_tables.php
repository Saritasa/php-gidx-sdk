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
        Schema::table('users', function (Blueprint $table) {
            $table->string('merchant_customer_id', 36)->nullable()->unique()->comment('Customer id to interact with GIDX service')->after('role_id');
        });

        Schema::create('gidx_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->string('merchant_session_id', 36)->unique()->comment('Session id to interact with GIDX service');
            $table->string('merchant_customer_id', 36)->comment('Customer id to interact with GIDX service')->index();
            $table->string('merchant_transaction_id', 36)->nullable()->comment('Transaction id to interact with GIDX service')->index();
            $table->string('service_type')->comment('Name of GIDX service');
            $table->string('ip_address')->nullable();
            $table->text('device_location')->nullable();
            $table->text('request_raw')->nullable()->comment('Store the QueryString or JSON of the request that is being made to a GIDX Service');
            $table->timestamps();

            $table->foreign(['user_id'])->references('id')->on('users');
        });

        Schema::create('gidx_session_responses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('gidx_session_id')->nullable();
            $table->string('merchant_session_id', 36)->comment('Session id to interact with GIDX service')->index();
            $table->string('merchant_customer_id', 36)->nullable()->comment('Customer id to interact with GIDX service')->index();
            $table->string('merchant_transaction_id', 36)->nullable()->comment('Transaction id to interact with GIDX service')->index();
            $table->string('service_type')->comment('Name of GIDX service');
            $table->smallInteger('status_code')->nullable();
            $table->string('status_message', 255)->nullable();
            $table->decimal('session_score', 10,2)->nullable();
            $table->text('response_raw')->nullable()->comment('Store the JSON response that is returned by the GIDX Service.');
            $table->timestamps();

            $table->foreign(['user_id'])->references('id')->on('users');
            $table->foreign(['gidx_session_id'])->references('id')->on('gidx_sessions');
        });

        Schema::create('payment_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');
            $table->enum('status', $this->paymentRequestStatuses())->default('new')->index();
            $table->enum('type', ['deposit', 'withdraw', 'refund'])->comment('deposit: Deposit from customer, withdraw: Payout winning prizes to customer, refund: Refund deposited coins to customer')->index();
            $table->string('merchant_transaction_id', 36)->nullable()->unique()->comment('Transaction id to interact with GIDX service');
            $table->unsignedBigInteger('gidx_session_id')->nullable();
//            $table->unsignedInteger('transaction_id')->nullable()->comment('Id of internal transaction');
            $table->unsignedInteger('reversal_transaction_id')->nullable()->comment('Id of internal reversal transaction');
            $table->string('method_type', 40)->nullable()->comment('Type of payment method from GIDX service')->index();
            $table->decimal('amount', 10, 2)->comment('Amount of the transaction that is processed by the bank/processing service');
            $table->string('currency', 3)->default('USD')->comment('USD or other ISO 4217 Currency Codes');
            $table->timestamp('created_at')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->softDeletes();

            $table->foreign(['user_id'])->references('id')->on('users');
//            $table->foreign(['transaction_id'])->references('id')->on('transactions');
            $table->foreign(['gidx_session_id'])->references('id')->on('gidx_sessions');
        });

        Schema::create('payment_status_tracking', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('payment_request_id');
            $table->unsignedInteger('action_by')->nullable()->comment('User who perform status changed');
            $table->enum('action_type', ['manual', 'automatic', 'gidx_callback'])->default('automatic')->index();
            $table->enum('old_status', $this->paymentRequestStatuses())->nullable()->index();
            $table->enum('status', $this->paymentRequestStatuses())->default('new')->index();
            $table->unsignedBigInteger('gidx_session_response_id')->nullable();
            $table->timestamp('created_at')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->foreign(['payment_request_id'])->references('id')->on('payment_requests');
            $table->foreign(['action_by'])->references('id')->on('users');
            $table->foreign(['gidx_session_response_id'])->references('id')->on('gidx_session_responses');
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

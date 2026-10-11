<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table 1: bandwidth packages and monthly cost
        Schema::create('bandwidth_plans', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('mbps')->unique();
            $t->decimal('monthly_cost', 10, 2);
            $t->timestamps();
        });

        // Table 2: LAN node price bands
        Schema::create('lan_node_rates', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('min_nodes');
            $t->unsignedInteger('max_nodes');
            $t->decimal('cost', 10, 2);
            $t->timestamps();
        });

        // Institutions + contact person details
        Schema::create('institutions', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->enum('category', ['primary', 'junior', 'senior', 'college']);
            $t->string('location');
            $t->string('postal_address')->nullable();
            $t->string('contact_name');
            $t->string('contact_id_number', 20);
            $t->string('contact_designation')->nullable();
            $t->string('contact_phone', 20);
            $t->string('contact_email');
            $t->foreignId('bandwidth_plan_id')->nullable()->constrained()->nullOnDelete();
            $t->enum('status', ['registered', 'surveyed', 'active', 'disconnected'])->default('registered');
            $t->date('registered_on');
            $t->timestamps();
        });

        // Site survey: readiness and infrastructure requirements
        Schema::create('infrastructure_requirements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('institution_id')->unique()->constrained()->cascadeOnDelete();
            $t->unsignedInteger('number_of_users');
            $t->unsignedInteger('computers_available')->default(0);
            $t->boolean('has_lan')->default(false);
            $t->unsignedInteger('pcs_to_purchase')->default(0);
            $t->unsignedInteger('lan_nodes_to_purchase')->default(0);
            $t->decimal('pc_cost', 12, 2)->default(0);
            $t->decimal('lan_cost', 12, 2)->default(0);
            $t->boolean('is_ready')->default(false);
            $t->date('surveyed_on');
            $t->text('remarks')->nullable();
            $t->timestamps();
        });

        // Bandwidth upgrades (10% discount on the new package)
        Schema::create('upgrades', function (Blueprint $t) {
            $t->id();
            $t->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $t->foreignId('from_plan_id')->constrained('bandwidth_plans');
            $t->foreignId('to_plan_id')->constrained('bandwidth_plans');
            $t->decimal('original_cost', 10, 2);
            $t->decimal('discount', 10, 2);
            $t->decimal('discounted_cost', 10, 2);
            $t->date('effective_month');
            $t->timestamps();
        });

        // Monthly bills
        Schema::create('monthly_bills', function (Blueprint $t) {
            $t->id();
            $t->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $t->date('billing_month');               // first day of month
            $t->decimal('amount', 10, 2);            // bandwidth charge
            $t->boolean('is_upgrade_rate')->default(false);
            $t->date('due_date');                    // end of billing month
            $t->date('disconnection_date');          // 10th of next month
            $t->decimal('overdue_fine', 10, 2)->default(0);
            $t->decimal('reconnection_fee', 10, 2)->default(0);
            $t->boolean('is_paid')->default(false);
            $t->date('paid_on')->nullable();
            $t->timestamps();
            $t->unique(['institution_id', 'billing_month']);
        });

        // All money received
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $t->foreignId('monthly_bill_id')->nullable()->constrained()->nullOnDelete();
            $t->enum('type', ['registration', 'installation', 'monthly', 'overdue_fine',
                              'reconnection', 'computers', 'lan']);
            $t->decimal('amount', 12, 2);
            $t->string('method')->default('M-Pesa');
            $t->string('reference')->nullable();
            $t->date('paid_on');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['payments', 'monthly_bills', 'upgrades', 'infrastructure_requirements',
                  'institutions', 'lan_node_rates', 'bandwidth_plans'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

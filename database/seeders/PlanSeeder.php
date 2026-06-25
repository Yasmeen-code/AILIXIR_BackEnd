<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::create([
            'name' => 'Free',
            'type' => 'free',
            'billing_period' => null,
            'price' => 0,
            'currency' => 'usd',
            'stripe_price_id' => null,
            'is_active' => true,
        ]);

        Plan::create([
            'name' => 'Pro Monthly',
            'type' => 'pro',
            'billing_period' => 'month',
            'price' => 9.99,
            'currency' => 'usd',
            'stripe_price_id' => 'price_1TltnqPK9FQXtJU73CGqIHqv',
            'is_active' => true,
        ]);

        Plan::create([
            'name' => 'Pro Yearly',
            'type' => 'pro',
            'billing_period' => 'year',
            'price' => 99.99,
            'currency' => 'usd',
            'stripe_price_id' => 'price_1TltnqPK9FQXtJU7hpD5HMD3',
            'is_active' => true,
        ]);

        Plan::create([
            'name' => 'Max Monthly',
            'type' => 'max',
            'billing_period' => 'month',
            'price' => 19.99,
            'currency' => 'usd',
            'stripe_price_id' => 'price_1TltqwPK9FQXtJU7gQaY03Kn',
            'is_active' => true,
        ]);

        Plan::create([
            'name' => 'Max Yearly',
            'type' => 'max',
            'billing_period' => 'year',
            'price' => 199.99,
            'currency' => 'usd',
            'stripe_price_id' => 'price_1TltqwPK9FQXtJU7K0jZcGPT',
            'is_active' => true,
        ]);
    }
}

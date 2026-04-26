<?php

namespace Tests\Feature;

use App\Models\ShopOrder;
use App\Models\ShopProduct;
use App\Services\ZiinaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ShopCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_endpoint_returns_sellable_catalog(): void
    {
        $response = $this->getJson('/api/shop/products');
        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(3, $data);
        $this->assertEquals(70.0, (float) $data[0]['price_aed']);
        $this->assertEquals(30.0, (float) $data[1]['price_aed']);
        $this->assertEquals(10.0, (float) $data[2]['price_aed']);
    }

    public function test_checkout_creates_pending_order_with_server_calculated_totals(): void
    {
        Mail::fake();

        $this->mock(ZiinaService::class, function ($mock): void {
            $mock->shouldReceive('createPaymentIntent')
                ->once()
                ->andReturn([
                    'id' => 'pi_test_shop_001',
                    'redirect_url' => 'https://pay.ziina.com/intent/pi_test_shop_001',
                    'status' => 'requires_payment_method',
                ]);
        });

        $book = ShopProduct::where('sku', 'book')->firstOrFail();
        $stickers = ShopProduct::where('sku', 'stickers')->firstOrFail();

        $response = $this->postJson('/api/shop/checkout', [
            'customer' => [
                'full_name' => 'Test Customer',
                'phone' => '+971500000000',
                'email' => 'customer@example.com',
            ],
            'delivery' => [
                'emirate' => 'Dubai',
                'area' => 'JVC',
                'detail' => 'Building 1, Apartment 10',
            ],
            'items' => [
                ['product_id' => $book->id, 'quantity' => 1],
                ['product_id' => $stickers->id, 'quantity' => 2],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', ShopOrder::STATUS_PENDING_PAYMENT)
            ->assertJsonPath('data.subtotal_aed', 90)
            ->assertJsonPath('data.shipping_fee_aed', 30)
            ->assertJsonPath('data.total_aed', 120);

        $order = ShopOrder::query()->firstOrFail();
        $this->assertSame(90.0, (float) $order->subtotal_aed);
        $this->assertSame(30.0, (float) $order->shipping_fee_aed);
        $this->assertSame(120.0, (float) $order->total_aed);
        $this->assertSame('pi_test_shop_001', $order->gateway_payment_intent_id);

        $token = $response->json('data.confirmation_token');
        $this->getJson("/api/shop/orders/{$order->id}?token={$token}")
            ->assertOk()
            ->assertJsonPath('data.order_number', $order->order_number)
            ->assertJsonPath('data.total_aed', 120);
    }
}

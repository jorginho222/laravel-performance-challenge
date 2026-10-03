<?php

namespace Tests\Feature;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Src\Catalog\Infrastructure\Persistence\Product;
use Src\Identity\Infrastructure\Persistence\User;
use Src\Ordering\Application\CreateOrder\CreateOrder;
use Src\Ordering\Application\CreateOrder\CreateOrderCommand;
use Src\Ordering\Application\CreateOrder\OrderItem;
use Src\Ordering\Application\OrderData;
use Src\Ordering\Domain\Events\OrderCreated;
use Src\Ordering\Infrastructure\Listeners\SendOrderConfirmationEmail;
use Src\Ordering\Infrastructure\Mail\LaravelOrderConfirmationMailer;
use Src\Ordering\Infrastructure\Mail\OrderConfirmationMail;
use Src\Ordering\Infrastructure\Persistence\OrderModel;
use Tests\TestCase;

class OrderConfirmationEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_order_dispatches_the_order_created_event(): void
    {
        Event::fake([OrderCreated::class]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $product = Product::factory()->create(['stock' => 10]);

        $response = $this->postJson('/api/orders', ['products' => [['product_id' => $product->id, 'quantity' => 1]]])
            ->assertCreated();

        Event::assertDispatched(OrderCreated::class, fn ($e) => $e->orderId === $response->json('data.id'));
    }

    public function test_no_event_is_dispatched_when_the_order_is_not_created(): void
    {
        Event::fake([OrderCreated::class]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/orders', ['products' => []])->assertUnprocessable();

        Event::assertNotDispatched(OrderCreated::class);
    }

    public function test_the_listener_is_queued(): void
    {
        Queue::fake();
        $order = $this->order(User::factory()->create());

        Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === SendOrderConfirmationEmail::class
            && $job->data[0]->orderId === $order->id);
    }

    public function test_the_listener_sends_the_confirmation_email_to_the_orders_user(): void
    {
        Mail::fake();
        $order = $this->order(User::factory()->create(['email' => 'buyer@example.com']));

        Mail::assertSent(OrderConfirmationMail::class, fn (OrderConfirmationMail $mail) => $mail->hasTo('buyer@example.com')
            && $mail->order->id === $order->id);
        Mail::assertSentCount(1);
    }

    public function test_the_listener_skips_a_deleted_order(): void
    {
        Mail::fake();
        Event::fake([OrderCreated::class]);
        $order = $this->order(User::factory()->create());
        OrderModel::destroy($order->id);

        app(SendOrderConfirmationEmail::class)->handle(new OrderCreated($order->id));

        Mail::assertNothingSent();
    }

    public function test_a_failed_listener_is_logged(): void
    {
        Log::spy();
        $order = $this->order(User::factory()->create());

        app(SendOrderConfirmationEmail::class)->failed(new OrderCreated($order->id), new RuntimeException('smtp down'));

        Log::shouldHaveReceived('error')->once()->withArgs(fn ($message, $context) => str_contains($message, $order->id)
            && $context['exception'] === 'smtp down');
    }

    public function test_the_email_shows_the_order_details(): void
    {
        Event::fake([OrderCreated::class]);
        $order = $this->order(User::factory()->create());

        $mail = new OrderConfirmationMail($order);

        $this->assertSame("Order #{$order->number} confirmed", $mail->envelope()->subject);
        $mail->assertSeeInHtml("#{$order->number}");
        $mail->assertSeeInHtml('blue widget');
        $mail->assertSeeInHtml('19.99');
        $mail->assertSeeInHtml('39.98');
    }

    public function test_the_mailer_delivers_the_email_to_the_recipient(): void
    {
        Mail::fake();
        Event::fake([OrderCreated::class]);
        $order = $this->order(User::factory()->create());

        app(LaravelOrderConfirmationMailer::class)->send('buyer@example.com', $order);

        Mail::assertSent(OrderConfirmationMail::class, fn ($m) => $m->hasTo('buyer@example.com') && $m->order === $order);
    }

    private function order(User $user): OrderData
    {
        $product = Product::factory()->create(['name' => 'blue widget', 'price' => '19.99', 'stock' => 10]);

        return app(CreateOrder::class)->handle(new CreateOrderCommand($user->id, [new OrderItem($product->id, 2)]));
    }
}

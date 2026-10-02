<?php

namespace Tests\Feature;

use App\Events\OrderCreated;
use App\Listeners\SendOrderConfirmationEmail;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\EmailSender;
use App\UseCases\BuildOrderConfirmationEmail;
use App\UseCases\CreateOrder;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
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

        Event::assertDispatched(OrderCreated::class, fn ($e) => $e->order->id === $response->json('data.id')
            && $e->order->user->is($user));
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
            && $job->data[0]->order->is($order));
    }

    public function test_the_listener_sends_the_confirmation_email_to_the_orders_user(): void
    {
        Mail::fake();
        $order = $this->order(User::factory()->create(['email' => 'buyer@example.com']));

        Mail::assertSent(OrderConfirmationMail::class, fn (OrderConfirmationMail $mail) => $mail->hasTo('buyer@example.com')
            && $mail->order->is($order));
        Mail::assertSentCount(1);
    }

    public function test_a_failed_listener_is_logged(): void
    {
        Log::spy();
        $order = $this->order(User::factory()->create());

        app(SendOrderConfirmationEmail::class)->failed(new OrderCreated($order), new RuntimeException('smtp down'));

        Log::shouldHaveReceived('error')->once()->withArgs(fn ($message, $context) => str_contains($message, "#{$order->number}")
            && $context['exception'] === 'smtp down');
    }

    public function test_the_use_case_builds_the_email_with_the_order_details_without_sending_it(): void
    {
        Mail::fake();
        Event::fake([OrderCreated::class]);
        $order = $this->order(User::factory()->create());

        $mail = app(BuildOrderConfirmationEmail::class)->handle($order);

        $this->assertSame("Order #{$order->number} confirmed", $mail->envelope()->subject);
        $mail->assertSeeInHtml("#{$order->number}");
        $mail->assertSeeInHtml('blue widget');
        $mail->assertSeeInHtml('39.98');
        Mail::assertNothingSent();
    }

    public function test_the_sender_delivers_the_email_to_the_recipient(): void
    {
        Mail::fake();
        Event::fake([OrderCreated::class]);
        $mail = app(BuildOrderConfirmationEmail::class)->handle($this->order(User::factory()->create()));

        app(EmailSender::class)->send('buyer@example.com', $mail);

        Mail::assertSent(OrderConfirmationMail::class, fn ($m) => $m->hasTo('buyer@example.com'));
    }

    private function order(User $user): Order
    {
        $product = Product::factory()->create(['name' => 'blue widget', 'price' => '19.99', 'stock' => 10]);

        return app(CreateOrder::class)->handle($user, [['product_id' => $product->id, 'quantity' => 2]]);
    }
}

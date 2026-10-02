<x-mail::message>
# Thanks for your order!

Your order **#{{ $order->number }}** has been received.

<x-mail::table>
| Product | Quantity | Price |
| :------ | -------: | ----: |
@foreach ($order->products as $product)
| {{ $product->name }} | {{ $product->pivot->quantity }} | {{ $product->price }} |
@endforeach
</x-mail::table>

**Total: {{ $order->total }}**

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

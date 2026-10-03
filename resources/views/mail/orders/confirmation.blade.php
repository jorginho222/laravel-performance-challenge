<x-mail::message>
# Thanks for your order!

Your order **#{{ $order->number }}** has been received.

<x-mail::table>
| Product | Quantity | Price |
| :------ | -------: | ----: |
@foreach ($order->lines as $line)
| {{ $line->productName }} | {{ $line->quantity }} | {{ $line->unitPrice }} |
@endforeach
</x-mail::table>

**Total: {{ $order->total }}**

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

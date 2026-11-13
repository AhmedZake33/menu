@props(['pricing', 'currency'])
<span class="d-inline-flex flex-column align-items-start">
    <strong class="price">{{ number_format($pricing['price'], 2) }} {{ $currency }}</strong>
    @if($pricing['original_price'] > $pricing['price'])
        <del class="small text-muted">{{ number_format($pricing['original_price'], 2) }} {{ $currency }}</del>
        <small class="text-success text-break">{{ $pricing['offer_title'] ?? 'خصم على الصنف' }}</small>
    @endif
</span>

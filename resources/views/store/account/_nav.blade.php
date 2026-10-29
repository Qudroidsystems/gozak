<nav class="box acc-nav" aria-label="My account">
    <a href="{{ route('store.account.orders') }}" class="{{ request()->routeIs('store.account.order*') ? 'on' : '' }}">📦 Orders</a>
    <a href="{{ route('store.account.wishlist') }}" class="{{ request()->routeIs('store.account.wishlist') ? 'on' : '' }}">♡ Saved items</a>
    <a href="{{ route('store.account.addresses') }}" class="{{ request()->routeIs('store.account.addresses*') ? 'on' : '' }}">📍 Addresses</a>
    <form method="post" action="{{ route('store.logout') }}">@csrf<button type="submit" class="dd-btn" style="padding:12px 16px">Sign out</button></form>
</nav>

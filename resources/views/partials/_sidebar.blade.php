<nav class="sidebar sidebar-offcanvas" id="sidebar">
    @php
        use Illuminate\Support\Str;
        $menuItems = config('menu.items', []);

        /**
         * decide whether the current user may see the item
         */
        $showItem = function ($item) {
            $user = auth()->user();
            if (!$user) {
                return false;
            }

            if (isset($item['permission']) && !$user->can($item['permission'])) {
                return false;
            }
            if (isset($item['roles'])) {
                $roles = (array) $item['roles'];
                if (!$user->hasAnyRole($roles)) {
                    return false;
                }
            }
            return true;
        };

        /**
         * return href for an item; supports route or url
         */
        $itemUrl = function ($item) {
            if (isset($item['route'])) {
                if (($item['type'] ?? 'url') === 'route') {
                    return route($item['route']);
                }
                return url($item['route']);
            }
            return '#';
        };
    @endphp

    <ul class="nav">
        @foreach ($menuItems as $item)
            @if($showItem($item))
                @if(isset($item['children']) && is_array($item['children']))
                    @php
                        $collapseId = Str::slug($item['title']);
                    @endphp
                    <li class="nav-item">
                        <a class="nav-link collapsed" data-bs-toggle="collapse" href="#{{ $collapseId }}" aria-expanded="false" aria-controls="{{ $collapseId }}">
                            <i class="{{ $item['icon'] ?? '' }} menu-icon"></i>
                            <span class="menu-title">{{ $item['title'] }}</span>
                            <i class="menu-arrow"></i>
                        </a>
                        <div class="collapse" id="{{ $collapseId }}">
                            <ul class="nav flex-column sub-menu">
                                @foreach($item['children'] as $child)
                                    @if($showItem($child))
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ $itemUrl($child) }}">{{ $child['title'] }}</a>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    </li>
                @else
                    <li class="nav-item">
                        <a class="nav-link" href="{{ $itemUrl($item) }}">
                            <i class="{{ $item['icon'] ?? '' }} menu-icon"></i>
                            <span class="menu-title">{{ $item['title'] }}</span>
                        </a>
                    </li>
                @endif
            @endif
        @endforeach
    </ul>
</nav>

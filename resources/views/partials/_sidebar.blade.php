<nav class="sidebar sidebar-offcanvas" id="sidebar">
    @php
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
                    <li class="nav-item">
                        <span class="nav-link" style="cursor: default;">
                            <i class="{{ $item['icon'] ?? '' }} menu-icon"></i>
                            <span class="menu-title">{{ $item['title'] }}</span>
                        </span>
                    </li>

                    @foreach($item['children'] as $child)
                        @if($showItem($child))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ $itemUrl($child) }}" style="padding-left: 3rem;">
                                    <i class="ti-angle-right menu-icon"></i>
                                    <span class="menu-title">{{ $child['title'] }}</span>
                                </a>
                            </li>
                        @endif
                    @endforeach
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

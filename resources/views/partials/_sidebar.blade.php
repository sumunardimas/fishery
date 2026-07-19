<nav class="sidebar sidebar-offcanvas" id="sidebar">
    @php
        $menuItems = config('menu.items', []);
        $currentRoute = request()->route()?->getName() ?? '';
        $currentPath = request()->path();

        /**
         * decide whether the current user may see the item
         */
        $showItem = function ($item) {
            $user = auth()->user();
            if (!$user) {
                return false;
            }

            // Staff users only see menu entries explicitly tagged with the staff role.
            if ($user->hasRole('staff')) {
                $roles = (array) ($item['roles'] ?? []);
                if (!in_array('staff', $roles, true)) {
                    return false;
                }
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

        /**
         * check if item is active
         */
        $isActive = function ($item) use ($currentRoute, $currentPath) {
            if (!isset($item['route'])) {
                return false;
            }

            $itemRoute = $item['route'];

            if (($item['type'] ?? 'url') === 'route') {
                // For named routes, check various patterns
                return request()->routeIs($itemRoute) ||
                    request()->routeIs($itemRoute . '*') ||
                    str_starts_with($currentRoute, $itemRoute);
            } else {
                // For URLs, use exact path match only
                $itemPath = '/' . ltrim($itemRoute, '/');
                $currentFullPath = '/' . $currentPath;

                return $currentFullPath === $itemPath;
            }
        };

        /**
         * check if any child is active
         */
        $hasActiveChild = function ($item) use ($isActive, $showItem) {
            if (!isset($item['children']) || !is_array($item['children'])) {
                return false;
            }

            foreach ($item['children'] as $child) {
            }
            return false;
        };
    @endphp

    <ul class="nav">
        @foreach ($menuItems as $item)
            @if ($showItem($item))
                @if (isset($item['children']) && is_array($item['children']))
                    <li class="nav-item {{ $hasActiveChild($item) ? 'active' : '' }}">
                        @if (($item['route'] ?? '#') !== '#')
                            <a class="nav-link" href="{{ $itemUrl($item) }}">
                                <i class="{{ $item['icon'] ?? '' }} menu-icon"></i>
                                <span class="menu-title">{{ $item['title'] }}</span>
                            </a>
                        @else
                            <span class="nav-link" style="cursor: default;">
                                <i class="{{ $item['icon'] ?? '' }} menu-icon"></i>
                                <span class="menu-title">{{ $item['title'] }}</span>
                            </span>
                        @endif
                    </li>

                    @foreach ($item['children'] as $child)
                        @if ($showItem($child))
                            <li class="nav-item {{ $isActive($child) ? 'active' : '' }}">
                                <a class="nav-link" href="{{ $itemUrl($child) }}" style="padding-left: 3rem;">
                                    <i class="ti-angle-right menu-icon"></i>
                                    <span class="menu-title">{{ $child['title'] }}</span>
                                </a>
                            </li>
                        @endif
                    @endforeach
                @else
                    <li class="nav-item {{ $isActive($item) ? 'active' : '' }}">
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

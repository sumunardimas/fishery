<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffMenuAccess
{
    /**
     * Restrict staff access to the allowed menu sections only.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->hasRole('staff')) {
            return $next($request);
        }

        $allowedRoutePatterns = [
            'kapal.*',
            'pelayaran.*',
            'pembelian.*',
            'master.item-pembelian.*',
            'master.perbekalan.*',
            'operasional-kantor.*',
            'keuangan.kas.*',
            'keuangan.bank.*',
            'keuangan.piutang.*',
            'keuangan.kas-bon-pegawai.*',
            'keuangan.hutang-modal.*',
            'keuangan.hutang-jons-group.*',
        ];

        foreach ($allowedRoutePatterns as $pattern) {
            if ($request->routeIs($pattern)) {
                return $next($request);
            }
        }

        abort(403, 'Forbidden');
    }
}

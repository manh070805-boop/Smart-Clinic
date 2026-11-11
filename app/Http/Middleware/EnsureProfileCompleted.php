<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasCompleteBookingProfile()) {
            return redirect()->route('profile.edit')->with(
                'warning',
                'Theo quy định đặt lịch (SC03), bạn cần cập nhật đầy đủ Số Căn cước công dân (CCCD), Số điện thoại và Ngày sinh trước khi thực hiện đặt lịch khám.'
            );
        }

        return $next($request);
    }
}

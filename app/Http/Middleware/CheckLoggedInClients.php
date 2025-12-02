<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckLoggedInClients
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        
        if(!$request->session()->has('username'))
        {
            // Lấy URL đích
            $intendedUrl = $request->fullUrl();
            $path = $request->path();
            
            // Kiểm tra nếu là POST-only route, không lưu vào session
            // Thay vào đó, nếu là booking route, lưu tour detail route
            if (preg_match('#^booking/(\d+)$#', $path, $matches)) {
                // Nếu là booking route, lưu tour detail route thay vì booking
                $tourId = $matches[1];
                $intendedUrl = route('tour-detail', ['id' => $tourId]);
            }
            // Kiểm tra các POST-only routes khác
            elseif (preg_match('#^(create-booking|create-momo-payment|momo-ipn|cancel-booking|confirm-booking|finish-booking|build-tour/choose|build-tour/checkout|custom-tours/checkout)#', $path)) {
                // Không lưu POST-only routes, redirect về trang chủ sau khi login
                $intendedUrl = route('home');
            }
            
            // Lưu URL đích để redirect lại sau khi đăng nhập
            $request->session()->put('url.intended', $intendedUrl);
            toastr()->error('Vui lòng đăng nhập để thực hiện.', "Thông báo");
            return redirect()->route('login');
        }
        return $next($request);
    }
}

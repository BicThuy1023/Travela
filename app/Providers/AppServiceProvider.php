<?php

namespace App\Providers;

use App\Models\admin\ContactModel;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\clients\Tours; 
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('admin.blocks.sidebar', function ($view) {
            $contactModel = new ContactModel();
            $unreadData = $contactModel->countContactsUnread(); // Lấy cả số lượng và danh sách thư

            // Chia sẻ số lượng và danh sách thư chưa trả lời vào view sidebar
            $view->with('unreadCount', $unreadData['countUnread']);
            $view->with('unreadContacts', $unreadData['contacts']);
        });
      
        View::composer('clients.blocks.banner_home', function ($view) {
            $destinations = Tours::getUniqueDestinations();
            $view->with('destinations', $destinations);
        });

        View::composer('clients.blocks.banner_home', function ($view) {
        $destinations = Tours::getUniqueDestinations(); // [ 'Đà Nẵng', 'Cù Lao Chàm', ... ]
        $view->with('destinations', $destinations);
        });
        
    }
}

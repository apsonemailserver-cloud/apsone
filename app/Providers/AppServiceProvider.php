<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (
            app()->environment('production') ||
            request()->header('x-forwarded-proto') === 'https' ||
            str_contains(request()->header('host', ''), 'ngrok') ||
            request()->isSecure()
        ) {
            URL::forceScheme('https');
        }

        View::composer('layout.admin', function ($view) {
            $req = request();
            $cacheKey = 'topbar_announcement_data';

            if (! $req->attributes->has($cacheKey)) {
                if (Auth::check() && \Illuminate\Support\Facades\Schema::hasTable('announcements') && \Illuminate\Support\Facades\Schema::hasTable('announcement_reads')) {
                    $user = Auth::user();
                    $readAnnouncementIds = AnnouncementRead::where('user_id', $user->id)
                        ->pluck('announcement_id')
                        ->toArray();

                    $topbarAnnouncements = Announcement::forUser($user)
                        ->latest()
                        ->take(3)
                        ->get();

                    $unreadAnnouncementsCount = Announcement::forUser($user)
                        ->whereNotIn('id', $readAnnouncementIds)
                        ->count();

                    $req->attributes->set($cacheKey, [
                        'unreadAnnouncementsCount' => $unreadAnnouncementsCount,
                        'topbarAnnouncements' => $topbarAnnouncements,
                        'readAnnouncementIds' => $readAnnouncementIds,
                    ]);
                } else {
                    $req->attributes->set($cacheKey, []);
                }
            }

            $view->with($req->attributes->get($cacheKey));
        });
    }
}

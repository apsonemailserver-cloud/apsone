<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Announcement::forUser($user)->with(['author'])->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $announcements = $query->paginate(10)->withQueryString();
        $readIds = AnnouncementRead::where('user_id', $user->id)->pluck('announcement_id')->toArray();
        $stations = Station::where('is_active', 1)->orderBy('code')->get();

        $selectedAnnouncement = null;
        if ($request->filled('select')) {
            $selectedAnnouncement = Announcement::find($request->input('select'));
            if ($selectedAnnouncement) {
                AnnouncementRead::firstOrCreate([
                    'announcement_id' => $selectedAnnouncement->id,
                    'user_id' => $user->id,
                ], [
                    'read_at' => now(),
                ]);
                if (!in_array($selectedAnnouncement->id, $readIds)) {
                    $readIds[] = $selectedAnnouncement->id;
                }
            }
        }

        return view('announcements.index', compact('announcements', 'readIds', 'stations', 'selectedAnnouncement'));
    }

    public function create()
    {
        if (strtolower((string) Auth::user()->role) !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $stations = Station::where('is_active', 1)->orderBy('code')->get();
        return view('announcements.create', compact('stations'));
    }

    public function store(Request $request)
    {
        if (strtolower((string) Auth::user()->role) !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'target_stations' => 'nullable|array',
        ]);

        $targetStations = $request->input('target_stations', []);
        if (empty($targetStations) || in_array('ALL', $targetStations, true)) {
            $targetStations = ['ALL'];
        }

        Announcement::create([
            'title' => $request->input('title'),
            'content' => $request->input('content'),
            'target_stations' => $targetStations,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('announcements.index')->with('success', 'Pengumuman berhasil diterbitkan.');
    }

    public function show(Request $request, $id)
    {
        $user = Auth::user();
        $announcement = Announcement::forUser($user)->with(['author'])->findOrFail($id);

        AnnouncementRead::firstOrCreate([
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
        ], [
            'read_at' => now(),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            $unreadCount = Announcement::forUser($user)
                ->whereNotIn('id', AnnouncementRead::where('user_id', $user->id)->pluck('announcement_id'))
                ->count();

            return response()->json([
                'success' => true,
                'announcement' => $announcement,
                'unreadCount' => $unreadCount,
            ]);
        }

        return view('announcements.show', compact('announcement'));
    }

    public function edit($id)
    {
        if (strtolower((string) Auth::user()->role) !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $announcement = Announcement::findOrFail($id);
        $stations = Station::where('is_active', 1)->orderBy('code')->get();
        return view('announcements.edit', compact('announcement', 'stations'));
    }

    public function update(Request $request, $id)
    {
        if (strtolower((string) Auth::user()->role) !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $announcement = Announcement::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'target_stations' => 'nullable|array',
        ]);

        $targetStations = $request->input('target_stations', []);
        if (empty($targetStations) || in_array('ALL', $targetStations, true)) {
            $targetStations = ['ALL'];
        }

        $announcement->update([
            'title' => $request->input('title'),
            'content' => $request->input('content'),
            'target_stations' => $targetStations,
        ]);

        return redirect()->route('announcements.index')->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function destroy($id)
    {
        if (strtolower((string) Auth::user()->role) !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $announcement = Announcement::findOrFail($id);
        $announcement->delete();

        return redirect()->route('announcements.index')->with('success', 'Pengumuman berhasil dihapus.');
    }

    public function markAsRead(Request $request, $id)
    {
        $user = Auth::user();
        AnnouncementRead::firstOrCreate([
            'announcement_id' => $id,
            'user_id' => $user->id,
        ], [
            'read_at' => now(),
        ]);

        if ($request->wantsJson()) {
            $unreadCount = Announcement::forUser($user)
                ->whereNotIn('id', AnnouncementRead::where('user_id', $user->id)->pluck('announcement_id'))
                ->count();

            return response()->json([
                'success' => true,
                'unreadCount' => $unreadCount,
            ]);
        }

        return redirect()->back()->with('success', 'Pengumuman telah ditandai dibaca.');
    }

    public function markAllRead(Request $request)
    {
        $user = Auth::user();
        $announcements = Announcement::forUser($user)->get();

        foreach ($announcements as $announcement) {
            AnnouncementRead::firstOrCreate([
                'announcement_id' => $announcement->id,
                'user_id' => $user->id,
            ], [
                'read_at' => now(),
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'unreadCount' => 0,
            ]);
        }

        return redirect()->back()->with('success', 'Semua pengumuman telah ditandai dibaca.');
    }
}

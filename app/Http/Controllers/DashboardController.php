<?php

namespace App\Http\Controllers;
use App\Models\PostKerja;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\Lamaran;
use App\Models\Interview;
use App\Models\Bio;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $notifications = [];

        if ($user->hasRole('perusahaan')) {
            // Notifikasi untuk perusahaan: pelamar baru untuk job yang mereka posting
            $jobIds = PostKerja::where('user_id', $user->id)->pluck('id');
            $notifications = Lamaran::with('postKerja') // relasi ke PostKerja
                ->whereIn('post_kerjas_id', $jobIds)
                ->latest()
                ->take(5)
                ->get();

            $recentJobs = PostKerja::where('user_id', $user->id)
                    ->latest()
                    ->take(3)
                    ->get();

            $jobList = PostKerja::where('user_id', $user->id)->get();

            return view('dashboardperusahaan', compact('user', 'recentJobs','jobList', 'notifications'));

        } elseif ($user->hasRole('pencarikerja')) {
            $user = auth()->user();
            $userId = $user->id;
            $bio = Bio::where('user_id', $userId)->first();
            $profileCompletion = $bio ? $bio->completion_percentage : 0;
            $completionFields = \App\Models\Bio::getCompletionFields();

            $incompleteFields = [];
            if ($bio) {
                foreach ($completionFields as $key => $label) {
                    $value = $bio->$key;
                    if (is_null($value) || trim($value) === '') {
                        $incompleteFields[] = $label;
                    }
                }

                $profileCompletion = $bio->completion_percentage;
            } else {
                $incompleteFields = array_values($completionFields);
                $profileCompletion = 0;
            }

            if ($bio) {
                foreach ($completionFields as $key => $label) {
                    $value = $bio->$key;
                    if (is_null($value) || trim($value) === '') {
                        $incompleteFields[] = $label;
                    }
                }
            }


            // Notifikasi untuk pencari kerja: status lamaran terbaru
            $notifications = Lamaran::with('postKerja')
                ->where('user_id', $user->id)
                ->whereNotNull('status')
                ->latest()
                ->take(5)
                ->get();

            $recentJobs = Lamaran::with('postKerja')
                ->where('user_id', $user->id)
                ->latest()
                ->take(2)
                ->get();

            $upcomingTests = Interview::with('postKerja')
                ->where('user_id', $user->id)
                ->whereDate('jadwal', '>=', now())
                ->orderBy('jadwal')
                ->get();

            return view('dashboardpencarikerja', compact('user', 'profileCompletion', 'recentJobs', 'upcomingTests', 'notifications', 'bio'))->with('incompleteFields', array_slice($incompleteFields, 0, 3));
        }

        return abort(403, 'Unauthorized');
    }
    public function getChartData($jobId)
    {
        // Pastikan job ini milik user yang login
        $job = PostKerja::where('id', $jobId)->where('user_id', Auth::id())->first();

        if (!$job) {
            return response()->json(['message' => 'Job not found or unauthorized'], 404);
        }

        $data = Lamaran::where('post_kerjas_id', $jobId)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'dates' => $data->pluck('date'),
            'totals' => $data->pluck('total'),
        ]);
    }
}

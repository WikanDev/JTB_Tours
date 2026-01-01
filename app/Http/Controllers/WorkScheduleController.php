<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkScheduleController extends Controller
{
    
    public function index(Request $request)
    {
        $year  = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        $users = User::whereIn('role', ['driver','guide'])
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        $existingScheduleUserIds = WorkSchedule::where('year', $year)
            ->where('month', $month)
            ->pluck('user_id')
            ->toArray();

        $missingUsers = $users->whereNotIn('id', $existingScheduleUserIds);

        
        if ($missingUsers->isNotEmpty()) {
            DB::transaction(function () use ($missingUsers, $year, $month) {
                foreach ($missingUsers as $user) {
                    WorkSchedule::create([
                        'user_id'     => $user->id,
                        'year'        => $year,
                        'month'       => $month,
                        'total_hours' => $user->monthly_work_limit ?? 200,
                        'used_hours'  => 0, 
                    ]);
                }
            });
        }

        
        $schedules = WorkSchedule::whereIn('user_id', $users->pluck('id'))
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('user_id');

        $perPage = 15; 
        $workSchedules = WorkSchedule::query()
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->paginate($perPage);
        

        return view('work_schedules.index', compact('users','schedules','month','year', 'workSchedules'));
    }

    
    public function generateForAll(Request $request)
    {
        $data = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'month'=> 'required|integer|min:1|max:12',
        ]);

        $year  = (int) $data['year'];
        $month = (int) $data['month'];

        $users = User::whereIn('role', ['driver','guide'])->get();

        DB::transaction(function() use ($users, $year, $month) {
            foreach ($users as $user) {
                WorkSchedule::updateOrCreate(
                    ['user_id' => $user->id, 'month' => $month, 'year' => $year],
                    [
                        'total_hours' => $user->monthly_work_limit ?? 200,
                        
                        
                    ]
                );
            }
        });

        return redirect()->route('work-schedules.index', ['year'=>$year,'month'=>$month])
            ->with('success','Work schedules dibuat / diperbarui untuk semua driver & guide.');
    }

    
    public function bulkUpdate(Request $request)
    {
        $data = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'month'=> 'required|integer|min:1|max:12',
            'schedules' => 'required|array',
            'schedules.*' => 'nullable|integer|min:0'
        ]);

        $year  = (int) $data['year'];
        $month = (int) $data['month'];
        $inputSchedules = $data['schedules'];

        DB::transaction(function() use ($inputSchedules, $month, $year) {
            foreach ($inputSchedules as $userId => $totalHours) {
                $user = User::find($userId);
                if (!$user || !in_array($user->role, ['driver','guide'])) continue;

                $ws = WorkSchedule::firstOrNew(['user_id'=>$user->id,'month'=>$month,'year'=>$year]);

                
                $ws->total_hours = $totalHours ?? ($user->monthly_work_limit ?? 200);
                $ws->used_hours = min($ws->used_hours ?? 0, $ws->total_hours);
                $ws->save();
            }
        });

        return redirect()->route('work-schedules.index', ['year'=>$year,'month'=>$month])
            ->with('success','Work schedules berhasil diperbarui.');
    }

    
    public function edit(WorkSchedule $workSchedule)
    {
        $workSchedule->load('user');
        return view('work_schedules.edit', compact('workSchedule'));
    }

    
    public function update(Request $request, WorkSchedule $workSchedule)
    {
        $data = $request->validate([
            'total_hours' => 'required|integer|min:0',
            'used_hours'  => 'nullable|integer|min:0'
        ]);

        $workSchedule->total_hours = $data['total_hours'];
        if (isset($data['used_hours'])) {
            $workSchedule->used_hours = min($data['used_hours'], $data['total_hours']);
        } else {
            $workSchedule->used_hours = min($workSchedule->used_hours ?? 0, $data['total_hours']);
        }
        $workSchedule->save();

        return redirect()->route('work-schedules.index', ['year'=>$workSchedule->year,'month'=>$workSchedule->month])
            ->with('success','Schedule diperbarui.');
    }

    
    public function resetUsedHours(Request $request)
    {
        $data = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'month'=> 'required|integer|min:1|max:12',
            'user_ids' => 'nullable|array'
        ]);

        $year  = (int)$data['year'];
        $month = (int)$data['month'];

        $query = WorkSchedule::where('year',$year)->where('month',$month);
        if (!empty($data['user_ids'])) {
            $query->whereIn('user_id',$data['user_ids']);
        }

        $query->update(['used_hours' => 0]);

        return redirect()->route('work-schedules.index', ['year'=>$year,'month'=>$month])
            ->with('success','Used hours di-reset menjadi 0.');
    }
}

<?php

namespace App\Observers;

use App\Models\LogActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityObserver
{
    public function created(Model $model): void
    {
        $this->log($model, 'CREATE ' . strtoupper(class_basename($model)));
    }

    public function updated(Model $model): void
    {
        $this->log($model, 'UPDATE ' . strtoupper(class_basename($model)));
    }

    public function deleted(Model $model): void
    {
        $this->log($model, 'DELETE ' . strtoupper(class_basename($model)));
    }

    private function log(Model $model, string $action): void
    {
        // Gunakan Auth Facade agar tidak error Intelephense
        $userId = Auth::check() ? Auth::id() : null;

        LogActivity::create([
            'user_id'    => $userId,
            'table_name' => $model->getTable(),
            'action'     => $action,
        ]);
    }
}

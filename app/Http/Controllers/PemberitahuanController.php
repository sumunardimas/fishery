<?php

namespace App\Http\Controllers;

use App\Services\StockNotificationService;
use Illuminate\View\View;

class PemberitahuanController extends Controller
{
    public function __construct(private readonly StockNotificationService $notificationService) {}

    public function index(): View
    {
        $notifications = $this->notificationService->notifications();

        return view('pemberitahuan.index', compact('notifications'));
    }
}

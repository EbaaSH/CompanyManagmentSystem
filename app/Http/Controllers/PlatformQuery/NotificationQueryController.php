<?php

namespace App\Http\Controllers\PlatformQuery;

use App\Http\Controllers\Controller;
use App\Http\Responses\Response;
use App\Models\Notification;
use App\Services\PlatformQueryServices\NotificationQueryService;
use Illuminate\Http\Request;
use Throwable;

class NotificationQueryController extends Controller
{
    protected $queryService;

    public function __construct(NotificationQueryService $queryService)
    {
        $this->queryService = $queryService;
    }

    public function index()
    {
        try {
            $this->authorize('viewAny', Notification::class);
            $data = $this->queryService->getAllNotifications();
            if ($data['code'] !== 200) {
                return Response::Error($data['data'], $data['message'], $data['code']);
            }
            return Response::Paginate($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $user = auth()->user();
            $notification = Notification::query()
                ->ForUserViaPermission($user)
                ->find($id);
            if (!$notification) {
                return Response::Error(null, 'employee not found', 404);
            }
            $this->authorize('view', $notification);
            $data = $this->queryService->getNotifyById($id);
            if ($data['code'] !== 200) {
                return Response::Error($data['data'], $data['message'], $data['code']);
            }

            return Response::Success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }
}

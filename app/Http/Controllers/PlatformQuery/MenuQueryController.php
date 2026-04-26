<?php

namespace App\Http\Controllers\PlatformQuery;

use App\Http\Controllers\Controller;
use App\Http\Responses\Response;
use App\Models\Menu\Menu;
use App\Services\PlatformQueryServices\MenuQueryService;
use Throwable;

class MenuQueryController extends Controller
{
    protected $queryService;

    public function __construct(MenuQueryService $queryService)
    {
        $this->queryService = $queryService;
    }

    public function index()
    {
        try {
            $this->authorize('viewAny', Menu::class);
            $data = $this->queryService->getAllMenus();
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
            $menu = Menu::query()
                ->ForUserViaPermission($user)
                ->find($id);
            if (!$menu) {
                return Response::Error(null, 'employee not found', 404);
            }
            $this->authorize('view', $menu);
            $data = $this->queryService->getMenuById($id);
            if ($data['code'] !== 200) {
                return Response::Error($data['data'], $data['message'], $data['code']);
            }

            return Response::Success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }
}

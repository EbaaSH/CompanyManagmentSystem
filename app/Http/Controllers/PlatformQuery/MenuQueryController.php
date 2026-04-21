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
        $user = auth()->user();
        $this->authorize('viewAny', Menu::class);
        try {
            $data = $this->queryService->getAllMenus();

            return Response::Paginate($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }

    public function show($id)
    {
        $menu = Menu::find($id);
        if (! $menu) {
            return Response::Error(null, 'employee not found', 404);
        }
        $this->authorize('view', $menu);
        try {
            $data = $this->queryService->getMenuById($id);
            if ($data['code'] === 200) {
                return Response::Success($data['data'], $data['message'], $data['code']);
            }

            return Response::Error($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            return Response::Error([], $th->getMessage());
        }
    }
}

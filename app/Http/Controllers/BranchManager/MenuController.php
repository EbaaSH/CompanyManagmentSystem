<?php

namespace App\Http\Controllers\BranchManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchManager\StoreMenuRequest;
use App\Http\Requests\BranchManager\UpdateMenuRequest;
use App\Http\Responses\Response;
use App\Models\Menu\Menu;
use App\Services\BranchManager\MenuService;
use Illuminate\Support\Facades\DB;
use Throwable;

class MenuController extends Controller
{
    protected $menuService;

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
    }

    public function store(StoreMenuRequest $request)
    {
        $this->authorize('create', Menu::class);
        DB::beginTransaction();
        try {
            $data = $this->menuService->createMenu($request);

            DB::commit();

            return Response::Success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }

    public function update(UpdateMenuRequest $request, $id)
    {
        $menu = Menu::find($id);
        if (! $menu) {
            return Response::Error(null, 'menu not found', 404);
        }
        $this->authorize('update', $menu);
        DB::beginTransaction();
        try {
            $data = $this->menuService->updateMenu($request, $id);

            DB::commit();

            return Response::Success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();

            return Response::Error([], $th->getMessage());
        }
    }
}

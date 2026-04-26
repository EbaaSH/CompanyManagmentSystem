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
        try {
            $this->authorize('create', Menu::class);
            DB::beginTransaction();
            $data = $this->menuService->createMenu($request);
            if ($data['code'] !== 201) {
                DB::rollBack();
                return Response::Error($data['data'], $data['message'], $data['code']);
            }
            DB::commit();
            return Response::Success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();
            return Response::Error([], $th->getMessage());
        }
    }

    public function update(UpdateMenuRequest $request, $id)
    {
        try {
            $user = auth()->user();
            $menu = Menu::query()
                ->forUserViaPermission($user)
                ->find($id);
            if (!$menu) {
                return Response::Error(null, 'menu not found', 404);
            }
            $this->authorize('update', $menu);
            DB::beginTransaction();
            $data = $this->menuService->updateMenu($request, $id);
            if ($data['code'] !== 200) {
                DB::rollBack();
                return Response::Error($data['data'], $data['message'], $data['code']);
            }
            DB::commit();
            return Response::Success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();
            return Response::Error([], $th->getMessage());
        }
    }
    public function delete($id)
    {
        try {
            $user = auth()->user();
            $menu = Menu::query()
                ->forUserViaPermission($user)
                ->find($id);
            if (!$menu) {
                return Response::Error(null, 'menu not found', 404);
            }
            $this->authorize('delete', $menu);
            DB::beginTransaction();
            $data = $this->menuService->deleteMenu($id);
            if ($data['code'] !== 200) {
                DB::rollBack();
                return Response::Error($data['data'], $data['message'], $data['code']);
            }
            DB::commit();
            return Response::Success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();
            return Response::Error([], $th->getMessage());
        }
    }
    public function restore($id)
    {
        try {
            $user = auth()->user();
            $menu = Menu::query()
                ->forUserViaPermission($user)
                ->withTrashed()
                ->find($id);
            if (!$menu) {
                return Response::Error(null, 'menu not found', 404);
            }
            $this->authorize('delete', $menu);
            DB::beginTransaction();
            $data = $this->menuService->restoreMenu($id);
            if ($data['code'] !== 200) {
                DB::rollBack();
                return Response::Error($data['data'], $data['message'], $data['code']);
            }
            DB::commit();
            return Response::Success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            DB::rollBack();
            return Response::Error([], $th->getMessage());
        }
    }
}

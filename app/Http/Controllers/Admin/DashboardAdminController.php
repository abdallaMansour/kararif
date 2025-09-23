<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminResource;
use App\Http\Requests\Admin\CreateAdminRequest;
use App\Http\Requests\Admin\UpdateAdminRequest;
use App\Repositories\Admin\DashboardAdminRepository;

class DashboardAdminController extends Controller
{
    /**
     * @var DashboardAdminRepository
     */
    private $repository;

    /**
     * DashboardAdminRepository constructor.
     * @param DashboardAdminRepository $repository
     */
    public function __construct(DashboardAdminRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Show the specified resource.
     * 
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException
     */
    public function index()
    {
        return $this->repository->all();
    }

    public function show($admin_id)
    {
        return new AdminResource($this->repository->find($admin_id));
    }

    public function create(CreateAdminRequest $request)
    {
        return $this->repository->create($request->all());
    }

    public function update($admin_id, UpdateAdminRequest $request)
    {
        return $this->repository->update($admin_id, $request->all());
    }

    public function destroy($admin_id)
    {
        return $this->repository->delete($admin_id);
    }
}

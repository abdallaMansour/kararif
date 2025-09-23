<?php

namespace App\Http\Controllers\ContactUs;

use App\Traits\ApiTrait;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContactUs\ContactUsResource;
use App\Repositories\ContactUs\ContactUsRepository;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DashboardContactUsController extends Controller
{
    use AuthorizesRequests, ValidatesRequests, ApiTrait;

    /**
     * @var ContactUsRepository
     */
    private $repository;

    /**
     * ContactUsRepository constructor.
     * @param ContactUsRepository $repository
     */
    public function __construct(ContactUsRepository $repository)
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

    /**
     * Show the specified resource.
     * 
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException
     */
    public function show($contact_us_id)
    {
        return new ContactUsResource($this->repository->find($contact_us_id));
    }

    public function destroy($contact_us_id)
    {
        return $this->repository->delete($contact_us_id);
    }
}

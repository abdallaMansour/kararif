<?php

namespace App\Http\Controllers\ContactUs;

use App\Traits\ApiTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContactUs\ContactUsRequest;
use App\Repositories\ContactUs\ContactUsRepository;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ContactUsController extends Controller
{
    use AuthorizesRequests, ValidatesRequests, ApiTrait;

    /**
     * @var ContactUsRepository
     */
    private $repository;

    /**
     * ContactUsController constructor.
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
    public function create(ContactUsRequest $request)
    {
        return $this->repository->create($request->all());
    }
}

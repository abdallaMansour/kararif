<?php

namespace App\Http\Controllers\Seo;

use App\Traits\ApiTrait;
use App\Helpers\PageHelper;
use Modules\Seo\Entities\Seo;
use App\Http\Controllers\Controller;
use App\Repositories\Seo\SeoRepository;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SeoController extends Controller
{
    use AuthorizesRequests, ValidatesRequests, ApiTrait, PageHelper;

    /**
     * @var SeoRepository
     */
    private $repository;

    /**
     * SeoController constructor.
     * @param SeoRepository $repository
     */
    public function __construct(SeoRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Show the specified resource.
     * 
     * @param Seo $seo
     * @return Factory|View
     * @throws AuthorizationException
     */
    public function show(string $page)
    {
        $seo = $this->getSeoPage($page);

        return $seo;
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Admin\AdminRepository;

class AdminController extends Controller
{
    /**
     * @var AdminRepository
     */
    private $repository;

    /**
     * AdminRepository constructor.
     * @param AdminRepository $repository
     */
    public function __construct(AdminRepository $repository)
    {
        $this->repository = $repository;
    }
}

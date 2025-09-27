<?php

namespace App\Helpers;

use App\Models\Seo;
use App\Http\Resources\Seo\SeoResource;
use App\Http\Resources\Seo\DashboardSeoResource;
use Illuminate\Http\Exceptions\HttpResponseException;

trait PageHelper
{
    /**
     * $pages
     * 
     * @var array
     */
    private $pages = [
        'home',
        'author',
        'opinion',
        'story',
        'availability',
        'about_us',
        'contact_us',
        'electronic_games',
        'coloring',
    ];

    /**
     * check the $page if exists
     * 
     * @param string $page
     * @return bool
     */
    public function checkFound(string $page): bool
    {
        if (!in_array($page, $this->pages)) {
            return false;
        }

        return true;
    }

    /**
     * check the $page if exists or return exception
     * 
     * @param string $page
     * @return \App\Models\Seo
     */
    public function checkPage(string $page): \App\Models\Seo
    {
        if (!in_array($page, $this->pages)) {
            $this->throwError('the page ' . $page . ' not found');
        } else if (! ($seo = Seo::where('name_id', $page)->first())) {
            $this->throwError('the page ' . $page . ' is empty');
        }

        return $seo;
    }

    /**
     * Get seo page from database
     * 
     * @param string $page
     * @return SeoResource
     */
    public function getSeoPage(string $page)
    {
        $seo = $this->checkPage($page);

        return $page == 'home' ? new SeoResource($seo) : response()->json([
            'data' => [
                'title' => $seo->title,
                'description' => $seo->description,
                'keyword' => $seo->keyword,
            ]
        ]);
    }

    /**
     * Get seo page from database
     * 
     * @param string $page
     * @return DashboardSeoResource
     */
    public function getSeoPageDashboard(string $page)
    {
        $seo = $this->checkPage($page);

        return new DashboardSeoResource($seo);
    }

    /**
     * throw an exception
     * 
     * @param string $error
     * @return void
     */
    private function throwError(string $error): void
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'error' => $error
        ], 400));
    }
}

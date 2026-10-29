<?php

namespace App\Http\Controllers;

use App\Http\Resources\OpmResource;
use App\Services\OpmService;

class OpmController extends Controller
{
    /**
     * Lista as OPMs cadastradas.
     */
    public function index(OpmService $opmService)
    {
        return OpmResource::collection(
            $opmService->listar()
        );
    }
}

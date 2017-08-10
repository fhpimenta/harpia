<?php

namespace Modulos\Academico\Http\Controllers\Async;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modulos\Academico\Repositories\DiplomaRepository;

class Diplomas
{
    protected $diplomaRepository;

    public function __construct(DiplomaRepository $diplomaRepository)
    {
        $this->diplomaRepository = $diplomaRepository;
    }

    public function getAlunosDiplomados($turmaId, Request $request)
    {
        try {
            $alunosdiplomados = $this->diplomaRepository->getAlunosDiplomados($turmaId);

            return new JsonResponse($alunosdiplomados, JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            if (config('app.debug')) {
                throw $e;
            }

            return new JsonResponse($e, JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
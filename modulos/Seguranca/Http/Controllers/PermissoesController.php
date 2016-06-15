<?php

namespace Modulos\Seguranca\Http\Controllers;

use Harpia\Providers\ActionButton\TButton;
use Modulos\Core\Http\Controller\BaseController;
use Modulos\Seguranca\Http\Requests\PermissaoRequest;
use Modulos\Seguranca\Http\Requests\StoreModuloRequest;
use Illuminate\Http\Request;
use Modulos\Seguranca\Repositories\ModuloRepository;
use Modulos\Seguranca\Repositories\PermissaoRepository;
use Modulos\Seguranca\Repositories\RecursoRepository;

class PermissoesController extends BaseController
{
    protected $permissaoRepository;
    protected $moduloRepository;
    protected $recursoRepository;

    public function __construct(PermissaoRepository $permissaoRepository, ModuloRepository $moduloRepository, RecursoRepository $recursoRepository)
    {
        $this->permissaoRepository = $permissaoRepository;
        $this->moduloRepository = $moduloRepository;
        $this->recursoRepository = $recursoRepository;
    }

    public function getIndex(Request $request)
    {
        $btnNovo = new TButton();
        $btnNovo->setName('Novo')->setAction('/seguranca/permissoes/create')->setIcon('fa fa-plus')->setStyle('btn btn-app bg-olive');

        $actionButtons[] = $btnNovo;

        $tableData = $this->permissaoRepository->paginateRequest($request->all());

        return view('Seguranca::permissoes.index', ['tableData' => $tableData, 'actionButton' => $actionButtons]);
    }

    public function getCreate()
    {
        $modulos = $this->moduloRepository->lists('mod_id', 'mod_nome');

        return view('Seguranca::permissoes.create', compact('modulos'));
    }

    public function postCreate(PermissaoRequest $request)
    {
        try {
            $permissao = $this->permissaoRepository->create($request->all());

            if (!$permissao) {
                flash()->error('Erro ao tentar salvar.');

                return redirect()->back()->withInput($request->all());
            }

            flash()->success('Permissão criada com sucesso.');

            return redirect('/seguranca/permissoes');
        } catch (\Exception $e) {
            if (config('app.debug')) {
                throw $e;
            } else {
                flash()->success('Erro ao tentar salvar. Caso o problema persista, entre em contato com o suporte.');

                return redirect()->back();
            }
        }
    }

    public function getEdit($permissaoId)
    {
        $permissao = $this->permissaoRepository->find($permissaoId);

        if (!$permissao) {
            flash()->error('Permissão não existe.');

            return redirect()->back();
        }

        $modulo = $this->permissaoRepository->findModulo($permissaoId);

        $permissao->mod_id = $modulo->mod_id;

        $modulos = $this->moduloRepository->lists('mod_id', 'mod_nome');
        $recursos = $this->recursoRepository->listsAllByModulo($modulo->mod_id);

        return view('Seguranca::permissoes.edit', compact('permissao', 'modulos', 'recursos'));
    }

    public function putEdit($id, PermissaoRequest $request)
    {
        try {
            $permissao = $this->permissaoRepository->find($id);

            if (!$permissao) {
                flash()->error('Permissão não existe.');

                return redirect('/seguranca/permissoes');
            }

            $requestData = $request->only($this->permissaoRepository->getFillableModelFields());

            if (!$this->permissaoRepository->update($requestData, $permissao->prm_id, 'prm_id')) {
                flash()->error('Erro ao tentar salvar.');

                return redirect()->back()->withInput($request->all());
            }

            flash()->success('Permissão atualizado com sucesso.');

            return redirect('/seguranca/permissoes');
        } catch (\Exception $e) {
            if (config('app.debug')) {
                throw $e;
            } else {
                flash()->success('Erro ao tentar salvar. Caso o problema persista, entre em contato com o suporte.');

                return redirect()->back();
            }
        }
    }

    public function postDelete(Request $request)
    {
        try {
            $permissaoId = $request->get('id');

            if ($this->permissaoRepository->delete($permissaoId)) {
                flash()->success('Permissão excluída com sucesso.');
            } else {
                flash()->error('Erro ao tentar excluir a permissão');
            }

            return redirect()->back();
        } catch (\Exception $e) {
            if (config('app.debug')) {
                throw $e;
            } else {
                flash()->success('Erro ao tentar salvar. Caso o problema persista, entre em contato com o suporte.');

                return redirect()->back();
            }
        }
    }
}
<?php

namespace Modulos\Seguranca\Providers\Security;

use Illuminate\Contracts\Foundation\Application;
use Modulos\Seguranca\Providers\Security\Contracts\Security as SecurityContract;
use Modulos\Seguranca\Providers\Security\Exceptions\ForbiddenException;
use DB;

class Security implements SecurityContract {
    /**
     * The Laravel Application.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Retorna o usuário logado na aplicação
     */
    public function getUser()
    {
        return $this->app['auth']->user();
    }

    public function makeCacheMenu()
    {
        $usrId = $this->app['auth']->user()->usr_pes_id;

        $sql = "SELECT mod_id, mod_nome, cr.*
                FROM 
                    seg_perfis_usuarios
                    INNER JOIN seg_perfis ON pru_prf_id = prf_id
                    INNER JOIN seg_modulos ON prf_mod_id = mod_id
                    INNER JOIN seg_categorias_recursos AS cr ON ctr_mod_id = mod_id
                WHERE
                    pru_usr_id = :usrId AND mod_ativo = 1 AND ctr_visivel = 1
                ORDER BY
                    mod_nome,ctr_referencia,ctr_id,ctr_ordem";

        $categoriasModulos =  DB::select($sql, ['usrId' => $usrId]);

        $arrayMenu = [];
        $pai = 0;

        for($i=0; $i < count($categoriasModulos); $i++){
            if(!array_key_exists($categoriasModulos[$i]->mod_id,$arrayMenu)){
                $arrayMenu[$categoriasModulos[$i]->mod_id] = array(
                    'mod_id' => $categoriasModulos[$i]->mod_id,
                    'mod_nome' => $categoriasModulos[$i]->mod_nome,
                    'CATEGORIAS' => array()
                );
            }

            if(is_null($categoriasModulos[$i]->ctr_referencia)){
                $arrayMenu[$categoriasModulos[$i]->mod_id]['CATEGORIAS'][$categoriasModulos[$i]->ctr_id] = array(
                    'ctr_id' => $categoriasModulos[$i]->ctr_id,
                    'ctr_nome' => $categoriasModulos[$i]->ctr_nome,
                    'ctr_icone' => $categoriasModulos[$i]->ctr_icone,
                );
            }

            if(!is_null($categoriasModulos[$i]->ctr_referencia)){
                $arrayMenu[$categoriasModulos[$i]->mod_id]['CATEGORIAS'][$categoriasModulos[$i]->ctr_referencia]['SUBCATEGORIA'][$categoriasModulos[$i]->ctr_id] = array(
                    'ctr_id' => $categoriasModulos[$i]->ctr_id,
                    'ctr_nome' => $categoriasModulos[$i]->ctr_nome,
                    'ctr_icone' => $categoriasModulos[$i]->ctr_icone,                    
                );
            }
        }

        $sqlRecursos = 'SELECT ctr_mod_id as mod_id, ctr_id, ctr_nome, ctr_referencia, rcs_id,rcs_nome,rcs_descricao,rcs_icone,prm_nome
                         FROM
                            seg_perfis_usuarios
                            INNER JOIN seg_perfis_permissoes ON prp_prf_id = pru_prf_id
                            INNER JOIN seg_permissoes ON prp_prm_id = prm_id AND prm_nome = "index"
                            INNER JOIN seg_recursos ON prm_rcs_id = rcs_id
                            INNER JOIN seg_categorias_recursos ON rcs_ctr_id = ctr_id
                         WHERE
                            rcs_ativo = 1 AND ctr_ativo = 1 AND pru_usr_id = :usrId
                         ORDER BY
                            mod_id,ctr_id,rcs_ordem';

        $recursos =  DB::select($sqlRecursos, ['usrId' => $usrId]);

        foreach ($recursos as $key => $recurso){

            dd($arrayMenu[$recurso->mod_id]['CATEGORIAS']);

            dd($recurso);
        }

        // dd($permission);
    }

    /**
     * Verifica se o usuário tem acesso ao recurso
     *
     * @param string|array $permissao
     * @return bool
     * @throws ForbiddenException
     */
    public function haspermission($path)
    {
        list($modulo, $recurso, $permissao) = $this->extractPathResources($path);

        // O usuario nao esta logado, porem a rota eh liberada para usuarios guest.
        if (is_null($this->getUser())) {
            if ($this->isPreLoginOpenActions($modulo, $recurso, $permissao)) {
                return true;
            }

            return false;
        }

        // Verifica se a rota eh liberada pas usuarios logados.
        if ($this->isPostLoginOpenActions($modulo, $recurso, $permissao)) {
            return true;
        }

        // Verifica na base de dados se o perfil do usuario tem acesso ao recurso
        $hasPermission = $this->verifyPermission($this->getUser()->getAuthIdentifier(), $modulo, $recurso, $permissao);

        if ($hasPermission){
            return true;
        }

        return false;
    }

    /**
     * Verifica se a rota eh liberada para usuarios que nao estao logados no sistema
     *
     * @param $modulo
     * @param $recurso
     * @param $permissao
     * @return bool
     */
    private function isPreLoginOpenActions($modulo, $recurso, $permissao)
    {
        $fullRoute = $modulo . '/' . $recurso . '/' . $permissao;

        $openActions = $this->app['config']->get('security.prelogin_openactions', []);

        return in_array($fullRoute, $openActions);
    }

    /**
     * Verifica se a rota eh liberada para usuarios que estao logados no sistema
     *
     * @param $modulo
     * @param $recurso
     * @param $permissao
     * @return bool
     */
    private function isPostLoginOpenActions($modulo, $recurso, $permissao)
    {
        $fullRoute = $modulo . '/' . $recurso . '/' . $permissao;

        $openActions = $this->app['config']->get('security.postlogin_openactions', []);

        return in_array($fullRoute, $openActions);
    }

    /**
     * Verifica se o usuario tem acesso ao recurso.
     *
     * @param int    $usr_id
     * @param stirng $mod_nome
     * @param string $rcs_nome
     * @param string $prm_nome
     *
     * @return mixed
     */
    private function verifyPermission($usr_id, $mod_nome, $rcs_nome = 'index', $prm_nome = 'index')
    {
        $sql = 'SELECT prm_id, prm_nome FROM seg_permissoes p
                INNER JOIN seg_perfis_permissoes pp ON prp_prm_id = prm_id
                INNER JOIN seg_recursos r ON rcs_id = prm_rcs_id
                INNER JOIN seg_modulos m ON mod_id = rcs_mod_id
                WHERE mod_nome = :mod_nome
                  AND rcs_nome = :rcs_nome
                  AND prm_nome = :prm_nome
                  AND rcs_ativo = 1
                  AND prp_prf_id = (
                    SELECT prf_id FROM seg_perfis
                    INNER JOIN seg_modulos ON mod_id = prf_mod_id
                    INNER JOIN seg_perfis_usuarios ON pru_prf_id = prf_id
                    WHERE pru_usr_id = :usr_id AND mod_nome = :modl_nome)';

        return DB::select(DB::raw($sql), [
            'mod_nome' => $mod_nome,
            'rcs_nome' => $rcs_nome,
            'prm_nome' => $prm_nome,
            'usr_id' => $usr_id,
            'modl_nome' => $mod_nome,
        ]);
    }

    /**
     * Gera um array com as partes da url -> modulo / recurso / permissao
     *
     * @param $fullPath
     * @return array
     */
    private function extractPathResources($fullPath)
    {
        if(is_string($fullPath)) {
            $fullPath = explode("/", $fullPath);
        }

        $pathArray[0] = isset($fullPath[0]) ? $fullPath[0] : 'index';
        $pathArray[1] = isset($fullPath[1]) ? $fullPath[1] : 'index';
        $pathArray[2] = isset($fullPath[2]) ? $fullPath[2] : 'index';

        return $pathArray;
    }
}

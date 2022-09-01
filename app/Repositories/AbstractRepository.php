<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

abstract class AbstractRepository {

    public function __construct(Model $model) {
        $this->model = $model;
    }

    public function selectAtributosRegistrosRelacionados($atributos) {
        $this->model = $this->model->with($atributos);
    }

    public function filtro($filtros) {
        $filtros = explode(';', $filtros);
        foreach($filtros as $key => $condicao) {
            $termo = explode(':', $condicao);
            $this->model = $this->model->where($termo[0], $termo[1], $termo[2]);
        }
    }

    public function selectAtributos($atributos) {
        $this->model = $this->model->selectRaw($atributos);
    }

    public function getResultado() {
        return $this->model->get();
    }

}
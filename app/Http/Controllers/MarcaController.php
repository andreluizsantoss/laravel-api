<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use Illuminate\Http\Request;

//* Utilizado para excluir imagem do servidor
use Illuminate\Support\Facades\Storage;

//* Recebendo o repository
use App\Repositories\MarcaRepository;

class MarcaController extends Controller
{
    public function __construct(Marca $marca)
    {
        $this->marca = $marca;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //! Como retornar apenas alguns atributos no response
    // public function index()
    public function index(Request $request)
    {
        
        $marcaRepository = new MarcaRepository($this->marca);

        if($request->has('atributos_modelos')) {
            $atributos_modelos = 'modelos:id,'.$request->atributos_modelos;
            $marcaRepository->selectAtributosRegistrosRelacionados($atributos_modelos);
        } else {
            $marcaRepository->selectAtributosRegistrosRelacionados('modelos');
        }

        if($request->has('filtro')) {
            $marcaRepository->filtro($request->filtro);
        }

        if($request->has('atributos')) {
            $marcaRepository->selectAtributos($request->atributos);
        }

        return response()->json($marcaRepository->getResultado(), 200);

        //------------------ SEM REPOSITORY -------------------//

        // $marcas = array();

        // //* Filtros de retorno no JSON
        // //* Verificar se vai fazer a busca por alguns atributos de forma dinâmica
        // if($request->has('atributos_modelos')) {
        //     $atributos_modelos = $request->atributos_modelos;
        //     //* Buscar de forma dinâmica os atributos de retorno usar => selectRaw
        //     //! Obrigatório enviar modelo:id dentro do with
        //     $marcas = $this->marca->with('modelos:id,'.$atributos_modelos);
        // } else {
        //     //* Retorna o JSON sem filtro
        //     $marcas = $this->marca->with('modelos');
        // }

        // //* FILTRO - POR CONDIÇÕES WHERE - DINAMICAMENTE (PODE CONTER VÁRIOS FILTROS)
        // //* Filtrando a busca (WHERE)
        // if($request->has('filtro')) {
        //     //* Pega automáticamente quantas condições de filtro possue
        //     $filtros = explode(';', $request->filtro);
        //     foreach($filtros as $key => $condicao) {
        //         $termo = explode(':', $condicao);
        //         $marcas = $marcas->where($termo[0], $termo[1], $termo[2]);
        //     }
        // }

        // //* Filtros de retorno no JSON
        // //* Verificar se vai fazer a busca por alguns atributos de forma dinâmica
        // if($request->has('atributos')) {
        //     $atributos = $request->atributos;
        //     //* Buscar de forma dinâmica os atributos de retorno usar => selectRaw
        //     $marcas = $marcas->selectRaw($atributos)->get();
        // } else {
        //     //* Retorna o JSON sem filtro
        //     $marcas = $marcas->get();
        // }

        // //* Troca do métodos ALL para GET
        // //* all() -> criando um objeto de consulta + get() => collection
        // //* get() -> modifica a consulta => collection (USADO PARA WITH())
        // // $marcas = $this->marca->all();
        // // $marcas = $this->marca->with('modelos')->get();
        // return response()->json($marcas, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate($this->marca->rules(), $this->marca->feedback());
        //* Pegando as informações da imagem
        $image  = $request->file('imagem');
        //* Salva a imagem no disco => PUBLIC (pode ser mudado de acordo com o arquivo FileSystem)
        $image_urn = $image->store('images', 'public');
        //*Salvando no banco das informações
        $marca = $this->marca->create([
            'nome' => $request->nome,
            'imagem' => $image_urn,
        ]);
        //* Utilizado apenas para salvar os dados quando não tem imagens
        // $marca = $this->marca->create($request->all());
        return response()->json($marca, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  Integer
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //* WITH => deve passar dentro como parâmetro o nome da função dentro da MODEL
        $marca = $this->marca->with('modelos')->find($id);
        if($marca === null) {
            return response()->json(['erro' => 'Recurso solicitado não existe'], 404);
        }
        return response()->json($marca, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Integer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //* Caso use a opção de envio FORM-DATA
        //* Deve colocar tambem como parâmetro (_method => PUT)
        $marca = $this->marca->find($id);
        if($marca === null) {
            return response()->json(['erro' => 'Impossível realizar a atualização. O recurso solicitado não existe'], 404);
        }

        //* Validações para o método PATCH
        if($request->method() === 'PATCH') {
            $dynamicRules = array();
            //* Percorrre todas as regras definidas na Model
            foreach($marca->rules() as $input => $rule) {
                //* Coleta apenas as regras aplicáveis aos parâmetros parciais da requisição
                if(array_key_exists($input, $request->all())){
                    $dynamicRules[$input] = $rule;
                }
            }
            $request->validate($dynamicRules, $marca->feedback());
        } else {
            $request->validate($marca->rules(), $marca->feedback());
        }

        //* Remove a imagem antiga, caso uma nova imagem tenha sido encaminhada no request
        if($request->file('imagem')) {
            Storage::disk('public')->delete($marca->imagem);
        }
        //* Pegando as informações da imagem
        $image  = $request->file('imagem');
        //* Salva a imagem no disco => PUBLIC (pode ser mudado de acordo com o arquivo FileSystem)
        $image_urn = $image->store('images', 'public');
        
        //! ESTA COM PROBLEMAS NO MODO PATCH => Tem que enviar imagem
        //!Tem que refatorar
        //* Preencher o objeto marca com os dados do request (Método PATH)
        $marca->fill($request->all());
        //* Sobrescreve os dados da imagem
        $marca->imagem = $image_urn;

        //*Salvando no banco das informações
        $marca->save();
        
        // $marca->update([
        //     'nome' => $request->nome,
        //     'imagem' => $image_urn,
        // ]);

        //* Utilizado apenas para salvar os dados quando não tem imagens
        // $marca->update($request->all());
        return response()->json($marca, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Integer
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $marca = $this->marca->find($id);
        if($marca === null) {
            return response()->json(['erro' => 'Impossível realizar a exclusão. O recurso solicitado não existe'], 404);
        }
        //* Remove a imagem
            Storage::disk('public')->delete($marca->imagem);
        //* Deleta o registro do banco de dados
        $marca->delete();
        return response()->json(['msg' => 'A marca foi removida com sucesso!'], 200);
    }
}

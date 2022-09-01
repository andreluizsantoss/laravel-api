<?php

namespace App\Http\Controllers;

use App\Models\Modelo;
use Illuminate\Http\Request;

//* Utlizado para excluir imagem do servidor
use Illuminate\Support\Facades\Storage;

//* Recebendo o repository
use App\Repositories\ModeloRepository;

class ModeloController extends Controller
{
    public function __construct(Modelo $modelo)
    {
        $this->modelo = $modelo;
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

        $modeloRepository = new ModeloRepository($this->modelo);

        if($request->has('atributos_marca')) {
            $atributos_marca = 'marca:id,'.$request->atributos_marca;
            $modeloRepository->selectAtributosRegistrosRelacionados($atributos_marca);
        } else {
            $modeloRepository->selectAtributosRegistrosRelacionados('marca');
        }

        if($request->has('filtro')) {
            $modeloRepository->filtro($request->filtro);
        }

        if($request->has('atributos')) {
            $modeloRepository->selectAtributos($request->atributos);
        }

        return response()->json($modeloRepository->getResultado(), 200);


        //------------------ SEM REPOSITORY -------------------//

        // $modelos = array();

        // //* Filtros de retorno no JSON
        // //* Verificar se vai fazer a busca por alguns atributos de forma dinâmica
        // if($request->has('atributos_marca')) {
        //     $atributos_marca = $request->atributos_marca;
        //     //* Buscar de forma dinâmica os atributos de retorno usar => selectRaw
        //     //! Obrigatório enviar marca_id nos atributos
        //     //! Obrigatório enviar marca:id dentro do with
        //     $modelos = $this->modelo->with('marca:id,'.$atributos_marca);
        // } else {
        //     //* Retorna o JSON sem filtro
        //     $modelos = $this->modelo->with('marca');
        // }

        // //* FILTRO - POR CONDIÇÕES WHERE - DINAMICAMENTE (PODE CONTER VÁRIOS FILTROS)
        // //* Filtrando a busca (WHERE)
        // if($request->has('filtro')) {
        //     //* Pega automáticamente quantas condições de filtro possue
        //     $filtros = explode(';', $request->filtro);
        //     foreach($filtros as $key => $condicao) {
        //         $termo = explode(':', $condicao);
        //         $modelos = $modelos->where($termo[0], $termo[1], $termo[2]);
        //     }
        // }

        // //* Filtros de retorno no JSON
        // //* Verificar se vai fazer a busca por alguns atributos de forma dinâmica
        // if($request->has('atributos')) {
        //     $atributos = $request->atributos;
        //     //* Buscar de forma dinâmica os atributos de retorno usar => selectRaw
        //     $modelos = $modelos->selectRaw($atributos)->get();
        // } else {
        //     //* Retorna o JSON sem filtro
        //     $modelos = $modelos->get();
        // }
        // //* Troca do métodos ALL para GET
        // //* all() -> criando um objeto de consulta + get() => collection
        // //* get() -> modifica a consulta => collection (USADO PARA WITH())
        // // $modelos = $this->modelo->all();
        // // $modelos = $this->modelo->with('marca')->get();
        // return response()->json($modelos, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate($this->modelo->rules());
        //* Pegando as informações da imagem
        $image  = $request->file('imagem');
        //* Salva a imagem no disco => PUBLIC (pode ser mudado de acordo com o arquivo FileSystem)
        $image_urn = $image->store('images/modelos', 'public');
        //*Salvando no banco das informações
        $modelo = $this->modelo->create([
            'marca_id' => $request->marca_id,
            'nome' => $request->nome,
            'imagem' => $image_urn,
            'numero_portas' => $request->numero_portas,
            'lugares' => $request->lugares,
            'air_bag' => $request->air_bag,
            'abs' => $request->abs
        ]);
        //* Utilizado apenas para salvar os dados quando não tem imagens
        // $modelo = $this->modelo->create($request->all());
        return response()->json($modelo, 201);
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
        $modelo = $this->modelo->with('marca')->find($id);
        if($modelo === null) {
            return response()->json(['erro' => 'Recurso solicitado não existe'], 404);
        }
        return response()->json($modelo, 200);
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
        $modelo = $this->modelo->find($id);
        if($modelo === null) {
            return response()->json(['erro' => 'Impossível realizar a atualização. O recurso solicitado não existe'], 404);
        }

        //* Validações para o método PATCH
        if($request->method() === 'PATCH') {
            $dynamicRules = array();
            //* Percorrre todas as regras definidas na Model
            foreach($modelo->rules() as $input => $rule) {
                //* Coleta apenas as regras aplicáveis aos parâmetros parciais da requisição
                if(array_key_exists($input, $request->all())){
                    $dynamicRules[$input] = $rule;
                }
            }
            $request->validate($dynamicRules);
        } else {
            $request->validate($modelo->rules());
        }

        //* Remove a imagem antiga, caso uma nova imagem tenha sido encaminhada no request
        if($request->file('imagem')) {
            Storage::disk('public')->delete($modelo->imagem);
        }
        //* Pegando as informações da imagem
        $image  = $request->file('imagem');
        //* Salva a imagem no disco => PUBLIC (pode ser mudado de acordo com o arquivo FileSystem)
        $image_urn = $image->store('images/modelos', 'public');

        //! ESTA COM PROBLEMAS NO MODO PATCH => Tem que enviar imagem
        //!Tem que refatorar
        //* Preencher o objeto modelo com os dados do request (Método PATH)
        $modelo->fill($request->all());
        //* Sobrescreve os dados da imagem
        $modelo->imagem = $image_urn;

        //*Salvando no banco das informações
        $modelo->save();

        // $modelo->update([
        //     'marca_id' => $request->marca_id,
        //     'nome' => $request->nome,
        //     'imagem' => $image_urn,
        //     'numero_portas' => $request->numero_portas,
        //     'lugares' => $request->lugares,
        //     'air_bag' => $request->air_bag,
        //     'abs' => $request->abs,
        // ]);

        //* Utilizado apenas para salvar os dados quando não tem imagens
        // $modelo->update($request->all());
        return response()->json($modelo, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Integer
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $modelo = $this->modelo->find($id);
        if($modelo === null) {
            return response()->json(['erro' => 'Impossível realizar a exclusão. O recurso solicitado não existe'], 404);
        }
        //* Remove a imagem
            Storage::disk('public')->delete($modelo->imagem);
        //* Deleta o registro do banco de dados
        $modelo->delete();
        return response()->json(['msg' => 'O modelo foi removido com sucesso!'], 200);
    }
}

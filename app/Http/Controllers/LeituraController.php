<?php

namespace App\Http\Controllers;

use Laravel\Dusk\Browser;
use Illuminate\Http\Request;

use Smalot\PdfParser\Parser;

use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;


class LeituraController extends Controller{

    public function lerPDF(){
        $caminhoPdf = public_path('pdfs/LeituraPDF.pdf');      
        $parser = new Parser();
        $pdf = $parser->parseFile($caminhoPdf);
        $pages  = $pdf->getPages();

        $coordenadasOperador = [
            ['campo' => 'Registro ANS', 'x' => 3.76, 'y' => 496.64],
            ['campo' => 'Nome da Operadora', 'x' => 108, 'y' => 496.76],
            ['campo' => 'Codigo na Operadora', 'x' => 3.76, 'y' => 450.44],
            ['campo' => 'Nome do Contratado', 'x' => 166.56, 'y' => 450.52],
            ['campo' => 'Numero do Lote', 'x' => 3.76, 'y' => 403.48],
            ['campo' => 'Numero do Protocolo', 'x' => 130.04, 'y' => 404.2],
            ['campo' => 'Data do Protocolo', 'x' => 255.56, 'y' => 404.2],
            ['campo' => 'Codigo da Glosa do Protocolo', 'x' => 381.24, 'y' => 403.76],
            ['campo' => 'Senha', 'x' => 527.04, 'y' => 499.56],
        ];

        //DADOS DA GUIA
        $coordenadasDadosGuia = [
            ['campo' => 'Nome do Beneficiario'                   , 'x' => 3.76  , 'x1'=> 0, 'x2'=> 0, 'y' => 471.2 ],
            ['campo' => 'Numero da Carteira'                     , 'x' => 527.16, 'x1'=> 0, 'x2'=> 0, 'y' => 471.12],
            ['campo' => 'Data Inicio do Faturamento'             , 'x' => 3.64  , 'x1'=> 0, 'x2'=> 0, 'y' => 442.08],
            ['campo' => 'Data Fim do Faturamento'                , 'x' => 265.36, 'x1'=> 0, 'x2'=> 0, 'y' => 442.04],
            ['campo' => 'Hora Inicio do Faturamento'             , 'x' => 134.56, 'x1'=> 0, 'x2'=> 0, 'y' => 442.04],
            ['campo' => 'Hora Fim do Faturamento'                , 'x' => 396.32, 'x1'=> 0, 'x2'=> 0, 'y' => 442.04],
            ['campo' => 'Codigo da Glosa da Guia'                , 'x' => 527.16, 'x1'=> 0, 'x2'=> 0, 'y' => 442.04],
            ['campo' => 'Numero da Guia no Prestador'            , 'x' => 3.76  , 'x1'=> 0, 'x2'=> 0, 'y' => 499.6 ],
            ['campo' => 'Numero da Guia Atribuido pela Operadora', 'x' => 279.48, 'x1'=> 0, 'x2'=> 0, 'y' => 497.32],

            //TOTAL DA GUIA
            ['campo' => 'Valor Informado da Guia (R$)' , 'x' => 283.39, 'x1'=> 278.95, 'x2'=> 274.51, 'y' => 51.04],
            ['campo' => 'Valor Processado da Guia (R$)', 'x' => 438.07, 'x1'=> 433.63, 'x2'=> 429.19, 'y' => 51.04],
            ['campo' => 'Valor Liberado da Guia (R$)'  , 'x' => 429.2 , 'x1'=> 433.63, 'x2'=> 438.07, 'y' => 51.04],
            ['campo' => 'Valor Glosa da Guia (R$)'     , 'x' => 583.87, 'x1'=> 588.31, 'x2'=> 592.75, 'y' => 51.04],            
        ];

        //DADOS DOS PROCEDIMENTO
        $coordenadasGrid =[
            ['campo' => 'Data de Realizacao'  , 'x' => 3.12   ,'x1'=> 0     , 'x2'=> 0     , 'y' => 402.4 ],
            ['campo' => 'Tabela'              , 'x' => 59.68  ,'x1'=> 0     , 'x2'=> 0     , 'y' => 402.4 ],
            ['campo' => 'Codigo Procedimento' , 'x' => 133    ,'x1'=> 0     , 'x2'=> 0     , 'y' => 402.4 ],
            ['campo' => 'Descricao'           , 'x' => 170.36 ,'x1'=> 0     , 'x2'=> 0     , 'y' => 402.52],           
            ['campo' => 'Grau de Participacao', 'x' => 284.88 ,'x1'=> 0     , 'x2'=> 0     , 'y' => 402.4 ],
            ['campo' => 'Valor Informado'     , 'x' => 368.28 ,'x1'=> 372.72, 'x2'=> 377.16, 'y' => 402.4 ],
            ['campo' => 'Quant. Executada'    , 'x' => 424.8  ,'x1'=> 0     , 'x2'=> 0     , 'y' => 402.4 ],
            ['campo' => 'Valor Processado'    , 'x' => 478.52 ,'x1'=> 482.96, 'x2'=> 487.4 , 'y' => 402.4 ],
            ['campo' => 'Valor Liberado'      , 'x' => 542.64 ,'x1'=> 547.08, 'x2'=> 551.52, 'y' => 402.4 ],
            ['campo' => 'Valor Glosa'         , 'x' => 613.76 ,'x1'=> 618.20, 'x2'=> 622.64, 'y' => 402.4 ],
            ['campo' => 'Codigo da Glosa'     , 'x' => 635.36 ,'x1'=> 639.8 , 'x2'=> 644.24, 'y' => 402.4 ],
        ];

        $result = [];
        foreach ($pages as $key => $page) { 
            $array = $page->getDataTm();   

            foreach ($array as $value) {
                if($key == 0){
                    foreach ( $coordenadasOperador as $valor ) {                      
                        if(($value[0][4] == $valor['x']) && ($value[0][5] == $valor['y'])){//Verifica se encontrou o campo pela coordenada
                            $result[$key][] = [
                                $valor['campo'] ,
                                $value[1],                                   
                            ];
                        }
                    }
                }else if($key > 1){
                    $pagina = $key-1;
                    //echo '<pre>';
                    //print_r($array);
                    //echo '<pre>';
                    if( ($value[0][5] < 526.12) && ($value[0][5] > 15.08) ){//IGNORA TOPO E RODAPE
                        foreach ( $coordenadasGrid as $coordenada ) { 
                            if($value[0][4] == $coordenada['x'] || $value[0][4] == $coordenada['x1'] || $value[0][4] == $coordenada['x2']){
                                if (preg_match('/^(?![a-z]*$)(?!.*-).*$/i', $value[1])) {//LIMPA POSSIVEL LIXO
                                    $result[$pagina][] = [
                                        $coordenada['campo'] ,
                                        //$value[1].'++++++',                                   
                                        $value[1],                                   
                                    ];
                                }
                            }
                        }
                        foreach ( $coordenadasDadosGuia as $coordenada ) {                             
                            if( ($value[0][4] == $coordenada['x']) && ($value[0][5] == $coordenada['y'])){
                                $result[$pagina][] = [
                                    $coordenada['campo'] ,
                                    //$value[1].'******',                                   
                                    $value[1],                                   
                                ];
                            }else if($value[0][4] == $coordenada['x'] || $value[0][4] == $coordenada['x1'] || $value[0][4] == $coordenada['x2']){
                                if($value[0][5] == $coordenada['y']){
                                    $result[$pagina][] = [
                                        $coordenada['campo'] ,
                                        //$value[1].'............',                                   
                                        $value[1],                                   
                                    ];
                                }
                                
                            }                     
                        }
                    }
                }
            }
        }

        /*echo '<pre>';
        print_r($result);        
        echo '</pre>';*/

        return $this->reorganizaArray($result);

    }

    public function gerarExcel(){
        // Dados do array a serem exportados
        //$data = $this->teste();
        $array = $this->lerPDF();

        // Cria uma nova instância do objeto Spreadsheet
        $spreadsheet = new Spreadsheet();

        // Obtém a planilha ativa
        $sheet = $spreadsheet->getActiveSheet();


        foreach ($array as $key => $data) {
                             
            // Criar uma nova planilha para cada array
            $sheet = $spreadsheet->createSheet($key);
            $sheet->setTitle("Planilha " . ($key + 1));
                
                // Definir os dados do array na planilha
            $row = 1;
            foreach ($data as $cell) {
                $sheet->setCellValueByColumnAndRow(1, $row, $cell[0]);
                $sheet->setCellValueByColumnAndRow(2, $row, $cell[1]);
                $row++;
            }
            
        }

       // Cria um objeto Writer para salvar o arquivo Excel
       $writer = new Xlsx($spreadsheet);

       // Define o caminho e nome do arquivo de saída
       $filename = 'LeituraPDF_'.date("His").'.xlsx';
      // $filename = 'LeituraPDF.xlsx';

       // Salva o arquivo Excel
       $writer->save($filename);

       // Retorna o caminho completo do arquivo gerado
       return public_path($filename);
    }        

    private function reorganizaArray($array){
        $result = array_values(array_filter($array));
        return $result;
    }
}





    
    

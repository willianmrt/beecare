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

    public function gerarExcel(){
        // Dados do array a serem exportados
        $data = $this->lerPDF();

       // Cria uma nova instância do objeto Spreadsheet
       $spreadsheet = new Spreadsheet();

       // Obtém a planilha ativa
       $sheet = $spreadsheet->getActiveSheet();

        foreach ($data as $linha) {
            foreach ($linha as $row => $rowData) {
                foreach ($rowData as $column => $value) {
                    $cell = $sheet->getCellByColumnAndRow($column + 1, $row + 1);
                    $cell->setValue($value);
                    $sheet->getColumnDimension('A')->setWidth(50); 
                    $sheet->getColumnDimension('B')->setWidth(50);                     
                }
            }
        }

       // Cria um objeto Writer para salvar o arquivo Excel
       $writer = new Xlsx($spreadsheet);

       // Define o caminho e nome do arquivo de saída
       //$filename = 'LeituraPDF_'.date("His").'.xlsx';
       $filename = 'LeituraPDF.xlsx';

       // Salva o arquivo Excel
       $writer->save($filename);

       // Retorna o caminho completo do arquivo gerado
       return public_path($filename);
    }
        
    public function lerPDF(){
        $caminhoPdf = public_path('pdfs/LeituraPDF.pdf');
      
        $parser = new Parser();

        $pdf = $parser->parseFile($caminhoPdf);
        $pages  = $pdf->getPages();

        $coordenadas = [
            ['campo' => 'Registro ANS', 'x' => 3.76, 'y' => 496.64],
            ['campo' => 'Nome da Operadora', 'x' => 108, 'y' => 496.76],
            ['campo' => 'Codigo na Operadora', 'x' => 3.76, 'y' => 450.44],
            ['campo' => 'Nome do Contratado', 'x' => 166.56, 'y' => 450.52],
            ['campo' => 'Numero do Lote', 'x' => 3.76, 'y' => 403.48],
            ['campo' => 'Numero do Protocolo', 'x' => 130.04, 'y' => 404.2],
            ['campo' => 'Data do Protocolo', 'x' => 255.56, 'y' => 404.2],
            ['campo' => 'Codigo da Glosa do Protocolo', 'x' => 381.24, 'y' => 403.76],
            ['campo' => 'Senha', 'x' => 527.04, 'y' => 499.56],

            //DADOS DA GUIA
            ['campo' => 'Nome do Beneficiario', 'x' => 3.76, 'y' => 471.2],
            ['campo' => 'Numero da Carteira', 'x' => 527.16, 'y' => 471.12],
            ['campo' => 'Data Inicio do Faturamento', 'x' => 3.64, 'y' => 442.08],
            ['campo' => 'Data Fim do Faturamento', 'x' => 265.36, 'y' => 442.04],
            ['campo' => 'Hora Inicio do Faturamento', 'x' => 134.56, 'y' => 442.04],
            ['campo' => 'Hora Fim do Faturamento', 'x' => 396.32, 'y' => 442.04],
            ['campo' => 'Codigo da Glosa da Guia', 'x' => 527.16, 'y' => 442.04],
            ['campo' => 'Numero da Guia no Prestador', 'x' => 3.76, 'y' => 499.6],
            ['campo' => 'Numero da Guia Atribuido pela Operadora', 'x' => 279.48, 'y' => 497.32],

             //DADOS DOS PROCEDIMENTO
            ['campo' => 'Data de Realizacao', 'x' => 3.12, 'y' => 402.4],
            ['campo' => 'Tabela', 'x' => 59.68, 'y' => 402.4],
            ['campo' => 'Codigo Procedimento', 'x' => 133, 'y' => 402.4],
            ['campo' => 'Descricao', 'x' => 170.36, 'y' => 402.52],// 'proximo' => 11.88],            
            ['campo' => 'Grau de Participacao' ,'x' => 284.88, 'y' => 402.4],
            ['campo' => 'Valor Informado' ,'x' => 368.28, 'y' => 402.4],
            ['campo' => 'Quant. Executada' ,'x' => 424.8, 'y' => 402.4],
            ['campo' => 'Valor Processado', 'x' => 478.52, 'y' => 402.4],
            ['campo' => 'Valor Liberado', 'x' => 542.64, 'y' => 402.4],
            ['campo' => 'Valor Glosa', 'x' => 613.76, 'y' => 402.4],
            ['campo' => 'Codigo da Glosa', 'x' => 635.36, 'y' => 402.4],

            //TOTAL DA GUIA
            ['campo' => 'Valor Informado da Guia (R$)', 'x' => 119.48, 'y' => 51.04],
            ['campo' => 'Valor Processado da Guia (R$)', 'x' => 274.52, 'y' => 51.04],
            ['campo' => 'Valor Glosa da Guia (R$)', 'x' => 592.76, 'y' => 51.04],
            ['campo' => 'Valor Liberado da Guia (R$)', 'x' => 429.2, 'y' => 51.04],
            
        ];
       
        $i = 0;
        $result[] = [];
        foreach ($pages as $key => $page) {            
            $array = $page->getDataTm();   
            foreach ($array as $value) {
                foreach ( $coordenadas as $valor ) {                      
                    if(($value[0][4] == $valor['x']) && ($value[0][5] == $valor['y'])){//Verifica se encontrou o campo pela coordenada
                        $i++;
                        
                        $result[$key][$i] = [
                            $valor['campo'] ,
                            $value[1],                                   
                        ];
                    }
                }
            }            
        }
         
        return $this->reorganizaArray($result);  
    }

    private function reorganizaArray($array){
        $result = array_values(array_filter($array));
        return $result;
    }
}





    
    
